<?php
require_once '../php/config.php';
require_once '../includes/header.php';
requireRole(['staff']);

// Ensure uploads directory exists and has proper permissions
$base_upload_dir = dirname(dirname(__FILE__)) . '/uploads';
$applications_upload_dir = $base_upload_dir . '/applications';

// Create directories if they don't exist
if (!file_exists($base_upload_dir)) {
    mkdir($base_upload_dir, 0755, true);
}
if (!file_exists($applications_upload_dir)) {
    mkdir($applications_upload_dir, 0755, true);
}

// Set permissions (only if on Linux/Unix)
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    chmod($base_upload_dir, 0755);
    chmod($applications_upload_dir, 0755);
}

// Function to delete a file
function deleteFile($file_id, $conn) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get file info before deleting
        $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $file = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$file) {
            throw new Exception('File not found');
        }
        
        // Delete file from server
        if (file_exists($file['file_path'])) {
            if (!unlink($file['file_path'])) {
                throw new Exception('Failed to delete file from server');
            }
            
            // Try to remove the directory if it's empty
            $file_dir = dirname($file['file_path']);
            if (is_dir($file_dir) && count(glob($file_dir . '/*')) === 0) {
                rmdir($file_dir);
            }
        }
        
        // Delete record from database
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete file record from database');
        }
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        error_log('Error deleting file: ' . $e->getMessage());
        return false;
    }
}

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid application ID';
    header('Location: applications.php');
    exit();
}

$application_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Verify the application exists and belongs to the current user or user is admin
$sql = "SELECT * FROM applications WHERE id = ? AND (user_id = ? OR ? IN (SELECT id FROM admin WHERE role = 'admin'))";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $application_id, $user_id, $user_id);
$stmt->execute();
$application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$application) {
    $_SESSION['error'] = 'Application not found or access denied';
    header('Location: applications.php');
    exit();
}

// Fetch associated documents
$stmt = $conn->prepare("SELECT * FROM files WHERE model_type = 'application' AND model_id = ? ORDER BY uploaded_at DESC");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
                $upload_dir = $applications_upload_dir . '/' . $application_id . '/';
                if (!file_exists($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        throw new Exception('Failed to create upload directory. Please check permissions.');
                    }
                    // Set directory permissions (for Linux/Unix)
                    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                        chmod($upload_dir, 0755);
                    }
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
            throw new Exception('Failed to update application');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Edit Application</h4>
                <p class="text-muted mb-0">Update application details and documents</p>
            </div>
            <a href="applications.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Applications
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
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
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($application['email']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($application['phone']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Service Type</label>
                    <select name="service_type" class="form-select" required>
                        <option value="" disabled>Select a service</option>
                        <option value="GST Registration" <?= $application['service_type'] === 'GST Registration' ? 'selected' : '' ?>>GST Registration</option>
                        <option value="MSME Registration" <?= $application['service_type'] === 'MSME Registration' ? 'selected' : '' ?>>MSME Registration</option>
                        <option value="Trademark Registration" <?= $application['service_type'] === 'Trademark Registration' ? 'selected' : '' ?>>Trademark Registration</option>
                        <option value="FSSAI License" <?= $application['service_type'] === 'FSSAI License' ? 'selected' : '' ?>>FSSAI License</option>
                        <option value="Import Export Code" <?= $application['service_type'] === 'Import Export Code' ? 'selected' : '' ?>>Import Export Code</option>
                    </select>
                </div>
                <div class="col-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($application['address']) ?></textarea>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" <?= $application['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="missing_document" <?= $application['status'] === 'missing_document' ? 'selected' : '' ?>>Missing Document</option>
                    </select>
                </div>
                
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Documents</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Upload Documents</label>
                                <input type="file" name="documents[]" class="form-control" multiple>
                                <div class="form-text">Allowed file types: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX. Max file size: 5MB</div>
                            </div>
                            
                            <?php if (!empty($documents)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>File Name</th>
                                                <th>Size</th>
                                                <th>Uploaded</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($documents as $doc): ?>
                                                <tr>
                                                    <td>
                                                        <i class="far fa-file me-2"></i>
                                                        <?= htmlspecialchars($doc['original_name']) ?>
                                                    </td>
                                                    <td><?= formatFileSize($doc['file_size']) ?></td>
                                                    <td><?= date('M d, Y h:i A', strtotime($doc['uploaded_at'])) ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="../download.php?file=<?= urlencode($doc['file_path']) ?>" 
                                                               class="btn btn-outline-primary" 
                                                               title="Download">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-outline-danger delete-document" 
                                                                    data-id="<?= $doc['id'] ?>"
                                                                    data-name="<?= htmlspecialchars($doc['original_name']) ?>"
                                                                    title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No documents uploaded yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="update_application" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Document Confirmation Modal -->
<div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <span id="documentName" class="fw-bold"></span>?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteDocumentForm" method="POST" style="display: inline;">
                    <input type="hidden" name="delete_document" id="deleteDocumentId" value="">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Document delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-document');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteDocumentModal'));
    const documentNameSpan = document.getElementById('documentName');
    const deleteDocumentForm = document.getElementById('deleteDocumentForm');
    const deleteDocumentId = document.getElementById('deleteDocumentId');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const docId = this.getAttribute('data-id');
            const docName = this.getAttribute('data-name');
            
            documentNameSpan.textContent = docName;
            deleteDocumentId.value = docId;
            
            deleteModal.show();
        });
    });
});
</script>

<?php 
// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 B';
    $k = 1024;
    $sizes = ['B', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

require_once '../includes/footer.php'; 
?>
