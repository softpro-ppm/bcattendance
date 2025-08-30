<?php
/**
 * Quick Fix for Attendance Status Mismatch
 * This script standardizes attendance status values to fix dashboard display issues
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

// Security check - only allow this to run in development/admin context
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    die("❌ Unauthorized access. Please login as admin first.\n");
}

echo "🔧 ATTENDANCE STATUS FIX\n";
echo "======================\n\n";

try {
    // Step 1: Check current status distribution
    echo "📊 BEFORE FIX - Current Status Distribution:\n";
    echo "-------------------------------------------\n";
    
    $beforeStats = fetchAll("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
        FROM attendance 
        GROUP BY status
        ORDER BY count DESC
    ");
    
    $totalRecords = 0;
    foreach ($beforeStats as $stat) {
        echo sprintf("%-15s: %6d records (%.2f%%)\n", 
            $stat['status'], $stat['count'], $stat['percentage']);
        $totalRecords += $stat['count'];
    }
    echo "\nTotal records: " . number_format($totalRecords) . "\n\n";
    
    // Step 2: Show what will be changed
    echo "🔄 STATUS MAPPING:\n";
    echo "------------------\n";
    echo "P → present\n";
    echo "A → absent\n";
    echo "H → absent (holiday treated as absent)\n";
    echo "late → present (late students are present)\n";
    echo "excused → absent (excused students are absent)\n\n";
    
    // Step 3: Perform the update
    echo "⚡ UPDATING ATTENDANCE STATUS...\n";
    echo "-------------------------------\n";
    
    $updateQuery = "
        UPDATE attendance 
        SET status = CASE 
            WHEN status = 'P' THEN 'present'
            WHEN status = 'A' THEN 'absent'
            WHEN status = 'H' THEN 'absent'
            WHEN status = 'late' THEN 'present'
            WHEN status = 'excused' THEN 'absent'
            ELSE status  -- Keep existing values that are already correct
        END,
        updated_at = NOW()
        WHERE status IN ('P', 'A', 'H', 'late', 'excused')
    ";
    
    $result = executeQuery($updateQuery);
    
    if ($result) {
        $affectedRows = getDBConnection()->affected_rows;
        echo "✅ Successfully updated " . number_format($affectedRows) . " records\n\n";
        
        // Step 4: Verify the fix
        echo "📊 AFTER FIX - New Status Distribution:\n";
        echo "--------------------------------------\n";
        
        $afterStats = fetchAll("
            SELECT 
                status,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
            FROM attendance 
            GROUP BY status
            ORDER BY count DESC
        ");
        
        foreach ($afterStats as $stat) {
            echo sprintf("%-15s: %6d records (%.2f%%)\n", 
                $stat['status'], $stat['count'], $stat['percentage']);
        }
        
        // Step 5: Check today's attendance specifically
        echo "\n📅 TODAY'S ATTENDANCE (2025-08-30) AFTER FIX:\n";
        echo "---------------------------------------------\n";
        
        $todayStats = fetchAll("
            SELECT 
                status,
                COUNT(*) as count
            FROM attendance 
            WHERE attendance_date = '2025-08-30'
            GROUP BY status
            ORDER BY count DESC
        ");
        
        if (!empty($todayStats)) {
            foreach ($todayStats as $stat) {
                echo sprintf("%-15s: %6d records\n", 
                    $stat['status'], $stat['count']);
            }
        } else {
            echo "No attendance records found for today.\n";
        }
        
        // Step 6: Test dashboard calculation
        echo "\n🔢 DASHBOARD CALCULATION TEST:\n";
        echo "-----------------------------\n";
        
        $presentCount = fetchRow("
            SELECT COUNT(*) as count 
            FROM attendance a 
            INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
            INNER JOIN batches bt ON b.batch_id = bt.id
            WHERE a.attendance_date = '2025-08-30' 
            AND bt.end_date >= '2025-08-30'
            AND a.status = 'present'
        ");
        
        $absentCount = fetchRow("
            SELECT COUNT(*) as count 
            FROM attendance a 
            INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
            INNER JOIN batches bt ON b.batch_id = bt.id
            WHERE a.attendance_date = '2025-08-30' 
            AND bt.end_date >= '2025-08-30'
            AND a.status = 'absent'
        ");
        
        $totalMarked = fetchRow("
            SELECT COUNT(*) as count 
            FROM attendance a 
            INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
            INNER JOIN batches bt ON b.batch_id = bt.id
            WHERE a.attendance_date = '2025-08-30' 
            AND bt.end_date >= '2025-08-30'
        ");
        
        echo "Present Count: " . ($presentCount['count'] ?? 0) . "\n";
        echo "Absent Count: " . ($absentCount['count'] ?? 0) . "\n";
        echo "Total Marked: " . ($totalMarked['count'] ?? 0) . "\n";
        
        if (($presentCount['count'] ?? 0) + ($absentCount['count'] ?? 0) == ($totalMarked['count'] ?? 0)) {
            echo "✅ Dashboard calculation should now work correctly!\n";
        } else {
            echo "⚠️  There might still be some records with unexpected status values\n";
        }
        
    } else {
        echo "❌ Error updating attendance status\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 FIX COMPLETE\n";
echo "==============\n";
echo "Please refresh your dashboard to see the corrected attendance counts.\n";
?>
