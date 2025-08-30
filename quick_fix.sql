-- Quick Fix for Attendance Status Mismatch
-- Run this SQL script in your database to fix the dashboard display issue

-- Check current status distribution before fix
SELECT 'BEFORE FIX:' as info;
SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Update attendance status to standardized format
UPDATE attendance 
SET status = CASE 
    WHEN status = 'P' THEN 'present'
    WHEN status = 'A' THEN 'absent'
    WHEN status = 'H' THEN 'absent'
    WHEN status = 'late' THEN 'present'
    WHEN status = 'excused' THEN 'absent'
    ELSE status
END,
updated_at = NOW()
WHERE status IN ('P', 'A', 'H', 'late', 'excused');

-- Check status distribution after fix
SELECT 'AFTER FIX:' as info;
SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Check today's attendance specifically
SELECT 'TODAY (2025-08-30):' as info;
SELECT 
    status,
    COUNT(*) as count
FROM attendance 
WHERE attendance_date = '2025-08-30'
GROUP BY status
ORDER BY count DESC;

-- Test dashboard calculation
SELECT 'DASHBOARD TEST:' as info;
SELECT 
    'Present' as status,
    COUNT(*) as count
FROM attendance a 
INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
INNER JOIN batches bt ON b.batch_id = bt.id
WHERE a.attendance_date = '2025-08-30' 
AND bt.end_date >= '2025-08-30'
AND a.status = 'present'

UNION ALL

SELECT 
    'Absent' as status,
    COUNT(*) as count
FROM attendance a 
INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
INNER JOIN batches bt ON b.batch_id = bt.id
WHERE a.attendance_date = '2025-08-30' 
AND bt.end_date >= '2025-08-30'
AND a.status = 'absent'

UNION ALL

SELECT 
    'Total Marked' as status,
    COUNT(*) as count
FROM attendance a 
INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
INNER JOIN batches bt ON b.batch_id = bt.id
WHERE a.attendance_date = '2025-08-30' 
AND bt.end_date >= '2025-08-30';
