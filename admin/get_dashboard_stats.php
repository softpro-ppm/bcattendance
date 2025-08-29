<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get dashboard statistics
    $stats = getDashboardStats();
    
    // Get batch attendance marking status for today
    $batchMarkingStatus = fetchAll("
        SELECT 
            bt.id as batch_id,
            bt.name as batch_name,
            c.name as constituency_name,
            m.name as mandal_name,
            COUNT(b.id) as total_beneficiaries,
            COUNT(CASE WHEN b.status = 'active' THEN 1 END) as active_beneficiaries,
            COUNT(a.id) as marked_attendance,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            CASE 
                WHEN COUNT(a.id) > 0 THEN 'submitted'
                ELSE 'pending'
            END as status,
            MAX(a.created_at) as last_marked_time
        FROM batches bt
        LEFT JOIN mandals m ON bt.mandal_id = m.id
        LEFT JOIN constituencies c ON m.constituency_id = c.id
        LEFT JOIN beneficiaries b ON bt.id = b.batch_id
        LEFT JOIN attendance a ON b.id = a.beneficiary_id AND a.attendance_date = CURDATE()
        WHERE bt.status IN ('active', 'completed')
        GROUP BY bt.id, bt.name, c.name, m.name
        ORDER BY 
            CASE WHEN COUNT(a.id) > 0 THEN 0 ELSE 1 END,
            MAX(a.created_at) DESC,
            bt.name
        LIMIT 10
    ");

    // Calculate attendance percentages based on WORKING students only (excluding holidays)
    $activeStudentsToday = $stats['active_students_today'];
    $workingStudentsToday = $stats['working_students_today'];
    $presentToday = $stats['present_today'];
    $absentToday = $stats['absent_today'];
    $holidaysToday = $stats['holidays_today'];
    
    // Calculate percentages based on working days only
    $presentPercentage = $workingStudentsToday > 0 ? round(($presentToday / $workingStudentsToday) * 100, 1) : 0;
    $absentPercentage = $workingStudentsToday > 0 ? round(($absentToday / $workingStudentsToday) * 100, 1) : 0;

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_beneficiaries' => $stats['total_beneficiaries'],
            'total_constituencies' => $stats['total_constituencies'],
            'total_mandals' => $stats['total_mandals'],
            'total_batches' => $stats['total_batches'],
            'active_students_today' => $activeStudentsToday,
            'working_students_today' => $workingStudentsToday,
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'holidays_today' => $holidaysToday,
            'today_attendance' => $stats['today_attendance'],
            'present_percentage' => $presentPercentage,
            'absent_percentage' => $absentPercentage
        ],
        'batchStatus' => $batchMarkingStatus,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
