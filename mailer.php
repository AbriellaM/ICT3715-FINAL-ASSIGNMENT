<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'C:/xampp/htdocs/amandla-lockersystem/PHPMailer/src/Exception.php';
require 'C:/xampp/htdocs/amandla-lockersystem/PHPMailer/src/PHPMailer.php';
require 'C:/xampp/htdocs/amandla-lockersystem/PHPMailer/src/SMTP.php';

/**
 * Generic mail sender used by all notification wrappers.
 */
function sendMail(
    string $to,
    string $toName,
    string $subject,
    string $htmlBody,
    string $altBody = '',
    array $attachments = []
): bool {
    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'amandlahighschoollockersystem2@gmail.com';
        $mail->Password   = 'rihh rxag gabw lque'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender & recipient
        $mail->setFrom('amandlahighschoollockersystem2@gmail.com', 'Amandla High School Locker System');
        $mail->addAddress($to, $toName ?: '');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $altBody ?: strip_tags($htmlBody);

        // Attachments
        foreach ($attachments as $filePath) {
            if (file_exists($filePath)) {
                $mail->addAttachment($filePath);
            }
        }

        $mail->send();
        error_log("sendMail: email sent to $to subject=$subject");
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
function sendPaymentProofToAdmin(
    array $adminEmails,
    string $studentName,
    string $studentSurname,
    string $parentName,
    string $parentSurname,
    string $proofFilePath   // must be full path, e.g. C:/xampp/.../uploads/proof_123.pdf
): void {
    $subject = "Payment Proof Submitted - {$studentName} {$studentSurname}";
    $bodyHtml = "<p>Admin,</p>
                 <p>Payment proof has been submitted for {$studentName} {$studentSurname}.</p>
                 <p>Parent: {$parentName} {$parentSurname}</p>
                 <p>See attached proof file.</p>";
    $altBody = "Admin,\n\nPayment proof submitted for {$studentName} {$studentSurname}.\n"
             . "Parent: {$parentName} {$parentSurname}\n\n"
             . "See attached proof file.";

    foreach ($adminEmails as $email) {
        if (file_exists($proofFilePath)) {
            sendMail($email, 'Admin', $subject, $bodyHtml, $altBody, [$proofFilePath]);
        } else {
            error_log("Attachment missing: $proofFilePath");
        }
    }
}
?>