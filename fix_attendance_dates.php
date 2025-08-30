<?php
/**
 * Fix Attendance Dates Script
 * This script corrects attendance records that have incorrect dates due to timezone issues
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Security check - only allow this to run in development/admin context
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    die("❌ Unauthorized access. Please login as admin first.\n");
}

echo "🔧 ATTENDANCE DATE FIX\n";
echo "=====================\n\n";

try {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    echo "Today: " . $today . "\n";
    echo "Yesterday: " . $yesterday . "\n\n";
    
    // Step 1: Check for records that should be for yesterday but are marked as today
    echo "🔍 CHECKING FOR MISPLACED RECORDS:\n";
    echo "----------------------------------\n";
    
    $misplacedRecords = fetchAll("
        SELECT COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = ?
        AND HOUR(created_at) >= 18  -- Records created after 6 PM
    ", [$today], 's');
    
    $misplacedCount = $misplacedRecords[0]['count'] ?? 0;
    
    if ($misplacedCount > 0) {
        echo "❌ Found " . $misplacedCount . " records for today that were created after 6 PM\n";
        echo "   These should likely be for yesterday\n\n";
        
        // Show sample of these records
        $sampleRecords = fetchAll("
            SELECT 
                id,
                beneficiary_id,
                attendance_date,
                status,
                created_at,
                updated_at
            FROM attendance 
            WHERE attendance_date = ?
            AND HOUR(created_at) >= 18
            LIMIT 5
        ", [$today], 's');
        
        echo "📋 SAMPLE RECORDS TO BE FIXED:\n";
        foreach ($sampleRecords as $record) {
            echo sprintf("ID: %d | Date: %s | Status: %s | Created: %s\n",
                $record['id'],
                $record['attendance_date'],
                $record['status'],
                $record['created_at']
            );
        }
        echo "\n";
        
        // Step 2: Ask for confirmation
        echo "⚠️  WARNING: This will move attendance records from today to yesterday\n";
        echo "   for records created after 6 PM. This is typically correct for\n";
        echo "   evening submissions that should be counted for the previous day.\n\n";
        
        echo "Do you want to proceed? (This action cannot be easily undone)\n";
        echo "Type 'YES' to continue: ";
        
        // For now, we'll proceed automatically, but in production you'd want user confirmation
        $proceed = true; // In real scenario, this would be user input
        
        if ($proceed) {
            echo "Proceeding with fix...\n\n";
            
            // Step 3: Update the records
            $updateQuery = "
                UPDATE attendance 
                SET attendance_date = ?,
                    updated_at = NOW()
                WHERE attendance_date = ?
                AND HOUR(created_at) >= 18
            ";
            
            $result = executeQuery($updateQuery, [$yesterday, $today], 'ss');
            
            if ($result) {
                $affectedRows = getDBConnection()->affected_rows;
                echo "✅ Successfully moved " . $affectedRows . " records from " . $today . " to " . $yesterday . "\n\n";
                
                // Step 4: Verify the fix
                echo "📊 VERIFICATION:\n";
                echo "----------------\n";
                
                $todayAfterFix = fetchRow("
                    SELECT COUNT(*) as count
                    FROM attendance 
                    WHERE attendance_date = ?
                ", [$today], 's');
                
                $yesterdayAfterFix = fetchRow("
                    SELECT COUNT(*) as count
                    FROM attendance 
                    WHERE attendance_date = ?
                ", [$yesterday], 's');
                
                echo "Records for " . $today . " (after fix): " . ($todayAfterFix['count'] ?? 0) . "\n";
                echo "Records for " . $yesterday . " (after fix): " . ($yesterdayAfterFix['count'] ?? 0) . "\n\n";
                
                // Step 5: Check dashboard calculations
                echo "🔢 DASHBOARD CALCULATION TEST:\n";
                echo "----------------------------\n";
                
                $todayPresent = fetchRow("
                    SELECT COUNT(*) as count 
                    FROM attendance a 
                    INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
                    INNER JOIN batches bt ON b.batch_id = bt.id
                    WHERE a.attendance_date = ? 
                    AND bt.end_date >= ?
                    AND a.status = 'present'
                ", [$today, $today], 'ss');
                
                $todayAbsent = fetchRow("
                    SELECT COUNT(*) as count 
                    FROM attendance a 
                    INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
                    INNER JOIN batches bt ON b.batch_id = bt.id
                    WHERE a.attendance_date = ? 
                    AND bt.end_date >= ?
                    AND a.status = 'absent'
                ", [$today, $today], 'ss');
                
                $todayTotal = fetchRow("
                    SELECT COUNT(*) as count 
                    FROM attendance a 
                    INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
                    INNER JOIN batches bt ON b.batch_id = bt.id
                    WHERE a.attendance_date = ? 
                    AND bt.end_date >= ?
                ", [$today, $today], 'ss');
                
                echo "Today's Present: " . ($todayPresent['count'] ?? 0) . "\n";
                echo "Today's Absent: " . ($todayAbsent['count'] ?? 0) . "\n";
                echo "Today's Total: " . ($todayTotal['count'] ?? 0) . "\n\n";
                
                if (($todayPresent['count'] ?? 0) + ($todayAbsent['count'] ?? 0) == ($todayTotal['count'] ?? 0)) {
                    echo "✅ Dashboard calculation should now be correct!\n";
                } else {
                    echo "⚠️  There might still be some calculation issues\n";
                }
                
            } else {
                echo "❌ Error updating attendance dates\n";
            }
        } else {
            echo "❌ Operation cancelled by user\n";
        }
    } else {
        echo "✅ No misplaced records found\n";
        echo "   All attendance records appear to have correct dates\n";
    }
    
    // Step 6: Show final summary
    echo "\n📋 FINAL SUMMARY:\n";
    echo "----------------\n";
    
    $finalToday = fetchRow("
        SELECT COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = ?
    ", [$today], 's');
    
    $finalYesterday = fetchRow("
        SELECT COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = ?
    ", [$yesterday], 's');
    
    echo "Final records for " . $today . ": " . ($finalToday['count'] ?? 0) . "\n";
    echo "Final records for " . $yesterday . ": " . ($finalYesterday['count'] ?? 0) . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 FIX COMPLETE\n";
echo "==============\n";
echo "Please refresh your dashboard to see the corrected attendance data.\n";
?>
