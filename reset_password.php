<?php
session_start();
require_once 'php/db.php';

$message = '';
$error = '';
$showForm = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid or expired reset link.';
} else {
    $token = $conn->real_escape_string($token);
    $currentTime = date('Y-m-d H:i:s');
    
    // Check if token is valid and not expired
    $result = $conn->query("SELECT id FROM users WHERE reset_token = '$token' AND reset_token_expires > '$currentTime' LIMIT 1");
    
    if ($result->num_rows === 0) {
        $error = 'Invalid or expired reset link. Please request a new one.';
    } else {
        $user = $result->fetch_assoc();
        $userId = $user['id'];
        $showForm = true;
        
        // Process form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if (empty($password) || empty($confirm_password)) {
                $error = 'Both password fields are required.';
            } elseif ($password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } elseif (strlen($password) < 8) {
                $error = 'Password must be at least 8 characters long.';
            } else {
                // Update password and clear reset token
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hashed_password', reset_token = NULL, reset_token_expires = NULL WHERE id = $userId");
                
                if ($conn->affected_rows > 0) {
                    $message = 'Your password has been reset successfully. You can now login with your new password.';
                    $showForm = false;
                } else {
                    $error = 'Failed to reset password. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Application System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .reset-password-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .reset-password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .reset-password-header h2 {
            color: #333;
        }
        .btn-reset {
            background-color: #0d6efd;
            border: none;
            padding: 10px;
            font-weight: 600;
        }
        .btn-reset:hover {
            background-color: #0b5ed7;
        }
        .form-footer {
            text-align: center;
            margin-top: 20px;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-password-container">
            <div class="reset-password-header">
                <h2>Reset Your Password</h2>
                <p class="text-muted">Choose a new password for your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?php echo $message; ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-primary">Back to Login</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($showForm): ?>
                <form id="resetPasswordForm" method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-requirements">
                            Password must be at least 8 characters long
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-reset">Reset Password</button>
                    </div>
                </form>
                
                <div class="form-footer">
                    <p class="mb-0">
                        Remember your password? <a href="index.php" class="text-decoration-none">Sign in</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Client-side form validation
        document.getElementById('resetPasswordForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
