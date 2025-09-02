-- ===============================================
-- HOLIDAY ATTENDANCE FIX - DATABASE SCRIPT
-- ===============================================
-- This script fixes the issue where Sundays and custom holidays 
-- are showing as "A" (Absent) or blank cells instead of "H" (Holiday)
-- in attendance reports.
-- ===============================================

USE u820431346_bcattendance;

-- ===============================================
-- STEP 1: ANALYZE CURRENT STATUS DISTRIBUTION
-- ===============================================

-- Check current attendance status distribution
SELECT 
    'Current Status Distribution:' as info;

SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- ===============================================
-- STEP 2: STANDARDIZE ATTENDANCE STATUSES
-- ===============================================

-- Convert all status variations to standard format
UPDATE attendance SET status = 'H' WHERE status IN ('holiday', 'H');
UPDATE attendance SET status = 'P' WHERE status IN ('present', 'P', 'late');
UPDATE attendance SET status = 'A' WHERE status IN ('absent', 'A', 'excused');

-- ===============================================
-- STEP 3: MARK ALL SUNDAYS AS HOLIDAYS
-- ===============================================

-- Update all Sunday attendance records to 'H'
UPDATE attendance 
SET status = 'H' 
WHERE DAYOFWEEK(attendance_date) = 1 
AND status != 'H';

-- ===============================================
-- STEP 4: MARK ALL CUSTOM HOLIDAYS AS 'H'
-- ===============================================

-- Mark attendance records for dates in holidays table as 'H'
UPDATE attendance a
JOIN holidays h ON a.attendance_date = h.date
SET a.status = 'H'
WHERE a.status != 'H';

-- ===============================================
-- STEP 5: MARK BATCH-SPECIFIC HOLIDAYS AS 'H'
-- ===============================================

-- Mark attendance records for batch-specific holidays as 'H'
UPDATE attendance a
JOIN beneficiaries b ON a.beneficiary_id = b.id
JOIN batch_holidays bh ON bh.holiday_date = a.attendance_date AND bh.batch_id = b.batch_id
SET a.status = 'H'
WHERE a.status != 'H';

-- ===============================================
-- STEP 6: VERIFY THE FIXES
-- ===============================================

-- Show final status distribution
SELECT 
    'Final Status Distribution:' as info;

SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Count total holiday records
SELECT 
    'Holiday Records Summary:' as info;

SELECT 
    COUNT(*) as total_holiday_records,
    COUNT(DISTINCT attendance_date) as unique_holiday_dates
FROM attendance 
WHERE status = 'H';

-- Show sample of holiday dates
SELECT 
    'Sample Holiday Dates:' as info;

SELECT 
    attendance_date,
    DAYNAME(attendance_date) as day_name,
    COUNT(*) as beneficiary_count
FROM attendance 
WHERE status = 'H'
GROUP BY attendance_date
ORDER BY attendance_date DESC
LIMIT 10;

-- ===============================================
-- STEP 7: CREATE INDEXES FOR BETTER PERFORMANCE
-- ===============================================

-- Add indexes for better holiday detection performance
CREATE INDEX IF NOT EXISTS idx_attendance_date_status ON attendance(attendance_date, status);
CREATE INDEX IF NOT EXISTS idx_holidays_date ON holidays(date);
CREATE INDEX IF NOT EXISTS idx_batch_holidays_date_batch ON batch_holidays(holiday_date, batch_id);

-- ===============================================
-- COMPLETION MESSAGE
-- ===============================================

SELECT 
    'HOLIDAY ATTENDANCE FIX COMPLETED SUCCESSFULLY!' as completion_message,
    'All Sundays and custom holidays should now be properly marked as "H" in attendance reports.' as next_steps;
