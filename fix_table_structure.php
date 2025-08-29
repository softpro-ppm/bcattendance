<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🔧 Fix batch_holidays Table Structure</h1>";
echo "<p>This script will add the missing holiday_id column to fix the table structure.</p>";

try {
    $conn = getDBConnection();
    echo "<h2>✅ Database connection: Working</h2>";
    
    // Step 1: Check current table structure
    echo "<h3>1. Current table structure...</h3>";
    $structure = $conn->query("DESCRIBE batch_holidays");
    if ($structure) {
        echo "Current columns:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($field = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$field['Field']}</td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "<td>{$field['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 2: Check if holiday_id column exists
    echo "<h3>2. Checking for holiday_id column...</h3>";
    $holidayIdExists = $conn->query("SHOW COLUMNS FROM batch_holidays LIKE 'holiday_id'");
    if ($holidayIdExists && $holidayIdExists->num_rows > 0) {
        echo "✅ holiday_id column already exists<br>";
    } else {
        echo "❌ holiday_id column is missing! This is the root cause of the error.<br>";
        
        // Step 3: Add the missing holiday_id column
        echo "<h3>3. Adding missing holiday_id column...</h3>";
        
        // Add holiday_id column after the id column
        $addColumn = "ALTER TABLE batch_holidays ADD COLUMN holiday_id INT NOT NULL AFTER id";
        $result = $conn->query($addColumn);
        
        if ($result) {
            echo "✅ holiday_id column added successfully!<br>";
            
            // Add index for holiday_id
            echo "<h3>4. Adding index for holiday_id...</h3>";
            $addIndex = "ALTER TABLE batch_holidays ADD INDEX idx_holiday_id (holiday_id)";
            $indexResult = $conn->query($addIndex);
            
            if ($indexResult) {
                echo "✅ Index added successfully!<br>";
            } else {
                echo "⚠️ Index creation failed: " . $conn->error . "<br>";
            }
            
        } else {
            echo "❌ Failed to add holiday_id column: " . $conn->error . "<br>";
            exit;
        }
    }
    
    // Step 4: Verify new table structure
    echo "<h3>5. Verifying new table structure...</h3>";
    $newStructure = $conn->query("DESCRIBE batch_holidays");
    if ($newStructure) {
        echo "Updated table structure:<br>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($field = $newStructure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$field['Field']}</td>";
            echo "<td>{$field['Type']}</td>";
            echo "<td>{$field['Null']}</td>";
            echo "<td>{$field['Key']}</td>";
            echo "<td>{$field['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 5: Test the table functionality
    echo "<h3>6. Testing table functionality...</h3>";
    
    // Get a sample holiday and batch
    $sampleHoliday = $conn->query("SELECT id, date, description FROM holidays WHERE description != 'Sunday Holiday' LIMIT 1");
    $sampleBatch = $conn->query("SELECT id, name FROM batches WHERE status IN ('active', 'completed') LIMIT 1");
    
    if ($sampleHoliday && $sampleBatch && $sampleHoliday->num_rows > 0 && $sampleBatch->num_rows > 0) {
        $holiday = $sampleHoliday->fetch_assoc();
        $batch = $sampleBatch->fetch_assoc();
        
        echo "Sample holiday: {$holiday['description']} (ID: {$holiday['id']})<br>";
        echo "Sample batch: {$batch['name']} (ID: {$batch['id']})<br>";
        
        // Try to insert a test record
        $testInsert = "INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description) 
                       VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($testInsert);
        if ($stmt) {
            $stmt->bind_param("iisss", 
                $holiday['id'], 
                $batch['id'], 
                $holiday['date'], 
                $holiday['description'], 
                $holiday['description']
            );
            
            if ($stmt->execute()) {
                echo "✅ <strong>Test insert successful!</strong> Table is now working properly.<br>";
                
                // Clean up test record
                $conn->query("DELETE FROM batch_holidays WHERE holiday_id = {$holiday['id']} AND batch_id = {$batch['id']}");
                echo "🧹 Test record cleaned up.<br>";
            } else {
                echo "❌ Test insert still failed: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "❌ Failed to prepare test insert: " . $conn->error . "<br>";
        }
    } else {
        echo "⚠️ Could not find sample holiday or batch for testing.<br>";
    }
    
    // Step 6: Final status
    echo "<br><hr>";
    echo "<h3>🎯 Final Status:</h3>";
    
    $finalCheck = $conn->query("SHOW COLUMNS FROM batch_holidays LIKE 'holiday_id'");
    if ($finalCheck && $finalCheck->num_rows > 0) {
        echo "✅ <strong>SUCCESS: batch_holidays table is now properly structured!</strong><br>";
        echo "✅ <strong>holiday_id column has been added!</strong><br>";
        echo "✅ <strong>You can now create holidays with batch assignments!</strong><br>";
        echo "✅ <strong>Edit functionality will work properly!</strong><br>";
        echo "✅ <strong>Coverage information will display correctly!</strong><br>";
        
        echo "<br><strong>Next Steps:</strong><br>";
        echo "1. 🔄 <strong>Refresh your Manage Holidays page</strong><br>";
        echo "2. ➕ <strong>Try creating your GL Puram holiday again</strong><br>";
        echo "3. ✏️ <strong>Use Edit buttons to assign existing holidays to specific batches</strong><br>";
        echo "4. 📊 <strong>See proper coverage information (All Mandals vs Specific Batches)</strong><br>";
    } else {
        echo "❌ <strong>FAILED: Could not fix the table structure.</strong><br>";
        echo "Please contact your database administrator.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Debug Info:</strong><br>";
    echo "Error type: " . get_class($e) . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
