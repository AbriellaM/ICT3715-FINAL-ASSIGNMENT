<?php
session_start();
require_once __DIR__ . '/../includes/dbconnect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';      // defines sendMail()
require_once __DIR__ . '/../includes/parentmailer.php';// calls sendMail()
if (!isset($_SESSION['admin_id'])) {
    die('Unauthorized. Please log in as an admin.');
}
$admin_id = $_SESSION['admin_id'];
$adminEmails = ["amandlahighschoollockersystem2@gmail.com"];

// Run the query
$sql = "
SELECT p.PaymentID,
       p.BookingID,
       p.StudentSchoolNumber,
       p.PaymentAmount,
       p.PaymentDate,
       p.PaymentStatus,
       p.ProofOfPaymentFile,
       b.Status AS BookingStatus,
       s.StudentName,
       s.StudentSurname
FROM payments p
JOIN bookings b ON p.BookingID = b.BookingID
JOIN students s ON p.StudentSchoolNumber = s.StudentSchoolNumber
ORDER BY p.PaymentDate DESC
";
$allPayments = $mysqli->query($sql);


// Simple HTML escape helper
function safe($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/* ---------------------------
   Unified Payment Status Handler
---------------------------- */
function updatePaymentStatus(mysqli $mysqli, string $paymentId, string $status, string $adminId, array $adminEmails): array {
    $success = "";
    $error   = "";

    try {
        // ---------------------------
        // 1. Update payment status
        // ---------------------------
        $mysqli->begin_transaction();

        $stmt = $mysqli->prepare("UPDATE payments SET PaymentStatus=? WHERE PaymentID=?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param("ss", $status, $paymentId);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $mysqli->rollback();
            throw new Exception("Payment update failed for PaymentID $paymentId");
        }
        $stmt->close();

        // Commit payment update immediately
        $mysqli->commit();
        $success = "Payment $paymentId updated to $status.";

        // ---------------------------
        // 2. Booking + waitinglist logic
        // ---------------------------
        $mysqli->begin_transaction();
        try {
            // Fetch booking + parent details
            $stmt = $mysqli->prepare("
                SELECT b.BookingID, s.StudentName, s.StudentSurname,
                       p.ParentName, p.ParentSurname, p.ParentEmail,
                       pay.ProofOfPaymentFile, b.Status AS BookingStatus
                FROM payments pay
                JOIN bookings b ON pay.BookingID = b.BookingID
                JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
                JOIN parents p ON b.ParentID = p.ParentID
                WHERE pay.PaymentID=?
            ");
            $stmt->bind_param("s", $paymentId);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$info) {
                throw new Exception("Booking/parent/student join failed for PaymentID $paymentId");
            }

            if ($status === 'Verified') {
                // Check booking status
                if ($info['BookingStatus'] !== 'Paid') {
                    $stmt = $mysqli->prepare("UPDATE bookings SET Status='Paid' WHERE BookingID=?");
                    $stmt->bind_param("s", $info['BookingID']);
                    $stmt->execute();
                    $stmt->close();
                }

                // Ensure waitinglist entry exists
                $stmt = $mysqli->prepare("
                    INSERT INTO waitinglist (BookingID, Status, RequestedOn, AdminID)
                    VALUES (?, 'Waiting', NOW(), ?)
                    ON DUPLICATE KEY UPDATE Status='Waiting', RequestedOn=NOW(), AdminID=VALUES(AdminID)
                ");
                $stmt->bind_param("ss", $info['BookingID'], $adminId);
                $stmt->execute();
                $stmt->close();

                // Notify parent
                sendMail(
                    $info['ParentEmail'],
                    "Payment Verified - {$info['BookingID']}",
                    "<p>Dear {$info['ParentName']} {$info['ParentSurname']},</p>
                     <p>Your payment for booking <strong>{$info['BookingID']}</strong> has been verified.
                     Your child {$info['StudentName']} {$info['StudentSurname']} is now in the allocation queue.</p>",
                    "Dear {$info['ParentName']} {$info['ParentSurname']},\n\n
                     Your payment for booking {$info['BookingID']} has been verified.
                     Your child {$info['StudentName']} {$info['StudentSurname']} is now in the allocation queue."
                );

            } else {
                // Rejection path
                $stmt = $mysqli->prepare("UPDATE bookings SET Status='Rejected', LockerID=NULL WHERE BookingID=?");
                $stmt->bind_param("s", $info['BookingID']);
                $stmt->execute();
                $stmt->close();

                $stmt = $mysqli->prepare("UPDATE waitinglist SET Status='Rejected', AdminID=? WHERE BookingID=?");
                $stmt->bind_param("ss", $adminId, $info['BookingID']);
                $stmt->execute();
                $stmt->close();

                // Notify parent
                sendMail(
                    $info['ParentEmail'],
                    "Payment Rejected - {$info['BookingID']}",
                    "<p>Dear {$info['ParentName']} {$info['ParentSurname']},</p>
                     <p>Unfortunately, your payment for booking <strong>{$info['BookingID']}</strong> was rejected.
                     Please contact the school administration for assistance.</p>",
                    "Dear {$info['ParentName']} {$info['ParentSurname']},\n\n
                     Unfortunately, your payment for booking {$info['BookingID']} was rejected.
                     Please contact the school administration for assistance."
                );
            }

            // Attach proof file for admins if available
            if (!empty($info['ProofOfPaymentFile'])) {
                $proofFilePath = __DIR__ . "/uploads/" . $info['ProofOfPaymentFile'];
                sendPaymentProofToAdmin(
                    $adminEmails,
                    $info['StudentName'],
                    $info['StudentSurname'],
                    $info['ParentName'],
                    $info['ParentSurname'],
                    $proofFilePath
                );
            }

            $mysqli->commit();
            $success .= " Booking cascade applied and notifications sent.";
        } catch (Throwable $txe) {
            $mysqli->rollback();
            error_log("Booking/waitinglist update failed: ".$txe->getMessage());
            $error = $txe->getMessage();
        }

    } catch (Throwable $txe) {
        $mysqli->rollback();
        error_log("Update payment failed: ".$txe->getMessage());
        $error = $txe->getMessage();
    }

    return [$success, $error];
}
/* ---------------------------
   POST Handlers
---------------------------- */
$success = "";
$error   = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_payment']) && !empty($_POST['payment_id'])) {
        [$success, $error] = updatePaymentStatus($mysqli, $_POST['payment_id'], 'Verified', $admin_id, $adminEmails);
    }
    if (isset($_POST['reject_payment']) && !empty($_POST['payment_id'])) {
        [$success, $error] = updatePaymentStatus($mysqli, $_POST['payment_id'], 'Rejected', $admin_id, $adminEmails);
    }
}


