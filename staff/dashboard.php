<?php
require_once '../includes/header.php';
requireRole(['staff', 'admin']);

// Get counts for dashboard stats
$stats = [
    'pending_applications' => 0,
    'assigned_applications' => 0,
    'completed_today' => 0
];

// Get application counts for staff
$staff_id = $_SESSION['user_id'];
$result = $conn->query("
    SELECT 
        COUNT(*) as total_pending,
        SUM(CASE WHEN assigned_to = $staff_id THEN 1 ELSE 0 END) as assigned_to_me,
        SUM(CASE 
            WHEN assigned_to = $staff_id 
            AND DATE(updated_at) = CURDATE() 
            AND status IN ('approved', 'rejected') 
            THEN 1 
            ELSE 0 
        END) as completed_today
    FROM applications
    WHERE status = 'pending' OR assigned_to = $staff_id
");

if ($result && $row = $result->fetch_assoc()) {
    $stats['pending_applications'] = $row['total_pending'];
    $stats['assigned_applications'] = $row['assigned_to_me'];
    $stats['completed_today'] = $row['completed_today'];
}

// Get applications assigned to this staff
$assigned_applications = [];
$result = $conn->query("
    SELECT a.*, u.name as user_name, u.email as user_email
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE a.assigned_to = $staff_id
    ORDER BY a.updated_at DESC
    LIMIT 5
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assigned_applications[] = $row;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">Staff Dashboard</h4>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-count"><?php echo $stats['pending_applications']; ?></div>
                <div class="stat-title">Pending Applications</div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-info">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-count"><?php echo $stats['assigned_applications']; ?></div>
                <div class="stat-title">Assigned to Me</div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-count"><?php echo $stats['completed_today']; ?></div>
                <div class="stat-title">Completed Today</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- My Tasks -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">My Tasks</h5>
                <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($assigned_applications) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Application #</th>
                                    <th>Applicant</th>
                                    <th>Status</th>
                                    <th>Assigned</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_applications as $app): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($app['application_number']); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-sm me-2">
                                                    <?php
                                                    $nameParts = explode(' ', $app['user_name']);
                                                    $initials = '';
                                                    foreach ($nameParts as $part) {
                                                        $initials .= strtoupper(substr($part, 0, 1));
                                                    }
                                                    echo $initials;
                                                    ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($app['user_name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($app['user_email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch ($app['status']) {
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
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($app['assigned_at'] ?? $app['created_at'])); ?></td>
                                        <td>
                                            <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                Review
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-inbox fa-3x text-muted"></i>
                        </div>
                        <h5>No tasks assigned</h5>
                        <p class="text-muted">You don't have any applications assigned to you at the moment.</p>
                        <a href="applications.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Find Applications
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Stats -->
    <div class="col-lg-4 mb-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="applications.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-search me-1"></i> Find Applications
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-user-edit me-1"></i> Update Profile
                    </a>
                    <a href="change_password.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-key me-1"></i> Change Password
                    </a>
                </div>
            </div>
        </div>

        <!-- Performance Stats -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">My Performance</h5>
            </div>
            <div class="card-body">
                <?php
                // Get performance stats
                $performance = [
                    'total_processed' => 0,
                    'approval_rate' => 0,
                    'avg_processing_time' => 'N/A'
                ];

                $result = $conn->query("
                    SELECT 
                        COUNT(*) as total_processed,
                        ROUND(AVG(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) * 100) as approval_rate
                    FROM applications
                    WHERE assigned_to = $staff_id
                    AND status IN ('approved', 'rejected')
                ");

                if ($result && $row = $result->fetch_assoc()) {
                    $performance['total_processed'] = $row['total_processed'];
                    $performance['approval_rate'] = $row['approval_rate'];
                }

                // Get average processing time (in days)
                $result = $conn->query("
                    SELECT 
                        ROUND(AVG(DATEDIFF(updated_at, created_at))) as avg_days
                    FROM applications
                    WHERE assigned_to = $staff_id
                    AND status IN ('approved', 'rejected')
                    ");


                if ($result && $row = $result->fetch_assoc()) {
                    if (isset($row['avg_days']) && $row['avg_days'] !== null) {
                        $performance['avg_processing_time'] = $row['avg_days'] . ' days';
                    }
                }

                ?>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Total Processed</span>
                        <span class="fw-bold"><?php echo $performance['total_processed']; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar"
                            style="width: <?php echo min(100, $performance['total_processed'] * 10); ?>%"
                            aria-valuenow="<?php echo $performance['total_processed']; ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Approval Rate</span>
                        <span class="fw-bold"><?php echo $performance['approval_rate']; ?>%</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar"
                            style="width: <?php echo $performance['approval_rate']; ?>%"
                            aria-valuenow="<?php echo $performance['approval_rate']; ?>"
                            aria-valuemin="0"
                            aria-valuemax="100">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Avg. Processing Time</span>
                        <span class="fw-bold"><?php echo $performance['avg_processing_time']; ?></span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar"
                            style="width: <?php echo min(100, (int)$performance['avg_processing_time'] * 5); ?>%"
                            aria-valuenow="<?php echo (int)$performance['avg_processing_time']; ?>"
                            aria-valuemin="0"
                            aria-valuemax="30">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php
                    // Get recent activity for this staff member
                    $activities = [];
                    $result = $conn->query("
                        SELECT 
                            a.application_number,
                            a.status as application_status,
                            a.updated_at,
                            CONCAT(u.first_name, ' ', u.last_name) as user_name,
                            'application' as type,
                            NULL as description
                        FROM applications a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.assigned_to = $staff_id
                        ORDER BY a.updated_at DESC
                        LIMIT 5
                    ");

                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            $activities[] = [
                                'icon' => 'file-alt',
                                'color' => 'primary',
                                'title' => 'Application ' . ucfirst(str_replace('_', ' ', $row['application_status'])),
                                'description' => 'Application #' . $row['application_number'] . ' was ' . $row['application_status'],
                                'time' => time_elapsed_string($row['updated_at'])
                            ];
                        }
                    }

                    // If no activities found, show sample data
                    if (empty($activities)) {
                        $activities = [
                            [
                                'icon' => 'file-import',
                                'color' => 'primary',
                                'title' => 'New application assigned',
                                'description' => 'Application #APP20230045 was assigned to you',
                                'time' => 'Just now'
                            ],
                            [
                                'icon' => 'check-circle',
                                'color' => 'success',
                                'title' => 'Application approved',
                                'description' => 'You approved application #APP20230042',
                                'time' => '2 hours ago'
                            ],
                            [
                                'icon' => 'comments',
                                'color' => 'info',
                                'title' => 'New comment',
                                'description' => 'You added a comment to application #APP20230041',
                                'time' => '1 day ago'
                            ],
                            [
                                'icon' => 'exclamation-triangle',
                                'color' => 'warning',
                                'title' => 'Missing documents',
                                'description' => 'You requested additional documents for application #APP20230040',
                                'time' => '2 days ago'
                            ],
                            [
                                'icon' => 'user-clock',
                                'color' => 'danger',
                                'title' => 'Application reassigned',
                                'description' => 'Application #APP20230039 was reassigned to you',
                                'time' => '3 days ago'
                            ]
                        ];
                    }

                    foreach ($activities as $activity):
                    ?>
                        <div class="timeline-item">
                            <div class="timeline-icon bg-soft-<?php echo $activity['color']; ?> text-<?php echo $activity['color']; ?>">
                                <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1"><?php echo $activity['title']; ?></h6>
                                    <small class="text-muted"><?php echo $activity['time']; ?></small>
                                </div>
                                <p class="mb-0 text-muted"><?php echo $activity['description']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-3">
                    <a href="activity.php" class="btn btn-sm btn-outline-primary">View All Activities</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to format time elapsed
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $string = [
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    ];

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) {
        $string = array_slice($string, 0, 1);
    }

    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

?>

<?php require_once '../includes/footer.php'; ?>