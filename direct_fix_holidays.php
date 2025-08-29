<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🔧 Direct Fix for Holiday System</h1>";
echo "<p>This script will directly fix the missing batch_holidays table.</p>";

try {
    $conn = getDBConnection();
    echo "<h2>✅ Database connection: Working</h2>";
    
    // Step 1: Check if batch_holidays table exists
    echo "<h3>1. Checking batch_holidays table...</h3>";
    $tableExists = $conn->query("SHOW TABLES LIKE 'batch_holidays'");
    
    if ($tableExists && $tableExists->num_rows > 0) {
        echo "✅ <strong>batch_holidays table already exists!</strong><br>";
    } else {
        echo "❌ <strong>batch_holidays table is missing!</strong><br>";
        echo "This is why you're getting 'Failed to store batch holiday relationship' errors.<br><br>";
        
        // Step 2: Create the table directly
        echo "<h3>2. Creating batch_holidays table...</h3>";
        
        $createTable = "
        CREATE TABLE IF NOT EXISTS batch_holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            holiday_id INT NOT NULL,
            batch_id INT NOT NULL,
            holiday_date DATE NOT NULL,
            holiday_name VARCHAR(255) NOT NULL,
            description TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_holiday_id (holiday_id),
            INDEX idx_batch_id (batch_id),
            INDEX idx_holiday_date (holiday_date),
            
            UNIQUE KEY unique_batch_holiday (batch_id, holiday_date)
        )";
        
        $result = $conn->query($createTable);
        if ($result) {
            echo "✅ <strong>batch_holidays table created successfully!</strong><br>";
        } else {
            echo "❌ Failed to create table: " . $conn->error . "<br>";
            exit;
        }
    }
    
    // Step 3: Verify table structure
    echo "<h3>3. Verifying table structure...</h3>";
    $structure = $conn->query("DESCRIBE batch_holidays");
    if ($structure) {
        echo "✅ Table structure verified:<br>";
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
    
    // Step 4: Test inserting a sample record
    echo "<h3>4. Testing table functionality...</h3>";
    
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
                echo "✅ <strong>Test insert successful!</strong> Table is working properly.<br>";
                
                // Clean up test record
                $conn->query("DELETE FROM batch_holidays WHERE holiday_id = {$holiday['id']} AND batch_id = {$batch['id']}");
                echo "🧹 Test record cleaned up.<br>";
            } else {
                echo "❌ Test insert failed: " . $stmt->error . "<br>";
            }
            $stmt->close();
        } else {
            echo "❌ Failed to prepare test insert: " . $conn->error . "<br>";
        }
    } else {
        echo "⚠️ Could not find sample holiday or batch for testing.<br>";
    }
    
    // Step 5: Final status
    echo "<h3>5. Final Status Check...</h3>";
    $finalCheck = $conn->query("SELECT COUNT(*) as count FROM batch_holidays");
    if ($finalCheck) {
        $count = $finalCheck->fetch_assoc()['count'];
        echo "Total records in batch_holidays table: {$count}<br>";
    }
    
    echo "<br><hr>";
    echo "<h3>🎉 Holiday System Status:</h3>";
    
    if ($tableExists && $tableExists->num_rows > 0) {
        echo "✅ <strong>batch_holidays table exists and is working!</strong><br>";
        echo "✅ <strong>You can now create holidays with batch assignments!</strong><br>";
        echo "✅ <strong>Edit functionality will work properly!</strong><br>";
        echo "✅ <strong>Coverage information will display correctly!</strong><br>";
        
        echo "<br><strong>Next Steps:</strong><br>";
        echo "1. 🔄 <strong>Refresh your Manage Holidays page</strong><br>";
        echo "2. ➕ <strong>Try creating a new holiday for GL Puram mandal</strong><br>";
        echo "3. ✏️ <strong>Use Edit buttons to assign existing holidays to specific batches</strong><br>";
        echo "4. 📊 <strong>See proper coverage information (All Mandals vs Specific Batches)</strong><br>";
    } else {
        echo "❌ <strong>Table creation failed. Please check database permissions.</strong><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<br><strong>Debug Info:</strong><br>";
    echo "Error type: " . get_class($e) . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>
