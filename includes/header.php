<?php
session_name("admin_session");
session_set_cookie_params([
    'lifetime' => 0, // Session cookie lasts until browser is closed
    'path' => '/application-system/',
    'domain' => 'localhost',
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
require_once __DIR__ . '/../php/auth.php';
requireLogin();
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS (if needed) -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/application-system/assets/css/style.css">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4>Application System</h4>
        </div>
        <ul class="sidebar-menu">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="/application-system/<?php echo $_SESSION['user_role']; ?>/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>

            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff'): ?>
                <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'applications') !== false ? 'active' : ''; ?>">
                    <a href="/application-system/<?php echo $_SESSION['user_role']; ?>/applications.php">
                        <i class="fas fa-file-alt"></i> Applications
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['user_role'] === 'admin' || $_SESSION['user_role'] === 'staff'): ?>
                <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'inquiry_contact') !== false || strpos($_SERVER['PHP_SELF'], 'inquiry_service') !== false) ? 'active' : ''; ?>">
                    <a href="#enquirySubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
                        <i class="fas fa-envelope"></i> Inquiry Form
                    </a>
                    <ul class="collapse list-unstyled ms-3" id="enquirySubmenu">
                        <li>
                            <a href="/application-system/<?php echo $_SESSION['user_role']; ?>/inquiry_contact.php">
                                <i class="fas fa-phone"></i> Contact Item
                            </a>
                        </li>
                        <li>
                            <a href="/application-system/<?php echo $_SESSION['user_role']; ?>/inquiry_service.php">
                                <i class="fas fa-tools"></i> Service Item
                            </a>
                        </li>
                    </ul>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'users') !== false ? 'active' : ''; ?>">
                    <a href="/application-system/admin/users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($_SESSION['user_role'] === 'user'): ?>
                <li class="<?php echo strpos($_SERVER['PHP_SELF'], 'my_application') !== false ? 'active' : ''; ?>">
                    <a href="/application-system/user/my_application.php">
                        <i class="fas fa-file-upload"></i> My Application
                    </a>
                </li>
            <?php endif; ?>

            <?php if (in_array($_SESSION['user_role'], ['admin', 'staff'])): ?>
                <li>
                    <a href="/application-system/<?php echo ($_SESSION['user_role'] === 'admin') ? 'admin' : 'staff'; ?>/profile.php">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="/application-system/php/logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="top-navbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-link d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0">
                    <?php
                    $pageTitle = '';
                    $currentPage = basename($_SERVER['PHP_SELF']);

                    switch ($currentPage) {
                        case 'dashboard.php':
                            $pageTitle = 'Dashboard';
                            break;
                        case 'applications.php':
                            $pageTitle = 'Applications';
                            break;
                        case 'users.php':
                            $pageTitle = 'User Management';
                            break;
                        case 'settings.php':
                            $pageTitle = 'Settings';
                            break;
                        case 'my_application.php':
                            $pageTitle = 'My Application';
                            break;
                        case 'profile.php':
                            $pageTitle = 'My Profile';
                            break;
                        default:
                            $pageTitle = 'Dashboard';
                    }

                    echo $pageTitle;
                    ?>
                </h5>
            </div>

            <?php
            // Get profile picture from session or database if not set
            if (!isset($_SESSION['profile_picture'])) {
                $stmt = $conn->prepare("SELECT file_name FROM files WHERE model_type = 'admin' AND model_id = ? ORDER BY id DESC LIMIT 1");
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $profile = $result->fetch_assoc();
                    $_SESSION['profile_picture'] = $profile['file_name'];
                }
                $stmt->close();
            }
            ?>
            <div class="user-dropdown d-flex align-items-center">
                <span class="badge bg-success me-2"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="<?php echo !empty($_SESSION['profile_picture']) ? '../uploads/profiles/' . htmlspecialchars($_SESSION['profile_picture']) : '../assets/img/default-avatar.png'; ?>" alt="User Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='../assets/img/default-avatar.png';">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/application-system/<?php echo $_SESSION['user_role']; ?>/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/application-system/includes/change_password.php"><i class="fas fa-key me-2"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/application-system/php/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

       
        <div class="content-wrapper">
          