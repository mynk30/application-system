<?php
require_once '../php/config.php';
require_once '../php/auth.php';

// Check if user is admin
if ($_SESSION['user_role'] !== 'admin') {
    header('Location: /application-system/403.php');
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $application_id = intval($_GET['id']);
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First, delete related records (if any)
        // For example, if you have an application_documents table:
        // $conn->query("DELETE FROM application_documents WHERE application_id = $application_id");
        
        // Then delete the application
        $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->bind_param("i", $application_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            $conn->commit();
            $_SESSION['success'] = 'Application deleted successfully';
        } else {
            throw new Exception('No application found with this ID');
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Error deleting application: ' . $e->getMessage();
    }
    
    $stmt->close();
} else {
    $_SESSION['error'] = 'Invalid application ID';
}

header('Location: applications.php');
exit();
?>
