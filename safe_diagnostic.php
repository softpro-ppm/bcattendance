<?php
/**
 * SAFE DIAGNOSTIC SCRIPT - READ ONLY
 * This script only reads data and does NOT make any changes
 * Safe to run on live production site
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "🔍 SAFE DIAGNOSTIC - READ ONLY\n";
echo "==============================\n\n";

try {
    // Check current timezone and date
    echo "⏰ CURRENT TIME INFO:\n";
    echo "--------------------\n";
    echo "PHP Timezone: " . date_default_timezone_get() . "\n";
    echo "Current PHP Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Current PHP Date: " . date('Y-m-d') . "\n\n";
    
    // Check database time
    $dbTime = fetchRow("SELECT NOW() as db_time, CURDATE() as db_date");
    if ($dbTime) {
        echo "Database Time: " . $dbTime['db_time'] . "\n";
        echo "Database Date: " . $dbTime['db_date'] . "\n\n";
    }
    
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
    
    echo "📊 TODAY'S ATTENDANCE (" . $today . "):\n";
    echo "Total Records: " . ($todayRecords['total'] ?? 0) . "\n";
    echo "Earliest Created: " . ($todayRecords['earliest'] ?? 'N/A') . "\n";
    echo "Latest Created: " . ($todayRecords['latest'] ?? 'N/A') . "\n";
    echo "Evening Records (after 6 PM): " . ($todayRecords['evening_records'] ?? 0) . "\n\n";
    
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
    
    echo "📊 YESTERDAY'S ATTENDANCE (" . $yesterday . "):\n";
    echo "Total Records: " . ($yesterdayRecords['total'] ?? 0) . "\n";
    echo "Earliest Created: " . ($yesterdayRecords['earliest'] ?? 'N/A') . "\n";
    echo "Latest Created: " . ($yesterdayRecords['latest'] ?? 'N/A') . "\n\n";
    
    // Check status distribution for today
    $todayStatus = fetchAll("
        SELECT status, COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = ?
        GROUP BY status
        ORDER BY count DESC
    ", [$today], 's');
    
    if (!empty($todayStatus)) {
        echo "📋 TODAY'S STATUS DISTRIBUTION:\n";
        foreach ($todayStatus as $status) {
            echo $status['status'] . ": " . $status['count'] . " records\n";
        }
        echo "\n";
    }
    
    // Check if there are records that should be moved
    $misplacedRecords = fetchRow("
        SELECT COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = ?
        AND HOUR(created_at) >= 18
    ", [$today], 's');
    
    if (($misplacedRecords['count'] ?? 0) > 0) {
        echo "⚠️  POTENTIAL ISSUE DETECTED:\n";
        echo "Found " . ($misplacedRecords['count'] ?? 0) . " records for today created after 6 PM\n";
        echo "These might need to be moved to yesterday's date\n\n";
        
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
        
        echo "📋 SAMPLE RECORDS THAT MIGHT NEED FIXING:\n";
        foreach ($sampleRecords as $record) {
            echo "ID: " . $record['id'] . " | Date: " . $record['attendance_date'] . " | Status: " . $record['status'] . " | Created: " . $record['created_at'] . "\n";
        }
        echo "\n";
    } else {
        echo "✅ No obvious date issues detected\n\n";
    }
    
    // Check TC user activity
    echo "👥 TC USER ACTIVITY:\n";
    echo "-------------------\n";
    
    $tcActivity = fetchAll("
        SELECT 
            tc_id,
            full_name,
            last_login,
            DATE(last_login) as login_date,
            TIME(last_login) as login_time
        FROM tc_users 
        WHERE last_login IS NOT NULL
        ORDER BY last_login DESC
        LIMIT 5
    ");
    
    if (!empty($tcActivity)) {
        foreach ($tcActivity as $activity) {
            echo $activity['tc_id'] . " (" . $activity['full_name'] . "): " . $activity['last_login'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 DIAGNOSTIC COMPLETE - NO CHANGES MADE\n";
echo "========================================\n";
echo "This script only read data and did not modify anything.\n";
?>
