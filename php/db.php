<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'application_system';

// Initialize logger
require_once 'Logger.php';
$logger = Logger::getInstance();

// Create connection
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        $errorMessage = "Connection failed: " . $conn->connect_error;
        $logger->error($errorMessage);
        die($errorMessage);
    }
    
    // Set charset to utf8mb4
    $conn->set_charset("utf8mb4");
    $logger->info("Database connection established successfully");
    
    // Set timezone
date_default_timezone_set('Asia/Kolkata');
} catch (Exception $e) {
    $logger->error("Database connection error: " . $e->getMessage());
    die("Database connection error");
}
?>
