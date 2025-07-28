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
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
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
            <form method="POST" action="../php/process_edit_user.php">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="editUserId">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="name" id="editUserName" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" name="email" id="editUserEmail" placeholder="Enter email address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" id="editUserRole" required>
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
                <form method="POST" action="../php/process_delete_user.php" style="display: inline;">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Edit User Modal Handler
    $('#editUserModal').on('show.bs.modal', async function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        
        try {
            const response = await fetch(`../php/get_user.php?user_id=${encodeURIComponent(userId)}`);
            const userData = await response.json();
            
            if (userData.success) {
                const user = userData.user;
                document.getElementById('editUserId').value = user.id;
                document.getElementById('editUserName').value = user.name;
                document.getElementById('editUserEmail').value = user.email;
                document.getElementById('editUserRole').value = user.role || '';
            } else {
                alert('Error loading user data');
            }
        } catch (error) {
            alert('Error loading user data. Please try again.');
        }
    });
    
    // Delete User Modal Handler
    $('#deleteUserModal').on('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-user-id');
        document.getElementById('deleteUserId').value = userId;
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
</style>

<?php require_once '../includes/footer.php'; ?>