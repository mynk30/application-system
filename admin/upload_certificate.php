<?php
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['document'])) {
    header('Location: applications.php');
    exit;
}

$appId = (int)$_POST['application_id'];

// Check if application exists
$stmt = $conn->prepare("SELECT id FROM applications WHERE id = ?");
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Application not found.";
    header("Location: applications.php");
    exit;
}

$file = $_FILES['document'];
$maxFileSize = 5 * 1024 * 1024; // 5 MB
$allowedTypes = ['application/pdf'];

// Set certificate folder
// $uploadDir = '../uploads/certificate/';
$uploadDir = __DIR__ . '/../uploads/certificate/';

// Ensure directory exists
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);  
}

// Validate file
if ($file['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['error'] = "Error uploading file.";
    header("Location: view_application.php?id=" . $appId);
    exit;
}

if ($file['size'] > $maxFileSize) {
    $_SESSION['error'] = "File size exceeds 5MB limit.";
    header("Location: view_application.php?id=" . $appId);
    exit;
}

$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $file['tmp_name']);
finfo_close($fileInfo);

if (!in_array($mimeType, $allowedTypes)) {
    $_SESSION['error'] = "Only PDF files are allowed.";
    header("Location: view_application.php?id=" . $appId);
    exit;
}

// Generate unique file name
$fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = uniqid('cert_') . '.' . $fileExt;
$filePath = $uploadDir . $fileName;

// Move file to uploads/certificate folder
if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    $_SESSION['error'] = "Failed to save the file.";
    header("Location: view_application.php?id=" . $appId);
    exit;
}

// Save file record to database
$relativePath = 'uploads/certificate/' . $fileName;
$stmt = $conn->prepare("
    INSERT INTO files (original_name, file_name, file_path, file_size, model_type, model_id)
    VALUES (?, ?, ?, ?, 'certificate', ?)
");
$stmt->bind_param("ssssi", $file['name'], $fileName, $relativePath, $file['size'], $appId);

if ($stmt->execute()) {
    $_SESSION['success'] = "Certificate uploaded successfully.";
} else {
    unlink($filePath);
    $_SESSION['error'] = "Failed to save file details to the database.";
}

header("Location: view_application.php?id=" . $appId);
exit;

// =================================================

// session_start();
// require_once '../php/db.php';

// // Ensure an admin is logged in
// if (!isset($_SESSION['admin_id'])) { // Use your actual admin session variable
//     die("Admin access required.");
// }

// // Check if application_id and file are submitted
// if (isset($_POST['application_id']) && isset($_FILES['certificateFile'])) {
    
//     $applicationId = intval($_POST['application_id']);
//     $file = $_FILES['certificateFile'];

//     // --- 1. HANDLE FILE UPLOAD ---

//     if ($file['error'] !== UPLOAD_ERR_OK) {
//         $_SESSION['error'] = "Error during file upload: " . $file['error'];
//         header("Location: /admin/view_application.php?id=" . $applicationId);
//         exit;
//     }

//     $originalName = basename($file['name']);
//     $targetDir = dirname(__DIR__) . "/uploads/certificates/";

//     if (!is_dir($targetDir)) {
//         mkdir($targetDir, 0755, true);
//     }

//     $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
//     $uniqueFileName = uniqid('cert_', true) . '.' . $fileExtension;
//     $targetFilePath = $targetDir . $uniqueFileName;

//     if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
        
//         // --- 2. SAVE TO 'files' DATABASE TABLE ---

//         $dbPath = "uploads/certificates/" . $uniqueFileName;

//         // This query now correctly inserts into the 'files' table
//         $stmt = $conn->prepare(
//             "INSERT INTO files (original_name, file_name, file_path, file_size, model_type, model_id) 
//              VALUES (?, ?, ?, ?, 'certificate', ?)"
//         );
//         $stmt->bind_param("sssis", $originalName, $uniqueFileName, $dbPath, $file['size'], $applicationId);
        
//         if ($stmt->execute()) {
//             $_SESSION['success'] = "Certificate uploaded and saved successfully!";
//         } else {
//             // If DB save fails, delete the orphaned file
//             unlink($targetFilePath);
//             $_SESSION['error'] = "File was saved, but database record failed: " . $stmt->error;
//         }

//     } else {
//         $_SESSION['error'] = "Sorry, there was an error moving the uploaded file.";
//     }

//     header("Location: /admin/view_application.php?id=" . $applicationId);
//     exit;
// }
?>
