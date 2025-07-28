<?php
require_once '../php/config.php';
require_once '../php/auth.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['message'] = 'Unauthorized access';
    header("Location: ../admin/users.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $mobile = trim($_POST['mobile'] ?? '');
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($role) || empty($password) || empty($mobile)) {
        $_SESSION['message'] = 'All fields are required';
        header("Location: ../admin/users.php");
        exit;
    }
    
    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $_SESSION['message'] = 'Passwords do not match';
        header("Location: ../admin/users.php");
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Invalid email format';
        header("Location: ../admin/users.php");
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Check if email already exists in either table
        $checkStmt = $conn->prepare("SELECT email FROM admin WHERE email = ? UNION SELECT email FROM users WHERE email = ?");
        $checkStmt->bind_param("ss", $email, $email);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            throw new Exception("Email already exists");
        }

        if ($role === 'staff') {
            // Insert into admin table for staff
            $stmt = $conn->prepare("
                INSERT INTO admin (name, email, password, role, status, mobile, created_at)
                VALUES (?, ?, ?, 'staff', 'active', ?, NOW())
            ");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $mobile);
        } else if ($role === 'user') {
            // Insert into users table for regular users
            $stmt = $conn->prepare("
                INSERT INTO users (name, email, password, status, mobile, created_at)
                VALUES (?, ?, ?, 'active', ?, NOW())
            ");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $mobile);
        } else {
            throw new Exception("Invalid role specified");
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create user: " . $stmt->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['message'] = "User created successfully!";
        header("Location: ../admin/users.php");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['message'] = "Error creating user: " . $e->getMessage();
        header("Location: ../admin/users.php");
        exit;
    }
} else {
    $_SESSION['message'] = "Invalid request method";
    header("Location: ../admin/users.php");
    exit;
}
?>