<?php
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: applications.php');
    exit;
}

$appId = (int)$_GET['id'];

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];

    // Process required_documents if status is missing_document
    $missingDocs = null;
    if ($newStatus === 'missing_document' && !empty($_POST['missing_documents'])) {
        $docsArray = array_map('trim', explode(',', $_POST['missing_documents']));
        $missingDocs = json_encode(array_filter($docsArray));
    }

    if ($missingDocs !== null) {
        $stmt = $conn->prepare("UPDATE applications SET status = ?, required_documents = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("sssi", $newStatus, $missingDocs, $_SESSION['user_name'], $appId);
    } else {
        $stmt = $conn->prepare("UPDATE applications SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->bind_param("ssi", $newStatus, $_SESSION['user_name'], $appId);
    }

    $stmt->execute();
    header("Location: view_application.php?id=" . $appId);
    exit;
}

// Get application details and associated files
$stmt = $conn->prepare("
      SELECT 
        a.*, 
        u.name , 
        u.email , 
        u.mobile ,
        GROUP_CONCAT(f.original_name ORDER BY f.uploaded_at DESC SEPARATOR ', ') AS uploaded_files
    FROM applications a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN files f ON f.model_type = 'application' AND f.model_id = a.id
    WHERE a.id = ?
    GROUP BY a.id
");
$stmt->bind_param("i", $appId);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();

if (!$application) {
    header('Location: applications.php');
    exit;
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Application ID #<?= htmlspecialchars($application['id']) ?></h4>
                <p class="text-muted">View application details</p>
            </div>
            <a href="applications.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Applications
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Applicant Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($application['name']) ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($application['email']) ?></dd>

                    <dt class="col-sm-4">Phone</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($application['mobile']) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Service Information</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Applied For</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($application['service_type']) ?></dd>

                    <dt class="col-sm-4">Application Date</dt>
                    <dd class="col-sm-8"><?= date('M d, Y', strtotime($application['created_at'])) ?></dd>

                    <dt class="col-sm-4">Status</dt>
                    <dd class="col-sm-8">
                        <?php
                        $statusClass = '';
                        switch ($application['status']) {
                            case 'approved':
                                $statusClass = 'badge bg-success';
                                break;
                            case 'rejected':
                                $statusClass = 'badge bg-danger';
                                break;
                            case 'missing_document':
                                $statusClass = 'badge bg-warning text-dark';
                                break;
                            default:
                                $statusClass = 'badge bg-secondary';
                        }
                        ?>
                        <span class="<?= $statusClass ?>">
                            <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Documents Uploaded</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($application['uploaded_files'])): ?>
                    <ul class="list-group">
                        <?php foreach (explode(', ', $application['uploaded_files']) as $docName): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($docName) ?>
                                <a href="download_file.php?id=<?= $appId ?>&file=<?= urlencode($docName) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No documents uploaded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Form -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Update Application Status</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="status" class="form-label">Select New Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pending" <?= $application['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $application['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $application['status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="missing_document" <?= $application['status'] == 'missing_document' ? 'selected' : '' ?>>Missing Documents</option>
                        </select>
                    </div>
                    
                    <!-- Add this below your Select in the Update Status Form -->
                    <div class="mb-3 d-none" id="missing-documents-group">
                        <label for="missing_documents" class="form-label">Missing Documents (comma separated)</label>
                        <input type="text" class="form-control" id="missing_documents" name="missing_documents" placeholder="Example: ID Proof, Address Proof, Signature">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('status').addEventListener('change', function() {
    const missingDocsGroup = document.getElementById('missing-documents-group');
    if (this.value === 'missing_document') {
        missingDocsGroup.classList.remove('d-none');
    } else {
        missingDocsGroup.classList.add('d-none');
        document.getElementById('missing_documents').value = '';
    }
});

// Show on page load if 'missing_document' is pre-selected
if (document.getElementById('status').value === 'missing_document') {
    document.getElementById('missing-documents-group').classList.remove('d-none');
}
</script>

<?php require_once '../includes/footer.php'; ?>
