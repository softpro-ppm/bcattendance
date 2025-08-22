-- Create TC Users Table and Setup Users for 8 Training Centers
-- This script will create the TC user system without affecting existing admin functionality

-- Create TC users table
CREATE TABLE IF NOT EXISTS tc_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tc_id VARCHAR(20) UNIQUE NOT NULL,
    training_center_id INT NOT NULL,
    mandal_id INT NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (training_center_id) REFERENCES training_centers(id) ON DELETE CASCADE,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE
);

-- Create attendance edit log table for tracking TC user edits
CREATE TABLE IF NOT EXISTS attendance_edit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_id INT NOT NULL,
    beneficiary_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    old_status ENUM('present', 'absent', 'late', 'excused'),
    new_status ENUM('present', 'absent', 'late', 'excused'),
    old_check_in_time TIME,
    new_check_in_time TIME,
    old_check_out_time TIME,
    new_check_out_time TIME,
    old_remarks TEXT,
    new_remarks TEXT,
    edited_by_tc_user INT,
    edited_by_admin_user INT,
    edit_type ENUM('create', 'update', 'delete') DEFAULT 'update',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE CASCADE,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (edited_by_tc_user) REFERENCES tc_users(id) ON DELETE SET NULL,
    FOREIGN KEY (edited_by_admin_user) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Insert TC users for all 8 Training Centers
-- Using TC ID as username and 'institute' as common password for all users
INSERT INTO tc_users (tc_id, training_center_id, mandal_id, password, full_name, status) VALUES 
-- PARVATHIPURAM Constituency Training Centers
('TTC7430317', 1, 1, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Parvathipuram Training Center User', 'active'),
('TTC7430652', 2, 2, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Balijipeta Training Center User', 'active'),
('TTC7430654', 3, 3, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Seethanagaram Training Center User', 'active'),
-- KURUPAM Constituency Training Centers
('TTC7430664', 4, 4, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Kurupam Training Center User', 'active'),
('TTC7430536', 5, 5, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'GL Puram Training Center User', 'active'),
('TTC7430529', 6, 6, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Jiyyammavalasa Training Center User', 'active'),
('TTC7430543', 7, 7, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Komarada Training Center User', 'active'),
('TTC7430653', 8, 8, '$2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m', 'Garugubilli Training Center User', 'active');

-- Display created users
SELECT 'TC Users Created Successfully!' as Status;
SELECT 
    tc.tc_id as 'Username (TC ID)',
    'institute' as 'Password',
    m.name as 'Mandal',
    tcr.name as 'Training Center',
    tc.status as 'Status'
FROM tc_users tc
JOIN mandals m ON tc.mandal_id = m.id
JOIN training_centers tcr ON tc.training_center_id = tcr.id
ORDER BY tc.tc_id;

-- Note: Password hash for 'institute' = $2y$10$Q8Gz7qsz9fCbRn4gRz.xCOLM1xC8K8bH8X9.yqR1tKzL5MpNjZH.m
