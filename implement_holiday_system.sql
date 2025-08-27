-- =====================================================
-- IMPLEMENT HOLIDAY SYSTEM FOR BC ATTENDANCE
-- =====================================================
-- This script implements the new holiday system as requested:
-- 1. Keep: present, absent
-- 2. Remove: late, excused  
-- 3. Add: holiday (for non-working days)
-- 4. Mark all Sundays as holidays
-- 5. Handle CSV uploads with "H" status as holidays
-- 6. Exclude holidays from all calculations (only count P and A)

USE bc_attendance;

-- =====================================================
-- STEP 1: UPDATE ATTENDANCE TABLE STATUS ENUM
-- =====================================================

-- First, let's see current status values
SELECT DISTINCT status, COUNT(*) as count FROM attendance GROUP BY status;

-- Update the status enum to include 'holiday' and remove 'late', 'excused'
ALTER TABLE attendance MODIFY COLUMN status ENUM('present','absent','holiday') NOT NULL;

-- =====================================================
-- STEP 2: MIGRATE EXISTING DATA
-- =====================================================

-- Convert 'late' to 'absent' (since late should be treated as present in most cases)
UPDATE attendance SET status = 'present' WHERE status = 'late';

-- Convert 'excused' to 'absent' 
UPDATE attendance SET status = 'absent' WHERE status = 'excused';

-- Convert any 'H' status to 'holiday' (if they exist from CSV uploads)
UPDATE attendance SET status = 'holiday' WHERE status = 'H';

-- =====================================================
-- STEP 3: CREATE HOLIDAYS TABLE IF NOT EXISTS
-- =====================================================

CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    description VARCHAR(255) NOT NULL,
    type ENUM('sunday', 'national', 'local', 'batch_specific') DEFAULT 'sunday',
    batch_id INT NULL,
    mandal_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE,
    INDEX idx_date (date),
    INDEX idx_batch_id (batch_id),
    INDEX idx_mandal_id (mandal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- STEP 4: MARK ALL SUNDAYS AS HOLIDAYS
-- =====================================================

-- Insert all Sundays from 2025-01-01 to 2025-12-31
INSERT IGNORE INTO holidays (date, description, type)
SELECT 
    date_value,
    'Sunday Holiday',
    'sunday'
FROM (
    SELECT DATE('2025-01-01') + INTERVAL (a.N + b.N * 10 + c.N * 100) DAY as date_value
    FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
    CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
    CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c
    WHERE DATE('2025-01-01') + INTERVAL (a.N + b.N * 10 + c.N * 100) DAY <= '2025-12-31'
) dates
WHERE DAYOFWEEK(date_value) = 1; -- 1 = Sunday

-- =====================================================
-- STEP 5: UPDATE ATTENDANCE RECORDS FOR SUNDAYS
-- =====================================================

-- Mark all Sunday attendance records as holidays
UPDATE attendance a
JOIN holidays h ON a.attendance_date = h.date AND h.type = 'sunday'
SET a.status = 'holiday'
WHERE a.status IN ('present', 'absent');

-- =====================================================
-- STEP 6: VERIFY THE CHANGES
-- =====================================================

-- Check current status distribution
SELECT 
    status, 
    COUNT(*) as count,
    CASE 
        WHEN status = 'present' THEN 'Working Day - Present'
        WHEN status = 'absent' THEN 'Working Day - Absent' 
        WHEN status = 'holiday' THEN 'Non-Working Day - Holiday'
        ELSE 'Unknown'
    END as description
FROM attendance 
GROUP BY status
ORDER BY status;

-- Check holidays table
SELECT 
    type,
    COUNT(*) as count,
    MIN(date) as earliest_date,
    MAX(date) as latest_date
FROM holidays 
GROUP BY type
ORDER BY type;

-- Check Sunday holidays specifically
SELECT 
    date,
    DAYNAME(date) as day_name,
    description
FROM holidays 
WHERE type = 'sunday' 
ORDER BY date 
LIMIT 10;

-- =====================================================
-- STEP 7: UPDATE BATCH REPORTS API FOR HOLIDAY EXCLUSION
-- =====================================================
-- Note: This will be done in the PHP files to ensure holidays are excluded from calculations

-- =====================================================
-- SUMMARY OF CHANGES
-- =====================================================
SELECT 
    'Database Schema Updated' as change_type,
    'Attendance status enum changed to: present, absent, holiday' as description
UNION ALL
SELECT 
    'Data Migration Completed' as change_type,
    'All late/excused records converted, Sundays marked as holidays' as description
UNION ALL
SELECT 
    'Holidays Table Created' as change_type,
    'Sundays and custom holidays can now be managed' as description
UNION ALL
SELECT 
    'Working Days Calculation' as change_type,
    'Only present + absent days count as working days' as description;

-- =====================================================
-- NEXT STEPS FOR PHP IMPLEMENTATION
-- =====================================================
-- 1. Update batch_reports_api.php to exclude holidays from working days calculation
-- 2. Update all attendance calculations to exclude holiday status
-- 3. Ensure CSV uploads with "H" are converted to "holiday" status
-- 4. Update holiday management interface at /admin/manage_holidays.php
