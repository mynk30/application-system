<?php
// Start session at the very beginning
// session_start();

// Autoload all required classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize global constants
define('BASE_PATH', dirname(__DIR__));

// Initialize global loggers
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/BrowserLogger.php';

// Initialize both loggers
global $logger, $browserLogger;
$logger = Logger::getInstance();
$browserLogger = BrowserLogger::getInstance();

// Log initialization
$logger->info('Application initialized');
$browserLogger->log('Application initialized');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Initialize database connection
require_once __DIR__ . '/db.php';
?>
