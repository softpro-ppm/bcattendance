<?php
// TC User Session management
session_start();

// Check if TC user is logged in
function isTCLoggedIn() {
    return isset($_SESSION['tc_user_id']) && !empty($_SESSION['tc_user_id']);
}

// Get current TC user data
function getCurrentTCUser() {
    if (!isTCLoggedIn()) {
        return false;
    }
    
    require_once '../config/database.php';
    
    $query = "SELECT tc.id, tc.tc_id, tc.training_center_id, tc.mandal_id, tc.full_name, tc.status,
                     tcr.name as training_center_name, m.name as mandal_name, c.name as constituency_name
              FROM tc_users tc
              JOIN training_centers tcr ON tc.training_center_id = tcr.id
              JOIN mandals m ON tc.mandal_id = m.id  
              JOIN constituencies c ON m.constituency_id = c.id
              WHERE tc.id = ? AND tc.status = 'active'";
    $user = fetchRow($query, [$_SESSION['tc_user_id']], 'i');
    
    return $user;
}

// Login TC user
function loginTCUser($tc_id, $password) {
    require_once '../config/database.php';
    
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
        
        return true;
    }
    
    return false;
}

// Logout TC user
function logoutTCUser() {
    session_unset();
    session_destroy();
    header('Location: ../tc_login.php');
    exit();
}

// Require TC login - redirect to login page if not logged in
function requireTCLogin() {
    if (!isTCLoggedIn()) {
        header('Location: ../tc_login.php');
        exit();
    }
}

// Check if TC user can access mandal data
function canAccessMandal($mandal_id) {
    return isTCLoggedIn() && isset($_SESSION['tc_user_mandal_id']) && 
           $_SESSION['tc_user_mandal_id'] == $mandal_id;
}

// Check if TC user can access training center data
function canAccessTrainingCenter($tc_id) {
    return isTCLoggedIn() && isset($_SESSION['tc_user_training_center_id']) && 
           $_SESSION['tc_user_training_center_id'] == $tc_id;
}

// Generate CSRF token for TC users
function generateTCCSRFToken() {
    if (!isset($_SESSION['tc_csrf_token'])) {
        $_SESSION['tc_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['tc_csrf_token'];
}

// Verify CSRF token for TC users
function verifyTCCSRFToken($token) {
    return isset($_SESSION['tc_csrf_token']) && hash_equals($_SESSION['tc_csrf_token'], $token);
}

// Get TC user's batches
function getTCUserBatches() {
    if (!isTCLoggedIn()) {
        return [];
    }
    
    require_once '../config/database.php';
    
    $query = "SELECT b.* FROM batches b 
              WHERE b.tc_id = ? AND b.status = 'active'
              ORDER BY b.name";
    $result = executeQuery($query, [$_SESSION['tc_user_training_center_id']], 'i');
    
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Get TC user's beneficiaries
function getTCUserBeneficiaries($batch_id = null) {
    if (!isTCLoggedIn()) {
        return [];
    }
    
    require_once '../config/database.php';
    
    $query = "SELECT ben.* FROM beneficiaries ben 
              JOIN batches b ON ben.batch_id = b.id 
              WHERE b.tc_id = ? AND ben.status = 'active'";
    $params = [$_SESSION['tc_user_training_center_id']];
    $types = 'i';
    
    if ($batch_id) {
        $query .= " AND ben.batch_id = ?";
        $params[] = $batch_id;
        $types .= 'i';
    }
    
    $query .= " ORDER BY ben.name";
    $result = executeQuery($query, $params, $types);
    
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Check if date is today (for editing restriction)
function isToday($date) {
    // Use IST date for comparison
    return getCurrentISTDate() === date('Y-m-d', strtotime($date));
}

// Log attendance edit for admin tracking
function logAttendanceEdit($attendance_id, $beneficiary_id, $attendance_date, $old_data, $new_data, $edit_type = 'update') {
    if (!isTCLoggedIn()) {
        return false;
    }
    
    require_once '../config/database.php';
    
    $query = "INSERT INTO attendance_edit_log 
              (attendance_id, beneficiary_id, attendance_date, old_status, new_status, 
               old_check_in_time, new_check_in_time, old_check_out_time, new_check_out_time,
               old_remarks, new_remarks, edited_by_tc_user, edit_type) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $params = [
        $attendance_id,
        $beneficiary_id,
        $attendance_date,
        $old_data['status'] ?? null,
        $new_data['status'] ?? null,
        $old_data['check_in_time'] ?? null,
        $new_data['check_in_time'] ?? null,
        $old_data['check_out_time'] ?? null,
        $new_data['check_out_time'] ?? null,
        $old_data['remarks'] ?? null,
        $new_data['remarks'] ?? null,
        $_SESSION['tc_user_id'],
        $edit_type
    ];
    
    $types = 'iisssssssssis';
    
    return executeQuery($query, $params, $types);
}
?>
