<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in as TC user
if (isset($_SESSION['tc_user_id'])) {
    header('Location: tc_user/dashboard.php');
    exit();
}

// Redirect if logged in as admin
if (isset($_SESSION['admin_user_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tc_id = sanitizeInput($_POST['tc_id']);
    $password = $_POST['password'];
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        if (empty($tc_id) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $query = "SELECT tc.id, tc.tc_id, tc.training_center_id, tc.mandal_id, tc.password, tc.full_name, tc.status,
                             tcr.name as training_center_name, m.name as mandal_name
                      FROM tc_users tc
                      JOIN training_centers tcr ON tc.training_center_id = tcr.id
                      JOIN mandals m ON tc.mandal_id = m.id
                      WHERE tc.tc_id = ? AND tc.status = 'active'";
            $user = fetchRow($query, [$tc_id], 's');
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['tc_user_id'] = $user['id'];
                $_SESSION['tc_user_tc_id'] = $user['tc_id'];
                $_SESSION['tc_user_training_center_id'] = $user['training_center_id'];
                $_SESSION['tc_user_mandal_id'] = $user['mandal_id'];
                $_SESSION['tc_user_full_name'] = $user['full_name'];
                $_SESSION['tc_user_training_center_name'] = $user['training_center_name'];
                $_SESSION['tc_user_mandal_name'] = $user['mandal_name'];
                
                // Update last login
                $updateQuery = "UPDATE tc_users SET last_login = NOW() WHERE id = ?";
                executeQuery($updateQuery, [$user['id']], 'i');
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                header('Location: tc_user/dashboard.php');
                exit();
            } else {
                $error = 'Invalid TC ID or password.';
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
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
    <title>TC Login - BC Attendance System</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .login-title {
            color: #495057;
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }
        
        .login-subtitle {
            color: #6c757d;
            font-size: 0.9rem;
            margin: 0.5rem 0 0 0;
        }
        
        .credentials-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
        }
        
        .credentials-info h4 {
            margin: 0 0 0.5rem 0;
            color: #0056b3;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .credentials-info p {
            margin: 0.25rem 0;
            color: #0056b3;
            line-height: 1.4;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            min-height: 48px; /* Touch-friendly height */
        }
        
        .form-control:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-left: 3rem;
        }
        
        .input-group-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            z-index: 10;
            font-size: 1.1rem;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 48px; /* Touch-friendly height */
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #218838 0%, #1abc9c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .admin-login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
        
        .admin-login-link a {
            color: #28a745;
            text-decoration: none;
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-login-link a:hover {
            background-color: #f8f9fa;
            text-decoration: none;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
                align-items: flex-start;
                padding-top: 2rem;
            }
            
            .login-container {
                margin: 0;
                padding: 1.5rem;
                border-radius: 12px;
                max-width: 100%;
            }
            
            .login-logo {
                width: 70px;
                height: 70px;
                font-size: 1.8rem;
            }
            
            .login-title {
                font-size: 1.3rem;
            }
            
            .login-subtitle {
                font-size: 0.85rem;
            }
            
            .credentials-info {
                padding: 0.75rem;
                font-size: 0.8rem;
            }
            
            .credentials-info h4 {
                font-size: 0.85rem;
            }
            
            .form-control {
                font-size: 16px; /* Prevent zoom on iOS */
                padding: 0.875rem 1rem;
            }
            
            .btn-login {
                padding: 1rem;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 360px) {
            .login-container {
                padding: 1.25rem;
            }
            
            .login-logo {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
            
            .login-title {
                font-size: 1.2rem;
            }
            
            .form-control {
                padding: 0.75rem 0.875rem;
            }
            
            .btn-login {
                padding: 0.875rem;
            }
        }
        
        /* Landscape orientation adjustments */
        @media (max-width: 768px) and (orientation: landscape) {
            body {
                align-items: center;
                padding: 1rem;
            }
            
            .login-container {
                max-width: 450px;
            }
        }
        
        /* Touch device optimizations */
        @media (hover: none) and (pointer: coarse) {
            .btn-login {
                min-height: 52px;
            }
            
            .form-control {
                min-height: 52px;
            }
            
            .admin-login-link a {
                min-height: 44px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        /* Loading state */
        .btn-login.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .btn-login.loading i {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Focus visible for accessibility */
        .btn-login:focus-visible,
        .form-control:focus-visible,
        .admin-login-link a:focus-visible {
            outline: 2px solid #28a745;
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="login-title">Training Center</h1>
            <p class="login-subtitle">BC Attendance System</p>
        </div>

        <div class="credentials-info">
            <h4><i class="fas fa-info-circle"></i> Login Credentials</h4>
            <p><strong>Username:</strong> Your TC ID (e.g., TTC7430317)</p>
            <p><strong>Password:</strong> institute</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" data-validate>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="tc_id" class="form-label">TC ID</label>
                <div class="input-group">
                    <i class="input-group-icon fas fa-id-card"></i>
                    <input type="text" 
                           id="tc_id" 
                           name="tc_id" 
                           class="form-control" 
                           placeholder="Enter your TC ID (e.g., TTC7430317)"
                           value="<?php echo isset($_POST['tc_id']) ? htmlspecialchars($_POST['tc_id']) : ''; ?>"
                           required
                           autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <i class="input-group-icon fas fa-lock"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Enter password"
                           required
                           autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In</span>
            </button>
        </form>

        <div class="admin-login-link">
            <a href="login.php">
                <i class="fas fa-user-shield"></i>
                Admin Login
            </a>
        </div>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> BC Attendance System. All rights reserved.</p>
        </div>
    </div>

    <script src="assets/js/admin.js"></script>
    <script>
        // Focus on TC ID field
        document.getElementById('tc_id').focus();
        
        // Add loading state to form
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = document.getElementById('loginBtn');
            const buttonText = button.querySelector('span');
            const buttonIcon = button.querySelector('i');
            
            // Show loading state
            button.classList.add('loading');
            buttonIcon.className = 'fas fa-spinner fa-spin';
            buttonText.textContent = 'Signing In...';
            button.disabled = true;
            
            // Prevent double submission
            setTimeout(() => {
                if (button.disabled) {
                    button.disabled = false;
                    button.classList.remove('loading');
                    buttonIcon.className = 'fas fa-sign-in-alt';
                    buttonText.textContent = 'Sign In';
                }
            }, 10000); // Reset after 10 seconds
        });
        
        // Mobile-specific enhancements
        if ('ontouchstart' in window) {
            // Touch device optimizations
            document.body.classList.add('touch-device');
            
            // Prevent zoom on input focus (iOS)
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.fontSize = '16px';
                });
            });
        }
        
        // Handle orientation change
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                // Force viewport recalculation
                const vh = window.innerHeight * 0.01;
                document.documentElement.style.setProperty('--vh', `${vh}px`);
            }, 100);
        });
    </script>
</body>
</html>
