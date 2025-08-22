<?php
// Attendance Restrictions CRUD API
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check authentication
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listRestrictions();
            break;
        case 'get':
            getRestriction();
            break;
        case 'create':
            createRestriction();
            break;
        case 'update':
            updateRestriction();
            break;
        case 'toggle':
            toggleRestriction();
            break;
        case 'delete':
            deleteRestriction();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function listRestrictions() {
    $query = "SELECT * FROM attendance_restrictions ORDER BY is_active DESC, restriction_name ASC";
    $restrictions = fetchAll($query);
    echo json_encode($restrictions);
}

function getRestriction() {
    $id = $_GET['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('Restriction ID is required');
    }
    
    $query = "SELECT * FROM attendance_restrictions WHERE id = ?";
    $restriction = fetchRow($query, [$id], 'i');
    
    if (!$restriction) {
        throw new Exception('Restriction not found');
    }
    
    echo json_encode(['success' => true, 'restriction' => $restriction]);
}

function createRestriction() {
    $restrictionType = $_POST['restriction_type'] ?? '';
    $restrictionValue = $_POST['restriction_value'] ?? '';
    $restrictionName = $_POST['restriction_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $appliesTo = $_POST['applies_to'] ?? 'all';
    $targetId = $_POST['target_id'] ?? null;
    $createdBy = $_SESSION['admin_user_id'];
    
    // Validation
    if (empty($restrictionType) || empty($restrictionValue) || empty($restrictionName)) {
        throw new Exception('Restriction type, value, and name are required');
    }
    
    // Check for duplicates
    $existing = fetchRow(
        "SELECT id FROM attendance_restrictions WHERE restriction_type = ? AND restriction_value = ? AND applies_to = ? AND COALESCE(target_id, 0) = COALESCE(?, 0)",
        [$restrictionType, $restrictionValue, $appliesTo, $targetId],
        'sssi'
    );
    
    if ($existing) {
        throw new Exception('A similar restriction already exists');
    }
    
    $query = "INSERT INTO attendance_restrictions 
              (restriction_type, restriction_value, restriction_name, description, start_date, end_date, is_active, applies_to, target_id, created_by) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = executeQuery($query, [
        $restrictionType, $restrictionValue, $restrictionName, $description, 
        $startDate, $endDate, $isActive, $appliesTo, $targetId, $createdBy
    ], 'ssssssisii');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Restriction created successfully']);
    } else {
        throw new Exception('Failed to create restriction');
    }
}

function updateRestriction() {
    $id = $_POST['id'] ?? 0;
    $restrictionType = $_POST['restriction_type'] ?? '';
    $restrictionValue = $_POST['restriction_value'] ?? '';
    $restrictionName = $_POST['restriction_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $appliesTo = $_POST['applies_to'] ?? 'all';
    $targetId = $_POST['target_id'] ?? null;
    
    if (!$id) {
        throw new Exception('Restriction ID is required');
    }
    
    if (empty($restrictionType) || empty($restrictionValue) || empty($restrictionName)) {
        throw new Exception('Restriction type, value, and name are required');
    }
    
    $query = "UPDATE attendance_restrictions 
              SET restriction_type = ?, restriction_value = ?, restriction_name = ?, description = ?, 
                  start_date = ?, end_date = ?, is_active = ?, applies_to = ?, target_id = ?
              WHERE id = ?";
    
    $result = executeQuery($query, [
        $restrictionType, $restrictionValue, $restrictionName, $description, 
        $startDate, $endDate, $isActive, $appliesTo, $targetId, $id
    ], 'ssssssisii');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Restriction updated successfully']);
    } else {
        throw new Exception('Failed to update restriction');
    }
}

function toggleRestriction() {
    $id = $_POST['id'] ?? 0;
    $isActive = $_POST['is_active'] ?? 0;
    
    if (!$id) {
        throw new Exception('Restriction ID is required');
    }
    
    $query = "UPDATE attendance_restrictions SET is_active = ? WHERE id = ?";
    $result = executeQuery($query, [$isActive, $id], 'ii');
    
    if ($result) {
        $status = $isActive ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => "Restriction $status successfully"]);
    } else {
        throw new Exception('Failed to toggle restriction');
    }
}

function deleteRestriction() {
    $id = $_POST['id'] ?? 0;
    
    if (!$id) {
        throw new Exception('Restriction ID is required');
    }
    
    // Don't allow deletion of the default Sunday restriction
    $restriction = fetchRow("SELECT restriction_type, restriction_value FROM attendance_restrictions WHERE id = ?", [$id], 'i');
    if ($restriction && $restriction['restriction_type'] == 'day_of_week' && $restriction['restriction_value'] == 'Sunday') {
        throw new Exception('Cannot delete the default Sunday restriction. You can deactivate it instead.');
    }
    
    $query = "DELETE FROM attendance_restrictions WHERE id = ?";
    $result = executeQuery($query, [$id], 'i');
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Restriction deleted successfully']);
    } else {
        throw new Exception('Failed to delete restriction');
    }
}
?>
