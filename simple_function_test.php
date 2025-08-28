<?php
echo "🧪 Simple Function Test\n";
echo "======================\n\n";

echo "1. Including functions.php...\n";
require_once 'includes/functions.php';
echo "   ✅ functions.php included\n\n";

echo "2. Checking functions...\n";
$functions = [
    'reEvaluateBatchStatus',
    'reEvaluateAllBatchStatuses', 
    'forceBatchStatusChange',
    'checkAndMarkCompletedBatches'
];

foreach ($functions as $funcName) {
    if (function_exists($funcName)) {
        echo "   ✅ {$funcName} exists\n";
    } else {
        echo "   ❌ {$funcName} missing\n";
    }
}

echo "\n3. Testing function call...\n";
try {
    if (function_exists('reEvaluateBatchStatus')) {
        echo "   ✅ Function exists, can be called\n";
    } else {
        echo "   ❌ Function doesn't exist\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test Complete!\n";
?>
