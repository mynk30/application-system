<?php
require_once '../php/config.php';
require_once '../includes/header.php';
requireRole(['admin']);

// Function to delete a file
function deleteFile($file_id, $conn) {
    // Get file info before deleting
    $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->bind_param("i", $file_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($file) {
        // Delete file from server
        if (file_exists($file['file_path'])) {
            unlink($file['file_path']);
        }
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $stmt->close();
        
        return true;
    }
    return false;
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid application ID';
    header('Location: applications.php');
    exit();
}

$application_id = intval($_GET['id']);

// Fetch application details
$stmt = $conn->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch associated documents
$stmt = $conn->prepare("SELECT * FROM files WHERE model_type = 'application' AND model_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (!$application) {
    $_SESSION['error'] = 'Application not found';
    header('Location: applications.php');
    exit();
}

// Handle document deletion
if (isset($_POST['delete_document']) && is_numeric($_POST['delete_document'])) {
    if (deleteFile($_POST['delete_document'], $conn)) {
        $_SESSION['success'] = 'Document deleted successfully';
    } else {
        $_SESSION['error'] = 'Error deleting document';
    }
    header("Location: edit_application.php?id=" . $application_id);
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    try {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $service_type = trim($_POST['service_type']);
        $status = trim($_POST['status']);
        
        // Basic validation
        if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($service_type)) {
            throw new Exception('All fields are required');
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Update application
        $stmt = $conn->prepare("UPDATE applications SET 
            name = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            service_type = ?, 
            status = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
            
        $stmt->bind_param("ssssssi", 
            $name, 
            $email, 
            $phone, 
            $address, 
            $service_type, 
            $status,
            $application_id
        );
        
        if ($stmt->execute()) {
            // Handle file uploads
            if (!empty($_FILES['documents']['name'][0])) {
                $upload_dir = '../uploads/applications/' . $application_id . '/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach ($_FILES['documents']['tmp_name'] as $key => $tmp_name) {
                    $file_name = $_FILES['documents']['name'][$key];
                    $file_tmp = $_FILES['documents']['tmp_name'][$key];
                    $file_size = $_FILES['documents']['size'][$key];
                    $file_error = $_FILES['documents']['error'][$key];
                    
                    if ($file_error === 0 && $file_size > 0) {
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
                        
                        if (in_array($file_ext, $allowed_ext)) {
                            $new_file_name = uniqid() . '.' . $file_ext;
                            $file_destination = $upload_dir . $new_file_name;
                            
                            if (move_uploaded_file($file_tmp, $file_destination)) {
                                // Save to database
                                $stmt = $conn->prepare("INSERT INTO files (original_name, file_name, file_path, file_size, model_type, model_id) VALUES (?, ?, ?, ?, 'application', ?)");
                                $stmt->bind_param("ssssi", 
                                    $file_name,
                                    $new_file_name,
                                    $file_destination,
                                    $file_size,
                                    $application_id
                                );
                                $stmt->execute();
                                $stmt->close();
                            }
                        }
                    }
                }
            }
            
            $_SESSION['success'] = 'Application updated successfully';
            header('Location: applications.php');
            exit();
        } else {
            throw new Exception('Error updating application: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">Edit Application</h4>
        <p class="text-muted">Update application details</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($application['name']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($application['email']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($application['phone']) ?>" required>
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($application['address']) ?></textarea>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service Type</label>
                    <select name="service_type" class="form-select" required>
                        <option value="" disabled>Select Service Type</option>
                        <option value="Passport" <?= $application['service_type'] === 'Passport' ? 'selected' : '' ?>>Passport</option>
                        <option value="Visa" <?= $application['service_type'] === 'Visa' ? 'selected' : '' ?>>Visa</option>
                        <option value="GST Registration" <?= $application['service_type'] === 'GST Registration' ? 'selected' : '' ?>>GST Registration</option>
                        <option value="Digital Signature" <?= $application['service_type'] === 'Digital Signature' ? 'selected' : '' ?>>Digital Signature</option>
                        <option value="MSME Registration" <?= $application['service_type'] === 'MSME Registration' ? 'selected' : '' ?>>MSME Registration</option>
                        <option value="Income Tax Filing" <?= $application['service_type'] === 'Income Tax Filing' ? 'selected' : '' ?>>Income Tax Filing</option>
                        <option value="Trademark Registration" <?= $application['service_type'] === 'Trademark Registration' ? 'selected' : '' ?>>Trademark Registration</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="missing_document" <?= $application['status'] === 'missing_document' ? 'selected' : '' ?>>Missing Document</option>
                        <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                
                <!-- Documents Section -->
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Documents</h5>
                        </div>
                        <div class="card-body">
                            <!-- Existing Documents -->
                            <div class="mb-4">
                                <h6>Existing Documents</h6>
                                <?php if (count($documents) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Document Name</th>
                                                    <th>Uploaded At</th>
                                                    <th>Size</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($documents as $doc): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($doc['original_name']) ?></td>
                                                        <td><?= date('M d, Y H:i', strtotime($doc['uploaded_at'])) ?></td>
                                                        <td><?= number_format($doc['file_size'] / 1024, 2) ?> KB</td>
                                                        <td>
                                                            <a href="../download_file.php?id=<?= $doc['id'] ?>" 
                                                               class="btn btn-sm btn-outline-primary" 
                                                               title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <button type="submit" 
                                                                    name="delete_document" 
                                                                    value="<?= $doc['id'] ?>" 
                                                                    class="btn btn-sm btn-outline-danger"
                                                                    onclick="return confirm('Are you sure you want to delete this document?')"
                                                                    title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No documents uploaded yet.</p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Upload New Documents -->
                            <div class="border-top pt-3">
                                <h6>Upload New Documents</h6>
                                <div class="mb-3">
                                    <label class="form-label">Select Files (PDF, JPG, PNG, DOC, DOCX, XLS, XLSX)</label>
                                    <input type="file" name="documents[]" class="form-control" multiple>
                                    <div class="form-text">Max file size: 10MB per file</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 mt-3">
                    <button type="submit" name="update_application" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Application
                    </button>
                    <a href="applications.php" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
