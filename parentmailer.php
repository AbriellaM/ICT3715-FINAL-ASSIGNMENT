<?php
require_once __DIR__ . '/mailer.php';

/* ============================================================
   Parent Notifications
   ============================================================ */

/**
 * Waiting list email to parent.
 */
function sendWaitingListEmail(
    string $toEmail,
    string $parentName,
    string $parentSurname,
    string $studentName,
    string $studentSurname
): bool {
    $subject = "Locker Waiting List - {$studentName} {$studentSurname}";
    $bodyHtml = "<p>Dear {$parentName} {$parentSurname},</p>
                 <p>Currently all lockers are allocated. Your child <strong>{$studentName} {$studentSurname}</strong> has been placed on the waiting list.</p>
                 <p>You will be notified as soon as a locker becomes available.</p>
                 <p>Kind regards,<br>Amandla High School Locker System</p>";
    $altBody = "Dear {$parentName} {$parentSurname},\n\n"
             . "Currently all lockers are allocated. Your child {$studentName} {$studentSurname} has been placed on the waiting list.\n"
             . "You will be notified as soon as a locker becomes available.\n\n"
             . "Kind regards,\nAmandla High School Locker System";

    return sendMail($toEmail, "{$parentName} {$parentSurname}", $subject, $bodyHtml, $altBody);
}

/**
 * Parent acknowledgment email (simple receipt).
 */
function sendParentAcknowledgment(
    string $toEmail,
    string $studentName,
    string $studentSurname,
    string $grade
): bool {
    $subject = "Locker Application Received";
    $bodyHtml = "<p>We have received your application for {$studentName} {$studentSurname} (Grade {$grade}).</p>
                 <p>You will be notified once processing is complete.</p>";
    $altBody = "We have received your application for {$studentName} {$studentSurname} (Grade {$grade}).\nYou will be notified once processing is complete.";

    // You can also use "{$parentName} {$parentSurname}" if preferred
    return sendMail($toEmail, "{$studentName} {$studentSurname}", $subject, $bodyHtml, $altBody);
}

/**
 * Payment email to parent.
 */
function sendPaymentEmail(
    string $toEmail,
    string $parentName,
    string $parentSurname,
    string $studentName,
    string $studentSurname,
    string $bookingId
): bool {
    $subject = "Locker Payment for $studentName $studentSurname";
    $paymentUrl = "http://localhost/amandla-lockersystem/parents/upload_payment.php?booking=" . urlencode($bookingId);

    $bodyHtml = "
        <p>Dear {$parentName} {$parentSurname},</p>
        <p>Your locker application for {$studentName} {$studentSurname} has been received.</p>
        <p>Please make payment within two days else your child will lose locker space.</p>
        <p>Please complete payment of R100 using the link below:</p>
        <p><a href='{$paymentUrl}'>Pay Now</a></p>
        <p>Booking ID: {$bookingId}</p>
    ";

    $altBody = "Dear {$parentName} {$parentSurname},\n\n"
             . "Your locker application for {$studentName} {$studentSurname} has been received.\n"
             . "Please make payment within two days else your child will lose locker space.\n"
             . "Please complete payment of R100 using the link below:\n"
             . "{$paymentUrl}\n\n"
             . "Booking ID: {$bookingId}";

    return sendMail($toEmail, "{$parentName} {$parentSurname}", $subject, $bodyHtml, $altBody);
}

/* ============================================================
   Admin Notifications
   ============================================================ */

/**
 * Notify admin(s) of application status changes.
 */
function sendAdminNotification(
    array|string $adminEmails,
    string $studentName,
    string $studentSurname,
    string $grade,
    string $status,
    string $parentName,
    string $parentSurname
): bool {
    $subject = "Locker Application {$status} - Amandla High School";
    $bodyHtml = "<p>Admin,</p>
                 <p>A locker application has been <strong>{$status}</strong>.</p>
                 <ul>
                   <li>Student: <strong>{$studentName} {$studentSurname}</strong> (Grade {$grade})</li>
                   <li>Parent: {$parentName} {$parentSurname}</li>
                 </ul>
                 <p>Regards,<br>Amandla High School Locker System</p>";
    $altBody = "Admin,\n\nA locker application has been {$status}.\n"
             . "Student: {$studentName} {$studentSurname} (Grade {$grade})\n"
             . "Parent: {$parentName} {$parentSurname}\n\n"
             . "Regards,\nAmandla High School Locker System";

    $emails = is_array($adminEmails) ? $adminEmails : [$adminEmails];
    $ok = true;
    foreach ($emails as $email) {
        $ok = $ok && sendMail($email, 'Admin', $subject, $bodyHtml, $altBody);
    }
    return $ok;
}

