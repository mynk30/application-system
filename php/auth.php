<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function authenticateUser($email, $password) {
    global $conn;
    // Initialize logger
    require_once 'Logger.php';
    $logger = Logger::getInstance();
    
    if (empty($email) || empty($password)) {
        $logger->warning("Authentication attempt with empty credentials");
        return ["error" => "Email and password are required."];
    }

    try {
        // Prepare and execute the statement for the 'admin' table
        $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $logger->info("User found: " . json_encode($user));

            // Verify hashed password
            if (password_verify($password, $user['password'])) {
                $logger->info("Password verified successfully");
                // Check user status
                if ($user['status'] === 'active') {
                    // Regenerate session ID
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

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
                    return ["error" => "Your account is inactive. Please contact support."];
                }
            }
        }
        
        // If we get here, login failed
        return ["error" => "Invalid email or password."];
    } catch (Exception $e) {
        $logger->error("Authentication error: " . $e->getMessage());
        return ["error" => "An error occurred during authentication. Please try again."];
    }
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
