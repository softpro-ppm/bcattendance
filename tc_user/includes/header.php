<?php
require_once '../includes/tc_session.php';
require_once '../includes/functions.php'; // Add missing functions file
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css>
    

    <style>
        /* Override admin sidebar gradient with TC dark blue-gray theme */
        .main-sidebar {
            background: #415E72 !important;
        }
        
        /* TC brand color */
        .brand-image {
            background: #fff !important;
            color: #415E72 !important;
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
            gap: 0.75rem;
        }
        
        .main-header .sidebar-toggle {
            background: none;
            border: none;
            color: #495057;
            font-size: 1.1rem;
            margin-right: 1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
            min-height: 44px;
            min-width: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .main-header .sidebar-toggle:hover {
            background-color: #f8f9fa;
        }
        
        .main-header .sidebar-toggle:active {
            background-color: #e9ecef;
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
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .main-header .navbar-nav .nav-item .dropdown-toggle:hover {
            background-color: #f8f9fa;
        }
        
        .main-header .navbar-nav .nav-item .dropdown-toggle:active {
            background-color: #e9ecef;
        }
        
        .main-header .dropdown-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 5px);
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 1050;
            display: none;
            padding: 0.5rem 0;
        }
        
        .main-header .dropdown-menu.show {
            display: block;
            animation: fadeInDown 0.2s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .main-header .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            color: #495057;
            text-decoration: none;
            transition: background-color 0.2s;
            font-size: 0.9rem;
        }
        
        .main-header .dropdown-item:hover {
            background-color: #f8f9fa;
            text-decoration: none;
        }
        
        .main-header .dropdown-item:active {
            background-color: #e9ecef;
        }
        
        .main-header .dropdown-divider {
            margin: 0.5rem 0;
            border-top: 1px solid #e9ecef;
        }
        
        /* Mobile-specific styles */
        @media (max-width: 768px) {
            .main-header .navbar {
                padding: 0.5rem 1rem;
            }
            
            .main-header .navbar-brand {
                font-size: 1.1rem;
                gap: 0.5rem;
            }
            
            .main-header .sidebar-toggle {
                margin-right: 0.5rem;
                padding: 0.4rem;
                min-height: 40px;
                min-width: 40px;
            }
            
            .main-header .dropdown-menu {
                min-width: 200px;
                max-width: 90vw;
                left: auto;
                right: 0;
            }
            
            .main-header .dropdown-item {
                padding: 0.875rem 1rem;
                font-size: 0.95rem;
                min-height: 44px;
            }
        }
        
        @media (max-width: 576px) {
            .main-header .navbar {
                padding: 0.5rem 0.75rem;
            }
            
            .main-header .navbar-brand {
                font-size: 1rem;
                gap: 0.4rem;
            }
            
            .main-header .sidebar-toggle {
                padding: 0.35rem;
                min-height: 38px;
                min-width: 38px;
                font-size: 1rem;
            }
            
            .main-header .dropdown-menu {
                min-width: 180px;
                max-width: 85vw;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .main-header .sidebar-toggle,
            .main-header .dropdown-toggle,
            .main-header .dropdown-item {
                min-height: 44px;
            }
            
            .main-header .sidebar-toggle {
                min-width: 44px;
            }
        }
        
        /* Focus visible for accessibility */
        .main-header .sidebar-toggle:focus-visible,
        .main-header .dropdown-toggle:focus-visible,
        .main-header .dropdown-item:focus-visible {
            outline: 2px solid #28a745;
            outline-offset: 2px;
        }
        
        /* Mobile navigation improvements */
        .mobile-nav-info {
            display: none;
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 0.75rem;
            margin: 0.5rem 0;
            font-size: 0.85rem;
            color: #0056b3;
        }
        
        @media (max-width: 768px) {
            .mobile-nav-info {
                display: block;
            }
        }
        
        /* Critical Mobile Dashboard Styles */
        @media (max-width: 768px) {
            .dashboard-stats,
            .dashboard-quick-actions,
            .attendance-summary,
            .recent-activities,
            .info-item {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .dashboard-stats .col-6 {
                flex: 0 0 50% !important;
                max-width: 50% !important;
                display: block !important;
                padding: 0.25rem !important;
            }
            
            .dashboard-stats .stats-card {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                border: none !important;
                margin-bottom: 0.5rem !important;
                display: block !important;
            }
            
            .dashboard-stats .stats-number {
                color: white !important;
                font-size: 1.5rem !important;
                font-weight: 700 !important;
            }
            
            .dashboard-stats .stats-label {
                color: rgba(255, 255, 255, 0.9) !important;
                font-size: 0.85rem !important;
            }
            
            .dashboard-stats .stats-icon {
                color: white !important;
                opacity: 0.3 !important;
            }
            
            .dashboard-quick-actions .col-6 {
                flex: 0 0 50% !important;
                max-width: 50% !important;
                display: block !important;
                padding: 0.25rem !important;
            }
            
            .dashboard-quick-actions .btn {
                display: flex !important;
                min-height: 70px !important;
                padding: 0.75rem 0.5rem !important;
            }
            
            .card {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                margin-bottom: 1rem !important;
            }
            
            .card-body {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                padding: 1rem !important;
            }
            
            .card-header {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
            
            .row {
                display: flex !important;
                flex-wrap: wrap !important;
                margin: 0 !important;
            }
            
            .col-12 {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                padding: 0.25rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-stats .stats-number {
                font-size: 1.3rem !important;
            }
            
            .dashboard-stats .stats-label {
                font-size: 0.75rem !important;
            }
            
            .dashboard-quick-actions .btn {
                min-height: 60px !important;
                padding: 0.5rem 0.25rem !important;
            }
        }
    </style>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="main-sidebar">
            <!-- Brand Logo -->
            <a href="dashboard.php" class="brand-link">
                <div class="brand-image">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <span class="brand-text">TC Panel</span>
            </a>

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
                            <span>Batches</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-chart-line"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                            <i class="nav-icon fas fa-user-cog"></i>
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
                        <button class="sidebar-toggle d-lg-none" type="button" aria-label="Toggle sidebar">
                            <i class="fas fa-bars"></i>
                        </button>
                        <span><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></span>
                    </div>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <button class="dropdown-toggle" type="button">
                                <i class="fas fa-user-circle"></i>
                                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($current_user['full_name']); ?></span>
                                <i class="fas fa-caret-down ml-1"></i>
                            </button>
                            <div class="dropdown-menu">
                                <div class="dropdown-item">
                                    <i class="fas fa-building"></i>
                                    <strong><?php echo htmlspecialchars($current_user['training_center_name']); ?></strong>
                                </div>
                                <div class="dropdown-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php echo htmlspecialchars($current_user['mandal_name']); ?>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user"></i> Profile
                                </a>
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
                    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    
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
                    
                    <!-- Mobile navigation info -->
                    <div class="mobile-nav-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>TC:</strong> <?php echo htmlspecialchars($current_user['training_center_name']); ?> | 
                        <strong>Mandal:</strong> <?php echo htmlspecialchars($current_user['mandal_name']); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Flash Messages -->
                <?php echo displayFlashMessages(); ?>