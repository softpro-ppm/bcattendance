<?php
// Test timezone and date settings
echo "<h2>Timezone and Date Test</h2>";
echo "<p><strong>Current PHP timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current server time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>IST timezone time:</strong> " . getCurrentISTDateTime() . "</p>";
echo "<p><strong>UTC time:</strong> " . gmdate('Y-m-d H:i:s') . "</p>";

// Test MySQL connection and timezone
require_once '../config/database.php';

if (isset($connection)) {
    echo "<h3>Database Timezone Test</h3>";
    
    // Test global timezone
    $result = $connection->query("SELECT @@global.time_zone as global_timezone, @@session.time_zone as session_timezone");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>Global MySQL timezone:</strong> " . ($row['global_timezone'] ?? 'N/A') . "</p>";
        echo "<p><strong>Session MySQL timezone:</strong> " . ($row['session_timezone'] ?? 'N/A') . "</p>";
    }
    
    // Test MySQL CURDATE() function
    $result = $connection->query("SELECT CURDATE() as curdate, CURDATE() + INTERVAL 0 SECOND as curdate_time");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>MySQL CURDATE():</strong> " . ($row['curdate'] ?? 'N/A') . "</p>";
        echo "<p><strong>MySQL CURDATE() + 0 seconds:</strong> " . ($row['curdate_time'] ?? 'N/A') . "</p>";
    }
    
    // Test MySQL NOW() function
    $result = $connection->query("SELECT NOW() as now_time, UTC_TIMESTAMP() as utc_time");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>MySQL NOW():</strong> " . ($row['now_time'] ?? 'N/A') . "</p>";
        echo "<p><strong>MySQL UTC_TIMESTAMP():</strong> " . ($row['utc_time'] ?? 'N/A') . "</p>";
    }
    
    // Test setting session timezone
    echo "<h3>Setting Session Timezone</h3>";
    $connection->query("SET time_zone = '+05:30'");
    $connection->query("SET @@session.time_zone = '+05:30'");
    
    // Test again after setting
    $result = $connection->query("SELECT @@session.time_zone as session_timezone");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>Session timezone after SET:</strong> " . ($row['session_timezone'] ?? 'N/A') . "</p>";
    }
    
    // Test CURDATE() after setting timezone
    $result = $connection->query("SELECT CURDATE() as curdate_after, NOW() as now_after");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p><strong>CURDATE() after timezone SET:</strong> " . ($row['curdate_after'] ?? 'N/A') . "</p>";
        echo "<p><strong>NOW() after timezone SET:</strong> " . ($row['now_after'] ?? 'N/A') . "</p>";
    }
    
    $connection->close();
} else {
    echo "<p><strong>Database connection failed</strong></p>";
}

// Check system time
if (function_exists('shell_exec')) {
    $system_time = shell_exec('date');
    echo "<p><strong>System time (shell):</strong> " . trim($system_time) . "</p>";
}

// PHP configuration
echo "<h3>PHP Configuration</h3>";
echo "<p><strong>date.timezone setting:</strong> " . ini_get('date.timezone') . "</p>";
echo "<p><strong>date.default_latitude:</strong> " . ini_get('date.default_latitude') . "</p>";
echo "<p><strong>date.default_longitude:</strong> " . ini_get('date.default_longitude') . "</p>";

// Manual IST calculation
echo "<h3>Manual IST Calculations</h3>";
$utc_time = time();
$ist_offset = 5.5 * 3600; // 5.5 hours in seconds
$ist_time = $utc_time + $ist_offset;

echo "<p><strong>UTC timestamp:</strong> " . $utc_time . "</p>";
echo "<p><strong>IST timestamp:</strong> " . $ist_time . "</p>";
echo "<p><strong>UTC date:</strong> " . gmdate('Y-m-d H:i:s', $utc_time) . "</p>";
echo "<p><strong>IST date:</strong> " . gmdate('Y-m-d H:i:s', $ist_time) . "</p>";

// Test the utility functions
echo "<h3>Utility Function Tests</h3>";
echo "<p><strong>getCurrentISTDate():</strong> " . getCurrentISTDate() . "</p>";
echo "<p><strong>getCurrentISTDateTime():</strong> " . getCurrentISTDateTime() . "</p>";

// Summary
echo "<h3>Summary</h3>";
echo "<p><strong>Expected IST date:</strong> " . date('Y-m-d', $ist_time) . "</p>";
echo "<p><strong>PHP date('Y-m-d'):</strong> " . date('Y-m-d') . "</p>";
echo "<p><strong>Utility function result:</strong> " . getCurrentISTDate() . "</p>";

if (date('Y-m-d') === getCurrentISTDate()) {
    echo "<p style='color: green;'><strong>✅ SUCCESS: PHP date matches IST date!</strong></p>";
} else {
    echo "<p style='color: red;'><strong>❌ ISSUE: PHP date does not match IST date!</strong></p>";
}
?>
