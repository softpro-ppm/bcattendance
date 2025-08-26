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
$constituencyId = isset($_GET['constituency_id']) && !empty($_GET['constituency_id']) ? (int)$_GET['constituency_id'] : null;

try {
    if ($constituencyId) {
        // Get mandals for the selected constituency
        $query = "SELECT id, name FROM mandals WHERE constituency_id = ? AND status = 'active' ORDER BY name";
        $mandals = fetchAll($query, [$constituencyId], 'i');
    } else {
        // Get all mandals
        $query = "SELECT id, name FROM mandals WHERE status = 'active' ORDER BY name";
        $mandals = fetchAll($query);
    }
    
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
