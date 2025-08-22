<?php
require_once 'config/database.php';

echo "Checking batches table structure...\n";

try {
    // Check if batches table exists and get its structure
    $result = fetchAll("DESCRIBE batches");
    
    echo "Batches table columns:\n";
    foreach ($result as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\nSample batch data:\n";
    $batches = fetchAll("SELECT * FROM batches LIMIT 3");
    foreach ($batches as $batch) {
        print_r($batch);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
