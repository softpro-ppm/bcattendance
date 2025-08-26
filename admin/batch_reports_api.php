<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Handle AJAX requests for data
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'get_batch_data':
            getBatchData();
            break;
        case 'get_batch_stats':
            getBatchStats();
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

function getBatchData() {
    $constituency = $_POST['constituency'] ?? '';
    $mandal = $_POST['mandal'] ?? '';
    $batch = $_POST['batch'] ?? '';
    $search = $_POST['search'] ?? '';
    $page = $_POST['page'] ?? 1;
    $limit = $_POST['limit'] ?? 10;
    $sort_by = $_POST['sort_by'] ?? 'b.full_name';
    $sort_order = $_POST['sort_order'] ?? 'ASC';
    
    $offset = ($page - 1) * $limit;
    
    // Build where clause
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($constituency)) {
        $where_conditions[] = "c.id = ?";
        $params[] = $constituency;
        $types .= 'i';
    }
    
    if (!empty($mandal)) {
        $where_conditions[] = "m.id = ?";
        $params[] = $mandal;
        $types .= 'i';
    }
    
    if (!empty($batch)) {
        $where_conditions[] = "bt.id = ?";
        $params[] = $batch;
        $types .= 'i';
    }
    
    if (!empty($search)) {
        $where_conditions[] = "(b.full_name LIKE ? OR b.beneficiary_id LIKE ? OR b.mobile_number LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count
    $count_query = "
        SELECT COUNT(*) as total
        FROM beneficiaries b
        JOIN batches bt ON b.batch_id = bt.id
        JOIN mandals m ON bt.mandal_id = m.id
        JOIN constituencies c ON m.constituency_id = c.id
        $where_clause
    ";
    
    $total_count = fetchRow($count_query, $params, $types)['total'];
    
    // Get data with pagination and sorting
    $query = "
        SELECT 
            b.id,
            b.beneficiary_id,
            b.full_name,
            b.mobile_number,
            b.aadhar_number,
            b.batch_start_date,
            b.batch_end_date,
            b.status as beneficiary_status,
            bt.name as batch_name,
            bt.code as batch_code,
            bt.start_date as batch_start,
            bt.end_date as batch_end,
            bt.status as batch_status,
            m.name as mandal_name,
            c.name as constituency_name,
            tc.tc_id as tc_code,
            tc.name as tc_name,
            COALESCE(att.present_days, 0) as present_days,
            COALESCE(att.total_days, 0) as total_days,
            CASE 
                WHEN COALESCE(att.total_days, 0) > 0 
                THEN ROUND((COALESCE(att.present_days, 0) / COALESCE(att.total_days, 0)) * 100, 2)
                ELSE 0 
            END as attendance_percentage
        FROM beneficiaries b
        JOIN batches bt ON b.batch_id = bt.id
        JOIN mandals m ON bt.mandal_id = m.id
        JOIN constituencies c ON m.constituency_id = c.id
        JOIN training_centers tc ON bt.tc_id = tc.id
        LEFT JOIN (
            SELECT 
                beneficiary_id,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                COUNT(*) as total_days
            FROM attendance 
            GROUP BY beneficiary_id
        ) att ON b.id = att.beneficiary_id
        $where_clause
        ORDER BY $sort_by $sort_order
        LIMIT $offset, $limit
    ";
    
    $data = fetchAll($query, $params, $types);
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => $total_count,
        'pages' => ceil($total_count / $limit),
        'current_page' => $page
    ]);
}

function getBatchStats() {
    $constituency = $_POST['constituency'] ?? '';
    $mandal = $_POST['mandal'] ?? '';
    $batch = $_POST['batch'] ?? '';
    
    // Build where clause
    $where_conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($constituency)) {
        $where_conditions[] = "c.id = ?";
        $params[] = $constituency;
        $types .= 'i';
    }
    
    if (!empty($mandal)) {
        $where_conditions[] = "m.id = ?";
        $params[] = $mandal;
        $types .= 'i';
    }
    
    if (!empty($batch)) {
        $where_conditions[] = "bt.id = ?";
        $params[] = $batch;
        $types .= 'i';
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(DISTINCT b.id) as total_students,
            COUNT(DISTINCT CASE WHEN b.status = 'active' THEN b.id END) as active_students,
            COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) as completed_students,
            COUNT(DISTINCT CASE WHEN b.status = 'dropped' THEN b.id END) as dropped_students,
            COUNT(DISTINCT bt.id) as total_batches,
            COUNT(DISTINCT CASE WHEN bt.status = 'active' THEN bt.id END) as active_batches,
            COUNT(DISTINCT CASE WHEN bt.status = 'completed' THEN bt.id END) as completed_batches,
            ROUND(AVG(CASE WHEN att.total_days > 0 THEN (att.present_days / att.total_days) * 100 END), 2) as avg_attendance_percentage
        FROM beneficiaries b
        JOIN batches bt ON b.batch_id = bt.id
        JOIN mandals m ON bt.mandal_id = m.id
        JOIN constituencies c ON m.constituency_id = c.id
        LEFT JOIN (
            SELECT 
                beneficiary_id,
                COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
                COUNT(*) as total_days
            FROM attendance 
            GROUP BY beneficiary_id
        ) att ON b.id = att.beneficiary_id
        $where_clause
    ";
    
    $stats = fetchRow($stats_query, $params, $types);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}
?>
