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
            height: 70px;
            min-height: 70px;
        }
        
        .main-header .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1.5rem;
            height: 100%;
            min-height: 70px;
        }
        
        .main-header .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #495057;
            margin: 0;
            display: flex;
            align-items: center;
            flex: 1;
            min-height: 44px;
        }
        
        .main-header .sidebar-toggle {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            font-size: 1.1rem;
            margin-right: 15px;
            cursor: pointer;
            padding: 10px 14px;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: none;
            min-width: 44px;
            min-height: 44px;
            align-items: center;
            justify-content: center;
        }
        
        .main-header .sidebar-toggle:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .main-header .sidebar-toggle:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
        }
        
        .main-header .navbar-nav {
            margin-left: auto;
            display: flex;
            align-items: center;
        }
        
        .main-header .navbar-nav .nav-item .dropdown-toggle {
            background: none;
            border: none;
            color: #495057;
            padding: 10px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            overflow: hidden;
            display: flex;
            align-items: center;
            min-height: 44px;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .main-header .navbar-nav .nav-item .dropdown-toggle:hover {
            background-color: #f8f9fa;
            color: #212529;
        }
        
        .main-header .dropdown-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 1050;
            display: none;
            padding: 0.5rem 0;
        }
        
        .main-header .dropdown-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #495057;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .main-header .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #212529;
        }
        
        .main-header .dropdown-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }
        
        .main-header .dropdown-divider {
            margin: 0.5rem 0;
            border-top: 1px solid #e9ecef;
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
                width: 280px;
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
                display: flex !important;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 6px;
                padding: 10px 14px;
                margin-right: 15px;
                z-index: 1001;
                position: relative;
                min-width: 44px;
                min-height: 44px;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            /* Force visibility on mobile */
            .sidebar-toggle.d-md-none {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .sidebar-toggle:hover {
                background: #e9ecef;
                border-color: #adb5bd;
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
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
            
            /* Mobile header optimizations */
            .main-header {
                height: 70px;
                min-height: 70px;
            }
            
            .main-header .navbar {
                padding: 0.75rem 1rem;
            }
            
            .main-header .navbar-brand {
                font-size: 1.25rem;
                margin-left: 0;
            }
            
            /* Mobile dropdown optimizations */
            .main-header .dropdown-menu {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                width: 100%;
                border-radius: 0;
                border-left: none;
                border-right: none;
                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                max-height: 300px;
                overflow-y: auto;
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
                width: 280px;
                z-index: 1000;
            }
            
            .content-wrapper {
                margin-left: 280px;
                transition: margin-left 0.3s ease-in-out;
            }
            
            .sidebar-toggle {
                display: none !important;
            }
            
            /* Desktop content optimizations */
            .content {
                padding: 1.5rem;
                min-height: calc(100vh - 70px);
            }
            
            .content-header {
                margin-bottom: 1.5rem;
            }
        }
        
        /* Content wrapper base styles */
        .content-wrapper {
            min-height: 100vh;
            background-color: #f8f9fa;
            transition: margin-left 0.3s ease-in-out;
        }
        
        /* Content area styling */
        .content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin: 1rem;
            min-height: calc(100vh - 110px);
        }
        
        /* Content header styling */
        .content-header {
            padding: 1.5rem 1.5rem 0;
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 1.5rem;
        }
        
        .content-header h1,
        .content-header h2,
        .content-header h3 {
            margin: 0;
            color: #495057;
            font-weight: 600;
        }
        
        /* Breadcrumb styling */
        .breadcrumb {
            background: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            color: #6c757d;
            margin: 0 0.5rem;
        }
        
        .breadcrumb-item a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        
        .breadcrumb-item.active {
            color: #6c757d;
        }
        
        /* Override any admin.css conflicts */
        .main-header .sidebar-toggle.d-md-none {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            position: relative !important;
            z-index: 1001 !important;
        }
        
        /* Ensure toggle button is always visible on mobile */
        @media (max-width: 767.98px) {
            .main-header .sidebar-toggle,
            .sidebar-toggle,
            .sidebar-toggle.d-md-none {
                display: flex !important;
                visibility: visible !important;
                opacity: 1 !important;
                position: relative !important;
                z-index: 1001 !important;
                pointer-events: auto !important;
                background: #f8f9fa !important;
                border: 1px solid #dee2e6 !important;
                border-radius: 6px !important;
                padding: 10px 14px !important;
                min-width: 44px !important;
                min-height: 44px !important;
                align-items: center !important;
                justify-content: center !important;
            }
        }
        
        /* Additional mobile optimizations */
        @media (max-width: 480px) {
            .main-header {
                height: 65px;
                min-height: 65px;
            }
            
            .main-header .navbar {
                padding: 0.5rem 0.75rem;
            }
            
            .main-header .navbar-brand {
                font-size: 1.1rem;
            }
            
            .main-header .sidebar-toggle {
                padding: 8px 12px;
                margin-right: 10px;
            }
            
            .content {
                margin: 0.5rem;
                padding: 1rem;
            }
            
            .content-header {
                padding: 1rem 1rem 0;
                margin-bottom: 1rem;
            }
        }
        
        /* Ensure proper spacing and alignment */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar improvements */
        .main-sidebar {
            background: #415E72 !important;
            box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .main-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .main-sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .main-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        .main-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        /* Brand link improvements */
        .brand-link {
            display: flex;
            align-items: center;
            padding: 1.25rem 1rem;
            color: #415E72;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }
        
        .brand-link:hover {
            background-color: rgba(255,255,255,0.05);
            text-decoration: none;
            color: #415E72;
        }
        
        .brand-image {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-right: 15px;
            font-size: 1.6rem;
            background: #fff !important;
            color: #415E72 !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .brand-text {
            font-size: 1.35rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        /* Navigation improvements */
        .nav-sidebar {
            padding: 1rem 0;
        }
        
        .nav-sidebar .nav-item {
            margin: 0;
        }
        
        .nav-sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 0.875rem 1.25rem;
            border-radius: 0;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.1);
            border-left-color: #fff;
            transform: translateX(5px);
        }
        
        .nav-sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        /* Mobile close button improvements */
        .sidebar-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: all 0.3s ease;
            min-width: 40px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-close:hover {
            background-color: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.3);
            transform: scale(1.1);
        }
        
        /* Final header layout fixes */
        .main-header {
            position: sticky;
            top: 0;
            z-index: 999;
            background: #fff !important;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Ensure proper flexbox layout */
        .main-header .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        /* Brand area styling */
        .main-header .navbar-brand {
            display: flex;
            align-items: center;
            flex: 1;
            min-width: 0;
            overflow: hidden;
        }
        
        /* Profile dropdown area */
        .main-header .navbar-nav {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
        }
        
        .main-header .navbar-nav .nav-item {
            margin: 0;
            padding: 0;
        }
        
        /* Ensure dropdown is properly positioned */
        .main-header .dropdown {
            position: relative;
        }
        
        .main-header .dropdown-menu {
            margin-top: 0;
            border: 1px solid #dee2e6;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        /* Fix for very small screens */
        @media (max-width: 360px) {
            .main-header .navbar {
                padding: 0.5rem 0.5rem;
            }
            
            .main-header .navbar-brand {
                font-size: 1rem;
            }
            
            .main-header .sidebar-toggle {
                padding: 8px 10px;
                margin-right: 8px;
                min-width: 40px;
                min-height: 40px;
            }
            
            .main-header .dropdown-toggle {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
        }
        
        /* Ensure proper spacing on all devices */
        .main-header .navbar-brand,
        .main-header .sidebar-toggle,
        .main-header .dropdown-toggle {
            line-height: 1;
            vertical-align: middle;
        }
        
        /* Fix for iOS Safari */
        @supports (-webkit-touch-callout: none) {
            .main-header {
                position: -webkit-sticky;
                position: sticky;
            }
            
            .main-sidebar {
                -webkit-transform: translateX(-100%);
                transform: translateX(-100%);
            }
            
            .main-sidebar.show {
                -webkit-transform: translateX(0);
                transform: translateX(0);
            }
        }
        
        /* Ensure proper touch targets on mobile */
        @media (max-width: 767.98px) {
            .main-header .sidebar-toggle,
            .main-header .dropdown-toggle {
                min-height: 44px;
                min-width: 44px;
                display: flex !important;
                align-items: center;
                justify-content: center;
            }
            
            .main-header .navbar-brand {
                min-height: 44px;
                display: flex;
                align-items: center;
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
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user"></i>
                            <span>Profile</span>
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
                        <!-- Only show sidebar toggle on mobile, not inside profile dropdown -->
                        <button class="sidebar-toggle d-md-none" id="mainSidebarToggle" type="button" aria-label="Toggle sidebar" style="display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; z-index: 1001 !important;">
                            <i class="fas fa-bars"></i>
                        </button>
                        <?php echo isset($pageTitle) ? $pageTitle : 'TC Panel'; ?>
                    </div>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <button class="dropdown-toggle profile-dropdown-toggle" type="button" aria-label="Profile menu">
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
