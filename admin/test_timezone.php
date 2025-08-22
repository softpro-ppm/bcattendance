<?php
// Test timezone and date settings
echo "<h2>Timezone and Date Test</h2>";

echo "<p><strong>Current PHP timezone:</strong> " . date_default_timezone_get() . "</p>";
echo "<p><strong>Current server time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test IST timezone
$ist = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
echo "<p><strong>IST timezone time:</strong> " . $ist->format('Y-m-d H:i:s') . "</p>";

// Test UTC timezone
$utc = new DateTime('now', new DateTimeZone('UTC'));
echo "<p><strong>UTC timezone time:</strong> " . $utc->format('Y-m-d H:i:s') . "</p>";

// Test database timezone
require_once '../config/database.php';
$conn = getDBConnection();
$result = $conn->query("SELECT NOW() as db_time, @@global.time_zone as global_tz, @@session.time_zone as session_tz");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p><strong>Database time:</strong> " . $row['db_time'] . "</p>";
    echo "<p><strong>Database global timezone:</strong> " . $row['global_tz'] . "</p>";
    echo "<p><strong>Database session timezone:</strong> " . $row['session_tz'] . "</p>";
}

// Test date functions
echo "<p><strong>date('Y-m-d'):</strong> " . date('Y-m-d') . "</p>";
echo "<p><strong>date('Y-m-d', time()):</strong> " . date('Y-m-d', time()) . "</p>";

// Test with different timezones
date_default_timezone_set('Asia/Kolkata');
echo "<p><strong>After setting IST timezone:</strong> " . date('Y-m-d H:i:s') . "</p>";

date_default_timezone_set('UTC');
echo "<p><strong>After setting UTC timezone:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test MySQL CURDATE() function
$result = $conn->query("SELECT CURDATE() as curdate, CURDATE() + INTERVAL 0 SECOND as curdate_time");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p><strong>MySQL CURDATE():</strong> " . $row['curdate'] . "</p>";
    echo "<p><strong>MySQL CURDATE() with time:</strong> " . $row['curdate_time'] . "</p>";
}

// Test what happens when we set timezone in database
$conn->query("SET time_zone = '+05:30'");
$result = $conn->query("SELECT CURDATE() as curdate_ist, NOW() as now_ist");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p><strong>MySQL CURDATE() after setting IST:</strong> " . $row['curdate_ist'] . "</p>";
    echo "<p><strong>MySQL NOW() after setting IST:</strong> " . $row['now_ist'] . "</p>";
}

// Show current time in different formats
echo "<h3>Current Time Analysis</h3>";
echo "<p><strong>Your local time (should be):</strong> " . date('Y-m-d H:i:s', time()) . " (IST)</p>";
echo "<p><strong>UTC time:</strong> " . gmdate('Y-m-d H:i:s') . "</p>";
echo "<p><strong>IST time (calculated):</strong> " . gmdate('Y-m-d H:i:s', time() + (5.5 * 3600)) . "</p>";

// Test if the issue is with the date() function specifically
echo "<h3>Date Function Test</h3>";
$timestamp = time();
echo "<p><strong>Current timestamp:</strong> " . $timestamp . "</p>";
echo "<p><strong>date('Y-m-d') from timestamp:</strong> " . date('Y-m-d', $timestamp) . "</p>";
echo "<p><strong>gmdate('Y-m-d') from timestamp:</strong> " . gmdate('Y-m-d', $timestamp) . "</p>";

// Test with explicit IST conversion
$ist_timestamp = $timestamp + (5.5 * 3600);
echo "<p><strong>IST timestamp (timestamp + 5.5 hours):</strong> " . $ist_timestamp . "</p>";
echo "<p><strong>date('Y-m-d') from IST timestamp:</strong> " . date('Y-m-d', $ist_timestamp) . "</p>";

// Check system time
echo "<h3>System Time Check</h3>";
if (function_exists('shell_exec')) {
    $system_time = shell_exec('date');
    echo "<p><strong>System time (shell):</strong> " . trim($system_time) . "</p>";
}

// Check PHP configuration
echo "<h3>PHP Configuration</h3>";
echo "<p><strong>date.timezone setting:</strong> " . ini_get('date.timezone') . "</p>";
echo "<p><strong>date.default_latitude:</strong> " . ini_get('date.default_latitude') . "</p>";
echo "<p><strong>date.default_longitude:</strong> " . ini_get('date.default_longitude') . "</p>";

// Check if there are any timezone-related errors
echo "<h3>Timezone Debugging</h3>";
$current_time = time();
$utc_time = gmdate('Y-m-d H:i:s', $current_time);
$ist_time = gmdate('Y-m-d H:i:s', $current_time + (5.5 * 3600));

echo "<p><strong>Current Unix timestamp:</strong> " . $current_time . "</p>";
echo "<p><strong>UTC time from timestamp:</strong> " . $utc_time . "</p>";
echo "<p><strong>IST time from timestamp:</strong> " . $ist_time . "</p>";

// Test what happens when we manually calculate the date
$ist_date = gmdate('Y-m-d', $current_time + (5.5 * 3600));
echo "<p><strong>IST date (manually calculated):</strong> " . $ist_date . "</p>";
?>
