<?php
require_once '../php/config.php';
require_once '../includes/header.php';
global $logger, $browserLogger, $conn;

requireRole(['staff']);

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM service_form WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Service inquiry deleted successfully';
    } else {
        $_SESSION['error'] = 'Error deleting service inquiry';
    }
    $stmt->close();
    header('Location: inquiry_service.php');
    exit();
}

// Fetch all service inquiries - Order by created_at DESC
$query = "SELECT * FROM service_form ORDER BY created_at DESC";
$logger->info('Fetching all service inquiries: ' . $query);

$result = $conn->query($query);
if (!$result) {
    $logger->error('Query failed: ' . $conn->error);
    die('Query failed: ' . $conn->error);
}
$logger->info('Fetched all service inquiries successfully');
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Service Inquiries</h4>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
            <p class="text-muted">View and manage service inquiries</p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Business Name</th>
                                <th>Service</th>
                                <th>Message</th>
                                <th>Submitted At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $count++ ?></td>
                                    <td><?= htmlspecialchars($row['name'] ?? 'N/A') ?></td>
                                    <td><a href="mailto:<?= htmlspecialchars($row['email'] ?? '') ?>"><?= htmlspecialchars($row['email'] ?? 'N/A') ?></a></td>
                                    <td><?= !empty($row['mobile']) ? htmlspecialchars($row['mobile']) : 'N/A' ?></td>
                                    <td><?= !empty($row['bname']) ? htmlspecialchars($row['bname']) : 'N/A' ?></td>
                                    <td>
                                        <?php 
                                        $service = $row['service'] ?? '';
                                        echo !empty($service) ? htmlspecialchars($service) : 'N/A';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $message = $row['message'] ?? '';
                                        echo strlen($message) > 50 ? 
                                            htmlspecialchars(substr($message, 0, 50)) . '...' : 
                                            htmlspecialchars($message);
                                        ?>
                                    </td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger" 
                                                onclick="if(confirm('Are you sure you want to delete this service inquiry?')) { window.location.href='inquiry_service.php?delete=<?= $row['id'] ?>' }"
                                                title="Delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No service inquiries found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
