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
        case 'get_student_calendar':
            getStudentCalendar();
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
                a.beneficiary_id,
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
                COUNT(CASE WHEN a.status IN ('present', 'absent') THEN 1 END) as total_days
            FROM attendance a
            JOIN beneficiaries b2 ON a.beneficiary_id = b2.id
            JOIN batches bt2 ON b2.batch_id = bt2.id
            WHERE a.attendance_date BETWEEN bt2.start_date AND bt2.end_date
            AND a.status != 'holiday'  -- Exclude holidays from working days calculation
            AND NOT EXISTS (
                -- Exclude dates that are holidays for this specific batch
                SELECT 1 FROM batch_holidays bh 
                WHERE bh.batch_id = bt2.id 
                AND bh.holiday_date = a.attendance_date
            )
            GROUP BY a.beneficiary_id
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
    
    // Get statistics - Using the same logic as dashboard (based on batch end_date, not beneficiary status)
    $stats_query = "
        SELECT 
            COUNT(DISTINCT b.id) as total_students,
            COUNT(DISTINCT CASE WHEN bt.end_date >= CURDATE() THEN b.id END) as active_students,
            COUNT(DISTINCT CASE WHEN bt.end_date < CURDATE() THEN b.id END) as completed_students,
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
                a.beneficiary_id,
                COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present_days,
                COUNT(CASE WHEN a.status IN ('present', 'absent') THEN 1 END) as total_days
            FROM attendance a
            JOIN beneficiaries b2 ON a.beneficiary_id = b2.id
            JOIN batches bt2 ON b2.batch_id = bt2.id
            WHERE a.attendance_date BETWEEN bt2.start_date AND bt2.end_date
            AND a.status != 'holiday'  -- Exclude holidays from working days calculation
            AND NOT EXISTS (
                -- Exclude dates that are holidays for this specific batch
                SELECT 1 FROM batch_holidays bh 
                WHERE bh.batch_id = bt2.id 
                AND bh.holiday_date = a.attendance_date
            )
            GROUP BY a.beneficiary_id
        ) att ON b.id = att.beneficiary_id
        $where_clause
    ";
    
    $stats = fetchRow($stats_query, $params, $types);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats
    ]);
}

function getStudentCalendar() {
    $studentId = $_POST['student_id'] ?? '';
    $month = (int)($_POST['month'] ?? date('n'));
    $year = (int)($_POST['year'] ?? date('Y'));
    
    if (empty($studentId)) {
        echo json_encode(['success' => false, 'error' => 'Student ID is required']);
        return;
    }
    
    // Get student details
    $student = fetchRow("
        SELECT b.*, bt.name as batch_name, bt.start_date, bt.end_date
        FROM beneficiaries b
        JOIN batches bt ON b.batch_id = bt.id
        WHERE b.id = ?
    ", [$studentId], 'i');
    
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        return;
    }
    
    // Get attendance data for the month
    $startDate = date('Y-m-01', strtotime("$year-$month-01"));
    $endDate = date('Y-m-t', strtotime("$year-$month-01"));
    
    $attendanceQuery = "
        SELECT a.attendance_date, a.status
        FROM attendance a
        WHERE a.beneficiary_id = ? 
        AND a.attendance_date BETWEEN ? AND ?
        ORDER BY a.attendance_date
    ";
    
    $attendanceData = fetchAll($attendanceQuery, [$studentId, $startDate, $endDate], 'iss');
    
    // Convert to associative array for easy lookup
    $attendanceLookup = [];
    foreach ($attendanceData as $att) {
        $attendanceLookup[$att['attendance_date']] = $att['status'];
    }
    
    // Get holidays for the month
    $holidayQuery = "
        SELECT h.date, h.description, h.type
        FROM holidays h
        WHERE h.date BETWEEN ? AND ?
        AND (
            h.type = 'national' 
            OR EXISTS (
                SELECT 1 FROM batch_holidays bh 
                WHERE bh.holiday_id = h.id 
                AND bh.batch_id = ?
            )
        )
        ORDER BY h.date
    ";
    
    $holidays = fetchAll($holidayQuery, [$startDate, $endDate, $student['batch_id']], 'ssi');
    
    // Convert to associative array
    $holidayLookup = [];
    foreach ($holidays as $holiday) {
        $holidayLookup[$holiday['date']] = $holiday;
    }
    
    // Generate calendar data
    $calendar = generateCalendarData($month, $year, $attendanceLookup, $holidayLookup, $student);
    
    // Calculate summary
    $summary = calculateMonthlySummary($month, $year, $attendanceLookup, $holidayLookup, $student);
    
    echo json_encode([
        'success' => true,
        'calendar' => $calendar,
        'summary' => $summary
    ]);
}

