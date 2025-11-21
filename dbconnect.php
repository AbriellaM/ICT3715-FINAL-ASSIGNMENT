<?php
// Database connection
$mysqli = new mysqli('127.0.0.1', 'root', '', 'amandlahighschool_lockersystem');

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');
?>