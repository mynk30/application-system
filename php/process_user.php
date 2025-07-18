<?php
header('Content-Type: application/json');
require_once '../php/config.php';
require_once '../php/auth.php';

// Prevent any output before JSON
ob_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($role) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Begin transaction
        $conn->begin_transaction();
        $logger->info("User created successfully");
        if ($role === 'staff') {
            // Insert into admin table for staff
            $stmt = $conn->prepare("
                INSERT INTO admin (name, email, password, role, status)
                VALUES (?, ?, ?, 'staff', 'active')
            ");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);
        } else {
            // Insert into users table for regular users
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, status)
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->bind_param("sss", $name, $email, $hashedPassword);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user: " . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'userId' => $conn->insert_id,
            'role' => $role
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error creating user: ' . $e->getMessage()
        ]);
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Clear any output buffer
ob_end_clean();
