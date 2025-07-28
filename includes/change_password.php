<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/Logger.php';

// Initialize logger
$logger = Logger::getInstance();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email']) || !isset($_SESSION['user_role'])) {
    header('Location: /application-system/index.php');
    exit();
}

// Set variables from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // log three of there
    $logger->info("Current password: " . $currentPassword);
    $logger->info("New password: " . $newPassword);
    $logger->info("Confirm password: " . $confirmPassword);

    // Clear any previous message
    $message = '';
    $success = false;

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New password and confirm password do not match.";
    } elseif (strlen($newPassword) < 8) {
        $message = "Password must be at least 8 characters long.";
    }
    //  elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || 
    //           !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
    //     $message = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    // } 
    
    else {
        // Determine the table based on user role
        
        
        try {
            // Get current password hash
            $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                $logger->info("User found:======== " . json_encode($user));
                
                if (password_verify($currentPassword, $user['password'])) {

                    $logger->info("Password verified successfully");
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $updateStmt->bind_param('si', $newPasswordHash, $userId);
                    
                    if ($updateStmt->execute()) {
                        // Log the password change
                        $logger->info("Password changed successfully for user ID: $userId");
                        
                        // Set success message
                        $message = "Password updated successfully.";
                        $success = true;
                        
                        // Clear the form on success
                        echo '<script>document.getElementById("changePasswordForm").reset();</script>';
                    } else {
                        $message = "Error updating password. Please try again.";
                        $logger->error("Failed to update password for user ID: $userId - " . $conn->error);
                    }
                    $updateStmt->close();
                } else {
                    $message = "Current password is incorrect.";
                    $logger->info("Incorrect current password attempt for user ID: $userId");
                }
            } else {
                $message = "User not found.";
                $logger->info("User not found while changing password - ID: $userId, Email: $email");
            }
            $stmt->close();
        } catch (Exception $e) {
            $message = "An error occurred. Please try again later.";
            $logger->error("Error in change_password.php: " . $e->getMessage());
        }
    }
}
?>

<?php 
// Set page title
$pageTitle = 'Change Password';

// Include header without requiring auth.php again to prevent redirection
include __DIR__ . '/header.php'; 
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?> alert-dismissible fade show mb-4" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas <?php echo $success ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                                <div>
                                    <?php echo htmlspecialchars($message); ?>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="changePasswordForm" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="current_password" class="form-label fw-medium">Current Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
                                <input type="password" class="form-control form-control-lg" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">Please enter your current password.</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="new_password" class="form-label fw-medium">New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-key text-primary"></i></span>
                                <input type="password" class="form-control form-control-lg" id="new_password" name="new_password" required 
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[^a-zA-Z0-9])(?=\S+$).{8,}">
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Password must be at least 8 characters long and include uppercase, lowercase, numbers, and special characters.
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-medium">Confirm New Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fas fa-key text-primary"></i></span>
                                <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm_password">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Passwords do not match.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" name="change_password" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Update Password
                            </button>
                            <a href="<?php echo isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'dashboard.php'; ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password strength validation
    // const newPassword = document.getElementById('new_password');
    // const confirmPassword = document.getElementById('confirm_password');
    // const requirements = {
    //     length: /.{8,}/,
    //     uppercase: /[A-Z]/, 
    //     lowercase: /[a-z]/,
    //     number: /[0-9]/,
    //     special: /[^A-Za-z0-9]/
    // };

    function updatePasswordRequirements() {
        const value = newPassword.value;
        
        Object.keys(requirements).forEach(key => {
            const requirement = document.querySelector(`[data-requirement="${key}"]`);
            const checkIcon = requirement.querySelector('.fa-check-circle');
            const timesIcon = requirement.querySelector('.fa-times-circle');
            
            if (requirements[key].test(value)) {
                checkIcon.classList.remove('d-none');
                timesIcon.classList.add('d-none');
            } else {
                checkIcon.classList.add('d-none');
                timesIcon.classList.remove('d-none');
            }
        });
    }

    // Check if passwords match
    function checkPasswordsMatch() {
        if (newPassword.value !== '' && confirmPassword.value !== '') {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
    }

    // Event listeners
    if (newPassword) {
        newPassword.addEventListener('input', updatePasswordRequirements);
        newPassword.addEventListener('input', checkPasswordsMatch);
    }
    
    if (confirmPassword) {
        confirmPassword.addEventListener('input', checkPasswordsMatch);
    }

    // Form validation
    (function () {
        'use strict';
        
        // Fetch all the forms we want to apply custom Bootstrap validation styles to
        const forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', function (event) {
                // Check if passwords match before form submission
                if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                }
                
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
            
            // Reset custom validation on input
            form.querySelectorAll('input').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.willValidate) {
                        this.setCustomValidity('');
                    }
                    this.reportValidity();
                });
            });
        });
    })();
    
    // Show password requirements on focus
    if (newPassword) {
        newPassword.addEventListener('focus', function() {
            document.querySelector('.password-requirements').classList.remove('d-none');
        });
        
        newPassword.addEventListener('blur', function() {
            if (this.value === '') {
                document.querySelector('.password-requirements').classList.add('d-none');
            }
        });
    }
</script>

<?php include 'footer.php'; ?>
