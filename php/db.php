<?php
// Database configuration
$db_host = 'localhost:3306';
$db_user = 'root';
$db_pass = '12345678';
$db_name = 'admin';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?>
