<?php
/**
 * HOLIDAY ISSUE DIAGNOSTIC SCRIPT
 * 
 * This script diagnoses why specific dates are showing as blank instead of "H"
 * for the following dates:
 * - GL Puram, Batch 1 & Batch 2: 20th May & 21st May & 9th August
 * - Parvathipuram, Batch 1: 3rd Jun & 4th Jun
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== HOLIDAY ISSUE DIAGNOSTIC SCRIPT ===\n";
echo "Checking specific dates that should be marked as 'H'...\n\n";

try {
    // Step 1: Check the specific dates in holidays table
    echo "Step 1: Checking holidays table for specific dates...\n";
    $specificDates = ['2025-05-20', '2025-05-21', '2025-08-09', '2025-06-03', '2025-06-04'];
    
    foreach ($specificDates as $date) {
        $holiday = fetchRow("SELECT * FROM holidays WHERE date = ?", [$date]);
        if ($holiday) {
            echo "✓ Found holiday for $date: " . $holiday['description'] . " (Type: " . $holiday['type'] . ")\n";
        } else {
            echo "✗ NO holiday found for $date in holidays table\n";
        }
    }
    echo "\n";
    
    // Step 2: Check batch_holidays table for these dates
    echo "Step 2: Checking batch_holidays table for specific dates...\n";
    foreach ($specificDates as $date) {
        $batchHolidays = fetchAll("SELECT bh.*, b.name as batch_name, b.code as batch_code 
                                  FROM batch_holidays bh 
                                  JOIN batches b ON bh.batch_id = b.id 
                                  WHERE bh.holiday_date = ?", [$date]);
        
        if ($batchHolidays) {
            echo "✓ Found batch holidays for $date:\n";
            foreach ($batchHolidays as $bh) {
                echo "  - " . $bh['batch_name'] . " (" . $bh['batch_code'] . "): " . $bh['holiday_name'] . "\n";
            }
        } else {
            echo "✗ NO batch holidays found for $date\n";
        }
    }
    echo "\n";
    
    // Step 3: Check which batches should have these holidays
    echo "Step 3: Checking which batches should have these holidays...\n";
    
    // GL Puram batches (should have May 20, 21 and August 9)
    $glPuramBatches = fetchAll("SELECT id, name, code FROM batches WHERE code LIKE 'KUR_GLP_B%'");
    echo "GL Puram batches:\n";
    foreach ($glPuramBatches as $batch) {
        echo "  - " . $batch['name'] . " (" . $batch['code'] . ") - ID: " . $batch['id'] . "\n";
    }
    
    // Parvathipuram batches (should have June 3, 4)
    $parvathipuramBatches = fetchAll("SELECT id, name, code FROM batches WHERE code LIKE 'PAR_PAR_B%'");
    echo "Parvathipuram batches:\n";
    foreach ($parvathipuramBatches as $batch) {
        echo "  - " . $batch['name'] . " (" . $batch['code'] . ") - ID: " . $batch['id'] . "\n";
    }
    echo "\n";
    
    // Step 4: Check attendance records for these specific dates
    echo "Step 4: Checking attendance records for specific dates...\n";
    foreach ($specificDates as $date) {
        $attendance = fetchAll("SELECT a.status, COUNT(*) as count 
                               FROM attendance a 
                               JOIN beneficiaries b ON a.beneficiary_id = b.id 
                               JOIN batches bt ON b.batch_id = bt.id 
                               WHERE a.attendance_date = ? 
                               GROUP BY a.status", [$date]);
        
        echo "Attendance for $date:\n";
        if ($attendance) {
            foreach ($attendance as $att) {
                echo "  - Status: " . $att['status'] . " - Count: " . $att['count'] . "\n";
            }
        } else {
            echo "  - No attendance records found\n";
        }
    }
    echo "\n";
    
    // Step 5: Check if beneficiaries exist for these batches on these dates
    echo "Step 5: Checking if beneficiaries exist for these dates...\n";
    foreach ($specificDates as $date) {
        $beneficiaries = fetchAll("SELECT COUNT(*) as count 
                                  FROM beneficiaries b 
                                  JOIN batches bt ON b.batch_id = bt.id 
                                  WHERE b.status = 'active' 
                                  AND (bt.code LIKE 'KUR_GLP_B%' OR bt.code LIKE 'PAR_PAR_B%')", []);
        
        echo "Active beneficiaries for $date: " . $beneficiaries[0]['count'] . "\n";
    }
    echo "\n";
    
    // Step 6: Test the holiday detection logic
    echo "Step 6: Testing holiday detection logic...\n";
    foreach ($specificDates as $date) {
        echo "Testing date: $date\n";
        
        // Check if it's Sunday
        $isSunday = (date('N', strtotime($date)) == 7);
        echo "  - Is Sunday: " . ($isSunday ? 'Yes' : 'No') . "\n";
        
        // Check holidays table
        $holidayCheck = fetchRow("SELECT id FROM holidays WHERE date = ?", [$date]);
        echo "  - In holidays table: " . ($holidayCheck ? 'Yes' : 'No') . "\n";
        
        // Check batch holidays for GL Puram batches
        $glPuramHoliday = fetchRow("SELECT bh.id FROM batch_holidays bh 
                                   JOIN batches b ON bh.batch_id = b.id 
                                   WHERE bh.holiday_date = ? AND b.code LIKE 'KUR_GLP_B%'", [$date]);
        echo "  - GL Puram batch holiday: " . ($glPuramHoliday ? 'Yes' : 'No') . "\n";
        
        // Check batch holidays for Parvathipuram batches
        $parvathipuramHoliday = fetchRow("SELECT bh.id FROM batch_holidays bh 
                                        JOIN batches b ON bh.batch_id = b.id 
                                        WHERE bh.holiday_date = ? AND b.code LIKE 'PAR_PAR_B%'", [$date]);
        echo "  - Parvathipuram batch holiday: " . ($parvathipuramHoliday ? 'Yes' : 'No') . "\n";
        
        echo "\n";
    }
    
    echo "=== DIAGNOSTIC COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
