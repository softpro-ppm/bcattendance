-- ===============================================
-- ATTENDANCE STATUS STANDARDIZATION MIGRATION
-- ===============================================
-- This script standardizes all attendance status values
-- to use consistent format: 'present', 'absent'
-- 
-- Before running: Backup your database!
-- ===============================================

-- First, let's check current status distribution
SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Show sample of records that will be updated
SELECT 
    'Records to be updated:' as info,
    COUNT(*) as total_records
FROM attendance 
WHERE status IN ('P', 'A', 'H');

-- Show breakdown of what will be changed
SELECT 
    status as current_status,
    CASE 
        WHEN status = 'P' THEN 'present'
        WHEN status = 'A' THEN 'absent'
        WHEN status = 'H' THEN 'absent'
        ELSE status
    END as new_status,
    COUNT(*) as record_count
FROM attendance 
WHERE status IN ('P', 'A', 'H')
GROUP BY status;

-- ===============================================
-- MAIN MIGRATION: Standardize status values
-- ===============================================

-- Update attendance status to standardized format
UPDATE attendance 
SET status = CASE 
    WHEN status = 'P' THEN 'present'
    WHEN status = 'A' THEN 'absent'  
    WHEN status = 'H' THEN 'absent'  -- Holiday treated as absent
    ELSE status  -- Keep existing values that are already standardized
END
WHERE status IN ('P', 'A', 'H');

-- ===============================================
-- VERIFICATION QUERIES
-- ===============================================

-- Verify the migration was successful
SELECT 
    'After migration - Status distribution:' as info;

SELECT 
    status,
    COUNT(*) as count,
    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
FROM attendance 
GROUP BY status
ORDER BY count DESC;

-- Check if any old format statuses remain
SELECT 
    'Remaining old format records:' as info,
    COUNT(*) as remaining_old_format
FROM attendance 
WHERE status IN ('P', 'A', 'H');

-- Show total attendance summary after migration
SELECT 
    'Final Summary:' as info,
    COUNT(*) as total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as attendance_percentage
FROM attendance;

-- ===============================================
-- SUCCESS MESSAGE
-- ===============================================
SELECT 'Migration completed successfully! All attendance status values have been standardized.' as result;
