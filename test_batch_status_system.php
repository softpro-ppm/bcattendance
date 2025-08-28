<?php
/**
 * Test Script for Enhanced Batch Status Management System
 * This script tests the new batch status re-evaluation functionality
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "🧪 Testing Enhanced Batch Status Management System\n";
echo "================================================\n\n";

// Test 1: Check if the new functions exist
echo "1. Testing Function Availability:\n";
if (function_exists('reEvaluateBatchStatus')) {
    echo "   ✅ reEvaluateBatchStatus function exists\n";
} else {
    echo "   ❌ reEvaluateBatchStatus function missing\n";
}

if (function_exists('reEvaluateAllBatchStatuses')) {
    echo "   ✅ reEvaluateAllBatchStatuses function exists\n";
} else {
    echo "   ❌ reEvaluateAllBatchStatuses function missing\n";
}

if (function_exists('forceBatchStatusChange')) {
    echo "   ✅ forceBatchStatusChange function exists\n";
} else {
    echo "   ❌ forceBatchStatusChange function missing\n";
}

echo "\n";

// Test 2: Check current batch statuses
echo "2. Current Batch Statuses:\n";
$batches = fetchAll("
    SELECT id, name, code, start_date, end_date, status 
    FROM batches 
    ORDER BY id
");

foreach ($batches as $batch) {
    $currentDate = date('Y-m-d');
    $startDate = $batch['start_date'];
    $endDate = $batch['end_date'];
    
    // Calculate expected status
    if ($currentDate < $startDate) {
        $expectedStatus = 'inactive';
    } elseif ($currentDate >= $startDate && $currentDate <= $endDate) {
        $expectedStatus = 'active';
    } else {
        $expectedStatus = 'completed';
    }
    
    $statusMatch = ($batch['status'] === $expectedStatus) ? '✅' : '⚠️';
    echo "   {$statusMatch} {$batch['name']} ({$batch['code']}): Current={$batch['status']}, Expected={$expectedStatus}\n";
    echo "      Dates: {$startDate} to {$endDate}\n";
}

echo "\n";

// Test 3: Test specific batch re-evaluation (GARUGUBILLI BATCH 2 - ID 15)
echo "3. Testing GARUGUBILLI BATCH 2 (ID 15) Re-evaluation:\n";
$batchId = 15;
$result = reEvaluateBatchStatus($batchId);

if ($result['success']) {
    echo "   ✅ Re-evaluation successful: {$result['message']}\n";
    if ($result['status_changed']) {
        echo "   🔄 Status changed from '{$result['old_status']}' to '{$result['new_status']}'\n";
        echo "   👥 {$result['beneficiaries_updated']} beneficiaries updated\n";
    } else {
        echo "   ℹ️  No status change needed\n";
    }
} else {
    echo "   ❌ Re-evaluation failed: {$result['message']}\n";
}

echo "\n";

// Test 4: Check updated status
echo "4. Updated Batch Status:\n";
$updatedBatch = fetchRow("SELECT id, name, code, status FROM batches WHERE id = ?", [$batchId], 'i');
if ($updatedBatch) {
    echo "   📊 {$updatedBatch['name']} ({$updatedBatch['code']}): Status = {$updatedBatch['status']}\n";
} else {
    echo "   ❌ Could not fetch updated batch information\n";
}

echo "\n";

// Test 5: Check beneficiary statuses
echo "5. Beneficiary Statuses for GARUGUBILLI BATCH 2:\n";
$beneficiaries = fetchAll("
    SELECT status, COUNT(*) as count 
    FROM beneficiaries 
    WHERE batch_id = ? 
    GROUP BY status
", [$batchId], 'i');

if ($beneficiaries) {
    foreach ($beneficiaries as $ben) {
        echo "   👥 {$ben['status']}: {$ben['count']} students\n";
    }
} else {
    echo "   ❌ No beneficiaries found for this batch\n";
}

echo "\n";

// Test 6: Check if batch_status_log table exists
echo "6. Checking Batch Status Log Table:\n";
try {
    $logCheck = fetchAll("SELECT COUNT(*) as count FROM batch_status_log");
    echo "   ✅ batch_status_log table exists with " . $logCheck[0]['count'] . " records\n";
} catch (Exception $e) {
    echo "   ❌ batch_status_log table missing or error: " . $e->getMessage() . "\n";
    echo "   💡 Run the SQL in sql/batch_status_log.sql to create the table\n";
}

echo "\n";
echo "🎯 Test Complete! Check the results above.\n";
echo "📝 If you see any ❌ errors, they need to be resolved.\n";
echo "🚀 If all ✅ tests pass, your system is ready!\n";
?>
