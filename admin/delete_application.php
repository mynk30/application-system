<?php
require_once '../php/config.php';
require_once '../php/auth.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: /application-system/403.php');
    exit();
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $application_id = intval($_GET['id']);
    $success = false;
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // 1. First, delete all related files
        $select_files = $conn->prepare("SELECT id, file_path FROM files WHERE model_type = 'application' AND model_id = ?");
        $select_files->bind_param("i", $application_id);
        $select_files->execute();
        $files_result = $select_files->get_result();
        
        // Delete physical files
        while ($file = $files_result->fetch_assoc()) {
            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/application-system/' . $file['file_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $select_files->close();
        
        // Delete file records from database
        $delete_files = $conn->prepare("DELETE FROM files WHERE model_type = 'application' AND model_id = ?");
        $delete_files->bind_param("i", $application_id);
        $delete_files->execute();
        $delete_files->close();
        
        // 2. Delete the application
        $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            $_SESSION['success'] = 'Application and all related files deleted successfully';
            $success = true;
        } else {
            throw new Exception('No application found with ID: ' . $application_id);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log('Delete Application Error: ' . $e->getMessage());
        $_SESSION['error'] = 'Error deleting application: ' . $e->getMessage();
    } finally {
        if (isset($stmt)) {
            $stmt->close();
        }
    }
} else {
    $_SESSION['error'] = 'Invalid application ID';
}

// Redirect back to applications page
header('Location: applications.php');
exit();
?>
