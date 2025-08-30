<?php
/**
 * Dashboard Diagnostic - Add this to your existing dashboard temporarily
 * This will help identify the attendance date issue
 */

// Add this code to your admin/dashboard.php file temporarily
// Place it after the existing dashboard content, before the closing </div>

echo '<div class="row mt-4">';
echo '<div class="col-12">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5 class="card-title"><i class="fas fa-bug"></i> Attendance Date Diagnostic</h5>';
echo '</div>';
echo '<div class="card-body">';

try {
    // Check current timezone and date
    echo '<h6>⏰ Current Time Info:</h6>';
    echo '<p><strong>PHP Timezone:</strong> ' . date_default_timezone_get() . '</p>';
    echo '<p><strong>Current PHP Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    echo '<p><strong>Current PHP Date:</strong> ' . date('Y-m-d') . '</p>';
    
    // Check database time
    $dbTime = fetchRow("SELECT NOW() as db_time, CURDATE() as db_date");
    if ($dbTime) {
        echo '<p><strong>Database Time:</strong> ' . $dbTime['db_time'] . '</p>';
        echo '<p><strong>Database Date:</strong> ' . $dbTime['db_date'] . '</p>';
    }
    
    echo '<hr>';
    
    // Check today's attendance records
    $today = date('Y-m-d');
    $todayRecords = fetchRow("
        SELECT 
            COUNT(*) as total,
            MIN(created_at) as earliest,
            MAX(created_at) as latest,
            COUNT(CASE WHEN HOUR(created_at) >= 18 THEN 1 END) as evening_records
        FROM attendance 
        WHERE attendance_date = ?
    ", [$today], 's');
    
    echo '<h6>📊 Today\'s Attendance (' . $today . '):</h6>';
    echo '<p><strong>Total Records:</strong> ' . ($todayRecords['total'] ?? 0) . '</p>';
    echo '<p><strong>Earliest Created:</strong> ' . ($todayRecords['earliest'] ?? 'N/A') . '</p>';
    echo '<p><strong>Latest Created:</strong> ' . ($todayRecords['latest'] ?? 'N/A') . '</p>';
    echo '<p><strong>Evening Records (after 6 PM):</strong> ' . ($todayRecords['evening_records'] ?? 0) . '</p>';
    
    echo '<hr>';
    
    // Check yesterday's attendance records
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $yesterdayRecords = fetchRow("
        SELECT 
            COUNT(*) as total,
            MIN(created_at) as earliest,
            MAX(created_at) as latest
        FROM attendance 
        WHERE attendance_date = ?
    ", [$yesterday], 's');
    
    echo '<h6>📊 Yesterday\'s Attendance (' . $yesterday . '):</h6>';
    echo '<p><strong>Total Records:</strong> ' . ($yesterdayRecords['total'] ?? 0) . '</p>';
    echo '<p><strong>Earliest Created:</strong> ' . ($yesterdayRecords['earliest'] ?? 'N/A') . '</p>';
    echo '<p><strong>Latest Created:</strong> ' . ($yesterdayRecords['latest'] ?? 'N/A') . '</p>';
    
    echo '<hr>';
    
    // Check if there are records that should be moved
    $misplacedRecords = fetchRow("
        SELECT COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = ?
        AND HOUR(created_at) >= 18
    ", [$today], 's');
    
    if (($misplacedRecords['count'] ?? 0) > 0) {
        echo '<div class="alert alert-warning">';
        echo '<h6>⚠️ Potential Issue Detected:</h6>';
        echo '<p>Found ' . ($misplacedRecords['count'] ?? 0) . ' records for today created after 6 PM.</p>';
        echo '<p>These might need to be moved to yesterday\'s date.</p>';
        echo '</div>';
        
        // Show sample of these records
        $sampleRecords = fetchAll("
            SELECT 
                id,
                beneficiary_id,
                attendance_date,
                status,
                created_at
            FROM attendance 
            WHERE attendance_date = ?
            AND HOUR(created_at) >= 18
            LIMIT 3
        ", [$today], 's');
        
        if (!empty($sampleRecords)) {
            echo '<h6>📋 Sample Records That Might Need Fixing:</h6>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm table-bordered">';
            echo '<thead><tr><th>ID</th><th>Date</th><th>Status</th><th>Created</th></tr></thead>';
            echo '<tbody>';
            foreach ($sampleRecords as $record) {
                echo '<tr>';
                echo '<td>' . $record['id'] . '</td>';
                echo '<td>' . $record['attendance_date'] . '</td>';
                echo '<td>' . $record['status'] . '</td>';
                echo '<td>' . $record['created_at'] . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table></div>';
        }
    } else {
        echo '<div class="alert alert-success">';
        echo '<h6>✅ No Obvious Date Issues Detected</h6>';
        echo '<p>All attendance records appear to have correct dates.</p>';
        echo '</div>';
    }
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">';
    echo '<h6>❌ Error:</h6>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '</div>';
}

echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // col-12
echo '</div>'; // row

// Remove this diagnostic section after you've identified and fixed the issue
?>
