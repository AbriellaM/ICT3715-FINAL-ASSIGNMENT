<?php
session_start();
require_once __DIR__ . '/../includes/dbconnect.php';
require_once __DIR__ . '/../includes/functions.php';   // update helpers
require_once __DIR__ . '/../includes/mailer.php';      // defines sendMail()
require_once __DIR__ . '/../includes/parentmailer.php';// calls sendMail()
// Admin session check
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized. Please log in as an admin.');
}
$admin_id = (int)$_SESSION['admin_id'];

$success = "";
$error   = "";

/* ===========================
   Allocation
   =========================== */
if (isset($_POST['allocate']) && !empty($_POST['waiting_id'])) {
    $waitingId = $_POST['waiting_id']; // keep as string

    $stmt = $mysqli->prepare("
        SELECT w.BookingID,
               s.StudentGrade,
               s.StudentSchoolNumber,
               p.ParentEmail,
               p.ParentName,
               p.ParentSurname,
               s.StudentName,
               s.StudentSurname
        FROM waitinglist w
        JOIN bookings b ON TRIM(w.BookingID) = TRIM(b.BookingID)
        JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
        JOIN parents p  ON b.ParentID = p.ParentID
        WHERE w.WaitingListID = ?
          AND w.Status = 'Waiting'
        LIMIT 1
    ");
    if (!$stmt) {
        $error = "Allocation prepare failed: ".$mysqli->error;
    } else {
        $stmt->bind_param("s", $waitingId);
        $stmt->execute();
        $entry = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($entry) {
            preg_match('/\d+/', $entry['StudentGrade'], $matches);
            $grade = isset($matches[0]) ? (int)$matches[0] : 0;

            $studentNo   = (int)$entry['StudentSchoolNumber'];
            $bookingId   = $entry['BookingID'];
            $parentEmail = $entry['ParentEmail'];
            $parentName  = $entry['ParentName'];
            $parentSurname = $entry['ParentSurname'];
            $studentName = $entry['StudentName'];
            $studentSurname = $entry['StudentSurname'];

            $mysqli->begin_transaction();
            try {
                // Pick one available locker for grade
                $stmt = $mysqli->prepare("
                    SELECT LockerID
                    FROM lockers
                    WHERE Status='Available' AND LockerGrade=?
                    ORDER BY LockerID ASC
                    LIMIT 1
                    FOR UPDATE
                ");
                if (!$stmt) throw new Exception("Locker select prepare failed");
                $stmt->bind_param("i", $grade);
                $stmt->execute();
                $stmt->bind_result($lockerId);
                $stmt->fetch();
                $stmt->close();

                if (!$lockerId) throw new Exception("No available lockers for Grade $grade.");

                // Update locker, booking, waitinglist
                if (!updateLocker($mysqli, $lockerId, $studentNo, 'Allocated')) throw new Exception("Locker update failed");
                if (!updateBooking($mysqli, $bookingId, 'Allocated', $lockerId)) throw new Exception("Booking update failed");
                if (!updateWaitingListById($mysqli, $waitingId, 'Allocated', $_SESSION['admin_id'] ?? 0, $lockerId)) throw new Exception("Waitinglist update failed");

                $mysqli->commit();

                // Parent email
                $htmlBody = "Dear {$parentName} {$parentSurname},<br><br>
                    Your child <strong>{$studentName} {$studentSurname}</strong> (Grade {$grade})
                    has been allocated locker <strong>{$lockerId}</strong>.<br><br>
                    Regards,<br>Amandla High School Admin";

                $altBody = "Dear {$parentName} {$parentSurname},\n\n
                    Your child {$studentName} {$studentSurname} (Grade {$grade})
                    has been allocated locker {$lockerId}.\n\n
                    Regards,\nAmandla High School Admin";

                sendMail($parentEmail, "{$parentName} {$parentSurname}", "Locker Allocation Update", $htmlBody, $altBody);

                // Admin notification
                sendAdminNotification(
                    ['amandlahighschoollockersystem2@gmail.com'],
                    $studentName,
                    $studentSurname,
                    $grade,
                    'Allocated',
                    $parentName,
                    $parentSurname
                );

                $success = "Locker {$lockerId} allocated successfully to {$studentName} {$studentSurname}.";
            } catch (Exception $e) {
                $mysqli->rollback();
                $error = "Allocation failed: ".$e->getMessage();
            }
        }
    }
}
/* -------------------------
   Fetch waiting list entries
------------------------- */
$stmt = $mysqli->prepare("
    SELECT w.WaitingListID,
           b.BookingID,
           s.StudentName, s.StudentSurname, s.StudentGrade,
           p.ParentName, p.ParentEmail,
           w.Status AS WaitingListStatus,
           w.RequestedOn,
           b.Status AS BookingStatus
    FROM waitinglist w
    JOIN bookings b ON w.BookingID = b.BookingID
    JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
    JOIN parents p ON b.ParentID = p.ParentID
    WHERE w.Status = 'Waiting'
      AND b.Status IN ('Paid','Waiting')
    ORDER BY w.WaitingListID ASC
");
$stmt->execute();
$waitingRows = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Allocate Lockers - Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;
      min-height:100vh;
      color:#000;
      margin:0;
    }
    .glass-card {
      background: rgba(255,255,255,0.25);
      border-radius:16px;
      padding:2rem;
      box-shadow:0 6px 25px rgba(0,0,0,0.2);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border:1px solid rgba(255,255,255,0.3);
      margin-top:5rem;
    }
    .glass-nav {
      background: rgba(255,255,255,0.2)!important;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom:1px solid rgba(255,255,255,0.3);
      box-shadow:0 4px 12px rgba(0,0,0,0.15);
    }
    .glass-nav .nav-link {
      color:#000!important;
      font-weight:600;
      position:relative;
    }
    .glass-nav .nav-link::after {
      content:"";
      position:absolute;
      width:0;
      height:2px;
      left:0;
      bottom:-4px;
      background:#000;
      transition:width .3s ease;
    }
    .glass-nav .nav-link:hover::after, .glass-nav .nav-link.active::after {
      width:100%;
    }
    footer.glass-footer {
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-top:1px solid rgba(255,255,255,0.3);
      box-shadow:0 -4px 12px rgba(0,0,0,0.15);
      text-align:center;
      padding:1rem;
      position:fixed;
      bottom:0;
      width:100%;
    }
    .btn-glass {
      padding: 6px 14px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      color: #fff;
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.25);
      backdrop-filter: blur(8px) saturate(180%);
      -webkit-backdrop-filter: blur(8px) saturate(180%);
      transition: all 0.25s ease;
    }
    .btn-glass:hover:not(:disabled) {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }
    .btn-allocate {
      background: rgba(0, 200, 83, 0.25);
      border-color: rgba(0, 200, 83, 0.4);
    }
    .btn-cancel {
      background: rgba(244, 67, 54, 0.25);
      border-color: rgba(244, 67, 54, 0.4);
    }
    .badge-status {
      font-weight: 600;
      border:1px solid rgba(0,0,0,0.15);
      background: rgba(255,255,255,0.35);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      color: #000;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="#">Amandla High School Allocate Lockers Dashboard</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div class="collapse navbar-collapse justify-content-end" id="nav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/admin/admin_portal.php">Administrators Portal</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
  <div class="glass-card">
    <h2 class="fw-bold mb-4">Allocate Lockers</h2>

    <?php if (!empty($success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-striped table-sm align-middle">
        <thead>
          <tr>
            <th>WaitingListID</th>
            <th>BookingID</th>
            <th>Student</th>
            <th>Grade</th>
            <th>Status</th>
            <th>Requested On</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
<?php if ($waitingRows && $waitingRows->num_rows > 0): ?>
    <?php while ($row = $waitingRows->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['WaitingListID']) ?></td>
          <td><?= htmlspecialchars($row['BookingID']) ?></td>
          <td><?= htmlspecialchars($row['StudentName'].' '.$row['StudentSurname']) ?></td>
          <td><?= htmlspecialchars($row['StudentGrade']) ?></td>
          <td>
            <span class="badge badge-status">Waitinglist: <?= htmlspecialchars($row['WaitingListStatus']) ?></span><br>
            <span class="badge badge-status">Booking: <?= htmlspecialchars($row['BookingStatus']) ?></span>
          </td>
          <td><?= htmlspecialchars(date("d M Y, H:i", strtotime($row['RequestedOn']))) ?></td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="waiting_id" value="<?= htmlspecialchars($row['WaitingListID']) ?>">
              <button type="submit" name="allocate" class="btn-glass btn-allocate btn-sm">Allocate</button>
            </form>
            <form method="post" style="display:inline;">
              <input type="hidden" name="booking_id" value="<?= htmlspecialchars($row['BookingID']) ?>">
              <button type="submit" name="cancel_allocation" class="btn-glass btn-cancel btn-sm">Cancel</button>
            </form>
          </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
        <tr><td colspan="7" class="text-muted text-center">No students currently on the waiting list.</td></tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer class="glass-footer">&copy; <?= date('Y') ?> Amandla High School Locker System</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>