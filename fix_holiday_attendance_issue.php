<?php
/**
 * HOLIDAY ATTENDANCE FIX SCRIPT
 * 
 * This script fixes the issue where Sundays and custom holidays are showing as "A" (Absent) 
 * or blank cells instead of "H" (Holiday) in attendance reports.
 * 
 * Issues addressed:
 * 1. Status inconsistency between old ('P','A','H') and new ('present','absent','holiday') formats
 * 2. Missing holiday detection in export functions
 * 3. Incomplete holiday marking for Sundays and custom holidays
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== HOLIDAY ATTENDANCE FIX SCRIPT ===\n";
echo "Starting fix process...\n\n";

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

    // Step 3: Mark all Sundays as holidays
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

    // Step 4: Mark all custom holidays as 'H'
    echo "Step 4: Marking custom holidays as 'H'...\n";
    
    // Get all holidays from holidays table
    $holidaysQuery = "SELECT date, description FROM holidays WHERE date IS NOT NULL";
    $holidaysResult = executeQuery($holidaysQuery);
    
    $holidayCount = 0;
    if ($holidaysResult) {
        while ($row = $holidaysResult->fetch_assoc()) {
            $holidayDate = $row['date'];
            $description = $row['description'];
            
            // Mark all attendance records for this holiday date as 'H'
            $holidayUpdateQuery = "UPDATE attendance SET status = 'H' WHERE attendance_date = ? AND status != 'H'";
            $holidayResult = executeQuery($holidayUpdateQuery, [$holidayDate]);
            if ($holidayResult) {
                $holidayCount++;
                echo "- Marked holiday {$holidayDate} ({$description}) as 'H'\n";
            }
        }
    }
    echo "- Total custom holidays processed: {$holidayCount}\n\n";

    // Step 5: Check batch-specific holidays
    echo "Step 5: Processing batch-specific holidays...\n";
    
    // Get batch-specific holidays
    $batchHolidaysQuery = "SELECT DISTINCT holiday_date, batch_id FROM batch_holidays WHERE holiday_date IS NOT NULL";
    $batchHolidaysResult = executeQuery($batchHolidaysQuery);
    
    $batchHolidayCount = 0;
    if ($batchHolidaysResult) {
        while ($row = $batchHolidaysResult->fetch_assoc()) {
            $holidayDate = $row['holiday_date'];
            $batchId = $row['batch_id'];
            
            // Mark attendance records for this batch and date as 'H'
            $batchHolidayUpdateQuery = "UPDATE attendance a 
                                      JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                      SET a.status = 'H' 
                                      WHERE a.attendance_date = ? AND b.batch_id = ? AND a.status != 'H'";
            $batchHolidayResult = executeQuery($batchHolidayUpdateQuery, [$holidayDate, $batchId]);
            if ($batchHolidayResult) {
                $batchHolidayCount++;
                echo "- Marked batch-specific holiday {$holidayDate} for batch {$batchId} as 'H'\n";
            }
        }
    }
    echo "- Total batch-specific holidays processed: {$batchHolidayCount}\n\n";

    // Step 6: Final verification
    echo "Step 6: Final verification...\n";
    
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
    
    echo "\n=== FIX COMPLETED SUCCESSFULLY ===\n";
    echo "All Sundays and custom holidays should now be properly marked as 'H' in attendance reports.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Fix process failed. Please check the error and try again.\n";
}
?>
