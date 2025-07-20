<?php
require_once '../php/auth.php';
requireRole(['staff']);
require_once '../php/config.php';
global $logger, $browserLogger;

require_once '../php/db.php';

$user_id = $_SESSION['user_id'];

$logger->info('Staff ID from session: ' . $user_id);

$logger->info('Session data: ' . print_r($_SESSION, true));
$message = '';
$error = '';

// Handle profile information update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $logger->info("this is logger and uploading file");
    $logger->info('Update profile attempt', [
        'user_id' => $user_id,
        'name' => $name,
        'email' => $email,
        'session' => $_SESSION
    ]);
    return;
    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
        $logger->warning('Profile update validation failed', ['error' => $error]);
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            $stmt = $conn->prepare("UPDATE admin SET name = ?, email = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $stmt->bind_param("ssi", $name, $email, $user_id);
            $result = $stmt->execute();
            
            if ($result) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                
                // Commit transaction
                $conn->commit();
                
                $message = 'Profile updated successfully.';
                $logger->info('Profile updated successfully', [
                    'user_id' => $user_id,
                    'name' => $name,
                    'email' => $email
                ]);
                
                // Redirect to prevent form resubmission
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                exit();
            } else {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = 'Failed to update profile. Please try again.';
            $logger->error('Profile update failed', [
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/';
        $file_name = uniqid() . '-' . basename($file['name']);
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $original_name = $file['name'];
            $file_size = $file['size'];
            $model_type = 'admin';
            $model_id = $user_id;

            $stmt = $conn->prepare("INSERT INTO files (original_name, file_name, file_path, file_size, model_type, model_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiis", $original_name, $file_name, $file_path, $file_size, $model_type, $model_id);

            if ($stmt->execute()) {
                $message = 'Profile picture uploaded successfully.';
            } else {
                $error = 'Failed to save file information to the database.';
            }
        } else {
            $error = 'Failed to upload file.';
        }
    } else {
        $error = 'Error uploading file.';
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM admin WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch profile picture
$stmt = $conn->prepare("SELECT * FROM files WHERE model_type = 'admin' AND model_id = ? ORDER BY uploaded_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_picture = $result->fetch_assoc();
$logger->info('Profile picture: ' . print_r($profile_picture, true));

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
        <h2>Staff Profile</h2>

        <?php 
if (isset($_GET['success']) && $_GET['success'] == 1) {
    echo '<div class="alert alert-success">Profile updated successfully.</div>';
} elseif (isset($message)) {
    echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}
if (isset($error)) {
    echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
}
?>

        <div class="row">
            <div class="col-md-4">
                <h4>Profile Picture</h4>
                <img id="preview" src="<?php echo $profile_picture ? '../uploads/' . $profile_picture['file_name'] : 'https://via.placeholder.com/150'; ?>" alt="Profile Picture" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px;">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Upload New Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" required onchange="previewImage(event)">
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>
            <div class="col-md-8">
                <h4>Profile Information</h4>
                <form action="" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
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
