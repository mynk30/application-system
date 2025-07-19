<?php
require_once '../php/auth.php';
require_once '../php/config.php';
requireRole(['staff']);

header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid application ID']);
    exit;
}

$application_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // First, verify the application exists and belongs to the current user
    $check_sql = "SELECT id FROM applications WHERE id = ? AND (user_id = ? OR ? IN (SELECT id FROM admin WHERE role = 'admin'))";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("iii", $application_id, $user_id, $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Application not found or access denied']);
        exit;
    }
    
    // Delete the application
    $delete_sql = "DELETE FROM applications WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $application_id);
    
    if ($delete_stmt->execute()) {
        // Delete associated files if any
        $delete_files_sql = "DELETE FROM files WHERE model_type = 'application' AND model_id = ?";
        $delete_files_stmt = $conn->prepare($delete_files_sql);
        $delete_files_stmt->bind_param("i", $application_id);
        $delete_files_stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Application deleted successfully']);
    } else {
        throw new Exception('Failed to delete application');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
