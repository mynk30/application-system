<?php
header('Content-Type: application/json');
require_once '../php/config.php';
require_once '../php/auth.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if user exists in admin table
        $stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Delete from admin table
            $stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
            $stmt->bind_param("i", $userId);
        } else {
            // Delete from users table
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting user: ' . $e->getMessage()
        ]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
