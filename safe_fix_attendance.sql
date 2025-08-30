-- SAFE ATTENDANCE DATE FIX SCRIPT
-- This script FIRST shows you what will be changed, then asks for confirmation
-- SAFE FOR LIVE PRODUCTION SITE

-- STEP 1: CHECK CURRENT SITUATION (READ ONLY)
SELECT '=== CURRENT SITUATION ===' as info;

-- Check current date and time
SELECT 
    'Current Date/Time' as info,
    CURDATE() as current_date,
    NOW() as current_time;

-- Check today's attendance records
SELECT 
    'Today Attendance Summary' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    MIN(created_at) as earliest_created,
    MAX(created_at) as latest_created
FROM attendance 
WHERE attendance_date = CURDATE();

-- Check yesterday's attendance records
SELECT 
    'Yesterday Attendance Summary' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    MIN(created_at) as earliest_created,
    MAX(created_at) as latest_created
FROM attendance 
WHERE attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);

-- STEP 2: IDENTIFY RECORDS THAT MIGHT NEED FIXING (READ ONLY)
SELECT '=== RECORDS THAT MIGHT NEED FIXING ===' as info;

-- Count records created after 6 PM but marked as today
SELECT 
    'Records created after 6 PM today' as info,
    COUNT(*) as record_count,
    MIN(created_at) as earliest_time,
    MAX(created_at) as latest_time
FROM attendance 
WHERE attendance_date = CURDATE()
AND HOUR(created_at) >= 18;

-- Show sample of these records
SELECT 
    'Sample records that might need fixing:' as info,
    id,
    beneficiary_id,
    attendance_date,
    status,
    created_at,
    updated_at
FROM attendance 
WHERE attendance_date = CURDATE()
AND HOUR(created_at) >= 18
LIMIT 5;

-- STEP 3: SHOW WHAT THE FIX WOULD DO (READ ONLY)
SELECT '=== WHAT THE FIX WOULD DO ===' as info;

-- Show what today would look like after fix
SELECT 
    'Today after fix (estimated)' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = CURDATE()
AND HOUR(created_at) < 18;  -- Only records created before 6 PM

-- Show what yesterday would look like after fix
SELECT 
    'Yesterday after fix (estimated)' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
OR (attendance_date = CURDATE() AND HOUR(created_at) >= 18);

-- STEP 4: RECOMMENDATION
SELECT '=== RECOMMENDATION ===' as info;

-- Check if fix is needed
SELECT 
    CASE 
        WHEN (SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND HOUR(created_at) >= 18) > 0 
        THEN 'FIX RECOMMENDED: Found evening records marked as today'
        ELSE 'NO FIX NEEDED: All records appear to have correct dates'
    END as recommendation;

-- STEP 5: SAFETY CHECK
SELECT '=== SAFETY CHECK ===' as info;
SELECT 
    'Before running the fix, please:' as safety_note,
    '1. Backup your database' as step1,
    '2. Run this diagnostic script first' as step2,
    '3. Review the results above' as step3,
    '4. Only proceed if you agree with the changes' as step4;

-- UNCOMMENT THE FOLLOWING SECTION ONLY AFTER REVIEWING THE RESULTS ABOVE
/*
-- STEP 6: ACTUAL FIX (UNCOMMENT ONLY AFTER REVIEW)
-- Move records from today to yesterday (for records created after 6 PM)
UPDATE attendance 
SET attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    updated_at = NOW()
WHERE attendance_date = CURDATE()
AND HOUR(created_at) >= 18;

-- STEP 7: VERIFICATION AFTER FIX
SELECT '=== AFTER FIX VERIFICATION ===' as info;

SELECT 
    'Today after fix' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = CURDATE();

SELECT 
    'Yesterday after fix' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
*/
