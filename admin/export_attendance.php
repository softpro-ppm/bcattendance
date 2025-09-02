<?php
// Export attendance for specific date and filters
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check authentication
if (!isset($_SESSION['admin_user_id'])) {
    die('Access denied');
}

// Get parameters
$date = $_GET['date'] ?? '';
$constituency = $_GET['constituency'] ?? '';
$mandal = $_GET['mandal'] ?? '';
$batch = $_GET['batch'] ?? '';

if (empty($date)) {
    die('Date parameter is required');
}

// Build query conditions
$whereConditions = ['a.attendance_date = ?'];
$params = [$date];
$types = 's';

if (!empty($constituency)) {
    $whereConditions[] = "c.id = ?";
    $params[] = $constituency;
    $types .= 'i';
}

if (!empty($mandal)) {
    $whereConditions[] = "m.id = ?";
    $params[] = $mandal;
    $types .= 'i';
}

if (!empty($batch)) {
    $whereConditions[] = "bt.id = ?";
    $params[] = $batch;
    $types .= 'i';
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// Query for attendance data (export ALL records, not paginated)
$query = "SELECT 
    a.attendance_date,
    b.beneficiary_id,
    b.full_name,
    b.mobile_number,
    c.name as constituency_name,
    m.name as mandal_name,
    bt.name as batch_name,
    a.status,
    a.created_at
FROM attendance a
JOIN beneficiaries b ON a.beneficiary_id = b.id
LEFT JOIN constituencies c ON b.constituency_id = c.id
LEFT JOIN mandals m ON b.mandal_id = m.id
LEFT JOIN batches bt ON b.batch_id = bt.id
$whereClause
ORDER BY b.full_name";

$data = fetchAll($query, $params, $types);

// Prepare CSV data
$headers = ['Date', 'Beneficiary ID', 'Name', 'Mobile', 'Constituency', 'Mandal', 'Batch', 'Status', 'Marked At'];

    $csvData = [];
    foreach ($data as $row) {
        // Enhanced holiday detection for single day export
        $status = $row['status'];
        $date = $row['attendance_date'];
        
        // Check if it should be marked as holiday
        $displayStatus = '';
        
        // First check if it's already marked as holiday
        if (strtoupper($status) === 'H' || strtoupper($status) === 'HOLIDAY') {
            $displayStatus = 'Holiday';
        }
        // Check if it's Sunday (should always be holiday)
        elseif (date('N', strtotime($date)) == 7) {
            $displayStatus = 'Holiday';
        }
        // Check if it's a custom holiday
        else {
            // Check if it's a batch-specific holiday first
            $batchHolidayCheck = fetchRow("SELECT id FROM batch_holidays WHERE holiday_date = ? AND batch_id = ?", 
                                         [$date, $row['batch_id']]);
            if ($batchHolidayCheck) {
                $displayStatus = 'Holiday';
            }
            // Check if it's an all-mandals holiday (no batch-specific entries)
            else {
                $holidayCheck = fetchRow("SELECT h.id FROM holidays h 
                                        LEFT JOIN batch_holidays bh ON h.date = bh.holiday_date 
                                        WHERE h.date = ? AND bh.id IS NULL", [$date]);
                if ($holidayCheck) {
                    $displayStatus = 'Holiday';
                }
                // If not a holiday, use the original status
                else {
                    $displayStatus = formatStatusDisplay($status);
                }
            }
        }
        
        $csvData[] = [
            formatDate($row['attendance_date'], 'd/m/Y'),
            $row['beneficiary_id'],
            $row['full_name'],
            $row['mobile_number'],
            $row['constituency_name'] ?? 'N/A',
            $row['mandal_name'] ?? 'N/A',
            $row['batch_name'] ?? 'N/A',
            $displayStatus,
            formatDateTime($row['created_at'], 'd/m/Y H:i')
        ];
    }

// Generate filename
$filename = 'attendance_' . str_replace('-', '', $date);
if (!empty($batch)) {
    $batchName = fetchRow("SELECT name FROM batches WHERE id = ?", [$batch], 'i');
    if ($batchName) {
        $filename .= '_' . str_replace(' ', '_', $batchName['name']);
    }
}
$filename .= '_' . date('H-i-s') . '.csv';

// Export CSV
exportToCSV($filename, $csvData, $headers);
?>
