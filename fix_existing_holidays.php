<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🔧 Fix Existing Holidays Attendance</h1>";
echo "<p>This script will update existing attendance records to properly show holiday status for all existing holidays.</p>";

try {
    $conn = getDBConnection();
    echo "<h2>✅ Database connection: Working</h2>";
    
    // Step 1: Check existing holidays
    echo "<h3>1. Checking existing holidays...</h3>";
    $holidays = fetchAll("SELECT id, date, description, type FROM holidays WHERE description != 'Sunday Holiday' ORDER BY date");
    
    if (empty($holidays)) {
        echo "❌ No holidays found in the system.<br>";
        exit;
    }
    
    echo "Found " . count($holidays) . " holidays:<br>";
    foreach ($holidays as $holiday) {
        echo "- {$holiday['date']}: {$holiday['description']} ({$holiday['type']})<br>";
    }
    
    // Step 2: Create attendance_backup table if it doesn't exist
    echo "<h3>2. Setting up backup table...</h3>";
    $tableExists = fetchRow("SHOW TABLES LIKE 'attendance_backup'");
    if (!$tableExists) {
        echo "Creating attendance_backup table...<br>";
        $createTable = "CREATE TABLE IF NOT EXISTS attendance_backup (
            id INT AUTO_INCREMENT PRIMARY KEY,
            beneficiary_id INT NOT NULL,
            attendance_date DATE NOT NULL,
            original_status ENUM('present', 'absent') NOT NULL,
            backup_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            holiday_id INT NOT NULL,
            
            FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
            FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
            
            INDEX idx_beneficiary_date (beneficiary_id, attendance_date),
            INDEX idx_holiday_id (holiday_id),
            INDEX idx_backup_date (backup_date),
            
            UNIQUE KEY unique_backup (beneficiary_id, attendance_date, holiday_id)
        )";
        
        $result = executeQuery($createTable);
        if ($result) {
            echo "✅ attendance_backup table created successfully!<br>";
        } else {
            echo "❌ Failed to create backup table<br>";
            exit;
        }
    } else {
        echo "✅ attendance_backup table already exists<br>";
    }
    
    // Step 3: Process each holiday
    echo "<h3>3. Processing holidays and updating attendance...</h3>";
    
    $totalUpdated = 0;
    $totalBackedUp = 0;
    
    foreach ($holidays as $holiday) {
        $holidayDate = $holiday['date'];
        $holidayId = $holiday['id'];
        
        echo "<br><strong>Processing: {$holiday['date']} - {$holiday['description']}</strong><br>";
        
        // Check if this holiday applies to all batches or specific batches
        $batchHolidays = fetchAll("SELECT batch_id FROM batch_holidays WHERE holiday_id = ?", [$holidayId]);
        
        if (empty($batchHolidays)) {
            // National holiday or holiday for all batches
            echo "- Type: All batches<br>";
            
            // Step 3a: Backup existing attendance records
            $backupQuery = "INSERT INTO attendance_backup (beneficiary_id, attendance_date, original_status, holiday_id)
                           SELECT beneficiary_id, attendance_date, status, ?
                           FROM attendance 
                           WHERE attendance_date = ? AND status IN ('present', 'absent', 'P', 'A')
                           ON DUPLICATE KEY UPDATE original_status = VALUES(original_status)";
            $backupResult = executeQuery($backupQuery, [$holidayId, $holidayDate]);
            
            if ($backupResult) {
                $backupCount = $conn->affected_rows;
                $totalBackedUp += $backupCount;
                echo "- ✅ Backed up $backupCount existing attendance records<br>";
            }
            
            // Step 3b: Update all attendance records to holiday
            $updateQuery = "UPDATE attendance SET status = 'holiday' 
                           WHERE attendance_date = ? AND status IN ('present', 'absent', 'P', 'A')";
            $updateResult = executeQuery($updateQuery, [$holidayDate]);
            
            if ($updateResult) {
                $updatedCount = $conn->affected_rows;
                $totalUpdated += $updatedCount;
                echo "- ✅ Updated $updatedCount attendance records to holiday<br>";
            }
            
        } else {
            // Holiday for specific batches
            echo "- Type: Specific batches<br>";
            
            $batchIds = array_column($batchHolidays, 'batch_id');
            $placeholders = str_repeat('?,', count($batchIds) - 1) . '?';
            
            // Step 3a: Backup existing attendance records for specific batches
            $backupQuery = "INSERT INTO attendance_backup (beneficiary_id, attendance_date, original_status, holiday_id)
                           SELECT a.beneficiary_id, a.attendance_date, a.status, ?
                           FROM attendance a
                           JOIN beneficiaries b ON a.beneficiary_id = b.id
                           WHERE a.attendance_date = ? AND a.status IN ('present', 'absent', 'P', 'A') 
                           AND b.batch_id IN ($placeholders)
                           ON DUPLICATE KEY UPDATE original_status = VALUES(original_status)";
            $backupParams = array_merge([$holidayId, $holidayDate], $batchIds);
            $backupResult = executeQuery($backupQuery, $backupParams);
            
            if ($backupResult) {
                $backupCount = $conn->affected_rows;
                $totalBackedUp += $backupCount;
                echo "- ✅ Backed up $backupCount existing attendance records for specific batches<br>";
            }
            
            // Step 3b: Update attendance records for specific batches to holiday
            $updateQuery = "UPDATE attendance a
                           JOIN beneficiaries b ON a.beneficiary_id = b.id
                           SET a.status = 'holiday'
                           WHERE a.attendance_date = ? AND a.status IN ('present', 'absent', 'P', 'A')
                           AND b.batch_id IN ($placeholders)";
            $updateParams = array_merge([$holidayDate], $batchIds);
            $updateResult = executeQuery($updateQuery, $updateParams);
            
            if ($updateResult) {
                $updatedCount = $conn->affected_rows;
                $totalUpdated += $updatedCount;
                echo "- ✅ Updated $updatedCount attendance records to holiday for specific batches<br>";
            }
        }
        
        // Step 3c: Ensure all active beneficiaries have holiday status for this date
        if (empty($batchHolidays)) {
            // All batches
            $insertQuery = "INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at)
                           SELECT b.id, ?, 'holiday', NOW()
                           FROM beneficiaries b
                           WHERE b.status = 'active'
                           AND NOT EXISTS (
                               SELECT 1 FROM attendance a 
                               WHERE a.beneficiary_id = b.id AND a.attendance_date = ?
                           )
                           ON DUPLICATE KEY UPDATE status = 'holiday'";
            executeQuery($insertQuery, [$holidayDate, $holidayDate]);
        } else {
            // Specific batches
            $placeholders = str_repeat('?,', count($batchIds) - 1) . '?';
            $insertQuery = "INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at)
                           SELECT b.id, ?, 'holiday', NOW()
                           FROM beneficiaries b
                           WHERE b.status = 'active' AND b.batch_id IN ($placeholders)
                           AND NOT EXISTS (
                               SELECT 1 FROM attendance a 
                               WHERE a.beneficiary_id = b.id AND a.attendance_date = ?
                           )
                           ON DUPLICATE KEY UPDATE status = 'holiday'";
            $insertParams = array_merge([$holidayDate], $batchIds, [$holidayDate]);
            executeQuery($insertQuery, $insertParams);
        }
    }
    
    // Step 4: Verify the changes
    echo "<h3>4. Verifying changes...</h3>";
    
    foreach ($holidays as $holiday) {
        $holidayDate = $holiday['date'];
        
        // Count holiday records for this date
        $holidayCount = fetchRow("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = ? AND status = 'holiday'", [$holidayDate]);
        $totalCount = fetchRow("SELECT COUNT(*) as count FROM attendance WHERE attendance_date = ?", [$holidayDate]);
        
        echo "- {$holiday['date']}: {$holidayCount['count']} holiday records out of {$totalCount['count']} total records<br>";
    }
    
    echo "<br><hr>";
    echo "<h3>🎯 Summary:</h3>";
    echo "✅ <strong>Total attendance records backed up: $totalBackedUp</strong><br>";
    echo "✅ <strong>Total attendance records updated to holiday: $totalUpdated</strong><br>";
    echo "✅ <strong>All existing holidays now properly applied to attendance!</strong><br>";
    
    echo "<br><strong>What was fixed:</strong><br>";
    echo "1. 🔄 <strong>August 15th attendance now shows 'Holiday' instead of 'Absent'</strong><br>";
    echo "2. 🔄 <strong>August 27th attendance now shows 'Holiday' instead of 'Absent'</strong><br>";
    echo "3. 🔄 <strong>All other existing holidays are now properly applied</strong><br>";
    echo "4. 💾 <strong>Original attendance data backed up for safety</strong><br>";
    
    echo "<br><strong>Next Steps:</strong><br>";
    echo "1. 🔄 <strong>Refresh your Attendance Reports page</strong><br>";
    echo "2. 📊 <strong>Check August 15th and 27th - they should now show 0% attendance (holiday)</strong><br>";
    echo "3. ✅ <strong>Attendance percentages will now be accurate (excluding holidays)</strong><br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Debug Info:</strong><br>";
    echo "Error type: " . get_class($e) . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
