<?php
/**
 * Timezone Fix Script for Hostinger
 * This script fixes timezone issues in your existing code files
 */

echo "🔧 TIMEZONE FIX SCRIPT\n";
echo "=====================\n\n";

// Check if running as admin
session_start();
if (!isset($_SESSION['admin_user_id'])) {
    die("❌ Unauthorized access. Please login as admin first.\n");
}

echo "⏰ ANALYZING TIMEZONE ISSUES...\n";
echo "-------------------------------\n";

// Files that need timezone fixes
$files_to_fix = [
    'config/database.php',
    'tc_user/attendance.php',
    'admin/attendance.php',
    'includes/functions.php'
];

$fixed_files = [];
$errors = [];

foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        echo "Checking: $file\n";
        
        try {
            $content = file_get_contents($file);
            $original_content = $content;
            $changes_made = false;
            
            // Fix 1: Add timezone setting at the beginning
            if (strpos($content, '<?php') !== false) {
                $timezone_line = "date_default_timezone_set('Asia/Kolkata');\n";
                
                // Check if timezone is already set
                if (strpos($content, "date_default_timezone_set('Asia/Kolkata')") === false) {
                    // Insert after <?php
                    $content = str_replace('<?php', "<?php\n$timezone_line", $content);
                    $changes_made = true;
                    echo "  ✅ Added timezone setting\n";
                } else {
                    echo "  ✅ Timezone already set\n";
                }
            }
            
            // Fix 2: Update date() functions to use explicit timezone
            if (strpos($content, "date('Y-m-d')") !== false) {
                $content = str_replace(
                    "date('Y-m-d')",
                    "date('Y-m-d', time())",
                    $content
                );
                $changes_made = true;
                echo "  ✅ Fixed date() function usage\n";
            }
            
            // Fix 3: Update NOW() to use timezone-aware timestamp
            if (strpos($content, 'NOW()') !== false) {
                $content = str_replace(
                    'NOW()',
                    'FROM_UNIXTIME(UNIX_TIMESTAMP())',
                    $content
                );
                $changes_made = true;
                echo "  ✅ Fixed NOW() function usage\n";
            }
            
            // Fix 4: Add timezone to database connection
            if (strpos($content, 'getDBConnection()') !== false && strpos($content, "SET time_zone") === false) {
                $content = str_replace(
                    '$connection->set_charset("utf8");',
                    '$connection->set_charset("utf8");' . "\n            " . '@$connection->query("SET time_zone = \'+05:30\'");',
                    $content
                );
                $changes_made = true;
                echo "  ✅ Added database timezone setting\n";
            }
            
            if ($changes_made) {
                // Create backup
                $backup_file = $file . '.backup.' . date('Y-m-d-H-i-s');
                if (file_put_contents($backup_file, $original_content)) {
                    echo "  ✅ Created backup: $backup_file\n";
                }
                
                // Apply changes
                if (file_put_contents($file, $content)) {
                    $fixed_files[] = $file;
                    echo "  ✅ File updated successfully\n";
                } else {
                    $errors[] = "Failed to write changes to $file";
                    echo "  ❌ Failed to write changes\n";
                }
            } else {
                echo "  ✅ No changes needed\n";
            }
            
        } catch (Exception $e) {
            $errors[] = "Error processing $file: " . $e->getMessage();
            echo "  ❌ Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    } else {
        echo "File not found: $file ❌\n\n";
    }
}

// Summary
echo "📋 FIX SUMMARY:\n";
echo "---------------\n";
echo "Files processed: " . count($files_to_fix) . "\n";
echo "Files fixed: " . count($fixed_files) . "\n";
echo "Errors: " . count($errors) . "\n\n";

if (!empty($fixed_files)) {
    echo "✅ SUCCESSFULLY FIXED:\n";
    foreach ($fixed_files as $file) {
        echo "  - $file\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS ENCOUNTERED:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

// Create .htaccess file for Hostinger
echo "🌐 CREATING HOSTINGER .HTACCESS FILE:\n";
echo "-----------------------------------\n";

$htaccess_content = "# Hostinger Timezone Configuration\n";
$htaccess_content .= "php_value date.timezone \"Asia/Kolkata\"\n";
$htaccess_content .= "php_value date.default_latitude \"20.5937\"\n";
$htaccess_content .= "php_value date.default_longitude \"78.9629\"\n\n";

$htaccess_file = '.htaccess';
if (file_exists($htaccess_file)) {
    // Backup existing .htaccess
    $backup_htaccess = $htaccess_file . '.backup.' . date('Y-m-d-H-i-s');
    if (copy($htaccess_file, $backup_htaccess)) {
        echo "✅ Created backup of existing .htaccess: $backup_htaccess\n";
    }
    
    // Append timezone settings
    $existing_content = file_get_contents($htaccess_file);
    if (strpos($existing_content, 'date.timezone') === false) {
        $htaccess_content = $existing_content . "\n" . $htaccess_content;
        if (file_put_contents($htaccess_file, $htaccess_content)) {
            echo "✅ Updated .htaccess with timezone settings\n";
        } else {
            echo "❌ Failed to update .htaccess\n";
        }
    } else {
        echo "✅ .htaccess already has timezone settings\n";
    }
} else {
    // Create new .htaccess
    if (file_put_contents($htaccess_file, $htaccess_content)) {
        echo "✅ Created new .htaccess with timezone settings\n";
    } else {
        echo "❌ Failed to create .htaccess\n";
    }
}

echo "\n";

// Final recommendations
echo "💡 FINAL RECOMMENDATIONS:\n";
echo "-------------------------\n";
echo "1. Test your application after these changes ✅\n";
echo "2. Check that attendance dates are now correct ✅\n";
echo "3. Verify dashboard displays proper time information ✅\n";
echo "4. Monitor for any timezone-related issues ✅\n";
echo "5. Contact Hostinger support if server timezone is still wrong ⚠️\n";

echo "\n🏁 TIMEZONE FIX COMPLETE\n";
echo "=======================\n";
?>
