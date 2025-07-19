<?php
// session_start();
require_once __DIR__ . '/../php/auth.php';
requireLogin();
ob_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application System - <?php echo ucfirst($_SESSION['user_role']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/application-system/assets/css/style.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 56px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: #2c3136;
        }

        .sidebar-menu {
            padding: 0;
            list-style: none;
        }

        .sidebar-menu li {
            position: relative;
        }

        .sidebar-menu li a {
            color: #c2c7d0;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
        }

        .sidebar-menu li a:hover,
        .sidebar-menu li.active a {
            color: #fff;
            background: #495057;
        }

        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }

        /* Top Navbar */
        .top-navbar {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 0 20px;
            height: var(--header-height);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .top-navbar .navbar-brand {
            font-weight: 600;
            color: #333;
        }

        .user-dropdown .dropdown-toggle {
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
        }

        .user-dropdown img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            margin-right: 8px;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }

        /* Stats Cards */
        .stat-card {
            border-left: 4px solid #0d6efd;
        }

        .stat-card .card-body {
            padding: 15px;
        }

        .stat-card .stat-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 10px;
        }

        .stat-card .stat-count {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Tables */
        .table {
            background: #fff;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #6c757d;
        }

        /* Status Badges */
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .badge-missing {
            background-color: #e2e3e5;
            color: #383d41;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: calc(-1 * var(--sidebar-width));
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar.active {
                left: 0;
            }
        }
    </style>
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

            <li>
                <a href="/application-system/admin/profile.php">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </li>

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

            <div class="user-dropdown">
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>" alt="User Avatar">
                        <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                        <i class="fas fa-chevron-down ms-2"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/application-system/user/profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="/application-system/user/change_password.php"><i class="fas fa-key me-2"></i> Change Password</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="/application-system/php/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="container-fluid py-4">



            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>