<?php
require_once '../includes/header.php';
requireRole(['admin']);

// Get all users from admin table
$stmt = $conn->prepare("SELECT * FROM admin WHERE role != 'admin'");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all users from users table
$stmt = $conn->prepare("SELECT * FROM users");
$stmt->execute();
$regularUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Combine users from both tables
$allUsers = array_merge($users, $regularUsers);

// Handle search
$searchTerm = isset($_GET['search']) ? strtolower($_GET['search']) : '';
if ($searchTerm) {
    $filteredUsers = array_filter($allUsers, function($user) use ($searchTerm) {
        return strpos(strtolower($user['name']), $searchTerm) !== false || 
               strpos(strtolower($user['email']), $searchTerm) !== false;
    });
} else {
    $filteredUsers = $allUsers;
}

// Handle role filter
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
if ($roleFilter && $roleFilter !== 'all') {
    $filteredUsers = array_filter($filteredUsers, function($user) use ($roleFilter) {
        return $user['role'] === $roleFilter;
    });
}


?>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">User Management</h4>
                <p class="text-muted">Manage system users and their permissions</p>
            </div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-1"></i> Add User
            </button>
        </div>
    </div>
</div>

<div class="container-fluid">
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            User created successfully
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form class="row g-3" method="GET">
            <div class="col-6 col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" placeholder="Search users..." name="search" 
                           value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
            </div>
            <div class="col-6 col-md-4">
                <select class="form-select" name="role">
                    <option value="all" <?= !$roleFilter || $roleFilter === 'all' ? 'selected' : '' ?>>All Roles</option>
                    <option value="staff" <?= $roleFilter === 'staff' ? 'selected' : '' ?>>Staff</option>
                    <option value="user" <?= $roleFilter === 'user' ? 'selected' : '' ?>>User</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <button type="submit" class="btn btn-outline-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($filteredUsers) > 0): ?>
                        <?php foreach ($filteredUsers as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar me-3">
                                            <?php 
                                            $nameParts = explode(' ', $user['name']);
                                            $initials = '';
                                            foreach ($nameParts as $part) {
                                                $initials .= strtoupper(substr($part, 0, 1));
                                            }
                                            echo $initials;
                                            ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                            <div class="text-muted small">ID: <?= $user['id'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge 
                                        <?= isset($user['role']) && $user['role'] === 'staff' ? 'bg-info' : 'bg-secondary' ?>">
                                        <?= isset($user['role']) ? ucfirst($user['role']) : 'User' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                data-user-id="<?= $user['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteUserModal"
                                                data-user-id="<?= $user['id'] ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="mb-3">
                                    <i class="fas fa-user-slash fa-3x text-muted"></i>
                                </div>
                                <h5>No users found</h5>
                                <p class="text-muted">Try adjusting your search or filter criteria</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="text-muted">
                Showing <?= count($filteredUsers) ?> of <?= count($allUsers) ?> users
            </div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="../php/process_user.php">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                    </div> 
                    <div class="mb-3">
                        <label class="form-label">Mobile Number</label>
                        <input type="text" class="form-control" name="mobile" placeholder="Enter mobile number" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="">Select role</option>
                            <option value="staff">Staff</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Create password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="../php/process_user.php">
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editUserName" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="editUserEmail" placeholder="Enter email address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" id="editUserRole" required>
                            <option value="staff">Staff</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    All associated data with this user will be permanently removed.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger">Delete User</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
    // Configure Toastr after it's loaded
    $(document).ready(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 3000,
            extendedTimeOut: 1000,
            showMethod: 'slideDown',
            hideMethod: 'slideUp'
        };
    });
</script>

<script>
    // Toast notification function using Toastr
    function showToast(message, type = 'success') {
        if (type === 'success') {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }

    // Add User Form Submission
    document.querySelector('#addUserModal form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        try {
            const response = await fetch('../php/process_user.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            
            const result = await response.json();
            
            if (result.success) {
                showToast(`User created successfully`);
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                modal.hide();
                // Reload page after a short delay to ensure toast is visible
                setTimeout(() => {
                    location.reload();
                }, 2000); // 2 seconds delay
            } else {
                showToast(result.message || 'An error occurred', 'error');
            }
        } catch (error) {
            showToast('Error creating user. Please try again.', 'error');
        }
    });

    // Edit User Modal Handler
    document.getElementById('editUserModal').addEventListener('show.bs.modal', async function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        
        try {
            // Fetch user data from server
            const response = await fetch(`../php/get_user.php?user_id=${encodeURIComponent(userId)}`);
            const userData = await response.json();
            
            if (userData.success) {
                const user = userData.user;
                document.getElementById('editUserId').value = user.id;
                document.getElementById('editUserName').value = user.name;
                document.getElementById('editUserEmail').value = user.email;
                document.getElementById('editUserRole').value = user.role || '';
                document.getElementById('editUserStatus').value = user.status;
            } else {
                showToast('Error loading user data', 'error');
            }
        } catch (error) {
            showToast('Error loading user data. Please try again.', 'error');
        }
    });

    // Edit User Form Submission
    document.querySelector('#editUserModal form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const userId = formData.get('user_id');
        
        try {
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            
            const response = await fetch('../php/process_edit_user.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                // Navigate to users page after successful update
                window.location.href = 'users.php';
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Error updating user. Please try again.', 'error');
        } finally {
            // Restore button state
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        }
    });
    
    // Delete User Modal Handler
    document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        document.querySelector('#deleteUserModal .btn-danger').dataset.userId = userId;
    });
    
    // Handle delete confirmation
    document.querySelector('#deleteUserModal .btn-danger').addEventListener('click', async function() {
        const userId = this.dataset.userId;
        
        try {
            // Show loading state
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteUserModal'));
            modal.hide();
            
            // Disable the delete button temporarily
            const deleteButton = this;
            deleteButton.disabled = true;
            deleteButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
            
            const response = await fetch('../php/process_delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'user_id=' + encodeURIComponent(userId)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast(result.message, 'success');
                // Reload page immediately after successful deletion
                window.location.href = 'users.php';
            } else {
                showToast(result.message, 'error');
                // Re-enable button and restore text if there's an error
                deleteButton.disabled = false;
                deleteButton.innerHTML = 'Delete';
            }
        } catch (error) {
            showToast('Error deleting user. Please try again.', 'error');
            // Re-enable button and restore text on error
            deleteButton.disabled = false;
            deleteButton.innerHTML = 'Delete';
        }
    });
</script>

<style>
    .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #6c757d;
    }
    
    .table th {
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(13, 110, 253, 0.05);
    }
    
    .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .toast {
        background-color: #fff;
        border: 1px solid #dee2e6;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
    }
    
    .toast-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
</style>

<?php require_once '../includes/footer.php'; ?>