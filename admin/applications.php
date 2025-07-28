<?php
require_once '../includes/header.php';
requireRole(['admin', 'staff']);
require_once '../php/config.php';

global $logger;

// Get all applications from applications table directly
// $sql = "
//     SELECT id, name, email, status, created_at 
//     FROM applications 
//     ORDER BY created_at DESC
     
// ";

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



$sql2 = 'SELECT * FROM users';
$result2 = $conn->query($sql2);

$users = [];
if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $users[] = $row;
    }
}

// $logger->info('Applications fetched successfully====================');
// $logger->info('This is result: ' . json_encode($users));


?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">All Applications</h4>
        <p class="text-muted">View and manage submitted service requests</p>
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
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Applicant</th>
                        <th>Email</th>
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
                Are you sure you want to delete this application? This action cannot be undone.
                <p class="mt-2 mb-0 fw-bold" id="applicationName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    document.getElementById('applicationName').textContent = 'Application: ' + name;
    document.getElementById('confirmDeleteBtn').href = 'delete_application.php?id=' + id;
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>
