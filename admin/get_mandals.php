<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if constituency_id is provided
if (!isset($_GET['constituency_id']) || empty($_GET['constituency_id'])) {
    echo json_encode(['error' => 'Constituency ID is required']);
    exit();
}

$constituencyId = (int)$_GET['constituency_id'];

try {
    // Get mandals for the selected constituency
    $query = "SELECT id, name FROM mandals WHERE constituency_id = ? AND status = 'active' ORDER BY name";
    $mandals = fetchAll($query, [$constituencyId], 'i');
    
    // Format response
    $response = [
        'success' => true,
        'mandals' => $mandals
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
