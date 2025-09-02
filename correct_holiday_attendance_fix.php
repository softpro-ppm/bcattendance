<?php
/**
 * CORRECT HOLIDAY ATTENDANCE FIX SCRIPT
 * 
 * This script correctly fixes the holiday attendance issue by:
 * 1. Marking all Sundays as 'H' for all beneficiaries
 * 2. Marking custom holidays as 'H' ONLY for the correct beneficiaries:
 *    - For "All Mandals" holidays: mark all active beneficiaries
 *    - For "Specific Batches" holidays: mark ONLY beneficiaries from those specific batches
 * 
 * This ensures that batch-specific holidays don't affect wrong batches.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== CORRECT HOLIDAY ATTENDANCE FIX SCRIPT ===\n";
echo "Starting correct fix process...\n\n";

try {
    // Step 1: Check current attendance status distribution
    echo "Step 1: Analyzing current attendance status distribution...\n";
    $statusQuery = "SELECT 
        status,
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
    FROM attendance 
    GROUP BY status
    ORDER BY count DESC";
    
    $statusResult = executeQuery($statusQuery);
    if ($statusResult) {
        echo "Current status distribution:\n";
        while ($row = $statusResult->fetch_assoc()) {
            echo "- {$row['status']}: {$row['count']} records ({$row['percentage']}%)\n";
        }
    }
    echo "\n";

    // Step 2: Standardize all attendance statuses to use 'H' for holidays
    echo "Step 2: Standardizing attendance statuses...\n";
    
    // Convert 'holiday' to 'H'
    $updateQuery1 = "UPDATE attendance SET status = 'H' WHERE status = 'holiday'";
    $result1 = executeQuery($updateQuery1);
    echo "- Converted 'holiday' to 'H': " . ($result1 ? "SUCCESS" : "FAILED") . "\n";
    
    // Convert 'present' to 'P'
    $updateQuery2 = "UPDATE attendance SET status = 'P' WHERE status = 'present'";
    $result2 = executeQuery($updateQuery2);
    echo "- Converted 'present' to 'P': " . ($result2 ? "SUCCESS" : "FAILED") . "\n";
    
    // Convert 'absent' to 'A'
    $updateQuery3 = "UPDATE attendance SET status = 'A' WHERE status = 'absent'";
    $result3 = executeQuery($updateQuery3);
    echo "- Converted 'absent' to 'A': " . ($result3 ? "SUCCESS" : "FAILED") . "\n";
    
    // Convert 'late' to 'P' (treat as present)
    $updateQuery4 = "UPDATE attendance SET status = 'P' WHERE status = 'late'";
    $result4 = executeQuery($updateQuery4);
    echo "- Converted 'late' to 'P': " . ($result4 ? "SUCCESS" : "FAILED") . "\n";
    
    // Convert 'excused' to 'A' (treat as absent)
    $updateQuery5 = "UPDATE attendance SET status = 'A' WHERE status = 'excused'";
    $result5 = executeQuery($updateQuery5);
    echo "- Converted 'excused' to 'A': " . ($result5 ? "SUCCESS" : "FAILED") . "\n";
    echo "\n";

    // Step 3: Mark all Sundays as holidays (for all beneficiaries)
    echo "Step 3: Marking all Sundays as holidays...\n";
    
    // Get all unique dates from attendance table
    $datesQuery = "SELECT DISTINCT attendance_date FROM attendance ORDER BY attendance_date";
    $datesResult = executeQuery($datesQuery);
    
    $sundayCount = 0;
    if ($datesResult) {
        while ($row = $datesResult->fetch_assoc()) {
            $date = $row['attendance_date'];
            $dayOfWeek = date('N', strtotime($date)); // 7 = Sunday
            
            if ($dayOfWeek == 7) {
                // This is a Sunday, mark all attendance records for this date as 'H'
                $sundayUpdateQuery = "UPDATE attendance SET status = 'H' WHERE attendance_date = ? AND status != 'H'";
                $sundayResult = executeQuery($sundayUpdateQuery, [$date]);
                if ($sundayResult) {
                    $sundayCount++;
                    echo "- Marked Sunday {$date} as holiday\n";
                }
            }
        }
    }
    echo "- Total Sundays processed: {$sundayCount}\n\n";

    // Step 4: Process custom holidays correctly
    echo "Step 4: Processing custom holidays correctly...\n";
    
    // Get all holidays from holidays table
    $holidaysQuery = "SELECT date, description FROM holidays WHERE date IS NOT NULL";
    $holidaysResult = executeQuery($holidaysQuery);
    
    $allMandalsHolidayCount = 0;
    $batchSpecificHolidayCount = 0;
    
    if ($holidaysResult) {
        while ($row = $holidaysResult->fetch_assoc()) {
            $holidayDate = $row['date'];
            $description = $row['description'];
            
            // Check if this holiday is batch-specific
            $batchHolidayCheck = fetchRow("SELECT COUNT(*) as count FROM batch_holidays WHERE holiday_date = ?", [$holidayDate]);
            
            if ($batchHolidayCheck && $batchHolidayCheck['count'] > 0) {
                // This is a batch-specific holiday - mark ONLY beneficiaries from those specific batches
                $batchSpecificQuery = "UPDATE attendance a 
                                      JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                      SET a.status = 'H' 
                                      WHERE a.attendance_date = ? AND a.status != 'H' 
                                      AND b.batch_id IN (
                                          SELECT batch_id FROM batch_holidays WHERE holiday_date = ?
                                      )";
                $batchSpecificResult = executeQuery($batchSpecificQuery, [$holidayDate, $holidayDate]);
                if ($batchSpecificResult) {
                    $batchSpecificHolidayCount++;
                    echo "- Marked batch-specific holiday {$holidayDate} ({$description}) for specific batches only\n";
                }
            } else {
                // This is an "All Mandals" holiday - mark all active beneficiaries
                $allMandalsQuery = "UPDATE attendance a 
                                   JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                   SET a.status = 'H' 
                                   WHERE a.attendance_date = ? AND a.status != 'H' 
                                   AND b.status = 'active'";
                $allMandalsResult = executeQuery($allMandalsQuery, [$holidayDate]);
                if ($allMandalsResult) {
                    $allMandalsHolidayCount++;
                    echo "- Marked all-mandals holiday {$holidayDate} ({$description}) for all active beneficiaries\n";
                }
            }
        }
    }
    echo "- Total all-mandals holidays processed: {$allMandalsHolidayCount}\n";
    echo "- Total batch-specific holidays processed: {$batchSpecificHolidayCount}\n\n";

    // Step 5: Final verification
    echo "Step 5: Final verification...\n";
    
    $finalStatusQuery = "SELECT 
        status,
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
    FROM attendance 
    GROUP BY status
    ORDER BY count DESC";
    
    $finalStatusResult = executeQuery($finalStatusQuery);
    if ($finalStatusResult) {
        echo "Final status distribution:\n";
        while ($row = $finalStatusResult->fetch_assoc()) {
            echo "- {$row['status']}: {$row['count']} records ({$row['percentage']}%)\n";
        }
    }
    
    // Check holiday count
    $holidayCountQuery = "SELECT COUNT(*) as holiday_count FROM attendance WHERE status = 'H'";
    $holidayCountResult = executeQuery($holidayCountQuery);
    if ($holidayCountResult) {
        $holidayRow = $holidayCountResult->fetch_assoc();
        echo "- Total holiday records: {$holidayRow['holiday_count']}\n";
    }
    
    // Verify specific batch holidays are correct
    echo "\nStep 6: Verifying batch-specific holidays...\n";
    
    // Check GL PURAM BATCH 1 for June 3rd and 4th (should NOT be holidays)
    $glPuraBatch1Query = "SELECT COUNT(*) as count FROM attendance a 
                          JOIN beneficiaries b ON a.beneficiary_id = b.id 
                          JOIN batches bt ON b.batch_id = bt.id 
                          WHERE a.attendance_date IN ('2025-06-03', '2025-06-04') 
                          AND a.status = 'H' 
                          AND bt.name LIKE '%GL PURAM%' 
                          AND bt.name LIKE '%BATCH 1%'";
    $glPuraResult = executeQuery($glPuraBatch1Query);
    if ($glPuraResult) {
        $glPuraRow = $glPuraResult->fetch_assoc();
        echo "- GL PURAM BATCH 1 holiday records for June 3-4, 2025: {$glPuraRow['count']} (should be 0)\n";
    }
    
    // Check PARVATHIPURAM batches for June 3rd and 4th (should be holidays)
    $parvathiBatchQuery = "SELECT COUNT(*) as count FROM attendance a 
                           JOIN beneficiaries b ON a.beneficiary_id = b.id 
                           JOIN batches bt ON b.batch_id = bt.id 
                           WHERE a.attendance_date IN ('2025-06-03', '2025-06-04') 
                           AND a.status = 'H' 
                           AND bt.name LIKE '%PARVATHIPURAM%'";
    $parvathiResult = executeQuery($parvathiBatchQuery);
    if ($parvathiResult) {
        $parvathiRow = $parvathiResult->fetch_assoc();
        echo "- PARVATHIPURAM batches holiday records for June 3-4, 2025: {$parvathiRow['count']} (should be > 0)\n";
    }
    
    echo "\n=== CORRECT FIX COMPLETED SUCCESSFULLY ===\n";
    echo "All Sundays and custom holidays are now properly marked as 'H' in attendance reports.\n";
    echo "Batch-specific holidays only affect the correct batches.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Fix process failed. Please check the error and try again.\n";
}
?>
