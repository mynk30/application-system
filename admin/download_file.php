<?php
require_once '../php/config.php';
global $logger, $browserLogger;

// Log file download attempt
$logger->info("File download attempt for application ID: {$_GET['id']}, file: {$_GET['file']}");
$browserLogger->log("File download attempt for application ID: {$_GET['id']}, file: {$_GET['file']}");

// Validate input
if (!isset($_GET['id']) || !isset($_GET['file'])) {
    $logger->error("Missing required parameters for file download");
    $browserLogger->log("Missing required parameters for file download");
    header("HTTP/1.0 400 Bad Request");
    echo "Missing required parameters";
    exit;
}

$applicationId = intval($_GET['id']);
$requestedFile = urldecode($_GET['file']);

// Get file information from database
$stmt = $conn->prepare("
    SELECT f.file_path, f.file_name, f.file_size
    FROM files f
    JOIN applications a ON f.model_id = a.id
    WHERE f.model_type = 'application'
    AND a.id = ?
    AND f.original_name = ?
");

$stmt->bind_param("is", $applicationId, $requestedFile);
$stmt->execute();
$result = $stmt->get_result();

if ($file = $result->fetch_assoc()) {
    $filePath = $file['file_path'];
    
    // Check if file exists
    if (!file_exists($filePath)) {
        $logger->error("File not found: $filePath");
        $browserLogger->log("File not found: $filePath");
        header("HTTP/1.0 404 Not Found");
        echo "File not found";
        exit;
    }

    // Set appropriate headers for file download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . $file['file_size']);

    // Log successful download
    $logger->info("File downloaded successfully: $filePath");
    $browserLogger->log("File downloaded successfully: $filePath");

    // Read and output file
    readfile($filePath);
    exit;
} else {
    $logger->error("File not found in database: application_id=$applicationId, filename=$requestedFile");
    $browserLogger->log("File not found in database: application_id=$applicationId, filename=$requestedFile");
    header("HTTP/1.0 404 Not Found");
    echo "File not found in database";
    exit;
}
?>
