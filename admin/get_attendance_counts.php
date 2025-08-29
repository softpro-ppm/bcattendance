<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get filters from request
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedConstituency = $_GET['constituency'] ?? '';
$selectedMandal = $_GET['mandal'] ?? '';
$selectedBatch = $_GET['batch'] ?? '';

try {
    // Build WHERE clause for filters
    $whereConditions = ["b.status = 'active'"];
    $params = [];
    $types = '';

    if (!empty($selectedConstituency)) {
        $whereConditions[] = "b.constituency_id = ?";
        $params[] = $selectedConstituency;
        $types .= 'i';
    }

    if (!empty($selectedMandal)) {
        $whereConditions[] = "b.mandal_id = ?";
        $params[] = $selectedMandal;
        $types .= 'i';
    }

    if (!empty($selectedBatch)) {
        $whereConditions[] = "b.batch_id = ?";
        $params[] = $selectedBatch;
        $types .= 'i';
    }

    $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    // Get total counts with attendance status for the selected date
    $query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN a.status = 'present' OR a.status = 'P' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN a.status = 'absent' OR a.status = 'A' OR a.status IS NULL THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN b.status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN b.status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM beneficiaries b
    LEFT JOIN constituencies c ON b.constituency_id = c.id
    LEFT JOIN mandals m ON b.mandal_id = m.id
    LEFT JOIN batches bt ON b.batch_id = bt.id
    LEFT JOIN attendance a ON b.id = a.beneficiary_id AND a.attendance_date = ?
    $whereClause";

    $allParams = [$selectedDate];
    $allParams = array_merge($allParams, $params);
    $allTypes = 's' . $types;

    $result = fetchRow($query, $allParams, $allTypes);

    // Ensure we have valid counts
    $total = (int)($result['total'] ?? 0);
    $present = (int)($result['present'] ?? 0);
    $absent = (int)($result['absent'] ?? 0);
    $active = (int)($result['active'] ?? 0);
    $inactive = (int)($result['inactive'] ?? 0);
    
    // If absent count seems wrong, calculate it properly
    if ($present + $absent > $total) {
        $absent = $total - $present;
    }

    // Calculate attendance rate
    $rate = $total > 0 ? round(($present / $total) * 100) : 0;

    // Format response
    $response = [
        'success' => true,
        'counts' => [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'present' => $present,
            'absent' => $absent,
            'rate' => $rate
        ],
        'filters' => [
            'date' => $selectedDate,
            'constituency' => $selectedConstituency,
            'mandal' => $selectedMandal,
            'batch' => $selectedBatch
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
