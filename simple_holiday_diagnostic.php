<?php
/**
 * SIMPLE HOLIDAY DIAGNOSTIC SCRIPT
 * 
 * This script helps diagnose why specific dates are showing as blank instead of "H"
 * without requiring database connection.
 */

echo "=== SIMPLE HOLIDAY DIAGNOSTIC ===\n";
echo "Checking why specific dates are showing as blank...\n\n";

// Define the problematic dates
$problemDates = [
    '2025-05-20' => ['KUR_GLP_B1', 'KUR_GLP_B2'],
    '2025-05-21' => ['KUR_GLP_B1'],
    '2025-08-09' => ['KUR_GLP_B1', 'KUR_GLP_B2'],
    '2025-06-03' => ['PAR_PAR_B1', 'PAR_PAR_B2'],
    '2025-06-04' => ['PAR_PAR_B1', 'PAR_PAR_B2']
];

echo "Problematic dates:\n";
foreach ($problemDates as $date => $batches) {
    echo "- $date: " . implode(', ', $batches) . "\n";
}
echo "\n";

echo "POSSIBLE CAUSES:\n";
echo "1. Holiday data missing from holidays table\n";
echo "2. Batch-specific holiday assignments missing from batch_holidays table\n";
echo "3. Export function not properly detecting batch-specific holidays\n";
echo "4. Attendance records exist but not being converted to 'H'\n";
echo "5. Batch codes don't match between database and export logic\n";
echo "\n";

echo "RECOMMENDED FIXES:\n";
echo "1. Run the targeted_holiday_fix.php script (after fixing database connection)\n";
echo "2. Check if batch codes in database match: KUR_GLP_B1, KUR_GLP_B2, PAR_PAR_B1, PAR_PAR_B2\n";
echo "3. Verify holidays table has entries for: 2025-05-20, 2025-05-21, 2025-08-09, 2025-06-03, 2025-06-04\n";
echo "4. Verify batch_holidays table has proper assignments\n";
echo "5. Check if attendance records exist for these dates and update them to 'H'\n";
echo "\n";

echo "MANUAL SQL FIXES (if needed):\n";
echo "-- 1. Add holidays to holidays table:\n";
foreach ($problemDates as $date => $batches) {
    $description = "Local Festival";
    if ($date == '2025-08-09') $description = "Girijana Vutsavalu";
    echo "INSERT INTO holidays (date, description, type) VALUES ('$date', '$description', 'other');\n";
}
echo "\n";

echo "-- 2. Add batch-specific holiday assignments:\n";
foreach ($problemDates as $date => $batches) {
    foreach ($batches as $batchCode) {
        $description = "Local Festival";
        if ($date == '2025-08-09') $description = "Girijana Vutsavalu";
        echo "INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description) \n";
        echo "SELECT h.id, b.id, '$date', '$description', '$description' \n";
        echo "FROM holidays h, batches b WHERE h.date = '$date' AND b.code = '$batchCode';\n";
    }
}
echo "\n";

echo "-- 3. Update existing attendance records to 'H':\n";
foreach ($problemDates as $date => $batches) {
    foreach ($batches as $batchCode) {
        echo "UPDATE attendance a \n";
        echo "JOIN beneficiaries b ON a.beneficiary_id = b.id \n";
        echo "JOIN batches bt ON b.batch_id = bt.id \n";
        echo "SET a.status = 'H' \n";
        echo "WHERE bt.code = '$batchCode' AND a.attendance_date = '$date';\n";
    }
}
echo "\n";

echo "EXPORT FUNCTION ISSUE:\n";
echo "The export functions in admin/reports.php and admin/export_attendance.php \n";
echo "should properly detect batch-specific holidays using this logic:\n";
echo "1. Check if date is Sunday → mark as 'H'\n";
echo "2. Check if date exists in batch_holidays for this specific batch → mark as 'H'\n";
echo "3. Check if date exists in holidays (all-mandals) → mark as 'H'\n";
echo "4. Otherwise use existing attendance status\n";
echo "\n";

echo "=== DIAGNOSTIC COMPLETE ===\n";
?>
