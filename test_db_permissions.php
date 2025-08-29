<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🔍 Database Permission Test</h1>";
echo "<p>This script will test database permissions and connection.</p>";

try {
    $conn = getDBConnection();
    echo "<h2>✅ Database connection: Working</h2>";
    
    // Test 1: Check if we can read from holidays table
    echo "<h3>1. Testing READ permissions...</h3>";
    $holidaysTest = $conn->query("SELECT COUNT(*) as count FROM holidays");
    if ($holidaysTest) {
        $count = $holidaysTest->fetch_assoc()['count'];
        echo "✅ Can read from holidays table: {$count} records found<br>";
    } else {
        echo "❌ Cannot read from holidays table: " . $conn->error . "<br>";
    }
    
    // Test 2: Check if we can read from batches table
    echo "<h3>2. Testing batches table access...</h3>";
    $batchesTest = $conn->query("SELECT COUNT(*) as count FROM batches");
    if ($batchesTest) {
        $count = $batchesTest->fetch_assoc()['count'];
        echo "✅ Can read from batches table: {$count} records found<br>";
    } else {
        echo "❌ Cannot read from batches table: " . $conn->error . "<br>";
    }
    
    // Test 3: Check if batch_holidays table exists
    echo "<h3>3. Checking batch_holidays table...</h3>";
    $tableTest = $conn->query("SHOW TABLES LIKE 'batch_holidays'");
    if ($tableTest && $tableTest->num_rows > 0) {
        echo "✅ batch_holidays table exists<br>";
    } else {
        echo "❌ batch_holidays table does not exist<br>";
    }
    
    // Test 4: Try to create a simple test table
    echo "<h3>4. Testing CREATE TABLE permissions...</h3>";
    
    // First, try to create a simple test table
    $testTableName = "test_table_" . time();
    $createTestTable = "CREATE TABLE IF NOT EXISTS {$testTableName} (id INT PRIMARY KEY, name VARCHAR(50))";
    
    $result = $conn->query($createTestTable);
    if ($result) {
        echo "✅ Can create tables - permissions are fine<br>";
        
        // Clean up test table
        $conn->query("DROP TABLE {$testTableName}");
        echo "🧹 Test table cleaned up<br>";
        
        // Now try to create the actual batch_holidays table
        echo "<h3>5. Creating batch_holidays table...</h3>";
        
        $createBatchHolidays = "
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
        
        $createResult = $conn->query($createBatchHolidays);
        if ($createResult) {
            echo "✅ <strong>batch_holidays table created successfully!</strong><br>";
            
            // Verify it was created
            $verifyTable = $conn->query("SHOW TABLES LIKE 'batch_holidays'");
            if ($verifyTable && $verifyTable->num_rows > 0) {
                echo "✅ Table verification: batch_holidays table now exists<br>";
            }
        } else {
            echo "❌ Failed to create batch_holidays table: " . $conn->error . "<br>";
        }
        
    } else {
        echo "❌ Cannot create tables: " . $conn->error . "<br>";
        echo "This suggests a database permission issue.<br>";
    }
    
    // Test 5: Check current user and permissions
    echo "<h3>6. Database user information...</h3>";
    $userInfo = $conn->query("SELECT USER(), DATABASE()");
    if ($userInfo) {
        $user = $userInfo->fetch_assoc();
        echo "Current user: " . ($user['USER()'] ?? 'Unknown') . "<br>";
        echo "Current database: " . ($user['DATABASE()'] ?? 'Unknown') . "<br>";
    }
    
    echo "<br><hr>";
    echo "<h3>🎯 Summary:</h3>";
    
    if ($conn->query("SHOW TABLES LIKE 'batch_holidays'")->num_rows > 0) {
        echo "✅ <strong>SUCCESS: batch_holidays table is now created!</strong><br>";
        echo "✅ <strong>You can now create holidays with batch assignments!</strong><br>";
        echo "✅ <strong>Edit functionality will work!</strong><br>";
        echo "✅ <strong>Coverage information will display correctly!</strong><br>";
        
        echo "<br><strong>Next Steps:</strong><br>";
        echo "1. 🔄 <strong>Refresh your Manage Holidays page</strong><br>";
        echo "2. ➕ <strong>Try creating your GL Puram holiday again</strong><br>";
        echo "3. ✏️ <strong>Use Edit buttons to fix existing holiday coverage</strong><br>";
    } else {
        echo "❌ <strong>FAILED: Could not create batch_holidays table</strong><br>";
        echo "This suggests a database permission or configuration issue.<br>";
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