function generateCalendarData($month, $year, $attendanceData, $holidays, $student) {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $firstDay = date('w', strtotime("$year-$month-01"));
    
    $calendar = [];
    $currentDate = 1;
    $currentWeek = [];
    
    // Add empty cells for days before the first day of the month
    for ($i = 0; $i < $firstDay; $i++) {
        $currentWeek[] = ['isCurrentMonth' => false];
    }
    
    // Add days of the month
    for ($day = 1; $day <= $daysInMonth; $day++) {
        if (count($currentWeek) == 7) {
            $calendar[] = $currentWeek;
            $currentWeek = [];
        }
        
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $attendanceInfo = getAttendanceStatus($date, $attendanceData, $holidays, $student);
        
        $currentWeek[] = [
            'isCurrentMonth' => true,
            'date' => $day,
            'class' => $attendanceInfo['class'],
            'tooltip' => $attendanceInfo['tooltip'],
            'statusIcon' => $attendanceInfo['statusIcon'],
            'status' => $attendanceInfo['status'],
            'holidayName' => $attendanceInfo['holidayName'] ?? null
        ];
    }
    
    // Fill the last week with empty cells if needed
    while (count($currentWeek) < 7) {
        $currentWeek[] = ['isCurrentMonth' => false];
    }
    
    if (!empty($currentWeek)) {
        $calendar[] = $currentWeek;
    }
    
    return $calendar;
}

function getAttendanceStatus($date, $attendanceData, $holidays, $student) {
    $dateStr = date('Y-m-d', strtotime($date));
    
    // Check if it's a holiday
    if (isset($holidays[$dateStr])) {
        return [
            'status' => 'holiday',
            'tooltip' => $holidays[$dateStr]['description'],
            'class' => 'holiday-cell',
            'statusIcon' => '<i class="fas fa-star text-warning"></i>',
            'holidayName' => $holidays[$dateStr]['description']
        ];
    }
    
    // Check if it's Sunday
    if (date('w', strtotime($dateStr)) == 0) {
        return [
            'status' => 'sunday',
            'tooltip' => 'Sunday Holiday',
            'class' => 'sunday-cell',
            'statusIcon' => '<i class="fas fa-church text-info"></i>',
            'holidayName' => null
        ];
    }
    
    // Check if it's within batch date range
    $batchStart = $student['start_date'];
    $batchEnd = $student['end_date'];
    
    if ($dateStr < $batchStart || $dateStr > $batchEnd) {
        return [
            'status' => 'outside_batch',
            'tooltip' => 'Outside batch period',
            'class' => 'outside-batch-cell',
            'statusIcon' => '',
            'holidayName' => null
        ];
    }
    
    // Check attendance
    if (isset($attendanceData[$dateStr])) {
        $status = $attendanceData[$dateStr];
        if ($status === 'present') {
            return [
                'status' => 'present',
                'tooltip' => 'Present',
                'class' => 'present-cell',
                'statusIcon' => '<i class="fas fa-check text-success"></i>',
                'holidayName' => null
            ];
        } elseif ($status === 'absent') {
            return [
                'status' => 'absent',
                'tooltip' => 'Absent',
                'class' => 'absent-cell',
                'statusIcon' => '<i class="fas fa-times text-danger"></i>',
                'holidayName' => null
            ];
        } elseif ($status === 'holiday') {
            return [
                'status' => 'holiday',
                'tooltip' => 'Holiday',
                'class' => 'holiday-cell',
                'statusIcon' => '<i class="fas fa-star text-warning"></i>',
                'holidayName' => 'Holiday'
            ];
        }
    }
    
    // No attendance marked
    return [
        'status' => 'not_marked',
        'tooltip' => 'No attendance marked',
        'class' => 'not-marked-cell',
        'statusIcon' => '',
        'holidayName' => null
    ];
}

function calculateMonthlySummary($month, $year, $attendanceData, $holidays, $student) {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    
    $presentCount = 0;
    $absentCount = 0;
    $holidayCount = 0;
    $sundayCount = 0;
    
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $attendanceInfo = getAttendanceStatus($date, $attendanceData, $holidays, $student);
        
        switch ($attendanceInfo['status']) {
            case 'present':
                $presentCount++;
                break;
            case 'absent':
                $absentCount++;
                break;
            case 'holiday':
                $holidayCount++;
                break;
            case 'sunday':
                $sundayCount++;
                break;
        }
    }
    
    $workingDays = $presentCount + $absentCount;
    $attendancePercentage = $workingDays > 0 ? round(($presentCount / $workingDays) * 100, 2) : 0;
    
    return [
        'present' => $presentCount,
        'absent' => $absentCount,
        'holiday' => $holidayCount,
        'sunday' => $sundayCount,
        'workingDays' => $workingDays,
        'attendancePercentage' => $attendancePercentage
    ];
}
?>
