<?php
session_start();

require_once 'php/Logger.php';
$logger = Logger::getInstance();

// Debug: Display session contents
error_log('Session contents: ' . print_r($_SESSION, true));
$logger->info('Session contents: ' . print_r($_SESSION, true));


// For development: Display session contents on screen (comment out in production)
// echo '<pre>' . print_r($_SESSION, true) . '</pre>';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    // Check if user_role exists in session, otherwise use empty string
    $userRole = $_SESSION['user_role'] ?? '';
    
    switch (strtolower($userRole)) {
        case 'admin':
            header("Location: /application-system/admin/dashboard.php");
            exit();
        case 'staff':
            header("Location: /application-system/staff/dashboard.php");
            exit();
        default:
            // If we get here, either user_role is not set or has an unexpected value
            $logger->error('Unknown or missing user role in session', [
                'user_id' => $_SESSION['user_id'],
                'session_data' => $_SESSION
            ]);
            // Clear the session to force re-login
            session_unset();
            session_destroy();
            break;
    }
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'php/auth.php';
    $response = authenticateUser($_POST['email'], $_POST['password']);
    if (is_array($response) && isset($response['error'])) {
        $error = $response['error'];
    } else {
        $error = $response;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Debug: Display session data in browser console -->
    <script>
        // Convert PHP session data to JavaScript object
        <?php
        $sessionData = $_SESSION;
        $jsonSession = json_encode($sessionData);
        ?>
        
        // Log session data to browser console
        console.log('PHP Session Data:', <?php echo $jsonSession; ?>);
        
        // Log specific session variables
        <?php if (isset($_SESSION['user_id'])): ?>
            console.log('User ID:', '<?php echo $_SESSION['user_id']; ?>');
            console.log('User Role:', '<?php echo $_SESSION['user_role']; ?>');
        <?php endif; ?>
    </script>
    <title>Login - Application System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-login {
            background-color: #0d6efd;
            border: none;
            padding: 10px;
            font-weight: 600;
        }
        .btn-login:hover {
            background-color: #0b5ed7;
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2>Application System</h2>
                <p class="text-muted">Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo is_array($error) ? implode('<br>', $error) : htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-login">Sign In</button>
                </div>
            </form>
          
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
