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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TC Login - BC Attendance System</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
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
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
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
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #218838 0%, #1abc9c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border: 1px solid #f5c6cb;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .admin-login-link {
            text-align: center;
            margin-top: 1rem;
        }
        
        .admin-login-link a {
            color: #28a745;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .admin-login-link a:hover {
            text-decoration: underline;
        }
        

        
        @media (max-width: 480px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
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
                           required>
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
                           required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
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
        document.querySelector('form').addEventListener('submit', function() {
            const button = this.querySelector('.btn-login');
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing In...';
            button.disabled = true;
        });
    </script>
</body>
</html>