/* ---------------------------
   Dashboard Queries
---------------------------- */
$submittedPayments = $mysqli->query("
    SELECT pay.PaymentID, b.BookingID, s.StudentName, s.StudentSurname, s.StudentGrade,
           p.ParentName, p.ParentSurname,
           pay.PaymentAmount, pay.PaymentStatus, pay.PaymentDate,
           pay.ProofOfPaymentFile
    FROM payments pay
    JOIN bookings b ON pay.BookingID = b.BookingID
    JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
    JOIN parents p ON b.ParentID = p.ParentID
    WHERE pay.PaymentStatus = 'Pending'
    ORDER BY pay.PaymentDate DESC;
");

$verifiedPayments = $mysqli->query("
    SELECT pay.PaymentID, b.BookingID, s.StudentName, s.StudentSurname, s.StudentGrade,
           p.ParentName, p.ParentSurname,
           pay.PaymentAmount, pay.PaymentStatus, pay.PaymentDate,
           b.LockerID
    FROM payments pay
    JOIN bookings b ON pay.BookingID = b.BookingID
    JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
    JOIN parents p ON b.ParentID = p.ParentID
    WHERE pay.PaymentStatus='Verified' AND b.Status='Paid'
    ORDER BY pay.PaymentDate DESC
");

$rejectedPayments = $mysqli->query("
    SELECT pay.PaymentID, b.BookingID, s.StudentName, s.StudentSurname, s.StudentGrade,
           p.ParentName, p.ParentSurname,
           pay.PaymentAmount, pay.PaymentStatus, pay.PaymentDate,
           pay.ProofOfPaymentFile
    FROM payments pay
    JOIN bookings b ON pay.BookingID = b.BookingID
    JOIN students s ON b.StudentSchoolNumber = s.StudentSchoolNumber
    JOIN parents p ON b.ParentID = p.ParentID
    WHERE pay.PaymentStatus='Rejected'
    ORDER BY pay.PaymentDate DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Payments Portal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: url(' /amandla-lockersystem/css/images/hallway.jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      color: #000;
    }
    nav {
      backdrop-filter: blur(10px) saturate(180%);
      -webkit-backdrop-filter: blur(10px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.25);
      border-bottom: 1px solid rgba(0, 0, 0, 0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 2rem;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    nav .logo { font-weight: bold; font-size: 1.2rem; }
    nav ul { list-style: none; display: flex; gap: 1.5rem; margin: 0; padding: 0; }
    nav ul li a { text-decoration: none; color: #000; font-weight: 500; position: relative; }
    nav ul li a::after {
      content: ""; position: absolute; left: 0; bottom: -4px; width: 0; height: 2px;
      background-color: #000; transition: width 0.3s ease;
    }
    nav ul li a:hover::after, nav ul li a.active::after { width: 100%; }

    .glass-card {
      backdrop-filter: blur(12px) saturate(180%);
      -webkit-backdrop-filter: blur(12px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.25);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.18);
      padding: 2rem;
      margin: 2rem auto;
      width: 92%;
      max-width: 1200px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.2);
      flex: 1;
      color: #000;
    }
    footer {
      backdrop-filter: blur(10px) saturate(180%);
      -webkit-backdrop-filter: blur(10px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.25);
      border-top: 1px solid rgba(0,0,0,0.1);
      text-align: center;
      padding: 0.75rem;
      font-size: 0.9rem;
      font-weight: 500;
      margin-top: auto;
      color: #000;
    }
    .glass-table {
      backdrop-filter: blur(12px) saturate(180%);
      -webkit-backdrop-filter: blur(12px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.25);
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
    }
    .glass-table thead { background-color: rgba(0, 0, 0, 0.4); color: #fff; }
    .glass-table tbody tr { background-color: rgba(255, 255, 255, 0.15); }
    .glass-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.3); }

    .btn-glass {
      padding: 6px 12px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      color: #fff;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.25);
      transition: all 0.25s ease;
    }
    .btn-glass:hover:not(:disabled) {
      background: rgba(255,255,255,0.3);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.25);
    }
    .btn-verify { background: rgba(0,200,83,0.25); border-color: rgba(0,200,83,0.4); }
    .btn-reject { background: rgba(244,67,54,0.25); border-color: rgba(244,67,54,0.4); }

    .section-title {
      font-size: 1.2rem;
      font-weight: 700;
      margin-top: 1rem;
      margin-bottom: 0.75rem;
    }
  </style>
</head>
<body>

<nav>
  <div class="logo">Payments Portal</div>
  <ul>
    <li><a class="nav-link fw-bold text-dark" href="/amandla-lockersystem/admin/admin_portal.php">Administrators Portal</a></li>
    <li><a class="nav-link fw-bold text-dark" href="/amandla-lockersystem/admin/admin_logout.php">Logout</a></li>
  </ul>
</nav>

<div class="glass-card">
  <h1 class="mb-3">Locker Payments</h1>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= safe($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

 <div class="section-title">Submitted payments (awaiting verification)</div>
<table class="table table-bordered table-striped align-middle glass-table">
  <thead>
    <tr>
      <th>Booking / Payment</th>
      <th>Student</th>
      <th>Parent</th>
      <th>Amount</th>
      <th>Date</th>
      <th>Proof</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($submittedPayments && $submittedPayments->num_rows > 0): ?>
      <?php while ($row = $submittedPayments->fetch_assoc()): ?>
        <tr>
          <td>
            Booking: <?= safe($row['BookingID']) ?><br>
            Payment: <?= safe($row['PaymentID']) ?>
          </td>
          <td><?= safe($row['StudentName'].' '.$row['StudentSurname'].' (Grade '.$row['StudentGrade'].')') ?></td>
          <td><?= safe($row['ParentName'].' '.$row['ParentSurname']) ?></td>
          <td><?= !empty($row['PaymentAmount']) ? 'R'.safe($row['PaymentAmount']) : '—' ?></td>
          <td><?= !empty($row['PaymentDate']) ? date("d M Y, H:i", strtotime($row['PaymentDate'])) : '—' ?></td>
          <td>
            <?php if (!empty($row['ProofOfPaymentFile'])): ?>
             <a href="/amandla-lockersystem/parents/uploads/<?= htmlspecialchars($row['ProofOfPaymentFile']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
            <a href="/amandla-lockersystem/parents/uploads/<?= htmlspecialchars($row['ProofOfPaymentFile']) ?>" download class="btn btn-sm btn-outline-secondary">Download</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <form method="post" style="display:inline;">
              <input type="hidden" name="payment_id" value="<?= safe($row['PaymentID']) ?>">
              <button type="submit" name="verify_payment" class="btn-glass btn-verify btn-sm">Verify</button>
            </form>
            <form method="post" style="display:inline;">
              <input type="hidden" name="payment_id" value="<?= safe($row['PaymentID']) ?>">
              <button type="submit" name="reject_payment" class="btn-glass btn-reject btn-sm">Reject</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="7">No submitted payments found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="section-title">Verified payments (ready for allocation)</div>
<table class="table table-bordered table-striped align-middle glass-table">
  <thead>
    <tr>
      <th>Booking / Payment</th>
      <th>Student</th>
      <th>Parent</th>
      <th>Amount</th>
      <th>Date</th>
      <th>Locker</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($verifiedPayments && $verifiedPayments->num_rows > 0): ?>
      <?php while ($row = $verifiedPayments->fetch_assoc()): ?>
        <tr>
          <td>
            Booking: <?= safe($row['BookingID']) ?><br>
            Payment: <?= safe($row['PaymentID']) ?>
          </td>
          <td><?= safe($row['StudentName'].' '.$row['StudentSurname'].' (Grade '.$row['StudentGrade'].')') ?></td>
          <td><?= safe($row['ParentName'].' '.$row['ParentSurname']) ?></td>
          <td><?= !empty($row['PaymentAmount']) ? 'R'.safe($row['PaymentAmount']) : '—' ?></td>
          <td><?= !empty($row['PaymentDate']) ? date("d M Y, H:i", strtotime($row['PaymentDate'])) : '—' ?></td>
          <td><?= !empty($row['LockerID']) ? safe($row['LockerID']) : 'Not yet allocated' ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6">No verified payments found.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<div class="section-title">Rejected payments</div>
<table class="table table-bordered table-striped align-middle glass-table">
  <thead>
    <tr>
      <th>Booking / Payment</th>
      <th>Student</th>
      <th>Parent</th>
      <th>Amount</th>
      <th>Date</th>
      <th>Proof</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($rejectedPayments && $rejectedPayments->num_rows > 0): ?>
      <?php while ($row = $rejectedPayments->fetch_assoc()): ?>
        <tr>
          <td>
            Booking: <?= safe($row['BookingID']) ?><br>
            Payment: <?= safe($row['PaymentID']) ?>
          </td>
          <td><?= safe($row['StudentName'].' '.$row['StudentSurname'].' (Grade '.$row['StudentGrade'].')') ?></td>
          <td><?= safe($row['ParentName'].' '.$row['ParentSurname']) ?></td>
          <td><?= !empty($row['PaymentAmount']) ? 'R'.safe($row['PaymentAmount']) : '—' ?></td>
          <td><?= !empty($row['PaymentDate']) ? date("d M Y, H:i", strtotime($row['PaymentDate'])) : '—' ?></td>
          <td>
            <?php if (!empty($row['ProofOfPaymentFile'])): ?>
             <a href="/amandla-lockersystem/parents/uploads/<?= htmlspecialchars($row['ProofOfPaymentFile']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
              <a href="/amandla-lockersystem/parents/uploads/<?= htmlspecialchars($row['ProofOfPaymentFile']) ?>" download class="btn btn-sm btn-outline-secondary">Download</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="6" class="text-center">No rejected payments.</td></tr>
    <?php endif; ?>
  </tbody>
</table>
</div>

<footer>
  © <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>