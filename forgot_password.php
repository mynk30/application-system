<?php
session_start();
require_once 'php/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $email = $conn->real_escape_string($email);
        $result = $conn->query("SELECT id, name FROM users WHERE email = '$email' LIMIT 1");
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $conn->query("UPDATE users SET reset_token = '$token', reset_token_expires = '$expires' WHERE id = {$user['id']}");
            
            // Send email with reset link (in a real application)
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/application-system/reset_password.php?token=$token";
            
            // For demo purposes, we'll just show the reset link
            $message = "Password reset link has been sent to your email. For demo purposes, here's the reset link: <a href='$resetLink'>$resetLink</a>";
            
            // In a real application, you would send an email here
            // mail($email, 'Password Reset', "Click the link to reset your password: $resetLink");
        } else {
            // For security, don't reveal if the email exists or not
            $message = 'If your email exists in our system, you will receive a password reset link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Application System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .forgot-password-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .forgot-password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .forgot-password-header h2 {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-password-container">
            <div class="forgot-password-header">
                <h2>Forgot Password</h2>
                <p class="text-muted">Enter your email to reset your password</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-info">
                    <?php echo $message; ?>
                    <div class="mt-3">
                        <a href="index.php" class="btn btn-primary">Back to Login</a>
                    </div>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">Enter the email address associated with your account.</div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-reset">Send Reset Link</button>
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
</body>
</html>
