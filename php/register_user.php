<?php
require_once 'db.php';
require_once 'Logger.php';

function registerUser($name, $email, $password, $confirm_password) {
    global $conn;
    $logger = Logger::getInstance();
    
    // Validate input
    $errors = [];
    
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
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
    try {
        // Use SQL command directly instead of fetching
        $sql = "SELECT COUNT(*) as count FROM admin WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $errors[] = "Email already registered.";
        }
    } catch (Exception $e) {
        $logger->error("Database error checking email: " . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['Database error occurred. Please try again.']
        ];
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
        $sql = "INSERT INTO admin (name, email, password, role, status, created_at) 
                VALUES (?, ?, ?, ?, 'active', NOW())";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $name, $email, $hashed_password, 'admin');
            
            if ($stmt->execute()) {
                $conn->commit();
                $logger->info("Admin registered successfully: " . $email);
                return [
                    'success' => true,
                    'message' => 'Registration successful! You can now login.'
                ];
            } else {
                $conn->rollback();
                $logger->error("Registration failed: " . $conn->error);
                return [
                    'success' => false,
                    'errors' => ['Registration failed. Please try again.']
                ];
            }
        } else {
            $conn->rollback();
            $logger->error("Failed to prepare statement: " . $conn->error);
            return [
                'success' => false,
                'errors' => ['Failed to prepare database statement. Please try again.']
            ];
        }
    } catch (Exception $e) {
        $conn->rollback();
        $logger->error("Registration error: " . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['An error occurred during registration. Please try again.']
        ];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = registerUser(
        $_POST['name'] ?? '',
        $_POST['email'] ?? '',
        $_POST['password'] ?? '',
        $_POST['confirm_password'] ?? ''
    );
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>
