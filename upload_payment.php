<?php
session_start();
require_once __DIR__ . '/../includes/dbconnect.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';      // defines sendMail()
require_once __DIR__ . '/../includes/parentmailer.php';// calls sendMail()
// Simple HTML escape helper
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

$success = "";
$error   = "";

// Turn on error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookingId = trim($_POST['booking_id'] ?? '');
    $amount    = trim($_POST['amount'] ?? '');
    $date      = trim($_POST['date'] ?? '');

    if ($bookingId && $amount && $date && !empty($_FILES['proof']['name'])) {
        $allowed = ['pdf','jpg','jpeg','png'];
        $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $uploadDir  = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $filename   = "proof_" . $bookingId . "." . $ext;
            $targetFile = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['proof']['tmp_name'], $targetFile)) {
                $paymentId = getNextPaymentId($mysqli);
                $status    = "Pending"; // align with your admin dashboard

                // Fetch booking details
                $info = getBookingDetails($mysqli, $bookingId);
                if (!$info) {
                    $error = "Booking not found.";
                } else {
                    $studentSchoolNo = $info['StudentSchoolNumber'];

                    $stmt = $mysqli->prepare("
                        INSERT INTO payments
                        (PaymentID, StudentSchoolNumber, BookingID, PaymentAmount, PaymentDate, PaymentStatus, ProofOfPaymentFile)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    if ($stmt) {
                        $stmt->bind_param("sisdsss", $paymentId, $studentSchoolNo, $bookingId, $amount, $date, $status, $filename);
                        if ($stmt->execute()) {
                            $success = "Payment proof uploaded successfully.";

                            // Update booking status
                            $upd = $mysqli->prepare("UPDATE bookings SET Status='Paid' WHERE BookingID=?");
                            $upd->bind_param("s", $bookingId);
                            $upd->execute();
                            $upd->close();

                            // Send notifications
                           $proofFilePath = __DIR__ . "/uploads/" . $filename;

                          sendPaymentProofToAdmin(
                              ["amandlahighschoollockersystem2@gmail.com"], // admin recipients
                              $info['StudentName'],
                              $info['StudentSurname'],
                              $info['ParentName'],
                              $info['ParentSurname'],
                              $proofFilePath
                              );

                            sendMail(
                                $info['ParentEmail'],
                                "{$info['ParentName']} {$info['ParentSurname']}",
                                "Payment Proof Received - {$bookingId}",
                                "<p>Dear {$info['ParentName']} {$info['ParentSurname']},</p>
                                 <p>We have received your proof of payment for booking <strong>{$bookingId}</strong>.</p>
                                 <p>Thank you for completing the process.</p>",
                                "Dear {$info['ParentName']} {$info['ParentSurname']},\n\nWe have received your proof of payment for booking {$bookingId}.\nThank you for completing the process."
                            );
                        } else {
                            $error = "Database error: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $error = "Prepare failed: " . $mysqli->error;
                    }
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only PDF, JPG, JPEG, PNG allowed.";
        }
    } else {
        $error = "All fields are required.";
    }
}

?>
 <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Upload Payment Proof</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {background:url('/amandla-lockersystem/css/images/hallway.jpg') center/cover no-repeat fixed;min-height:100vh;margin:0;padding:0;color:#000}
    .glass-card {background:rgba(255,255,255,0.25);border-radius:16px;padding:2rem;box-shadow:0 6px 25px rgba(0,0,0,0.2);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.3);max-width:600px;margin:4rem auto}
    .glass-nav {background:rgba(255,255,255,0.2)!important;backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.3);box-shadow:0 4px 12px rgba(0,0,0,0.15)}
    .glass-nav .nav-link {color:#000!important;font-weight:600;position:relative}
    .glass-nav .nav-link::after {content:"";position:absolute;width:0;height:2px;left:0;bottom:-4px;background-color:black;transition:width .3s ease}
    .glass-nav .nav-link:hover::after,.glass-nav .nav-link.active::after{width:100%}
    footer.glass-footer {background:rgba(255,255,255,0.2);backdrop-filter:blur(12px);border-top:1px solid rgba(255,255,255,0.3);box-shadow:0 -4px 12px rgba(0,0,0,0.15);text-align:center;padding:1rem;position:fixed;bottom:0;width:100%}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg glass-nav fixed-top">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold text-dark" href="#">Amandla High School Payment Portal</a>
    <div class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/parents/parents.php">Parents Login</a></li>
        <li class="nav-item"><a class="nav-link" href="/amandla-lockersystem/index.php">Home</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="glass-card">
  <h2 class="fw-bold mb-4">Upload Proof of Payment</h2>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= esc($success) ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= esc($error) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Booking ID</label>
      <input type="text" name="booking_id" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Payment Amount (R)</label>
      <input type="number" step="0.01" name="amount" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Payment Date</label>
      <input type="date" name="date" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Proof of Payment (PDF/JPG/PNG)</label>
      <input type="file" name="proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
    </div>
    <button type="submit" class="btn btn-dark">Upload</button>
  </form>
</div>

<footer class="glass-footer">
  &copy; <?= date('Y') ?> Amandla High School Locker System. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>