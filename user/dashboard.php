<?php
require_once '../includes/header.php';
requireRole(['user']);

$user_id = $_SESSION['user_id'];
$user_applications = [];
$latest_application = null;
$status_counts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'missing_docs' => 0,
    'total' => 0
];

// Get user's applications
$result = $conn->query("
    SELECT * 
    FROM applications 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $user_applications[] = $row;
        $status = $row['status'];
        
        if (isset($status_counts[$status])) {
            $status_counts[$status]++;
        } else {
            $status_counts[$status] = 1;
        }
        
        $status_counts['total']++;
        
        if ($latest_application === null) {
            $latest_application = $row;
        }
    }
}

// Get unread notifications count
$unread_notifications = 0;
$result = $conn->query("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = $user_id AND is_read = 0
");

if ($result) {
    $row = $result->fetch_assoc();
    $unread_notifications = $row['count'];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">My Dashboard</h4>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-primary">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-count"><?php echo $status_counts['total']; ?></div>
                <div class="stat-title">Total Applications</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-count"><?php echo $status_counts['pending']; ?></div>
                <div class="stat-title">Pending</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-count"><?php echo $status_counts['approved']; ?></div>
                <div class="stat-title">Approved</div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="card stat-card h-100">
            <div class="card-body text-center">
                <div class="stat-icon text-danger">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-count"><?php echo $status_counts['rejected']; ?></div>
                <div class="stat-title">Rejected</div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Latest Application Status -->
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <?php echo $latest_application ? 'Latest Application' : 'No Applications Yet'; ?>
                </h5>
                <?php if ($latest_application): ?>
                    <a href="my_application.php?id=<?php echo $latest_application['id']; ?>" class="btn btn-sm btn-outline-primary">
                        View Details
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php if ($latest_application): ?>
                    <div class="application-status">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="mb-1">Application #<?php echo htmlspecialchars($latest_application['application_number']); ?></h6>
                                <p class="text-muted mb-0">Submitted on <?php echo date('F j, Y', strtotime($latest_application['created_at'])); ?></p>
                            </div>
                            <div>
                                <?php 
                                $statusClass = '';
                                $statusText = ucfirst(str_replace('_', ' ', $latest_application['status']));
                                
                                switch ($latest_application['status']) {
                                    case 'approved':
                                        $statusClass = 'badge-approved';
                                        $statusIcon = 'check-circle';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'badge-rejected';
                                        $statusIcon = 'times-circle';
                                        break;
                                    case 'missing_docs':
                                        $statusClass = 'badge-missing';
                                        $statusIcon = 'exclamation-circle';
                                        $statusText = 'Missing Documents';
                                        break;
                                    default:
                                        $statusClass = 'badge-pending';
                                        $statusIcon = 'clock';
                                }
                                ?>
                                <span class="badge <?php echo $statusClass; ?> p-2">
                                    <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                    <?php echo $statusText; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="progress mb-4" style="height: 8px;">
                            <?php
                            $progress = 0;
                            $steps = [
                                'submitted' => 25,
                                'under_review' => 50,
                                'in_progress' => 75,
                                'approved' => 100,
                                'rejected' => 100,
                                'missing_docs' => 75
                            ];
                            
                            $currentStep = $latest_application['status'];
                            $progress = $steps[$currentStep] ?? 0;
                            
                            $progressClass = 'bg-primary';
                            if ($latest_application['status'] === 'approved') {
                                $progressClass = 'bg-success';
                            } elseif ($latest_application['status'] === 'rejected') {
                                $progressClass = 'bg-danger';
                            } elseif ($latest_application['status'] === 'missing_docs') {
                                $progressClass = 'bg-warning';
                            }
                            ?>
                            <div class="progress-bar <?php echo $progressClass; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $progress; ?>%" 
                                 aria-valuenow="<?php echo $progress; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        
                        <div class="timeline-steps">
                            <div class="timeline-step <?php echo $progress >= 25 ? 'active' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="timeline-label">Submitted</div>
                                <div class="timeline-date">
                                    <?php echo date('M j', strtotime($latest_application['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="timeline-step <?php echo $progress >= 50 ? 'active' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="timeline-label">Under Review</div>
                                <div class="timeline-date">
                                    <?php 
                                    if ($progress >= 50) {
                                        $reviewDate = date('M j', strtotime($latest_application['created_at'] . ' +1 day'));
                                        echo $reviewDate;
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="timeline-step <?php echo $progress >= 75 ? 'active' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-tasks"></i>
                                </div>
                                <div class="timeline-label">In Progress</div>
                                <div class="timeline-date">
                                    <?php 
                                    if ($progress >= 75) {
                                        $inProgressDate = date('M j', strtotime($latest_application['created_at'] . ' +3 days'));
                                        echo $inProgressDate;
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <div class="timeline-step <?php echo $progress >= 100 ? 'active' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fas fa-check-double"></i>
                                </div>
                                <div class="timeline-label">
                                    <?php echo $latest_application['status'] === 'rejected' ? 'Rejected' : 'Completed'; ?>
                                </div>
                                <div class="timeline-date">
                                    <?php 
                                    if ($progress >= 100) {
                                        $completedDate = date('M j', strtotime($latest_application['updated_at']));
                                        echo $completedDate;
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($latest_application['notes']): ?>
                            <div class="mt-4">
                                <h6>Latest Update:</h6>
                                <div class="alert alert-light">
                                    <?php echo nl2br(htmlspecialchars($latest_application['notes'])); ?>
                                    <div class="text-muted small mt-2">
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($latest_application['updated_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <h5>No Applications Yet</h5>
                            <p class="text-muted">You haven't submitted any applications yet.</p>
                        </div>
                        <a href="new_application.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Start New Application
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Notifications -->
    <div class="col-lg-4 mb-4">
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="new_application.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> New Application
                    </a>
                    <a href="my_applications.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i> My Applications
                    </a>
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-user-edit me-2"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Notifications</h5>
                <?php if ($unread_notifications > 0): ?>
                    <span class="badge bg-danger"><?php echo $unread_notifications; ?> New</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php
                // Get user's notifications
                $notifications = [];
                $result = $conn->query("
                    SELECT * 
                    FROM notifications 
                    WHERE user_id = $user_id 
                    ORDER BY created_at DESC 
                    LIMIT 5
                ");
                
                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()) {
                        $notifications[] = $row;
                    }
                else:
                    // Sample notifications if none found
                    $notifications = [
                        [
                            'id' => 1,
                            'title' => 'Welcome to the Application System',
                            'message' => 'Thank you for registering with our application system. You can now start submitting applications.',
                            'is_read' => 1,
                            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
                            'icon' => 'info-circle',
                            'color' => 'primary'
                        ],
                        [
                            'id' => 2,
                            'title' => 'Application Submitted',
                            'message' => 'Your application #APP20230001 has been received and is pending review.',
                            'is_read' => 1,
                            'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
                            'icon' => 'check-circle',
                            'color' => 'success'
                        ]
                    ];
                endif;
                ?>
                
                <?php if (!empty($notifications)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notification): ?>
                            <a href="#" class="list-group-item list-group-item-action border-0 <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>" 
                               data-notification-id="<?php echo $notification['id']; ?>">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar avatar-sm bg-soft-<?php echo $notification['color'] ?? 'primary'; ?> text-<?php echo $notification['color'] ?? 'primary'; ?>">
                                            <i class="fas fa-<?php echo $notification['icon'] ?? 'bell'; ?>"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                            <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-danger ms-1">New</span>
                                            <?php endif; ?>
                                        </h6>
                                        <p class="mb-0 text-muted small">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php 
                                            $created = new DateTime($notification['created_at']);
                                            $now = new DateTime();
                                            $interval = $created->diff($now);
                                            
                                            if ($interval->y > 0) {
                                                echo $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->m > 0) {
                                                echo $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->d > 0) {
                                                echo $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->h > 0) {
                                                echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                                            } elseif ($interval->i > 0) {
                                                echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                                            } else {
                                                echo 'Just now';
                                            }
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                            View All Notifications
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-bell-slash fa-3x text-muted"></i>
                        </div>
                        <h5>No Notifications</h5>
                        <p class="text-muted">You don't have any notifications yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Help Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Need Help?</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-question-circle fa-2x"></i>
                            </div>
                            <div>
                                <h6>FAQs</h6>
                                <p class="text-muted small mb-0">Find answers to common questions about the application process.</p>
                                <a href="faq.php" class="btn btn-link p-0 mt-2">View FAQs <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="d-flex">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-phone-alt fa-2x"></i>
                            </div>
                            <div>
                                <h6>Contact Support</h6>
                                <p class="text-muted small mb-0">Need help? Our support team is here to assist you.</p>
                                <a href="contact.php" class="btn btn-link p-0 mt-2">Contact Us <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0 text-primary me-3">
                                <i class="fas fa-file-alt fa-2x"></i>
                            </div>
                            <div>
                                <h6>Documentation</h6>
                                <p class="text-muted small mb-0">View our guides and documentation for more information.</p>
                                <a href="docs.php" class="btn btn-link p-0 mt-2">View Docs <i class="fas fa-arrow-right ms-1"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Timeline Steps */
    .timeline-steps {
        display: flex;
        justify-content: space-between;
        margin: 2rem 0;
        position: relative;
    }
    
    .timeline-steps::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 3px;
        background-color: #e9ecef;
        z-index: 1;
    }
    
    .timeline-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }
    
    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        left: 50%;
        right: -50%;
        height: 3px;
        background-color: #e9ecef;
        z-index: -1;
    }
    
    .timeline-step.active:not(:last-child)::after {
        background-color: #0d6efd;
    }
    
    .timeline-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.5rem;
        color: #6c757d;
    }
    
    .timeline-step.active .timeline-icon {
        background-color: #0d6efd;
        color: #fff;
    }
    
    .timeline-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: center;
        margin-bottom: 0.25rem;
    }
    
    .timeline-step.active .timeline-label {
        font-weight: 600;
        color: #0d6efd;
    }
    
    .timeline-date {
        font-size: 0.65rem;
        color: #adb5bd;
        text-align: center;
    }
    
    /* Application Status */
    .application-status {
        position: relative;
    }
    
    /* Avatar */
    .avatar {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background-color: #e9ecef;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
    }
    
    .avatar-sm {
        width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
    }
    
    /* Badges */
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .badge-approved {
        background-color: #d4edda;
        color: #0f5132;
    }
    
    .badge-pending {
        background-color: #fff3cd;
        color: #664d03;
    }
    
    .badge-rejected {
        background-color: #f8d7da;
        color: #842029;
    }
    
    .badge-missing {
        background-color: #e2e3e5;
        color: #383d41;
    }
    
    /* Notification Icons */
    .avatar-sm {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .bg-soft-primary {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-soft-success {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-soft-danger {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-soft-warning {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-soft-info {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    .text-primary {
        color: #0d6efd !important;
    }
    
    .text-success {
        color: #198754 !important;
    }
    
    .text-danger {
        color: #dc3545 !important;
    }
    
    .text-warning {
        color: #ffc107 !important;
    }
    
    .text-info {
        color: #0dcaf0 !important;
    }
</style>

<script>
// Mark notification as read when clicked
document.addEventListener('DOMContentLoaded', function() {
    const notificationLinks = document.querySelectorAll('[data-notification-id]');
    
    notificationLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.getAttribute('data-notification-id');
            
            // Mark as read via AJAX
            fetch('/application-system/php/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'notification_id=' + notificationId
            });
            
            // Update UI
            this.classList.remove('bg-light');
            const badge = this.querySelector('.badge');
            if (badge) {
                badge.remove();
            }
            
            // Navigate to the notification URL
            window.location.href = 'notification_details.php?id=' + notificationId;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
