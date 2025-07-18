<?php
require_once 'db.php';
require_once 'Logger.php';

function registerAdmin($name, $email, $password, $confirm_password, $role) {
    global $conn;
    $logger = Logger::getInstance();
    
    // Validate input
    $errors = [];
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $errors[] = "All fields are required.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already registered.";
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert admin into database
        $stmt = $conn->prepare("INSERT INTO admin (name, email, password, role, status, created_at) 
                                VALUES (?, ?, ?, ?, 'active', NOW())");
        
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            $conn->commit();
            $logger->info("Admin registered successfully: " . $email);
            return [
                'success' => true,
                'message' => 'Admin registered successfully!'
            ];
        } else {
            $conn->rollback();
            $logger->error("Admin registration failed: " . $conn->error);
            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];
        }
    } catch (Exception $e) {
        $conn->rollback();
        $logger->error("Admin registration error: " . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['An error occurred during registration. Please try again.']
        ];
    }
}
?>
