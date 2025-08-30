-- Fix Attendance Dates Due to Timezone Issues
-- This script moves attendance records that were submitted in the evening
-- but incorrectly marked as the next day's date

-- Check current date
SELECT 'CURRENT DATE INFO:' as info;
SELECT CURDATE() as current_date, NOW() as current_time;

-- Check for records that should be for yesterday but are marked as today
SELECT 'RECORDS TO BE FIXED:' as info;
SELECT 
    COUNT(*) as record_count,
    MIN(created_at) as earliest_created,
    MAX(created_at) as latest_created
FROM attendance 
WHERE attendance_date = CURDATE()
AND HOUR(created_at) >= 18;  -- Records created after 6 PM

-- Show sample of records that will be moved
SELECT 'SAMPLE RECORDS:' as info;
SELECT 
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

-- Move records from today to yesterday (for records created after 6 PM)
UPDATE attendance 
SET attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY),
    updated_at = NOW()
WHERE attendance_date = CURDATE()
AND HOUR(created_at) >= 18;

-- Verify the fix
SELECT 'AFTER FIX - TODAY:' as info;
SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = CURDATE();

SELECT 'AFTER FIX - YESTERDAY:' as info;
SELECT 
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);

-- Final summary
SELECT 'FINAL SUMMARY:' as info;
SELECT 
    'Today' as date_label,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = CURDATE()

UNION ALL

SELECT 
    'Yesterday' as date_label,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
FROM attendance 
WHERE attendance_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY);
