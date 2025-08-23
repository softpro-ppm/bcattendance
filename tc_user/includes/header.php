<?php
require_once '../includes/tc_session.php';
requireTCLogin();
$current_user = getCurrentTCUser();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="theme-color" content="#28a745">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>TC Panel - BC Attendance</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Override admin sidebar gradient with TC dark blue-gray theme */
        .main-sidebar {
            background: #415E72 !important;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        /* TC brand color */
        .brand-image {
            background: #fff !important;
            color: #415E72 !important;
        }
        
        /* Sidebar brand styling */
        .brand-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: #415E72;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .brand-image {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            margin-right: 12px;
            font-size: 1.5rem;
        }
        
        .brand-text {
            font-size: 1.25rem;
            font-weight: 600;
            color: #fff;
        }
        
        /* Sidebar navigation styling */
        .nav-sidebar {
            padding: 1rem 0;
        }
        
        .nav-sidebar .nav-item {
            margin: 0;
        }
        
        .nav-sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 0.75rem 1rem;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            border-left-color: #fff;
        }
        
        .nav-sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Mobile close button styling */
        .sidebar-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: #fff;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }
        
        .sidebar-close:hover {
            background-color: rgba(255,255,255,0.1);
        }
        
        .sidebar-close.d-md-none {
            display: block;
        }
        
        @media (min-width: 768px) {
            .sidebar-close {
                display: none !important;
            }
        }
        
        /* Top navbar styling for TC - White with drop shadow like admin */
        .main-header {
            background: #fff !important;
            border-bottom: 1px solid #dee2e6;
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 60px;
            min-height: 60px;
        }
        
        .main-header .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 1.5rem;
            height: 100%;
            min-height: 60px;
        }
        
        .main-header .navbar-brand {
            font-size: 1.25rem;
            font-weight: 600;
            color: #495057;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .main-header .sidebar-toggle {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            font-size: 1.1rem;
            margin-right: 15px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: none;
        }
        
        .main-header .sidebar-toggle:hover {
            background: #e9ecef;
        }
        
        .main-header .sidebar-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .main-header .navbar-nav .nav-item .dropdown-toggle {
            background: none;
            border: none;
            color: #495057;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            white-space: nowrap;
            overflow: hidden;
        }
        
        .main-header .navbar-nav .nav-item .dropdown-toggle:hover {
            background-color: #f8f9fa;
        }
        
        .main-header .dropdown-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 5px);
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            min-width: 160px;
            z-index: 1050;
            display: none;
        }
        
        .main-header .dropdown-item {
            display: block;
            padding: 8px 16px;
            color: #495057;
            text-decoration: none;
            transition: background-color 0.3s;
        }
        
        .main-header .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        /* Dropdown visibility */
        .dropdown-menu.show {
            display: block;
        }
        
        /* Mobile sidebar toggle functionality */
        @media (max-width: 767.98px) {
            .main-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 1000;
                width: 250px;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                will-change: transform;
                background: #415E72 !important;
            }
            
            .main-sidebar.show {
                transform: translateX(0) !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .content-wrapper {
                margin-left: 0;
                width: 100%;
                transition: margin-left 0.3s ease-in-out;
            }
            
            /* Overlay when sidebar is open */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 999;
                display: none;
                opacity: 0;
                transition: opacity 0.3s ease-in-out;
                will-change: opacity;
            }
            
            .sidebar-overlay.show {
                display: block;
                opacity: 1;
            }
            
                    /* Ensure sidebar toggle button is visible and styled */
        .sidebar-toggle {
            display: block !important;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px 12px;
            margin-right: 15px;
            z-index: 1001;
            position: relative;
            min-width: 44px;
            min-height: 44px;
        }
        
        /* Force visibility on mobile */
        .sidebar-toggle.d-md-none {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
            
            .sidebar-toggle:hover {
                background: #e9ecef;
            }
            
            .sidebar-toggle:focus {
                outline: none;
                box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
            }
            
            /* Prevent body scroll when sidebar is open */
            body.sidebar-open {
                overflow: hidden;
                position: fixed;
                width: 100%;
            }
            
            /* Ensure content is properly positioned */
            .content {
                padding: 1rem;
            }
        }
        
        /* Desktop sidebar behavior */
        @media (min-width: 768px) {
            .main-sidebar {
                transform: translateX(0);
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                width: 250px;
            }
            
            .content-wrapper {
                margin-left: 250px;
            }
            
            .sidebar-toggle {
                display: none !important;
            }
        }
    </style>
    <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
</head>
<body class="">
    <div class="wrapper">
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="main-sidebar" id="mainSidebar">
            <!-- Brand Logo -->
            <a href="dashboard.php" class="brand-link">
                <div class="brand-image">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="brand-text">TC Panel</span>
            </a>
            
            <!-- Mobile Close Button -->
            <button class="sidebar-close d-md-none" type="button" aria-label="Close sidebar">
                <i class="fas fa-times"></i>
            </button>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="attendance.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'attendance.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <span>Daily Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'students.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="batches.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'batches.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-layer-group"></i>
                            <span>Training Batches</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
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
                        <button class="sidebar-toggle d-md-none" type="button" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <?php echo isset($pageTitle) ? $pageTitle : 'TC Panel'; ?>
                    </div>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <button class="dropdown-toggle" type="button">
                                <i class="fas fa-graduation-cap"></i>
                                <?php echo htmlspecialchars($current_user['tc_id']); ?>
                                <i class="fas fa-caret-down ml-1"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a>
                                <a class="dropdown-item" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../tc_logout.php">
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
                <?php 
                if (function_exists('displayFlashMessages')) {
                    echo displayFlashMessages(); 
                }
                ?>
            </div>
        </div>
    </div>
