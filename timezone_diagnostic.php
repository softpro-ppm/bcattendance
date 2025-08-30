<?php
/**
 * Hostinger Timezone Diagnostic Script
 * This script checks all timezone-related settings on your Hostinger server
 */

echo "🔍 HOSTINGER TIMEZONE DIAGNOSTIC\n";
echo "================================\n\n";

// Check PHP timezone settings
echo "⏰ PHP TIMEZONE SETTINGS:\n";
echo "------------------------\n";
echo "Default Timezone: " . date_default_timezone_get() . "\n";
echo "Current PHP Time: " . date('Y-m-d H:i:s') . "\n";
echo "Current PHP Date: " . date('Y-m-d') . "\n";
echo "Server Request Time: " . date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']) . "\n\n";

// Check if timezone functions are available
echo "🔧 PHP TIMEZONE FUNCTIONS:\n";
echo "--------------------------\n";
echo "date_default_timezone_set(): " . (function_exists('date_default_timezone_set') ? 'Available ✅' : 'Not Available ❌') . "\n";
echo "DateTimeZone class: " . (class_exists('DateTimeZone') ? 'Available ✅' : 'Not Available ❌') . "\n";
echo "timezone_identifiers_list(): " . (function_exists('timezone_identifiers_list') ? 'Available ✅' : 'Not Available ❌') . "\n\n";

// Check available timezones
echo "🌍 AVAILABLE TIMEZONES:\n";
echo "----------------------\n";
if (function_exists('timezone_identifiers_list')) {
    $timezones = timezone_identifiers_list();
    $india_timezones = array_filter($timezones, function($tz) {
        return strpos($tz, 'Asia/Kolkata') !== false || strpos($tz, 'Asia/Calcutta') !== false;
    });
    
    if (!empty($india_timezones)) {
        echo "India timezones found: " . implode(', ', $india_timezones) . " ✅\n";
    } else {
        echo "No India timezones found ❌\n";
    }
    
    echo "Total timezones available: " . count($timezones) . "\n";
} else {
    echo "Cannot check available timezones ❌\n";
}
echo "\n";

// Test timezone setting
echo "🧪 TIMEZONE SETTING TEST:\n";
echo "------------------------\n";
$original_timezone = date_default_timezone_get();
echo "Original timezone: " . $original_timezone . "\n";

// Try to set timezone to Asia/Kolkata
if (date_default_timezone_set('Asia/Kolkata')) {
    echo "Successfully set timezone to Asia/Kolkata ✅\n";
    echo "New timezone: " . date_default_timezone_get() . "\n";
    echo "New time: " . date('Y-m-d H:i:s') . "\n";
    echo "New date: " . date('Y-m-d') . "\n";
} else {
    echo "Failed to set timezone to Asia/Kolkata ❌\n";
}

// Restore original timezone
date_default_timezone_set($original_timezone);
echo "Restored original timezone: " . date_default_timezone_get() . "\n\n";

// Check server environment
echo "🖥️  SERVER ENVIRONMENT:\n";
echo "----------------------\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Operating System: " . PHP_OS . "\n";
echo "Server Time: " . date('Y-m-d H:i:s', time()) . "\n";
echo "UTC Offset: " . date('P') . "\n\n";

// Check if we can connect to database for timezone check
echo "🗄️  DATABASE TIMEZONE CHECK:\n";
echo "---------------------------\n";
if (file_exists('config/database.php')) {
    try {
        require_once 'config/database.php';
        $dbTime = fetchRow("SELECT NOW() as db_time, CURDATE() as db_date, @@global.time_zone as global_tz, @@session.time_zone as session_tz");
        if ($dbTime) {
            echo "Database Time: " . $dbTime['db_time'] . "\n";
            echo "Database Date: " . $dbTime['db_date'] . "\n";
            echo "Global Timezone: " . $dbTime['global_tz'] . "\n";
            echo "Session Timezone: " . $dbTime['session_tz'] . "\n";
        } else {
            echo "Database connection failed ❌\n";
        }
    } catch (Exception $e) {
        echo "Database error: " . $e->getMessage() . " ❌\n";
    }
} else {
    echo "Database config file not found ❌\n";
}
echo "\n";

// Timezone analysis
echo "📊 TIMEZONE ANALYSIS:\n";
echo "--------------------\n";
$current_hour = (int)date('H');
$current_minute = (int)date('i');

// Check if current time makes sense for IST
if ($current_hour >= 6 && $current_hour <= 18) {
    echo "Current time (" . $current_hour . ":" . $current_minute . ") suggests daytime hours ✅\n";
} else {
    echo "Current time (" . $current_hour . ":" . $current_minute . ") suggests nighttime hours ⚠️\n";
}

// Check timezone offset
$utc_time = gmdate('Y-m-d H:i:s');
$local_time = date('Y-m-d H:i:s');
echo "UTC Time: " . $utc_time . "\n";
echo "Local Time: " . $local_time . "\n";

// Calculate offset
$utc_timestamp = strtotime($utc_time);
$local_timestamp = strtotime($local_time);
$offset_hours = ($local_timestamp - $utc_timestamp) / 3600;
echo "Calculated Offset: " . $offset_hours . " hours\n";

if (abs($offset_hours - 5.5) < 1) {
    echo "Offset is close to IST (+5:30) ✅\n";
} else {
    echo "Offset is NOT IST (+5:30) ❌\n";
    echo "Expected: +5.5 hours, Actual: " . $offset_hours . " hours\n";
}
echo "\n";

// Recommendations
echo "💡 RECOMMENDATIONS:\n";
echo "------------------\n";
if (date_default_timezone_get() !== 'Asia/Kolkata') {
    echo "1. Set PHP timezone to Asia/Kolkata in your code ✅\n";
} else {
    echo "1. PHP timezone is already set to Asia/Kolkata ✅\n";
}

if (abs($offset_hours - 5.5) < 1) {
    echo "2. Server timezone appears correct ✅\n";
} else {
    echo "2. Contact Hostinger support to set server timezone to IST ❌\n";
}

echo "3. Always use explicit timezone in your code ✅\n";
echo "4. Test timezone changes in development first ✅\n";
echo "\n";

echo "🏁 DIAGNOSTIC COMPLETE\n";
echo "=====================\n";
?>
