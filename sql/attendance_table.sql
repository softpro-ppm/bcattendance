-- Attendance Management System - Database Schema
-- Add attendance table to existing bc_attendance database

USE bc_attendance;

-- Create attendance table
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('P', 'A', 'H') NOT NULL COMMENT 'P=Present, A=Absent, H=Holiday',
    batch_id INT NULL,
    constituency_id INT NULL,
    mandal_id INT NULL,
    tc_id INT NULL,
    created_by INT NULL COMMENT 'Admin user who marked/imported attendance',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL,
    FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE SET NULL,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE SET NULL,
    FOREIGN KEY (tc_id) REFERENCES training_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    
    -- Unique constraint to prevent duplicate attendance for same beneficiary on same date
    UNIQUE KEY unique_attendance (beneficiary_id, attendance_date),
    
    -- Indexes for performance
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_beneficiary_date (beneficiary_id, attendance_date),
    INDEX idx_batch_date (batch_id, attendance_date),
    INDEX idx_status (status)
);

-- Create attendance import log table
CREATE TABLE IF NOT EXISTS attendance_import_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    total_records INT NOT NULL,
    successful_records INT NOT NULL,
    failed_records INT NOT NULL,
    total_beneficiaries INT NOT NULL COMMENT 'Number of unique beneficiaries in import',
    date_range_start DATE NULL COMMENT 'Earliest attendance date in import',
    date_range_end DATE NULL COMMENT 'Latest attendance date in import',
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('completed', 'failed', 'partial') NOT NULL,
    error_log TEXT,
    FOREIGN KEY (uploaded_by) REFERENCES admin_users(id)
);

-- Insert some sample data for testing (optional)
-- This can be removed in production
INSERT INTO attendance (beneficiary_id, attendance_date, status, batch_id) 
SELECT 
    b.id as beneficiary_id,
    CURDATE() - INTERVAL 1 DAY as attendance_date,
    'P' as status,
    b.batch_id
FROM beneficiaries b 
LIMIT 5;
