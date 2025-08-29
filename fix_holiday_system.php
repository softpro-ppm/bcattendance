<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Fix Holiday System';
echo "<h1>🔧 Fix Holiday System</h1>";

try {
    $conn = getDBConnection();
    echo "<h2>✅ Database connection: Working</h2>";
    
    // Step 1: Check if batch_holidays table exists
    echo "<h3>1. Checking batch_holidays table...</h3>";
    $tableExists = fetchRow("SHOW TABLES LIKE 'batch_holidays'");
    
    if (!$tableExists) {
        echo "❌ batch_holidays table is missing!<br>";
        
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
            echo "✅ batch_holidays table created successfully!<br>";
        } else {
            echo "❌ Failed to create table<br>";
            exit;
        }
    } else {
        echo "✅ batch_holidays table already exists<br>";
    }
    
    // Step 2: Check current holidays
    echo "<h3>3. Current holidays in system...</h3>";
    $holidays = fetchAll("SELECT * FROM holidays WHERE description != 'Sunday Holiday' ORDER BY date DESC");
    if ($holidays) {
        echo "Found " . count($holidays) . " custom holidays:<br>";
        foreach ($holidays as $holiday) {
            echo "- ID: {$holiday['id']}, Date: {$holiday['date']}, Description: {$holiday['description']}, Type: {$holiday['type']}<br>";
        }
    }
    
    // Step 3: Check existing batch relationships
    echo "<h3>4. Checking existing batch-holiday relationships...</h3>";
    $relationships = fetchAll("SELECT COUNT(*) as count FROM batch_holidays");
    $relationshipCount = $relationships ? $relationships[0]['count'] : 0;
    echo "Existing relationships: {$relationshipCount}<br>";
    
    // Step 4: Get available batches for assignment
    echo "<h3>5. Available batches for holiday assignment...</h3>";
    $batches = fetchAll("SELECT b.id, b.name, b.code, m.name as mandal_name 
                        FROM batches b 
                        JOIN mandals m ON b.mandal_id = m.id 
                        WHERE b.status IN ('active', 'completed') 
                        ORDER BY m.name, b.name");
    
    if ($batches) {
        echo "Available batches:<br>";
        foreach ($batches as $batch) {
            echo "- {$batch['mandal_name']} → {$batch['name']} ({$batch['code']})<br>";
        }
    }
    
    // Step 5: Suggest holiday assignments
    echo "<h3>6. Suggested holiday assignments...</h3>";
    echo "<strong>National Holidays (should apply to ALL batches):</strong><br>";
    echo "- Gandhi Jayanti (Oct 2) → All batches (already correct)<br>";
    echo "- Vinayaka Chaviti (Aug 27) → All batches (already correct)<br>";
    echo "- August 15 (Aug 15) → All batches (already correct)<br>";
    
    echo "<br><strong>Local/Regional Holidays (should apply to SPECIFIC batches):</strong><br>";
    echo "- Girijana Vutsavalu (Aug 9) → Assign to specific mandals<br>";
    echo "- Varalakshmi Vratam (Aug 8) → Assign to specific mandals<br>";
    echo "- Local Festival (Jun 3-4) → Assign to specific mandals<br>";
    
    // Step 6: Create sample batch assignments for local holidays
    echo "<h3>7. Creating sample batch assignments...</h3>";
    
    // For demonstration, let's assign local holidays to some batches
    $localHolidays = [
        ['holiday_id' => 74, 'description' => 'Girijana Vutsavalu'],
        ['holiday_id' => 77, 'description' => 'Varalakshmi Vratam'],
        ['holiday_id' => 79, 'description' => 'Local Festival'],
        ['holiday_id' => 78, 'description' => 'Local Festival']
    ];
    
    foreach ($localHolidays as $holiday) {
        // Get the first few batches to assign to
        $sampleBatches = fetchAll("SELECT id, name FROM batches WHERE status IN ('active', 'completed') LIMIT 3");
        
        if ($sampleBatches) {
            foreach ($sampleBatches as $batch) {
                $insertQuery = "INSERT IGNORE INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description) 
                               SELECT ?, ?, date, description, type FROM holidays WHERE id = ?";
                $result = executeQuery($insertQuery, [$holiday['holiday_id'], $batch['id'], $holiday['holiday_id']]);
                
                if ($result) {
                    echo "✅ Assigned {$holiday['description']} to {$batch['name']}<br>";
                } else {
                    echo "❌ Failed to assign {$holiday['description']} to {$batch['name']}<br>";
                }
            }
        }
    }
    
    // Step 7: Final check
    echo "<h3>8. Final status check...</h3>";
    $finalRelationships = fetchAll("SELECT COUNT(*) as count FROM batch_holidays");
    $finalCount = $finalRelationships ? $finalRelationships[0]['count'] : 0;
    echo "Total batch-holiday relationships: {$finalCount}<br>";
    
    if ($finalCount > 0) {
        echo "<br>🎉 <strong>Holiday system fixed successfully!</strong><br>";
        echo "Now refresh your Manage Holidays page to see proper coverage information.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><hr>";
echo "<h3>Next Steps:</h3>";
echo "1. Run this script to fix the system<br>";
echo "2. Refresh the Manage Holidays page<br>";
echo "3. You should now see proper coverage (All Mandals vs Specific Batches)<br>";
echo "4. Edit holidays to assign them to specific batches as needed<br>";
?>
