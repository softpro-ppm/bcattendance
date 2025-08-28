<?php
/**
 * Test Script for Enhanced Batch Status Management System
 * This script tests only the PHP functions without database connection
 */

echo "🧪 Testing Enhanced Batch Status Management System (Functions Only)\n";
echo "================================================================\n\n";

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

if (function_exists('checkAndMarkCompletedBatches')) {
    echo "   ✅ checkAndMarkCompletedBatches function exists\n";
} else {
    echo "   ❌ checkAndMarkCompletedBatches function missing\n";
}

echo "\n";

// Test 2: Check if required files exist
echo "2. Testing File Availability:\n";
$requiredFiles = [
    'includes/functions.php',
    'admin/batches.php',
    'admin/batch_status_manager.php',
    'sql/batch_status_log.sql',
    'includes/header.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ {$file} exists\n";
    } else {
        echo "   ❌ {$file} missing\n";
    }
}

echo "\n";

// Test 3: Check if functions.php can be included
echo "3. Testing Function File Inclusion:\n";
try {
    require_once 'includes/functions.php';
    echo "   ✅ functions.php included successfully\n";
} catch (Exception $e) {
    echo "   ❌ Error including functions.php: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Check function signatures
echo "4. Testing Function Signatures:\n";
if (function_exists('reEvaluateBatchStatus')) {
    $reflection = new ReflectionFunction('reEvaluateBatchStatus');
    $params = $reflection->getParameters();
    echo "   ✅ reEvaluateBatchStatus takes " . count($params) . " parameters\n";
    foreach ($params as $param) {
        echo "      - \${$param->getName()}" . ($param->isOptional() ? " (optional)" : "") . "\n";
    }
}

if (function_exists('reEvaluateAllBatchStatuses')) {
    $reflection = new ReflectionFunction('reEvaluateAllBatchStatuses');
    $params = $reflection->getParameters();
    echo "   ✅ reEvaluateAllBatchStatuses takes " . count($params) . " parameters\n";
}

if (function_exists('forceBatchStatusChange')) {
    $reflection = new ReflectionFunction('forceBatchStatusChange');
    $params = $reflection->getParameters();
    echo "   ✅ forceBatchStatusChange takes " . count($params) . " parameters\n";
    foreach ($params as $param) {
        echo "      - \${$param->getName()}" . ($param->isOptional() ? " (optional)" : "") . "\n";
    }
}

echo "\n";

// Test 5: Check if admin files can be parsed
echo "5. Testing Admin File Syntax:\n";
$adminFiles = [
    'admin/batches.php',
    'admin/batch_status_manager.php'
];

foreach ($adminFiles as $file) {
    $output = [];
    $returnCode = 0;
    exec("php -l {$file} 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "   ✅ {$file} - No syntax errors\n";
    } else {
        echo "   ❌ {$file} - Syntax errors found:\n";
        foreach ($output as $line) {
            echo "      {$line}\n";
        }
    }
}

echo "\n";

// Test 6: Check navigation integration
echo "6. Testing Navigation Integration:\n";
if (file_exists('includes/header.php')) {
    $headerContent = file_get_contents('includes/header.php');
    if (strpos($headerContent, 'batch_status_manager.php') !== false) {
        echo "   ✅ Batch Status Manager link found in navigation\n";
    } else {
        echo "   ❌ Batch Status Manager link missing from navigation\n";
    }
} else {
    echo "   ❌ header.php file not found\n";
}

echo "\n";
echo "🎯 Function Test Complete!\n";
echo "📝 If you see any ❌ errors, they need to be resolved.\n";
echo "🚀 If all ✅ tests pass, your functions are ready!\n";
echo "\n";
echo "💡 Next Steps:\n";
echo "   1. Ensure your database server is running\n";
echo "   2. Run the full test: php test_batch_status_system.php\n";
echo "   3. Test the web interface: admin/batch_status_manager.php\n";
?>
