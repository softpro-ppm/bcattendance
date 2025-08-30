<?php
/**
 * Attendance Status Diagnostic Script
 * This script checks the current attendance status values and identifies mismatches
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "🔍 ATTENDANCE STATUS DIAGNOSTIC\n";
echo "==============================\n\n";

try {
    // Check current attendance status distribution
    echo "📊 CURRENT ATTENDANCE STATUS DISTRIBUTION:\n";
    echo "-----------------------------------------\n";
    
    $statusStats = fetchAll("
        SELECT 
            status,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
        FROM attendance 
        GROUP BY status
        ORDER BY count DESC
    ");
    
    $totalRecords = 0;
    foreach ($statusStats as $stat) {
        echo sprintf("%-15s: %6d records (%.2f%%)\n", 
            $stat['status'], $stat['count'], $stat['percentage']);
        $totalRecords += $stat['count'];
    }
    echo "\nTotal attendance records: " . number_format($totalRecords) . "\n\n";
    
    // Check today's attendance specifically
    echo "📅 TODAY'S ATTENDANCE (2025-08-30):\n";
    echo "----------------------------------\n";
    
    $todayStats = fetchAll("
        SELECT 
            status,
            COUNT(*) as count,
            created_at,
            updated_at
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
    
    // Check sample records to see actual status values
    echo "\n📋 SAMPLE ATTENDANCE RECORDS:\n";
    echo "-----------------------------\n";
    
    $sampleRecords = fetchAll("
        SELECT 
            id,
            beneficiary_id,
            attendance_date,
            status,
            created_at,
            updated_at
        FROM attendance 
        WHERE attendance_date = '2025-08-30'
        LIMIT 10
    ");
    
    if (!empty($sampleRecords)) {
        foreach ($sampleRecords as $record) {
            echo sprintf("ID: %d | Beneficiary: %d | Date: %s | Status: '%s' | Created: %s\n",
                $record['id'],
                $record['beneficiary_id'],
                $record['attendance_date'],
                $record['status'],
                $record['created_at']
            );
        }
    }
    
    // Check dashboard stats calculation
    echo "\n🔢 DASHBOARD STATS CALCULATION:\n";
    echo "-----------------------------\n";
    
    // Present count (what dashboard is looking for)
    $presentCount = fetchRow("
        SELECT COUNT(*) as count 
        FROM attendance a 
        INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE a.attendance_date = '2025-08-30' 
        AND bt.end_date >= '2025-08-30'
        AND (a.status = 'present' OR a.status = 'P')
    ");
    
    // Absent count (what dashboard is looking for)
    $absentCount = fetchRow("
        SELECT COUNT(*) as count 
        FROM attendance a 
        INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE a.attendance_date = '2025-08-30' 
        AND bt.end_date >= '2025-08-30'
        AND (a.status = 'absent' OR a.status = 'A')
    ");
    
    // Total marked count
    $totalMarked = fetchRow("
        SELECT COUNT(*) as count 
        FROM attendance a 
        INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE a.attendance_date = '2025-08-30' 
        AND bt.end_date >= '2025-08-30'
    ");
    
    echo "Dashboard Present Count: " . ($presentCount['count'] ?? 0) . "\n";
    echo "Dashboard Absent Count: " . ($absentCount['count'] ?? 0) . "\n";
    echo "Dashboard Total Marked: " . ($totalMarked['count'] ?? 0) . "\n";
    
    // Check what status values are actually stored
    echo "\n🔍 ACTUAL STATUS VALUES STORED:\n";
    echo "-------------------------------\n";
    
    $actualStatuses = fetchAll("
        SELECT DISTINCT status
        FROM attendance 
        WHERE attendance_date = '2025-08-30'
        ORDER BY status
    ");
    
    echo "Status values found: ";
    if (!empty($actualStatuses)) {
        $statusList = array_column($actualStatuses, 'status');
        echo "'" . implode("', '", $statusList) . "'\n";
    } else {
        echo "None found\n";
    }
    
    // Check if there's a status format mismatch
    echo "\n⚠️  STATUS FORMAT ANALYSIS:\n";
    echo "-------------------------\n";
    
    $mismatchCount = fetchRow("
        SELECT COUNT(*) as count
        FROM attendance 
        WHERE attendance_date = '2025-08-30'
        AND status IN ('P', 'A', 'H')
    ");
    
    if ($mismatchCount['count'] > 0) {
        echo "❌ MISMATCH DETECTED: " . $mismatchCount['count'] . " records use old format (P, A, H)\n";
        echo "   Dashboard expects: 'present', 'absent'\n";
        echo "   Actual values: 'P', 'A', 'H'\n";
        echo "\n💡 SOLUTION: Run the status standardization migration\n";
    } else {
        echo "✅ Status format appears to be consistent\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🏁 DIAGNOSTIC COMPLETE\n";
?>
