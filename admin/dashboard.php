<?php
require_once '../php/config.php';
global $logger, $browserLogger;

require_once '../includes/header.php';
requireRole(['admin']);

// Log dashboard access
$logger->info('Admin dashboard accessed');
$browserLogger->log('Admin dashboard accessed');

// Get counts for dashboard stats
$stats = [
    'total_applications' => 0,
    'pending_applications' => 0,
    'approved_applications' => 0,
    'missing_document_applications' => 0,
    'rejected_applications' => 0,
    'total_users' => 0,
    'total_staff' => 0
];

// Fetch application statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'missing_document' THEN 1 ELSE 0 END) as missing_docs,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications
");

if ($stmt === false) {
    $logger->error('Failed to prepare statistics query: ' . $conn->error);
    $browserLogger->log('Failed to prepare statistics query: ' . $conn->error);
    die('Database error: ' . $conn->error);
}

if (!$stmt->execute()) {
    $logger->error('Failed to execute statistics query: ' . $stmt->error);
    $browserLogger->log('Failed to execute statistics query: ' . $stmt->error);
    die('Database error: ' . $stmt->error);
}

$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $stats['total_applications'] = $row['total'];
    $stats['pending_applications'] = $row['pending'];
    $stats['approved_applications'] = $row['approved'];
    $stats['missing_document_applications'] = $row['missing_docs'];
    $stats['rejected_applications'] = $row['rejected'];
}

// get user data in form
$stmt = $conn->prepare("SELECT * FROM users WHERE role = 'user'");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
}

// Get user and staff counts
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_users'] = $row['total_users'];
    }
}

$stmt = $conn->prepare("SELECT COUNT(*) as total_staff FROM admin WHERE role = 'staff'");
if ($stmt && $stmt->execute()) {
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_staff'] = $row['total_staff'];
    }
}

// Get recent applications with their files
$sql = '
    SELECT 
        a.*,
        u.name , u.email, u.mobile 
    FROM applications a
    LEFT JOIN users u ON a.user_id = u.id
    LEFT JOIN admin ad ON a.user_id = ad.id
    ORDER BY a.created_at DESC
    LIMIT 10
';

$result = $conn->query($sql);
if ($result === false) {
    $error = $conn->error;
    $logger->error('Failed to fetch recent applications: ' . $error);
    $browserLogger->log('Failed to fetch recent applications: ' . $error);
    $recentApplications = [];
} else {
    $recentApplications = $result->fetch_all(MYSQLI_ASSOC);
}

// Notifications feature is currently disabled
$notifications = [];

?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
        <strong>Success!</strong> Application submitted successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <strong>Error!</strong> There was an error processing your application. Please try again.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">Dashboard Overview</h4>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-6 col-lg-2 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-count"><?php echo $stats['total_applications']; ?></div>
                <div class="stat-title">Total Applications</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-2 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-count"><?php echo $stats['pending_applications']; ?></div>
                <div class="stat-title">Pending</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-2 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-count"><?php echo $stats['approved_applications']; ?></div>
                <div class="stat-title">Complete</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-2 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-info">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-count"><?php echo $stats['missing_document_applications']; ?></div>
                <div class="stat-title">Missing Document</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-2 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-count"><?php echo $stats['rejected_applications']; ?></div>
                <div class="stat-title">Rejected</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Applications -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Applications</h5>
                <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Sr. No.</th>
                                <th>Application No.</th>
                                <th>Submitted By</th>
                                <th>Service Type</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentApplications)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No applications found</td>
                            </tr>
                            <?php else: ?>
                            <?php $srNo = 1; ?>
                            <?php foreach ($recentApplications as $app): ?>
                            <tr>
                                <td><?php echo $srNo++; ?></td>
                                <td><?php echo htmlspecialchars($app['application_number'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($app['name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($app['service_type'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $statusClass = 'bg-secondary';
                                    switch ($app['status']) {
                                        case 'pending':
                                            $statusClass = 'bg-warning';
                                            break;
                                        case 'approved':
                                            $statusClass = 'bg-success';
                                            break;
                                        case 'missing_document':
                                            $statusClass = 'bg-info';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php
                                        $status = $app['status'] ?? 'pending';
                                        if ($status === 'missing_document') {
                                            echo 'Missing Document';
                                        } else {
                                            echo ucfirst(str_replace('_', ' ', $status));
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo !empty($app['created_at']) ? date('M d, Y', strtotime($app['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Users</span>
                        <span class="fw-bold"><?php echo $stats['total_users']; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, $stats['total_users'] * 10); ?>%"
                            aria-valuenow="<?php echo $stats['total_users']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Staff Members</span>
                        <span class="fw-bold"><?php echo $stats['total_staff']; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, $stats['total_staff'] * 20); ?>%"
                            aria-valuenow="<?php echo $stats['total_staff']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top">
                    <h6 class="mb-3">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newApplicationModal">
                            <i class="fas fa-plus me-1"></i> New Application
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-user-plus me-1"></i> Add User
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- New Application Modal -->
<div class="modal fade" id="newApplicationModal" tabindex="-1" aria-labelledby="newApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="../php/process_application.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">New Application</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <div class="mb-3">
                        <label class="form-label">Select User</label>
                        <input type="text" id="userSearch" class="form-control mb-2" placeholder="Search users..." onkeyup="filterUsers()">
                        <select name="user_id" id="userSelect" class="form-select" onchange="updateUserDetails(this.value)">
                            <option value="" disabled selected>-- Select a user --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                        data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($user['mobile']); ?>">
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <script>
                        function filterUsers() {
                            const input = document.getElementById('userSearch');
                            const filter = input.value.toLowerCase();
                            const select = document.getElementById('userSelect');
                            const options = select.getElementsByTagName('option');
                            
                            for (let i = 0; i < options.length; i++) {
                                const text = options[i].text.toLowerCase();
                                if (text.indexOf(filter) > -1) {
                                    options[i].style.display = '';
                                } else {
                                    options[i].style.display = 'none';
                                }
                            }
                        }
                        
                        function updateUserDetails(userId) {
                            const option = document.querySelector(`#userSelect option[value="${userId}"]`);
                            if (option) {
                                document.querySelector('input[name="name"]').value = option.dataset.name || '';
                                document.querySelector('input[name="email"]').value = option.dataset.email || '';
                                document.querySelector('input[name="phone"]').value = option.dataset.phone || '';
                            }
                        }
                        </script>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"> Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" readonly required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" readonly required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $user['mobile']; ?>" readonly required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Application Type</label>
                        <select name="application_type" class="form-select" required>
                            <option disabled selected>-- Select Application Type --</option>
                            <option value="GST Registration">GST Registration</option>
                            <option value="Digital Signature">Digital Signature</option>
                            <option value="MSME Registration">MSME Registration</option>
                            <option value="Income Tax Filing">Income Tax Filing</option>
                            <option value="Trademark Registration">Trademark Registration</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload Documents (PDF, JPG, PNG)</label>
                        <input type="file" name="document[]" class="form-control" multiple required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
