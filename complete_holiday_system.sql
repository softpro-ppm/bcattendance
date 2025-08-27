-- =====================================================
-- COMPLETE HOLIDAY SYSTEM SETUP
-- =====================================================
-- This script sets up the complete holiday system for BC Attendance
-- Run this script to create all necessary tables and update existing data

-- =====================================================
-- STEP 1: UPDATE ATTENDANCE TABLE STATUS ENUM
-- =====================================================

-- Update attendance table to only allow present, absent, holiday
ALTER TABLE attendance MODIFY COLUMN status ENUM('present','absent','holiday') NOT NULL;

-- =====================================================
-- STEP 2: MIGRATE EXISTING DATA
-- =====================================================

-- Convert 'late' to 'present' (as per user requirement)
UPDATE attendance SET status = 'present' WHERE status = 'late';

-- Convert 'excused' to 'absent' (as per user requirement)  
UPDATE attendance SET status = 'absent' WHERE status = 'excused';

-- Convert 'H' to 'holiday' (historical holiday uploads)
UPDATE attendance SET status = 'holiday' WHERE status = 'H';

-- =====================================================
-- STEP 3: CREATE HOLIDAYS TABLE
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
-- STEP 4: CREATE BATCH_HOLIDAYS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS batch_holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_id INT NOT NULL,
    batch_id INT NOT NULL,
    holiday_date DATE NOT NULL,
    holiday_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    INDEX idx_holiday_id (holiday_id),
    INDEX idx_batch_id (batch_id),
    INDEX idx_holiday_date (holiday_date),
    UNIQUE KEY unique_batch_holiday (batch_id, holiday_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================
-- STEP 5: MARK ALL SUNDAYS AS HOLIDAYS
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
-- STEP 6: UPDATE ATTENDANCE RECORDS FOR SUNDAYS
-- =====================================================

-- Mark all Sunday attendance records as holidays
UPDATE attendance a
JOIN holidays h ON a.attendance_date = h.date AND h.type = 'sunday'
SET a.status = 'holiday'
WHERE a.status IN ('present', 'absent');

-- =====================================================
-- STEP 7: VERIFY THE CHANGES
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
-- STEP 8: CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Add indexes to attendance table for better performance
CREATE INDEX IF NOT EXISTS idx_attendance_beneficiary_date ON attendance(beneficiary_id, attendance_date);
CREATE INDEX IF NOT EXISTS idx_attendance_date_status ON attendance(attendance_date, status);

-- =====================================================
-- SUMMARY OF CHANGES
-- =====================================================
SELECT 
    'Database Schema Updated' as change_type,
    'Attendance status enum changed to: present, absent, holiday' as description
UNION ALL
SELECT 
    'Holidays Table Created',
    'New holidays table with support for different holiday types'
UNION ALL
SELECT 
    'Batch Holidays Table Created',
    'New batch_holidays table for batch-specific holiday assignments'
UNION ALL
SELECT 
    'Sundays Marked as Holidays',
    'All Sundays automatically marked as holidays for 2025'
UNION ALL
SELECT 
    'Existing Data Migrated',
    'Late->Present, Excused->Absent, H->Holiday'
UNION ALL
SELECT 
    'Performance Indexes Added',
    'Indexes created for better query performance';

-- =====================================================
-- NEXT STEPS
-- =====================================================
-- 1. Run this script to set up the database
-- 2. Use the admin/manage_holidays.php page to add custom holidays
-- 3. Use the admin/attendance_calendar.php page to view student calendars
-- 4. The system will automatically exclude holidays from working days calculations
