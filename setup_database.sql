-- =====================================================
-- Complete Database Setup for Enhanced Batch Status System
-- =====================================================

-- 1. Create the batch_status_log table
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

-- 2. Check current batch statuses
SELECT 
    'Current Batch Statuses' as info,
    id,
    name,
    code,
    start_date,
    end_date,
    status,
    CASE 
        WHEN CURDATE() < start_date THEN 'inactive'
        WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
        ELSE 'completed'
    END as expected_status
FROM batches 
ORDER BY id;

-- 3. Check GARUGUBILLI BATCH 2 specifically (ID 15)
SELECT 
    'GARUGUBILLI BATCH 2 Status Check' as info,
    id,
    name,
    code,
    start_date,
    end_date,
    status,
    CASE 
        WHEN CURDATE() < start_date THEN 'inactive'
        WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
        ELSE 'completed'
    END as expected_status,
    CASE 
        WHEN status != CASE 
            WHEN CURDATE() < start_date THEN 'inactive'
            WHEN CURDATE() BETWEEN start_date AND end_date THEN 'active'
            ELSE 'completed'
        END THEN 'NEEDS UPDATE'
        ELSE 'OK'
    END as status_check
FROM batches 
WHERE id = 15;

-- 4. Check beneficiary statuses for GARUGUBILLI BATCH 2
SELECT 
    'GARUGUBILLI BATCH 2 Beneficiaries' as info,
    status,
    COUNT(*) as count
FROM beneficiaries 
WHERE batch_id = 15 
GROUP BY status;

-- 5. Display summary
SELECT 'Database Setup Complete!' as Status;
SELECT COUNT(*) as 'Total Batches' FROM batches;
SELECT COUNT(*) as 'Total Beneficiaries' FROM beneficiaries;
SELECT COUNT(*) as 'Batch Status Log Records' FROM batch_status_log;
