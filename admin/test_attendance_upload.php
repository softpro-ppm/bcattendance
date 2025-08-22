<?php
session_start();

echo "<h3>Debug Information</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session data: " . print_r($_SESSION, true) . "<br>";
echo "Current directory: " . __DIR__ . "<br>";
echo "Database file exists: " . (file_exists('../config/database.php') ? 'YES' : 'NO') . "<br>";
echo "Functions file exists: " . (file_exists('../includes/functions.php') ? 'YES' : 'NO') . "<br>";
echo "Attendance upload file exists: " . (file_exists('attendance_bulk_upload.php') ? 'YES' : 'NO') . "<br>";

// Try to include files
try {
    require_once '../config/database.php';
    echo "Database.php loaded successfully<br>";
} catch (Exception $e) {
    echo "Error loading database.php: " . $e->getMessage() . "<br>";
}

try {
    require_once '../includes/functions.php';
    echo "Functions.php loaded successfully<br>";
} catch (Exception $e) {
    echo "Error loading functions.php: " . $e->getMessage() . "<br>";
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<strong style='color: red;'>NOT LOGGED IN</strong><br>";
    echo "Available session keys: " . implode(', ', array_keys($_SESSION)) . "<br>";
} else {
    echo "<strong style='color: green;'>LOGGED IN</strong><br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
}

echo '<br><a href="dashboard.php">Back to Dashboard</a>';
echo '<br><a href="attendance_bulk_upload.php">Try Attendance Upload</a>';
?>
