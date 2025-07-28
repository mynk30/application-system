<?php
require_once '../includes/header.php';

require_once '../php/config.php';
requireRole(['staff']);

// Get applications assigned to the current staff member
$sql = "
    SELECT 
        applications.id, 
        users.name, 
        users.email, 
        applications.status, 
        applications.created_at 
    FROM applications 
    JOIN users ON applications.user_id = users.id 
    ORDER BY applications.created_at DESC
";

$result = $conn->query($sql);

?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">My Assigned Applications</h4>
        <p class="text-muted">View and manage applications assigned to you</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Applicant</th>
                        <th>Service Type</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch ($row['status']) {
                                        case 'approved':
                                            $statusClass = 'badge-approved';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'badge-rejected';
                                            break;
                                        case 'missing_docs':
                                            $statusClass = 'badge-missing';
                                            break;
                                        default:
                                            $statusClass = 'badge-pending';
                                    }
                                    ?>
                                    <span class="badge <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view_application.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_application.php?id=<?= $row['id'] ?>" 
                                           class="btn btn-sm btn-outline-warning"
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="confirmDelete(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['name'])) ?>')"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-inbox fa-3x text-muted"></i>
                                </div>
                                <h5>No applications found</h5>
                                <p class="text-muted">There are no applications to display at the moment.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the application for <span id="deleteAppName" class="fw-bold"></span>?
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
let applicationToDelete = null;
let applicationNameToDelete = '';

function confirmDelete(id, name) {
    applicationToDelete = id;
    applicationNameToDelete = name;
    document.getElementById('deleteAppName').textContent = name;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!applicationToDelete) return;
    
    fetch(`delete_application.php?id=${applicationToDelete}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close the modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            
            // Show success message and reload
            window.location.href = 'applications.php?success=' + encodeURIComponent('Application deleted successfully');
        } else {
            alert('Error deleting application: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error deleting application. Please try again.');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
