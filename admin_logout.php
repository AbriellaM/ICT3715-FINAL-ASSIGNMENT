<?php
session_start();

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect back to the parent login page
header("Location: /amandla-lockersystem/admin/admin.php");
exit;