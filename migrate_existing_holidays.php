<?php
/**
 * Migration Script: Update Existing Holidays to Use batch_holidays Table
 * 
 * This script will:
 * 1. Check existing holidays in the holidays table
 * 2. For each holiday, determine which batches it applies to based on attendance records
 * 3. Create entries in batch_holidays table
 * 4. Update the display to show specific batch information
 */

require_once '../config/database.php';
require_once '../includes/functions.php';

echo "<h2>Holiday Migration Script</h2>";
echo "<p>This script will migrate existing holidays to use the new batch_holidays system.</p>";

try {
    // Step 1: Get all existing holidays (excluding Sundays)
    $holidays = fetchAll("
        SELECT id, date, description, type 
        FROM holidays 
        WHERE description != 'Sunday Holiday' 
        ORDER BY date
    ");
    
    echo "<h3>Found " . count($holidays) . " existing holidays to migrate</h3>";
    
    if (empty($holidays)) {
        echo "<p>No holidays to migrate.</p>";
        exit;
    }
    
    $migratedCount = 0;
    $errors = [];
    
    foreach ($holidays as $holiday) {
        echo "<hr>";
        echo "<h4>Processing Holiday: " . htmlspecialchars($holiday['description']) . " (" . $holiday['date'] . ")</h4>";
        
        // Step 2: Check if this holiday is already in batch_holidays
        $existingBatchHoliday = fetchRow("
            SELECT COUNT(*) as count 
            FROM batch_holidays 
            WHERE holiday_id = ?
        ", [$holiday['id']], 'i');
        
        if ($existingBatchHoliday['count'] > 0) {
            echo "<p>✅ Already migrated to batch_holidays</p>";
            continue;
        }
        
        // Step 3: Determine which batches this holiday applies to
        if ($holiday['type'] === 'national') {
            // National holidays apply to all batches
            echo "<p>🔄 National holiday - will apply to all batches</p>";
            
            // Get all active batches
            $allBatches = fetchAll("
                SELECT id FROM batches 
                WHERE status IN ('active', 'completed')
            ");
            
            foreach ($allBatches as $batch) {
                $insertResult = executeQuery("
                    INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ", [
                    $holiday['id'], 
                    $batch['id'], 
                    $holiday['date'], 
                    $holiday['description'], 
                    $holiday['type'], 
                    1 // Default admin user ID
                ]);
                
                if ($insertResult) {
                    echo "<p>✅ Added to batch " . $batch['id'] . "</p>";
                } else {
                    echo "<p>❌ Failed to add to batch " . $batch['id'] . "</p>";
                    $errors[] = "Failed to add holiday {$holiday['id']} to batch {$batch['id']}";
                }
            }
            
        } else {
            // Other holidays - check which batches have attendance marked as holiday
            echo "<p>🔄 Checking which batches this holiday applies to...</p>";
            
            $affectedBatches = fetchAll("
                SELECT DISTINCT b.batch_id, bt.name as batch_name, bt.code as batch_code
                FROM attendance a
                JOIN beneficiaries b ON a.beneficiary_id = b.id
                JOIN batches bt ON b.batch_id = bt.id
                WHERE a.attendance_date = ? AND a.status = 'holiday'
                ORDER BY bt.name
            ", [$holiday['date']], 's');
            
            if (empty($affectedBatches)) {
                echo "<p>⚠️ No batches found with holiday attendance - treating as all batches</p>";
                
                // Get all active batches
                $allBatches = fetchAll("
                    SELECT id FROM batches 
                    WHERE status IN ('active', 'completed')
                ");
                
                foreach ($allBatches as $batch) {
                    executeQuery("
                        INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ", [
                        $holiday['id'], 
                        $batch['id'], 
                        $holiday['date'], 
                        $holiday['description'], 
                        $holiday['type'], 
                        1
                    ]);
                }
            } else {
                echo "<p>📋 Found " . count($affectedBatches) . " affected batches:</p>";
                
                foreach ($affectedBatches as $batch) {
                    echo "<p>  - " . htmlspecialchars($batch['batch_name']) . " (" . htmlspecialchars($batch['batch_code']) . ")</p>";
                    
                    $insertResult = executeQuery("
                        INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ", [
                        $holiday['id'], 
                        $batch['batch_id'], 
                        $holiday['date'], 
                        $holiday['description'], 
                        $holiday['type'], 
                        1
                    ]);
                    
                    if ($insertResult) {
                        echo "<p>✅ Added to batch_holidays</p>";
                    } else {
                        echo "<p>❌ Failed to add to batch_holidays</p>";
                        $errors[] = "Failed to add holiday {$holiday['id']} to batch {$batch['batch_id']}";
                    }
                }
            }
        }
        
        $migratedCount++;
        echo "<p>✅ Holiday migration completed</p>";
    }
    
    // Step 4: Summary
    echo "<hr>";
    echo "<h3>Migration Summary</h3>";
    echo "<p>✅ Successfully migrated: " . $migratedCount . " holidays</p>";
    
    if (!empty($errors)) {
        echo "<h4>❌ Errors encountered:</h4>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    echo "<p>🎉 Migration completed! Your holidays now properly show which batches they apply to.</p>";
    
} catch (Exception $e) {
    echo "<h3>❌ Migration Failed</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and try again.</p>";
}

echo "<hr>";
echo "<p><a href='manage_holidays.php'>← Back to Manage Holidays</a></p>";
?>
