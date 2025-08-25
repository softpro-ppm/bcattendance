<?php
/**
 * Batch Completion Check Script
 * 
 * This script checks for batches that have ended and marks them as completed,
 * along with their beneficiaries.
 * 
 * Usage: Run this script manually or via cron job
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== BC Attendance - Batch Completion Check ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Check and mark completed batches
    $result = checkAndMarkCompletedBatches();
    
    if ($result['success']) {
        echo "✓ " . $result['message'] . "\n";
        echo "✓ Process completed successfully\n";
    } else {
        echo "✗ Error: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fatal Error: " . $e->getMessage() . "\n";
}

echo "\n=== End of Batch Completion Check ===\n";
?>
