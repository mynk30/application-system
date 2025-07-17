<?php
header('Content-Type: application/json');
require_once '../php/config.php';
require_once '../php/auth.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }

    try {
        // Check if user exists in admin table
        $stmt = $conn->prepare("SELECT id, name, email, role, status FROM admin WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
        } else {
            // Check if user exists in users table
            $stmt = $conn->prepare("SELECT id, name, email, status FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $user['role'] = ''; // Regular users don't have a role
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                exit;
            }
        }

        echo json_encode([
            'success' => true,
            'user' => $user
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error loading user data: ' . $e->getMessage()
        ]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
