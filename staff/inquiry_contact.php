<?php
require_once '../includes/header.php';
requireRole(['staff']);
require_once '../php/config.php';
global $logger, $browserLogger, $conn;
$logger->info('Accessed contact enquiries page');

$pageTitle = 'Contact Enquiries';
?>

<div class="row mb-4">
    <div class="col-12">
        <h4 class="mb-0">Contact Enquiries</h4>
        <p class="text-muted">View and manage contact enquiries</p>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <div class="mb-3">
                                <i class="fas fa-inbox fa-3x"></i>
                            </div>
                            <h5>No contact enquiries found</h5>
                            <p class="mb-0">Contact form submissions will appear here</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