/* ============================================================
   Admin Application / Cancellation Wrappers
   ============================================================ */

/**
 * Send all necessary emails when an admin applies for a locker.
 */
function sendAdminApplicationEmails(
    string $parentEmail,
    string $parentName,
    string $parentSurname,
    string $studentName,
    string $studentSurname,
    string $grade,
    string $bookingId,
    string $status,
    array|string $adminEmails = ['amandlahighschoollockersystem2@gmail.com']
): void {
    sendParentAcknowledgment($parentEmail, $studentName, $studentSurname, $grade);
    sendPaymentEmail($parentEmail, $parentName, $parentSurname, $studentName, $studentSurname, $bookingId);
    if (strtolower($status) === 'waiting') {
        sendWaitingListEmail($parentEmail, $parentName, $parentSurname, $studentName, $studentSurname);
    }
    sendAdminNotification($adminEmails, $studentName, $studentSurname, $grade, $status, $parentName, $parentSurname);
}

/* ============================================================
   Allocation / Cancellation Emails
   ============================================================ */

/**
 * Allocation email to parent.
 */
function sendAllocationEmail(
    string $to,
    string $parentName,
    string $parentSurname,
    string $studentName,
    string $studentSurname,
    string $studentGrade,
    string $lockerId,
    string $bookingId
): bool {
    $subject = "Locker Allocation Confirmation";
    $bodyHtml = "<p>Dear {$parentName} {$parentSurname},</p>
                 <p>Your child <strong>{$studentName} {$studentSurname}</strong> (Grade {$studentGrade}) has been allocated locker <strong>{$lockerId}</strong>.</p>
                 <p>Booking reference: {$bookingId}.</p>
                 <p>Regards,<br>Amandla High School Locker System</p>";
    $altBody = "Dear {$parentName} {$parentSurname},\n\n"
             . "Your child {$studentName} {$studentSurname} (Grade {$studentGrade}) has been allocated locker {$lockerId}.\n"
             . "Booking reference: {$bookingId}.\n\n"
             . "Regards,\nAmandla High School Locker System";

    return sendMail($to, "{$parentName} {$parentSurname}", $subject, $bodyHtml, $altBody);
}

/**
 * Cancellation emails.
 */
function sendCancellationEmails(
    string $parentEmail,
    string $parentName,
    string $parentSurname,
    string $studentName,
    string $studentSurname,
    string $grade,
    string $bookingId,
    string $reason,
    array|string $adminEmails = ['amandlahighschoollockersystem2@gmail.com'],
    bool $notifyAdmins = true
): void {
    $subject = "Locker Booking Cancelled - {$studentName} {$studentSurname}";
    $bodyHtml = "<p>Dear {$parentName} {$parentSurname},</p>
                 <p>The locker booking for your child <strong>{$studentName} {$studentSurname}</strong> (Grade {$grade}) has been cancelled.</p>
                 <p><strong>Reason:</strong> {$reason}</p>
                 <p>If you believe this is an error, please contact the school office.</p>
                 <p>Regards,<br>Amandla High School Locker System</p>";
    $altBody = "Dear {$parentName} {$parentSurname},\n\n"
             . "The locker booking for your child {$studentName} {$studentSurname} (Grade {$grade}) has been cancelled.\n"
             . "Reason: {$reason}\n\n"
             . "If you believe this is an error, please contact the school office.\n\n"
             . "Regards,\nAmandla High School Locker System";

    // Always send parent email
    sendMail($parentEmail, "{$parentName} {$parentSurname}", $subject, $bodyHtml, $altBody);

    // Optionally notify admins
    if ($notifyAdmins && !empty($adminEmails)) {
        sendAdminNotification(
            $adminEmails,
            $studentName,
            $studentSurname,
            $grade,
            "Cancelled ({$reason})",
            $parentName,
            $parentSurname
        );
    }
}

?>