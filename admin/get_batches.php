<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if mandal_id is provided
$mandalId = isset($_GET['mandal_id']) && !empty($_GET['mandal_id']) ? (int)$_GET['mandal_id'] : null;

try {
    if ($mandalId) {
        // Get batches for the selected mandal
        $query = "SELECT id, name, code FROM batches WHERE mandal_id = ? AND status IN ('active', 'completed') ORDER BY status DESC, name";
        $batches = fetchAll($query, [$mandalId], 'i');
    } else {
        // Get all batches
        $query = "SELECT id, name, code FROM batches WHERE status IN ('active', 'completed') ORDER BY status DESC, name";
        $batches = fetchAll($query);
    }
    
    // Format response
    $response = [
        'success' => true,
        'batches' => $batches
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
