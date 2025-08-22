<?php
/**
 * Attendance Status Standardization Migration Script
 * 
 * This script migrates all attendance status values from mixed formats
 * (P, A, H) to standardized format (present, absent)
 */

require_once 'config/database.php';

// Security check - only allow this to run in development/admin context
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    die("❌ Unauthorized access. Please login as admin first.\n");
}

echo "🔄 ATTENDANCE STATUS STANDARDIZATION MIGRATION\n";
echo "=============================================\n\n";

try {
    // Step 1: Check current status distribution
    echo "📊 CURRENT STATUS DISTRIBUTION:\n";
    echo "--------------------------------\n";
    
    $currentStats = fetchAll("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
        FROM attendance 
        GROUP BY status
        ORDER BY count DESC
    ");
    
    $totalRecords = 0;
    foreach ($currentStats as $stat) {
        echo sprintf("%-15s: %6d records (%.2f%%)\n", 
            $stat['status'], $stat['count'], $stat['percentage']);
        $totalRecords += $stat['count'];
    }
    echo "\nTotal records: " . number_format($totalRecords) . "\n\n";
    
    // Step 2: Show what will be changed
    echo "🔄 MIGRATION PREVIEW:\n";
    echo "--------------------\n";
    
    $toUpdate = fetchAll("
        SELECT 
            status as current_status,
            CASE 
                WHEN status = 'P' THEN 'present'
                WHEN status = 'A' THEN 'absent'
                WHEN status = 'H' THEN 'absent'
                ELSE status
            END as new_status,
            COUNT(*) as record_count
        FROM attendance 
        WHERE status IN ('P', 'A', 'H')
        GROUP BY status
    ");
    
    $totalToUpdate = 0;
    if (!empty($toUpdate)) {
        foreach ($toUpdate as $change) {
            echo sprintf("'%s' → '%s': %d records\n", 
                $change['current_status'], $change['new_status'], $change['record_count']);
            $totalToUpdate += $change['record_count'];
        }
        echo "\nTotal records to update: " . number_format($totalToUpdate) . "\n\n";
    } else {
        echo "✅ No records need updating - all statuses are already standardized!\n\n";
        exit(0);
    }
    
    // Step 3: Confirm migration
    if (php_sapi_name() === 'cli') {
        echo "⚠️  WARNING: This will modify your database!\n";
        echo "📋 Make sure you have a backup before proceeding.\n\n";
        echo "Continue with migration? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $confirm = trim(fgets($handle));
        fclose($handle);
        
        if (strtolower($confirm) !== 'y' && strtolower($confirm) !== 'yes') {
            echo "❌ Migration cancelled by user.\n";
            exit(0);
        }
    }
    
    // Step 4: Run the migration
    echo "🚀 RUNNING MIGRATION...\n";
    echo "----------------------\n";
    
    $updateQuery = "
        UPDATE attendance 
        SET status = CASE 
            WHEN status = 'P' THEN 'present'
            WHEN status = 'A' THEN 'absent'
            WHEN status = 'H' THEN 'absent'
            ELSE status
        END
        WHERE status IN ('P', 'A', 'H')
    ";
    
    $affectedRows = executeQuery($updateQuery);
    
    echo "✅ Migration completed!\n";
    echo "📊 Updated " . number_format($affectedRows) . " records\n\n";
    
    // Step 5: Verify migration results
    echo "📊 POST-MIGRATION STATUS DISTRIBUTION:\n";
    echo "--------------------------------------\n";
    
    $newStats = fetchAll("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
        FROM attendance 
        GROUP BY status
        ORDER BY count DESC
    ");
    
    foreach ($newStats as $stat) {
        echo sprintf("%-15s: %6d records (%.2f%%)\n", 
            $stat['status'], $stat['count'], $stat['percentage']);
    }
    
    // Step 6: Check for any remaining old format records
    $remainingOld = fetchRow("
        SELECT COUNT(*) as count 
        FROM attendance 
        WHERE status IN ('P', 'A', 'H')
    ");
    
    if ($remainingOld['count'] > 0) {
        echo "\n⚠️  WARNING: " . $remainingOld['count'] . " records still have old format!\n";
    } else {
        echo "\n✅ No old format records remaining - migration successful!\n";
    }
    
    // Step 7: Show final attendance summary
    $finalSummary = fetchRow("
        SELECT 
            COUNT(*) as total_records,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as attendance_percentage
        FROM attendance
    ");
    
    echo "\n📈 FINAL ATTENDANCE SUMMARY:\n";
    echo "----------------------------\n";
    echo "Total Records: " . number_format($finalSummary['total_records']) . "\n";
    echo "Present: " . number_format($finalSummary['present_count']) . "\n";
    echo "Absent: " . number_format($finalSummary['absent_count']) . "\n";
    echo "Attendance Rate: " . $finalSummary['attendance_percentage'] . "%\n\n";
    
    echo "🎉 MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "🔄 Your dashboard and reports will now show accurate statistics.\n";
    
} catch (Exception $e) {
    echo "❌ MIGRATION FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "📋 Please check your database and try again.\n";
    exit(1);
}
?>
