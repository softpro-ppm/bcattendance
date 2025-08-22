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
?>
