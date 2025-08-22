<?php
// Session management
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['admin_user_id']) && !empty($_SESSION['admin_user_id']);
}

// Get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return false;
    }
    
    require_once '../config/database.php';
    
    $query = "SELECT id, username, email, full_name, role, status FROM admin_users WHERE id = ? AND status = 'active'";
    $user = fetchRow($query, [$_SESSION['admin_user_id']], 'i');
    
    return $user;
}

// Login user
function loginUser($username, $password) {
    require_once '../config/database.php';
    
    $query = "SELECT id, username, email, password, full_name, role, status FROM admin_users WHERE username = ? AND status = 'active'";
    $user = fetchRow($query, [$username], 's');
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['admin_email'] = $user['email'];
        $_SESSION['admin_full_name'] = $user['full_name'];
        $_SESSION['admin_role'] = $user['role'];
        
        return true;
    }
    
    return false;
}

// Logout user
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit();
}

// Require login - redirect to login page if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

// Check if user has specific role
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === $role;
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
