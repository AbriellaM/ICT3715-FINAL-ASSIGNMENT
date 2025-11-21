<?php
require_once __DIR__ . '/dbconnect.php';
require_once __DIR__.   '/mailer.php';
require_once __DIR__ . '/parentmailer.php';

/* ============================================================
   Shared utilities
   ============================================================ */

function getNextBookingId(mysqli $db): string {
    // Look at the numeric part of BookingID
    $result = $db->query("SELECT MAX(CAST(SUBSTRING(BookingID, 2) AS UNSIGNED)) AS max_id FROM bookings");
    $row = $result->fetch_assoc();
    $next = $row['max_id'] ? $row['max_id'] + 1 : 1;

    // Format as B23, B24, etc.
    return 'B' . $next;
}
function getNextWaitingListId(mysqli $mysqli): string {
    $res = $mysqli->query("SELECT MAX(CAST(SUBSTRING(WaitingListID, 3) AS UNSIGNED)) AS maxId FROM waitinglist");
    $row = $res ? $res->fetch_assoc() : null;
    if ($res) $res->close();
    $next = ($row && $row['maxId']) ? intval($row['maxId']) + 1 : 1;
    return "WL" . $next;
}
/**
 * Generate the next PaymentID with prefix P
 */
function getNextPaymentId(mysqli $mysqli): string {
    // Find the highest numeric part of existing PaymentIDs
    $res = $mysqli->query("SELECT MAX(CAST(SUBSTRING(PaymentID, 2) AS UNSIGNED)) AS maxId FROM payments");
    $row = $res ? $res->fetch_assoc() : null;
    if ($res) $res->close();

    $next = ($row && $row['maxId']) ? intval($row['maxId']) + 1 : 1;

    // Prefix with P (e.g. P1, P2, P3 …)
    return "P" . $next;
}
//Get BookingDetails
function getBookingDetails(mysqli $mysqli, string $bookingId): ?array {
    $stmt = $mysqli->prepare("SELECT 
            b.BookingID,
            b.StudentSchoolNumber,
            s.StudentName,
            s.StudentSurname,
            p.ParentName,
            p.ParentSurname,
            p.ParentEmail
        FROM bookings b
        JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
        JOIN parents p ON b.ParentID = p.ParentID
        WHERE b.BookingID = ?");
    if (!$stmt) {
        error_log("Prepare failed: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param("s", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $info = $result->fetch_assoc();
    $stmt->close();

    return $info ?: null;
}
/* ===========================
   Locker / Booking Updates
   =========================== */

function updateLocker(mysqli $mysqli, string $lockerId, int $studentNo, string $status): bool {
    $stmt = $mysqli->prepare("UPDATE lockers SET Status=?, StudentSchoolNumber=? WHERE LockerID=?");
    if (!$stmt) return false;
    $stmt->bind_param("sis", $status, $studentNo, $lockerId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

function updateBooking(mysqli $mysqli, string $bookingId, string $status, string $lockerId): bool {
    $stmt = $mysqli->prepare("UPDATE bookings SET Status=?, LockerID=? WHERE BookingID=?");
    if (!$stmt) return false;
    $stmt->bind_param("sss", $status, $lockerId, $bookingId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}

function updateWaitingListById(mysqli $mysqli, string $waitingId, string $status, int $adminId, string $lockerId): bool {
    $stmt = $mysqli->prepare("UPDATE waitinglist SET Status=?, AdminID=?, LockerID=? WHERE WaitingListID=?");
    if (!$stmt) return false;
    $stmt->bind_param("siss", $status, $adminId, $lockerId, $waitingId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    return $ok;
}
/* ============================================================
   Student registration
   ============================================================ */
function registerStudent(mysqli $db, int $studentNo, string $studentName, string $studentSurname, string $studentGrade, int $parentId): string {
    error_log("registerStudent called: studentNo=$studentNo parentId=$parentId");

    $check = $db->prepare("SELECT 1 FROM students WHERE StudentSchoolNumber=? LIMIT 1");
    if (!$check) { error_log("registerStudent duplicate check prepare failed: ".$db->error); return "error"; }
    $check->bind_param("i", $studentNo);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    if ($exists) return "duplicate";

    $stmt = $db->prepare("INSERT INTO students (StudentSchoolNumber, StudentName, StudentSurname, StudentGrade, ParentID) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) { error_log("registerStudent prepare failed: ".$db->error); return "error"; }

    $stmt->bind_param("isssi", $studentNo, $studentName, $studentSurname, $studentGrade, $parentId);
    $ok = $stmt->execute();
    if ($ok) error_log("registerStudent succeeded: $studentName $studentSurname ($studentNo)");
    else error_log("registerStudent failed: ".$stmt->error);
    $stmt->close();

    return $ok ? "success" : "error";
}

/* ============================================================
   Parent workflows
   ============================================================ */
function applyForLocker(
    mysqli $mysqli,
    int $studentNo,
    string $studentName,
    string $studentSurname,
    int $parentId,
    string $date
): ?string {
    try {
       $bookingId = getNextBookingId($mysqli);
        // Always use current date for BookedOnDate
    $bookedOnDate = date('Y-m-d'); // current date in YYYY-MM-DD format
        // Insert booking
        $stmt = $mysqli->prepare("
            INSERT INTO bookings (BookingID, StudentSchoolNumber, ParentID, BookedForDate, Status)
            VALUES (?, ?, ?, ?, 'Waiting')
        ");
        if (!$stmt) throw new Exception("Bookings insert prepare failed: ".$mysqli->error);

        $stmt->bind_param("siis", $bookingId, $studentNo, $parentId, $date);
        if (!$stmt->execute()) throw new Exception("Bookings insert failed: ".$stmt->error);
        $stmt->close();

        // Insert into waitinglist
      $stmtW = $mysqli->prepare("
    INSERT INTO waitinglist (BookingID, StudentSchoolNumber, ParentID, Status)
    VALUES (?, ?, ?, 'Waiting')
    ON DUPLICATE KEY UPDATE Status = VALUES(Status)
    ");
    if (!$stmtW) {
    throw new Exception("Waitinglist insert prepare failed: " . $mysqli->error);
    }

    // BookingID = string, StudentSchoolNumber = int, ParentID = bigint
    $stmtW->bind_param("sii", $bookingId, $studentNo, $parentId);

    if (!$stmtW->execute()) {
    throw new Exception("Waitinglist insert failed: " . $stmtW->error);
    }
    $stmtW->close();

    return $bookingId;

    } catch (Exception $e) {
        error_log("applyForLocker failed: ".$e->getMessage());
        return null;
    }
}
/* ============================================================
   Admin workflows
   ============================================================ */
function applyForLockerAdmin(mysqli $db, int $studentNo, int $adminId, string $date): ?string {
    error_log("applyForLockerAdmin called: studentNo=$studentNo adminId=$adminId");

    // Prevent duplicate active bookings
    $check = $db->prepare("SELECT BookingID FROM bookings WHERE StudentSchoolNumber=? AND Status IN ('Waiting','Allocated') LIMIT 1");
    if (!$check) { error_log("Duplicate check prepare failed: ".$db->error); return null; }
    $check->bind_param("i", $studentNo);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();
    if ($exists) {
        error_log("applyForLockerAdmin blocked: existing active booking for studentNo=$studentNo");
        return null;
    }

    $bookingId     = getNextBookingId($db);
    $waitingListId = getNextWaitingListId($db);

    // Fetch ParentID
    $parentStmt = $db->prepare("SELECT ParentID FROM students WHERE StudentSchoolNumber=?");
    if (!$parentStmt) { error_log("ParentID fetch prepare failed: ".$db->error); return null; }
    $parentStmt->bind_param("i", $studentNo);
    $parentStmt->execute();
    $parentRow = $parentStmt->get_result()->fetch_assoc();
    $parentStmt->close();
    if (!$parentRow) { error_log("applyForLockerAdmin: student not found $studentNo"); return null; }
    $parentIdInt = (int)$parentRow['ParentID'];

    $db->begin_transaction();
    try {
        // Insert booking
        $stmt = $db->prepare("INSERT INTO bookings (BookingID, StudentSchoolNumber, ParentID, BookedForDate, Status, CreatedByAdminID) VALUES (?, ?, ?, ?, 'Waiting', ?)");
        if (!$stmt) throw new Exception("Bookings insert prepare failed: ".$db->error);
        $stmt->bind_param("siisi", $bookingId, $studentNo, $parentIdInt, $date, $adminId);
        if (!$stmt->execute()) throw new Exception("Bookings insert failed: ".$stmt->error);
        $stmt->close();

        // Insert waitinglist
        $stmt = $db->prepare("INSERT INTO waitinglist (WaitingListID, BookingID, ParentID, StudentSchoolNumber, AdminID, Status, RequestedOn) VALUES (?, ?, ?, ?, ?, 'Waiting', NOW())");
        if (!$stmt) throw new Exception("Waitinglist insert prepare failed: ".$db->error);
        $stmt->bind_param("ssiii", $waitingListId, $bookingId, $parentIdInt, $studentNo, $adminId);
        if (!$stmt->execute()) throw new Exception("Waitinglist insert failed: ".$stmt->error);
        $stmt->close();

        $db->commit();
        error_log("applyForLockerAdmin succeeded: BookingID=$bookingId WaitingListID=$waitingListId");
        return $bookingId;
    } catch (Throwable $e) {
        $db->rollback();
        error_log("applyForLockerAdmin failed: ".$e->getMessage());
        return null;
    }
}
/* ============================================================
   Allocation
   ============================================================ */

/**
 * Allocate a locker to a booking (transactional).
 * Updates lockers, bookings, and waitinglist.
 * Sends notification email to parent.
 */
function allocateLocker(mysqli $db, string $bookingId, string $lockerId, int $adminId): bool {
    error_log("allocateLocker called: bookingId=$bookingId lockerId=$lockerId adminId=$adminId");

    // Fetch booking + parent info
    $stmt = $db->prepare("
        SELECT b.StudentSchoolNumber, b.ParentID,
               s.StudentName, s.StudentSurname, s.StudentGrade,
               p.ParentEmail, p.ParentName, p.ParentSurname
        FROM bookings b
        JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
        JOIN parents p ON b.ParentID = p.ParentID
        WHERE b.BookingID=? LIMIT 1
    ");
    if (!$stmt) { error_log("allocateLocker prepare failed: ".$db->error); return false; }
    $stmt->bind_param("s", $bookingId);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$info) { error_log("allocateLocker: booking not found $bookingId"); return false; }

    $db->begin_transaction();
    try {
        // Update locker
        $stmt = $db->prepare("UPDATE lockers SET Status='Allocated', StudentSchoolNumber=? WHERE LockerID=?");
        if (!$stmt) throw new Exception("Locker update prepare failed: ".$db->error);
        $stmt->bind_param("is", $info['StudentSchoolNumber'], $lockerId);
        $stmt->execute();
        $stmt->close();

        // Update booking
        $stmt = $db->prepare("UPDATE bookings SET LockerID=?, Status='Allocated', CreatedByAdminID=? WHERE BookingID=?");
        if (!$stmt) throw new Exception("Booking update prepare failed: ".$db->error);
        $stmt->bind_param("sis", $lockerId, $adminId, $bookingId);
        $stmt->execute();
        $stmt->close();

        // Update waitinglist
        $stmt = $db->prepare("UPDATE waitinglist SET LockerID=?, AdminID=?, Status='Allocated' WHERE BookingID=?");
        if (!$stmt) throw new Exception("Waitinglist update prepare failed: ".$db->error);
        $stmt->bind_param("sis", $lockerId, $adminId, $bookingId);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        error_log("allocateLocker succeeded: bookingId=$bookingId lockerId=$lockerId");

        // Send notification email
        if (!empty($info['ParentEmail'])) {
            sendAllocationEmail(
                $info['ParentEmail'],
                $info['ParentName'],
                $info['ParentSurname'],
                $info['StudentName'],
                $info['StudentSurname'],
                $info['StudentGrade'],
                $lockerId,
                $bookingId
            );
        }
        return true;
    } catch (Throwable $e) {
        $db->rollback();
        error_log("allocateLocker failed: ".$e->getMessage());
        return false;
    }
}

/* ============================================================
   Cancellation
   ============================================================ */

/**
 * Cancel a booking and free locker if allocated (transactional).
 * Booking row is deleted; waitinglist keeps history (Status=Cancelled).
 * Sends cancellation email to parent.
 */
function cancelBooking(mysqli $db, string $bookingId, string $reason = 'No reason provided'): bool {
    error_log("cancelBooking called: bookingId=$bookingId reason=$reason");

    // Fetch booking + parent info
    $stmt = $db->prepare("
        SELECT b.LockerID, b.StudentSchoolNumber, b.ParentID,
               s.StudentName, s.StudentSurname, s.StudentGrade,
               p.ParentEmail, p.ParentName, p.ParentSurname
        FROM bookings b
        JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
        JOIN parents p ON b.ParentID = p.ParentID
        WHERE b.BookingID=? LIMIT 1
    ");
    if (!$stmt) { error_log("cancelBooking prepare failed: ".$db->error); return false; }
    $stmt->bind_param("s", $bookingId);
    $stmt->execute();
    $info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$info) { error_log("cancelBooking: booking not found $bookingId"); return false; }

    $db->begin_transaction();
    try {
        // Free locker if allocated
        if (!empty($info['LockerID'])) {
            $stmt = $db->prepare("UPDATE lockers SET Status='Available', StudentSchoolNumber=NULL WHERE LockerID=?");
            if (!$stmt) throw new Exception("Locker free prepare failed: ".$db->error);
            $stmt->bind_param("s", $info['LockerID']);
            $stmt->execute();
            $stmt->close();
            error_log("cancelBooking: locker freed ".$info['LockerID']);
        }

        // Delete booking
        $stmt = $db->prepare("DELETE FROM bookings WHERE BookingID=?");
        if (!$stmt) throw new Exception("Booking delete prepare failed: ".$db->error);
        $stmt->bind_param("s", $bookingId);
        $stmt->execute();
        $stmt->close();
        error_log("cancelBooking: booking deleted $bookingId");

        // Mark waitinglist as Cancelled
        $stmt = $db->prepare("UPDATE waitinglist SET Status='Cancelled' WHERE BookingID=?");
        if (!$stmt) throw new Exception("Waitinglist cancel prepare failed: ".$db->error);
        $stmt->bind_param("s", $bookingId);
        $stmt->execute();
        $stmt->close();
        error_log("cancelBooking: waitinglist marked cancelled for bookingId=$bookingId");

        $db->commit();

        // Send cancellation email
        if (!empty($info['ParentEmail'])) {
            sendCancellationEmails(
                $info['ParentEmail'],
                $info['ParentName'],
                $info['ParentSurname'],
                $info['StudentName'],
                $info['StudentSurname'],
                $info['StudentGrade'],
                $bookingId,
                $reason
            );
        }

        return true;
    } catch (Throwable $e) {
        $db->rollback();
        error_log("cancelBooking failed: ".$e->getMessage());
        return false;
    }
}
?>