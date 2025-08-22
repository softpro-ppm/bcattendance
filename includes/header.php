<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
requireLogin();

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>BC Attendance Admin</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Additional CSS -->
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="main-sidebar">
            <!-- Brand Logo -->
            <a href="../admin/dashboard.php" class="brand-link">
                <div class="brand-image">
                    <i class="fas fa-users"></i>
                </div>
                <span class="brand-text">BC Attendance</span>
            </a>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="../admin/dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/beneficiaries.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'beneficiaries.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/attendance.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <span>Daily Attendance</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="/v2bc_attendance/admin/attendance_bulk_upload.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance_bulk_upload.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-history"></i>
                            <span>Historical Data Import</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <span>Attendance Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/bulk_upload.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'bulk_upload.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-users-cog"></i>
                            <span>Student Data Import</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../admin/tc_user_tracking.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tc_user_tracking.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-graduation-cap"></i>
                            <span>TC User Tracking</span>
                        </a>
                    </li>
                    <li class="nav-item has-treeview <?php echo in_array(basename($_SERVER['PHP_SELF']), ['settings.php', 'constituencies.php', 'mandals.php', 'batches.php', 'manage_holidays.php']) ? 'menu-open' : ''; ?>">
                        <a href="#" class="nav-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['settings.php', 'constituencies.php', 'mandals.php', 'batches.php', 'manage_holidays.php']) ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-cogs"></i>
                            <span>System Configuration</span>
                            <i class="right fas fa-angle-left"></i>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="../admin/constituencies.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'constituencies.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <span>Constituencies</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../admin/mandals.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'mandals.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <span>Training Centers</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../admin/batches.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'batches.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <span>Training Batches</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../admin/settings.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'settings.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <span>System Settings</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="../admin/manage_holidays.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_holidays.php') ? 'active' : ''; ?>">
                                    <i class="far fa-circle nav-icon"></i>
                                    <span>Manage Holidays</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Main Header -->
            <nav class="main-header">
                <div class="navbar">
                    <div class="navbar-brand">
                        <button class="sidebar-toggle d-lg-none" type="button" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?>
                    </div>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <button class="dropdown-toggle" type="button">
                                <i class="fas fa-user-circle"></i>
                                <?php echo htmlspecialchars($currentUser['full_name']); ?>
                                <i class="fas fa-caret-down ml-1"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="../admin/profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a class="dropdown-item" href="../admin/change-password.php">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Content -->
            <div class="content">
                <?php if (isset($pageTitle)): ?>
                <div class="content-header">

                    <?php if (isset($breadcrumbs)): ?>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <?php if (isset($crumb['url'])): ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?php echo $crumb['url']; ?>"><?php echo htmlspecialchars($crumb['title']); ?></a>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($crumb['title']); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Flash Messages -->
                <?php echo displayFlashMessages(); ?>
