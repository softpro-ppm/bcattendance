-- =====================================================
-- PHASE 1: DATABASE SETUP FOR ENHANCED BATCH STATUS SYSTEM
-- =====================================================
-- 
-- INSTRUCTIONS:
-- 1. Connect to your database (phpMyAdmin, MySQL Workbench, or command line)
-- 2. Select your database: u820431346_bcattendance
-- 3. Run this entire SQL script
-- 4. Check the results at the bottom
--
-- =====================================================

-- STEP 1: Create the batch_status_log table for audit trail
-- =====================================================
CREATE TABLE IF NOT EXISTS `batch_status_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `batch_id` int(11) NOT NULL,
  `old_status` enum('active','inactive','completed') NOT NULL,
  `new_status` enum('active','inactive','completed') NOT NULL,
  `changed_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who made the change',
  `change_reason` text DEFAULT NULL COMMENT 'Reason for status change',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `batch_id` (`batch_id`),
  KEY `changed_by` (`changed_by`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- STEP 2: Verify the table was created
SELECT '✅ batch_status_log table created successfully!' as Status;

-- STEP 3: Check current batch statuses vs expected statuses
-- =====================================================
SELECT 
    '📊 CURRENT BATCH STATUS ANALYSIS' as info,
    '' as separator
UNION ALL
SELECT 
    CONCAT('Batch: ', name, ' (', code, ')') as info,
    CONCAT('Current: ', status, ' | Expected: ', 
        CASE 
            WHEN CURDATE() < start_date THEN 'inactive'
            WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
            ELSE 'completed'
        END
    ) as separator
FROM batches 
ORDER BY id;

-- STEP 4: Focus on GARUGUBILLI BATCH 2 (ID 15) - The Problem Batch
-- =====================================================
SELECT 
    '🎯 GARUGUBILLI BATCH 2 STATUS CHECK' as info,
    '' as separator
UNION ALL
SELECT 
    CONCAT('Batch ID: ', id) as info,
    CONCAT('Name: ', name, ' (', code, ')') as separator
UNION ALL
SELECT 
    CONCAT('Start Date: ', start_date) as info,
    CONCAT('End Date: ', end_date) as separator
UNION ALL
SELECT 
    CONCAT('Current Status: ', status) as info,
    CONCAT('Expected Status: ', 
        CASE 
            WHEN CURDATE() < start_date THEN 'inactive'
            WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
            ELSE 'completed'
        END
    ) as separator
UNION ALL
SELECT 
    CONCAT('Status Check: ', 
        CASE 
            WHEN status != CASE 
                WHEN CURDATE() < start_date THEN 'inactive'
                WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
                ELSE 'completed'
            END THEN '⚠️ NEEDS UPDATE'
            ELSE '✅ OK'
        END
    ) as info,
    '' as separator
FROM batches 
WHERE id = 15;

-- STEP 5: Check beneficiary statuses for GARUGUBILLI BATCH 2
-- =====================================================
SELECT 
    '👥 GARUGUBILLI BATCH 2 BENEFICIARIES' as info,
    '' as separator
UNION ALL
SELECT 
    CONCAT('Total Students: ', COUNT(*)) as info,
    '' as separator
UNION ALL
SELECT 
    CONCAT('Status: ', status, ' | Count: ', COUNT(*)) as info,
    '' as separator
FROM beneficiaries 
WHERE batch_id = 15 
GROUP BY status;

-- STEP 6: Check if any batches need immediate status updates
-- =====================================================
SELECT 
    '🔍 BATCHES NEEDING STATUS UPDATES' as info,
    '' as separator
UNION ALL
SELECT 
    CONCAT('Batch: ', name, ' (', code, ')') as info,
    CONCAT('Current: ', status, ' → Expected: ', 
        CASE 
            WHEN CURDATE() < start_date THEN 'inactive'
            WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
            ELSE 'completed'
        END
    ) as separator
FROM batches 
WHERE status != CASE 
    WHEN CURDATE() < start_date THEN 'inactive'
    WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
    ELSE 'completed'
END
ORDER BY id;

-- STEP 7: Summary and Next Steps
-- =====================================================
SELECT '🎯 PHASE 1 COMPLETE - SUMMARY' as info,
       '' as separator
UNION ALL
SELECT '✅ batch_status_log table created' as info,
       'Ready for audit trail' as separator
UNION ALL
SELECT '📊 Batch status analysis complete' as info,
       'Check results above' as separator
UNION ALL
SELECT '🔍 Identified batches needing updates' as info,
       'Use Batch Status Manager to fix' as separator
UNION ALL
SELECT '🚀 Next: Test the web interface' as info,
       'Go to Admin → System Configuration → Batch Status Manager' as separator;

-- STEP 8: Final verification queries
-- =====================================================
SELECT COUNT(*) as 'Total Batches' FROM batches;
SELECT COUNT(*) as 'Total Beneficiaries' FROM beneficiaries;
SELECT COUNT(*) as 'Batch Status Log Records' FROM batch_status_log;
SELECT COUNT(*) as 'Batches Needing Updates' FROM batches 
WHERE status != CASE 
    WHEN CURDATE() < start_date THEN 'inactive'
    WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
    ELSE 'completed'
END;
