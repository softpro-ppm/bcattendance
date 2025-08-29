<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>🔧 Create Missing batch_holidays Table</h1>";

try {
    $conn = getDBConnection();
    echo "<h2>✅ Database connection: Working</h2>";
    
    // Check if table exists
    echo "<h3>1. Checking if batch_holidays table exists...</h3>";
    $tableExists = fetchRow("SHOW TABLES LIKE 'batch_holidays'");
    
    if (!$tableExists) {
        echo "❌ <strong>batch_holidays table is missing!</strong><br>";
        echo "This is why you're getting 'Failed to store batch holiday relationship' errors.<br><br>";
        
        // Create the table
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
            
            FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
            FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
            
            INDEX idx_holiday_id (holiday_id),
            INDEX idx_batch_id (batch_id),
            INDEX idx_holiday_date (holiday_date),
            
            UNIQUE KEY unique_batch_holiday (batch_id, holiday_date)
        )";
        
        $result = executeQuery($createTable);
        if ($result) {
            echo "✅ <strong>batch_holidays table created successfully!</strong><br>";
        } else {
            echo "❌ Failed to create table<br>";
            exit;
        }
    } else {
        echo "✅ batch_holidays table already exists<br>";
    }
    
    // Verify table structure
    echo "<h3>3. Verifying table structure...</h3>";
    $structure = fetchAll("DESCRIBE batch_holidays");
    if ($structure) {
        echo "Table structure:<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($structure as $field) {
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
    
    // Check current relationships
    echo "<h3>4. Current batch-holiday relationships...</h3>";
    $relationships = fetchAll("SELECT COUNT(*) as count FROM batch_holidays");
    $relationshipCount = $relationships ? $relationships[0]['count'] : 0;
    echo "Existing relationships: {$relationshipCount}<br>";
    
    if ($relationshipCount == 0) {
        echo "💡 <strong>No relationships exist yet.</strong> This is why all holidays show 'All Mandals'.<br>";
        echo "After creating this table, you can use the Edit buttons to assign holidays to specific batches.<br>";
    }
    
    echo "<br><hr>";
    echo "<h3>🎉 Next Steps:</h3>";
    echo "1. ✅ <strong>Table created successfully!</strong><br>";
    echo "2. 🔄 <strong>Refresh your Manage Holidays page</strong><br>";
    echo "3. ✏️ <strong>Click Edit on a local holiday</strong> (like Girijana Vutsavalu)<br>";
    echo "4. 📋 <strong>Assign it to specific batches</strong> instead of all batches<br>";
    echo "5. 💾 <strong>Save changes</strong> and see coverage update correctly<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
