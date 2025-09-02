<?php
/**
 * SAFE ATTENDANCE REVERT SCRIPT
 * 
 * This script safely reverts any incorrect holiday markings
 * and restores the original attendance status before applying the correct fix.
 * 
 * IMPORTANT: This script will only revert attendance records that were 
 * incorrectly marked as 'H' due to the previous fix attempt.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== SAFE ATTENDANCE REVERT SCRIPT ===\n";
echo "Starting safe revert process...\n\n";

try {
    // Step 1: Create a backup of current attendance status
    echo "Step 1: Creating backup of current attendance status...\n";
    
    $backupQuery = "CREATE TABLE IF NOT EXISTS attendance_backup_before_fix AS 
                    SELECT * FROM attendance WHERE status = 'H'";
    $backupResult = executeQuery($backupQuery);
    echo "- Backup created: " . ($backupResult ? "SUCCESS" : "FAILED") . "\n";
    
    // Step 2: Identify incorrectly marked holidays
    echo "\nStep 2: Identifying incorrectly marked holidays...\n";
    
    // Get all holidays from holidays table
    $holidaysQuery = "SELECT date, description FROM holidays WHERE date IS NOT NULL";
    $holidaysResult = executeQuery($holidaysQuery);
    
    $incorrectHolidays = [];
    if ($holidaysResult) {
        while ($row = $holidaysResult->fetch_assoc()) {
            $holidayDate = $row['date'];
            $description = $row['description'];
            
            // Check if this holiday is batch-specific
            $batchHolidayCheck = fetchRow("SELECT COUNT(*) as count FROM batch_holidays WHERE holiday_date = ?", [$holidayDate]);
            
            if ($batchHolidayCheck && $batchHolidayCheck['count'] > 0) {
                // This is a batch-specific holiday - mark as incorrect for non-matching batches
                $incorrectQuery = "SELECT COUNT(*) as count FROM attendance a 
                                  JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                  WHERE a.attendance_date = ? AND a.status = 'H' 
                                  AND b.batch_id NOT IN (
                                      SELECT batch_id FROM batch_holidays WHERE holiday_date = ?
                                  )";
                $incorrectResult = executeQuery($incorrectQuery, [$holidayDate, $holidayDate]);
                if ($incorrectResult) {
                    $incorrectRow = $incorrectResult->fetch_assoc();
                    if ($incorrectRow['count'] > 0) {
                        $incorrectHolidays[] = [
                            'date' => $holidayDate,
                            'description' => $description,
                            'incorrect_count' => $incorrectRow['count']
                        ];
                        echo "- Found {$incorrectRow['count']} incorrect holiday records for {$holidayDate} ({$description})\n";
                    }
                }
            }
        }
    }
    
    // Step 3: Revert incorrect holiday markings
    echo "\nStep 3: Reverting incorrect holiday markings...\n";
    
    $totalReverted = 0;
    foreach ($incorrectHolidays as $holiday) {
        $revertQuery = "UPDATE attendance a 
                       JOIN beneficiaries b ON a.beneficiary_id = b.id 
                       SET a.status = 'A' 
                       WHERE a.attendance_date = ? AND a.status = 'H' 
                       AND b.batch_id NOT IN (
                           SELECT batch_id FROM batch_holidays WHERE holiday_date = ?
                       )";
        $revertResult = executeQuery($revertQuery, [$holiday['date'], $holiday['date']]);
        if ($revertResult) {
            $totalReverted += $holiday['incorrect_count'];
            echo "- Reverted {$holiday['incorrect_count']} records for {$holiday['date']} ({$holiday['description']})\n";
        }
    }
    
    echo "- Total records reverted: {$totalReverted}\n";
    
    // Step 4: Verify the revert
    echo "\nStep 4: Verifying the revert...\n";
    
    $verifyQuery = "SELECT 
        status,
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
    FROM attendance 
    GROUP BY status
    ORDER BY count DESC";
    
    $verifyResult = executeQuery($verifyQuery);
    if ($verifyResult) {
        echo "Current status distribution after revert:\n";
        while ($row = $verifyResult->fetch_assoc()) {
            echo "- {$row['status']}: {$row['count']} records ({$row['percentage']}%)\n";
        }
    }
    
    echo "\n=== REVERT COMPLETED SUCCESSFULLY ===\n";
    echo "Total records reverted: {$totalReverted}\n";
    echo "Your attendance data is now safe and ready for the correct fix.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Revert process failed. Please check the error and try again.\n";
}
?>
