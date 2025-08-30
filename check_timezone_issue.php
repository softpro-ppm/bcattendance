<?php
/**
 * Timezone and Attendance Date Diagnostic Script
 * This script checks for timezone mismatches and attendance date issues
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "🔍 TIMEZONE AND ATTENDANCE DATE DIAGNOSTIC\n";
echo "=========================================\n\n";

try {
    // Check current server timezone settings
    echo "⏰ TIMEZONE INFORMATION:\n";
    echo "------------------------\n";
    echo "PHP Default Timezone: " . date_default_timezone_get() . "\n";
    echo "Current PHP Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Current PHP Date: " . date('Y-m-d') . "\n";
    
    // Check database timezone
    $dbTimezone = fetchRow("SELECT @@global.time_zone as global_tz, @@session.time_zone as session_tz, NOW() as db_time, CURDATE() as db_date");
    if ($dbTimezone) {
        echo "Database Global Timezone: " . $dbTimezone['global_tz'] . "\n";
        echo "Database Session Timezone: " . $dbTimezone['session_tz'] . "\n";
        echo "Database Current Time: " . $dbTimezone['db_time'] . "\n";
        echo "Database Current Date: " . $dbTimezone['db_date'] . "\n";
    }
    
    // Check attendance records for today and yesterday
    echo "\n📅 ATTENDANCE DATE ANALYSIS:\n";
    echo "----------------------------\n";
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Today (PHP): " . $today . "\n";
    echo "Yesterday (PHP): " . $yesterday . "\n\n";
    
    // Check attendance for today
    $todayAttendance = fetchAll("
        SELECT 
            COUNT(*) as total_records,
            MIN(created_at) as earliest_created,
            MAX(created_at) as latest_created,
            MIN(updated_at) as earliest_updated,
            MAX(updated_at) as latest_updated
        FROM attendance 
        WHERE attendance_date = ?
    ", [$today], 's');
    
    if (!empty($todayAttendance)) {
        $todayData = $todayAttendance[0];
        echo "📊 TODAY'S ATTENDANCE (" . $today . "):\n";
        echo "Total Records: " . $todayData['total_records'] . "\n";
        echo "Earliest Created: " . $todayData['earliest_created'] . "\n";
        echo "Latest Created: " . $todayData['latest_created'] . "\n";
        echo "Earliest Updated: " . $todayData['earliest_updated'] . "\n";
        echo "Latest Updated: " . $todayData['latest_updated'] . "\n\n";
    }
    
    // Check attendance for yesterday
    $yesterdayAttendance = fetchAll("
        SELECT 
            COUNT(*) as total_records,
            MIN(created_at) as earliest_created,
            MAX(created_at) as latest_created,
            MIN(updated_at) as earliest_updated,
            MAX(updated_at) as latest_updated
        FROM attendance 
        WHERE attendance_date = ?
    ", [$yesterday], 's');
    
    if (!empty($yesterdayAttendance)) {
        $yesterdayData = $yesterdayAttendance[0];
        echo "📊 YESTERDAY'S ATTENDANCE (" . $yesterday . "):\n";
        echo "Total Records: " . $yesterdayData['total_records'] . "\n";
        echo "Earliest Created: " . $yesterdayData['earliest_created'] . "\n";
        echo "Latest Created: " . $yesterdayData['latest_created'] . "\n";
        echo "Earliest Updated: " . $yesterdayData['earliest_updated'] . "\n";
        echo "Latest Updated: " . $yesterdayData['latest_updated'] . "\n\n";
    }
    
    // Check for records with mismatched dates
    echo "🔍 DATE MISMATCH ANALYSIS:\n";
    echo "--------------------------\n";
    
    $mismatchedRecords = fetchAll("
        SELECT 
            attendance_date,
            DATE(created_at) as created_date,
            DATE(updated_at) as updated_date,
            COUNT(*) as record_count
        FROM attendance 
        WHERE attendance_date != DATE(created_at) 
           OR attendance_date != DATE(updated_at)
        GROUP BY attendance_date, DATE(created_at), DATE(updated_at)
        ORDER BY attendance_date DESC
    ");
    
    if (!empty($mismatchedRecords)) {
        echo "❌ FOUND RECORDS WITH DATE MISMATCHES:\n";
        foreach ($mismatchedRecords as $record) {
            echo "Attendance Date: " . $record['attendance_date'] . "\n";
            echo "Created Date: " . $record['created_date'] . "\n";
            echo "Updated Date: " . $record['updated_date'] . "\n";
            echo "Record Count: " . $record['record_count'] . "\n";
            echo "---\n";
        }
    } else {
        echo "✅ No date mismatches found\n";
    }
    
    // Check recent attendance records with details
    echo "\n📋 RECENT ATTENDANCE RECORDS (Last 10):\n";
    echo "--------------------------------------\n";
    
    $recentRecords = fetchAll("
        SELECT 
            id,
            beneficiary_id,
            attendance_date,
            status,
            created_at,
            updated_at
        FROM attendance 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    if (!empty($recentRecords)) {
        foreach ($recentRecords as $record) {
            echo sprintf("ID: %d | Date: %s | Status: '%s' | Created: %s | Updated: %s\n",
                $record['id'],
                $record['attendance_date'],
                $record['status'],
                $record['created_at'],
                $record['updated_at']
            );
        }
    }
    
    // Check TC user login times
    echo "\n👥 TC USER LOGIN ANALYSIS:\n";
    echo "-------------------------\n";
    
    $tcLogins = fetchAll("
        SELECT 
            tc_id,
            full_name,
            last_login,
            DATE(last_login) as login_date,
            TIME(last_login) as login_time
        FROM tc_users 
        WHERE last_login IS NOT NULL
        ORDER BY last_login DESC
        LIMIT 10
    ");
    
    if (!empty($tcLogins)) {
        foreach ($tcLogins as $login) {
            echo sprintf("TC ID: %s | Name: %s | Login: %s | Date: %s | Time: %s\n",
                $login['tc_id'],
                $login['full_name'],
                $login['last_login'],
                $login['login_date'],
                $login['login_time']
            );
        }
    }
    
    // Check if there are attendance records that should be for yesterday but are marked as today
    echo "\n⚠️  POTENTIAL TIMEZONE ISSUE CHECK:\n";
    echo "----------------------------------\n";
    
    $potentialIssue = fetchAll("
        SELECT 
            COUNT(*) as record_count,
            MIN(created_at) as earliest_time,
            MAX(created_at) as latest_time
        FROM attendance 
        WHERE attendance_date = ?
        AND HOUR(created_at) >= 18  -- Records created after 6 PM
    ", [$today], 's');
    
    if (!empty($potentialIssue) && $potentialIssue[0]['record_count'] > 0) {
        echo "❌ POTENTIAL TIMEZONE ISSUE DETECTED:\n";
        echo "Found " . $potentialIssue[0]['record_count'] . " records for today created after 6 PM\n";
        echo "Earliest: " . $potentialIssue[0]['earliest_time'] . "\n";
        echo "Latest: " . $potentialIssue[0]['latest_time'] . "\n";
        echo "\n💡 These records were likely submitted yesterday evening but marked as today's date\n";
    } else {
        echo "✅ No obvious timezone issues detected\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 DIAGNOSTIC COMPLETE\n";
?>
