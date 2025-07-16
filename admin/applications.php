<?php
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

// Get all applications from applications table directly
$sql = "
    SELECT id, name, email, status, created_at 
    FROM applications 
    ORDER BY created_at DESC
";
$result = $conn->query($sql);
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">All Applications</h4>
        <p class="text-muted">View and manage submitted service requests</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
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
                                    <a href="view_application.php?id=<?= $row['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
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

<?php require_once '../includes/footer.php'; ?>
