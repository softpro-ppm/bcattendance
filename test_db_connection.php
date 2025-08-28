<?php
/**
 * Database Connection Test Script
 * Tests if we can connect to your remote database
 */

echo "🔌 Testing Database Connection\n";
echo "==============================\n\n";

// Test 1: Check if database config exists
echo "1. Checking Database Configuration:\n";
if (file_exists('config/database.php')) {
    echo "   ✅ config/database.php exists\n";
    
    // Include the config to see the settings
    require_once 'config/database.php';
    echo "   📊 DB Host: " . DB_HOST . "\n";
    echo "   📊 DB Name: " . DB_NAME . "\n";
    echo "   📊 DB User: " . DB_USERNAME . "\n";
    echo "   📊 DB Password: " . (strlen(DB_PASSWORD) > 0 ? "***" . substr(DB_PASSWORD, -3) : "NOT SET") . "\n";
} else {
    echo "   ❌ config/database.php missing\n";
    exit(1);
}

echo "\n";

// Test 2: Try to connect to database
echo "2. Testing Database Connection:\n";
try {
    $conn = getDBConnection();
    if ($conn && !$conn->connect_error) {
        echo "   ✅ Database connection successful!\n";
        echo "   📊 Server Info: " . $conn->server_info . "\n";
        echo "   📊 Database: " . $conn->database . "\n";
        
        // Test 3: Check if we can query the database
        echo "\n3. Testing Database Queries:\n";
        
        // Check if batches table exists
        $result = $conn->query("SHOW TABLES LIKE 'batches'");
        if ($result && $result->num_rows > 0) {
            echo "   ✅ batches table exists\n";
            
            // Count total batches
            $result = $conn->query("SELECT COUNT(*) as count FROM batches");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "   📊 Total batches: " . $row['count'] . "\n";
            }
        } else {
            echo "   ❌ batches table missing\n";
        }
        
        // Check if beneficiaries table exists
        $result = $conn->query("SHOW TABLES LIKE 'beneficiaries'");
        if ($result && $result->num_rows > 0) {
            echo "   ✅ beneficiaries table exists\n";
            
            // Count total beneficiaries
            $result = $conn->query("SELECT COUNT(*) as count FROM beneficiaries");
            if ($result) {
                $row = $result->fetch_assoc();
                echo "   📊 Total beneficiaries: " . $row['count'] . "\n";
            }
        } else {
            echo "   ❌ beneficiaries table missing\n";
        }
        
        // Check if batch_status_log table exists
        $result = $conn->query("SHOW TABLES LIKE 'batch_status_log'");
        if ($result && $result->num_rows > 0) {
            echo "   ✅ batch_status_log table exists\n";
        } else {
            echo "   ⚠️  batch_status_log table missing (will be created in Phase 1)\n";
        }
        
        $conn->close();
        
    } else {
        echo "   ❌ Database connection failed: " . ($conn ? $conn->connect_error : "Unknown error") . "\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database connection error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "🎯 Database Connection Test Complete!\n";
echo "📝 If connection successful, proceed to Phase 1 database setup\n";
echo "💡 If connection failed, check your database credentials and server status\n";
?>
