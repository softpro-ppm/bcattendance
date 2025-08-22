-- Quick Status Standardization Fix
-- Run this SQL script directly in your database

-- Check current distribution before migration
SELECT 'BEFORE MIGRATION:' as info;
SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Run the standardization update
UPDATE attendance 
SET status = CASE 
    WHEN status = 'P' THEN 'present'
    WHEN status = 'A' THEN 'absent'
    WHEN status = 'H' THEN 'absent'
    ELSE status
END
WHERE status IN ('P', 'A', 'H');

-- Check results after migration
SELECT 'AFTER MIGRATION:' as info;
SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Final summary
SELECT 
    'FINAL SUMMARY:' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as attendance_percentage
FROM attendance;
