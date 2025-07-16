<?php
require_once 'db.php';

function registerUser($name, $email, $password, $confirm_password) {
    global $conn;
    
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
    $email = $conn->real_escape_string($email);
    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already registered.";
    }
    
    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Determine role based on email domain (example: admin@example.com, staff@example.com)
    $role = 'user'; // Default role
    $email_parts = explode('@', $email);
    $domain = strtolower(end($email_parts));
    
    if (strpos($domain, 'admin.') === 0) {
        $role = 'admin';
    } elseif (strpos($domain, 'staff.') === 0) {
        $role = 'staff';
    }
    
    // Insert user into database
    $name = $conn->real_escape_string($name);
    $sql = "INSERT INTO users (name, email, password, role, status, created_at) 
            VALUES (?, ?, ?, ?, 'active', NOW())";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'Registration successful! You can now login.'
            ];
        } else {
            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];
        }
    }
    
    return [
        'success' => false,
        'errors' => ['Something went wrong. Please try again.']
    ];
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
