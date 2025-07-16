<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function authenticateUser($email, $password) {
    global $conn;
    
    $email = $conn->real_escape_string($email);
    $sql = "SELECT id, name, email, phone, address, password, role, status FROM users WHERE email = ?";

    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === 'active') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['user_address'] = $user['address'];

                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: /application-system/admin/dashboard.php");
                            break;
                        case 'staff':
                            header("Location: /application-system/staff/dashboard.php");
                            break;
                        case 'user':
                            header("Location: /application-system/user/dashboard.php");
                            break;
                    }
                    exit();
                } else {
                    return "Your account is inactive. Please contact support.";
                }
            }
        }
        
        // If we get here, login failed
        return "Invalid email or password.";
    }
    
    return "Something went wrong. Please try again later.";
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /application-system/index.php");
        exit();
    }
}

function requireRole($allowedRoles) {
    requireLogin();
    
    if (!in_array($_SESSION['user_role'], (array)$allowedRoles)) {
        header("HTTP/1.1 403 Forbidden");
        die("You don't have permission to access this page.");
    }
}

// Logout function
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header("Location: /application-system/index.php");
    exit();
}
?>
