<?php
/**
 * TARGETED HOLIDAY FIX SCRIPT
 * 
 * This script specifically fixes the issue where these dates are showing as blank instead of "H":
 * - GL Puram, Batch 1 & Batch 2: 20th May & 21st May & 9th August
 * - Parvathipuram, Batch 1: 3rd Jun & 4th Jun
 * 
 * The issue is that the export functions are not properly detecting batch-specific holidays.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== TARGETED HOLIDAY FIX SCRIPT ===\n";
echo "Fixing specific dates that should be marked as 'H'...\n\n";

try {
    // Step 1: Define the specific dates and their batch assignments
    $holidayAssignments = [
        // GL Puram batches (should have May 20, 21 and August 9)
        '2025-05-20' => ['KUR_GLP_B1', 'KUR_GLP_B2'], // Local Festival
        '2025-05-21' => ['KUR_GLP_B1'], // Local Festival  
        '2025-08-09' => ['KUR_GLP_B1', 'KUR_GLP_B2'], // Girijana Vutsavalu
        
        // Parvathipuram batches (should have June 3, 4)
        '2025-06-03' => ['PAR_PAR_B1', 'PAR_PAR_B2'], // Local Festival
        '2025-06-04' => ['PAR_PAR_B1', 'PAR_PAR_B2']  // Local Festival
    ];
    
    echo "Step 1: Ensuring holidays exist in holidays table...\n";
    foreach ($holidayAssignments as $date => $batchCodes) {
        // Check if holiday exists in holidays table
        $holiday = fetchRow("SELECT id FROM holidays WHERE date = ?", [$date]);
        if (!$holiday) {
            // Add to holidays table
            $description = "Local Festival";
            if ($date == '2025-08-09') {
                $description = "Girijana Vutsavalu";
            }
            
            $result = executeQuery("INSERT INTO holidays (date, description, type) VALUES (?, ?, 'other')", 
                                 [$date, $description]);
            if ($result) {
                echo "✓ Added holiday for $date: $description\n";
            } else {
                echo "✗ Failed to add holiday for $date\n";
            }
        } else {
            echo "✓ Holiday already exists for $date\n";
        }
    }
    echo "\n";
    
    // Step 2: Ensure batch-specific holiday assignments exist
    echo "Step 2: Ensuring batch-specific holiday assignments...\n";
    foreach ($holidayAssignments as $date => $batchCodes) {
        foreach ($batchCodes as $batchCode) {
            // Get batch ID
            $batch = fetchRow("SELECT id, name FROM batches WHERE code = ?", [$batchCode]);
            if (!$batch) {
                echo "✗ Batch not found: $batchCode\n";
                continue;
            }
            
            // Check if batch holiday assignment exists
            $batchHoliday = fetchRow("SELECT id FROM batch_holidays WHERE batch_id = ? AND holiday_date = ?", 
                                   [$batch['id'], $date]);
            
            if (!$batchHoliday) {
                // Get holiday ID
                $holiday = fetchRow("SELECT id FROM holidays WHERE date = ?", [$date]);
                if (!$holiday) {
                    echo "✗ Holiday not found for date: $date\n";
                    continue;
                }
                
                // Add batch holiday assignment
                $description = "Local Festival";
                if ($date == '2025-08-09') {
                    $description = "Girijana Vutsavalu";
                }
                
                $result = executeQuery("INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description) 
                                       VALUES (?, ?, ?, ?, ?)", 
                                       [$holiday['id'], $batch['id'], $date, $description, $description]);
                
                if ($result) {
                    echo "✓ Added batch holiday for $batchCode on $date\n";
                } else {
                    echo "✗ Failed to add batch holiday for $batchCode on $date\n";
                }
            } else {
                echo "✓ Batch holiday already exists for $batchCode on $date\n";
            }
        }
    }
    echo "\n";
    
    // Step 3: Check existing attendance records before updating
    echo "Step 3: Checking existing attendance records...\n";
    foreach ($holidayAssignments as $date => $batchCodes) {
        foreach ($batchCodes as $batchCode) {
            // Get batch ID
            $batch = fetchRow("SELECT id FROM batches WHERE code = ?", [$batchCode]);
            if (!$batch) {
                continue;
            }
            
            // Check existing attendance records for this batch and date
            $existingRecords = fetchAll("SELECT a.status, COUNT(*) as count 
                                        FROM attendance a 
                                        JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                        WHERE b.batch_id = ? AND a.attendance_date = ? 
                                        GROUP BY a.status", 
                                        [$batch['id'], $date]);
            
            if ($existingRecords) {
                echo "Existing attendance for $batchCode on $date:\n";
                foreach ($existingRecords as $record) {
                    echo "  - Status: " . $record['status'] . " - Count: " . $record['count'] . "\n";
                }
            } else {
                echo "No existing attendance records for $batchCode on $date\n";
            }
        }
    }
    echo "\n";
    
    // Step 4: Update existing attendance records to holiday
    echo "Step 4: Updating existing attendance records to holiday...\n";
    foreach ($holidayAssignments as $date => $batchCodes) {
        foreach ($batchCodes as $batchCode) {
            // Get batch ID
            $batch = fetchRow("SELECT id FROM batches WHERE code = ?", [$batchCode]);
            if (!$batch) {
                continue;
            }
            
            // UPDATE existing attendance records to 'H' for this batch and date
            $result = executeQuery("UPDATE attendance a 
                                   JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                   SET a.status = 'H' 
                                   WHERE b.batch_id = ? AND a.attendance_date = ?", 
                                   [$batch['id'], $date]);
            
            if ($result) {
                echo "✓ Updated existing attendance to holiday for $batchCode on $date\n";
            } else {
                echo "✗ Failed to update attendance for $batchCode on $date\n";
            }
        }
    }
    echo "\n";
    
    // Step 5: Verify the fixes
    echo "Step 5: Verifying the fixes...\n";
    foreach ($holidayAssignments as $date => $batchCodes) {
        echo "Verifying $date:\n";
        
        // Check holidays table
        $holiday = fetchRow("SELECT * FROM holidays WHERE date = ?", [$date]);
        if ($holiday) {
            echo "  ✓ Holiday exists: " . $holiday['description'] . "\n";
        } else {
            echo "  ✗ Holiday missing\n";
        }
        
        // Check batch holidays
        foreach ($batchCodes as $batchCode) {
            $batch = fetchRow("SELECT id FROM batches WHERE code = ?", [$batchCode]);
            if ($batch) {
                $batchHoliday = fetchRow("SELECT bh.* FROM batch_holidays bh 
                                        JOIN batches b ON bh.batch_id = b.id 
                                        WHERE b.code = ? AND bh.holiday_date = ?", 
                                        [$batchCode, $date]);
                
                if ($batchHoliday) {
                    echo "  ✓ Batch holiday exists for $batchCode\n";
                } else {
                    echo "  ✗ Batch holiday missing for $batchCode\n";
                }
                
                // Check attendance records
                $attendance = fetchAll("SELECT a.status, COUNT(*) as count 
                                      FROM attendance a 
                                      JOIN beneficiaries b ON a.beneficiary_id = b.id 
                                      JOIN batches bt ON b.batch_id = bt.id 
                                      WHERE bt.code = ? AND a.attendance_date = ? 
                                      GROUP BY a.status", 
                                      [$batchCode, $date]);
                
                if ($attendance) {
                    foreach ($attendance as $att) {
                        echo "    - Status: " . $att['status'] . " - Count: " . $att['count'] . "\n";
                    }
                } else {
                    echo "    - No attendance records found\n";
                }
            }
        }
        echo "\n";
    }
    
    echo "=== TARGETED FIX COMPLETE ===\n";
    echo "The specific dates should now show as 'H' in exports.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
