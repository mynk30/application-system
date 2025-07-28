<?php
session_start();
require_once '../php/auth.php';
requireRole(['admin']);

require_once '../php/db.php';

$user_id = $_SESSION['user_id'];
$current_page = basename($_SERVER['PHP_SELF']);

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'],$_SESSION['error']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_SESSION['user_email'];
    $hasError = false;

    // Validate required fields
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = 'Name and email are required.';
        $hasError = true;
    }
    
    // Process profile picture if uploaded
    $file_uploaded = isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE;
    
    if ($file_uploaded && !$hasError) {
        $file = $_FILES['profile_picture'];
        $allowed_types = ['image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_type = $file['type'];
        $max_file_size = 5 * 1024 * 1024; // 5MB
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Error uploading file. Error code: ' . $file['error'];
            $hasError = true;
        } elseif (!in_array($file_type, array_keys($allowed_types))) {
            $_SESSION['error'] = 'Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.';
            $hasError = true;
        } elseif ($file['size'] > $max_file_size) {
            $_SESSION['error'] = 'File is too large. Maximum size allowed is 5MB.';
            $hasError = true;
        }
    }
    
    // If no errors, proceed with database operations
    if (!$hasError) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update user profile
            $stmt = $conn->prepare("UPDATE admin SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
            $logger->info("Updating profile for user ID: $user_id. Name: " . $name);
            $updateSuccess = $stmt->execute();
            
            $logger->info("Profile update success: " . ($updateSuccess ? 'Yes' : 'No'));
            
            if (!$updateSuccess) {
                throw new Exception('Failed to update profile information.');
            }
            
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            // Handle file upload if a file was uploaded
            if ($file_uploaded) {
                $upload_dir = '../uploads/profiles/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate a unique filename
                $file_name = uniqid() . '.' . $allowed_types[$file_type];
                $file_path = $upload_dir . $file_name;
                
                // Move the uploaded file
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    try {
                        // Delete old profile picture if exists
                        $deleteStmt = $conn->prepare("SELECT file_path FROM files WHERE model_type = 'admin' AND model_id = ?");
                        $deleteStmt->bind_param("i", $user_id);
                        $deleteStmt->execute();
                        $oldFile = $deleteStmt->get_result()->fetch_assoc();
                        
                        $deleteStmt = $conn->prepare("DELETE FROM files WHERE model_type = 'admin' AND model_id = ?");
                        $deleteStmt->bind_param("i", $user_id);
                        $deleteStmt->execute();
                        
                        // Insert new file record
                        $original_name = $file['name'];
                        $file_size = $file['size'];
                        $model_type = 'admin';
                        
                        $insertStmt = $conn->prepare("INSERT INTO files (original_name, file_name, file_path, file_size, model_type, model_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $insertStmt->bind_param("sssssi", $original_name, $file_name, $file_path, $file_size, $model_type, $user_id);
                        
                        if (!$insertStmt->execute()) {
                            throw new Exception('Failed to save profile picture information.');
                        }
                        
                        // Update session with new profile picture
                        $_SESSION['profile_picture'] = $file_name;
                        
                        // Delete the old file from server after successful database operations
                        if ($oldFile && file_exists($oldFile['file_path'])) {
                            unlink($oldFile['file_path']);
                        }
                        
                        
                    } catch (Exception $e) {
                        // Clean up the uploaded file if it exists
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                        throw $e;
                    }
                } else {
                    throw new Exception('Failed to upload profile picture.');
                }
            }
        
            
            // If we got here, everything was successful
            $conn->commit();
            $_SESSION['message'] = 'Profile updated successfully' . ($file_uploaded ? ' with new profile picture' : '') . '.';
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->in_transaction) {
                $conn->rollback();
            }
            $_SESSION['error'] = $e->getMessage();
            $logger->error("Profile update failed: " . $e->getMessage());
            
            // Clean up uploaded file if it exists
            if (isset($file_path) && file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    }

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch profile picture if not in session
if (!isset($_SESSION['profile_picture'])) {
    $picStmt = $conn->prepare("SELECT file_name FROM files WHERE model_type = 'admin' AND model_id = ? ORDER BY id DESC LIMIT 1");
    $picStmt->bind_param("i", $user_id);
    $picStmt->execute();
    $picResult = $picStmt->get_result();
    if ($picResult && $picResult->num_rows > 0) {
        $picture = $picResult->fetch_assoc();
        $_SESSION['profile_picture'] = $picture['file_name'];
    }
    $picStmt->close();
}
$user = $result->fetch_assoc();

// Fetch profile picture
$stmt = $conn->prepare("SELECT * FROM files WHERE model_type = 'admin' AND model_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_picture = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <h2>Admin Profile</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <form action="" method="POST" enctype="multipart/form-data" id="profileForm">
                    <div class="row">
                        <div class="col-md-4 text-center mb-4">
                            <div class="mb-3">
                                <img id="preview" src="<?php echo $profile_picture ? '../uploads/profiles/' . $profile_picture['file_name'] : 'https://via.placeholder.com/150'; ?>" 
                                     alt="Profile Picture" class="img-fluid rounded-circle mb-3" 
                                     style="width: 200px; height: 200px; object-fit: cover; border: 3px solid #dee2e6;">
                                <div class="position-relative d-inline-block">
                                    <label for="profile_picture" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-camera"></i> Change Photo
                                    </label>
                                    <input type="file" class="d-none" id="profile_picture" name="profile_picture" 
                                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" 
                                           onchange="previewImage(event)">
                                </div>
                                <div id="fileError" class="invalid-feedback d-block"></div>
                                <div class="small text-muted mt-2">Allowed JPG, JPEG, PNG or WEBP. Max size 5MB</div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title mb-4">Profile Information</h4>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input disabled type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                        <button type="submit" name="update_profile" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Save Changes
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <script>
        function validateImage() {
            const fileInput = document.getElementById('profile_picture');
            const fileError = document.getElementById('fileError');
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            // Reset previous errors
            fileError.textContent = '';
            fileInput.classList.remove('is-invalid');
            
            // Only validate file if one is selected
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                
                // Check file type
                if (!allowedTypes.includes(file.type)) {
                    fileInput.classList.add('is-invalid');
                    fileError.textContent = 'Invalid file type. Only JPG, JPEG, PNG, and WEBP files are allowed.';
                    return false;
                }
                
                // Check file size
                if (file.size > maxSize) {
                    fileInput.classList.add('is-invalid');
                    fileError.textContent = 'File is too large. Maximum size allowed is 5MB.';
                    return false;
                }
                
                return true;
            }
            
            return true; // No file is also valid
        }
        
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.src = e.target.result;
                    
                    // Validate the image when selected
                    validateImage();
                };
                reader.readAsDataURL(file);
            }
        }
        </script>
        
        <style>
        .btn-outline-primary:hover {
            background-color: #0d6efd;
            color: white;
        }
        .invalid-feedback {
            color: #dc3545;
        }
        .is-invalid {
            border-color: #dc3545;
        }
        </style>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('preview');
                output.src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>
</html>
