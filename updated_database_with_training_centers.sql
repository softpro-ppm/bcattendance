-- BC Attendance Database Schema - Updated with Training Centers
-- Drop and recreate database
DROP DATABASE IF EXISTS bc_attendance;
CREATE DATABASE bc_attendance;
USE bc_attendance;

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Constituencies table
CREATE TABLE constituencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Mandals table
CREATE TABLE mandals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    constituency_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE CASCADE
);

-- Training Centers table (NEW)
CREATE TABLE training_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mandal_id INT NOT NULL,
    tc_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    contact_person VARCHAR(100),
    phone_number VARCHAR(15),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE
);

-- Batches table (updated to link with training centers)
CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mandal_id INT NOT NULL,
    tc_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE,
    FOREIGN KEY (tc_id) REFERENCES training_centers(id) ON DELETE CASCADE
);

-- Beneficiaries table (updated to match Excel format)
CREATE TABLE beneficiaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    constituency_id INT NOT NULL,
    mandal_id INT NOT NULL,
    tc_id INT NOT NULL,
    batch_id INT NOT NULL,
    phone_number VARCHAR(15),
    aadhar_number VARCHAR(12) UNIQUE NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    batch_start_date DATE,
    batch_end_date DATE,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE CASCADE,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE,
    FOREIGN KEY (tc_id) REFERENCES training_centers(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE
);

-- Attendance table (updated)
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    remarks TEXT,
    marked_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (beneficiary_id, attendance_date)
);

-- Bulk Upload Log table (NEW - to track bulk uploads)
CREATE TABLE bulk_upload_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    total_records INT NOT NULL,
    successful_records INT NOT NULL,
    failed_records INT NOT NULL,
    uploaded_by INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('completed', 'failed', 'partial') NOT NULL,
    error_log TEXT,
    FOREIGN KEY (uploaded_by) REFERENCES admin_users(id)
);

-- Insert default admin user
INSERT INTO admin_users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@bcattendance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Insert Constituencies
INSERT INTO constituencies (name, code, description) VALUES 
('PARVATHIPURAM', 'PAR', 'Parvathipuram Parliamentary Constituency'),
('KURUPAM', 'KUR', 'Kurupam Parliamentary Constituency');

-- Insert Mandals
INSERT INTO mandals (constituency_id, name, code, description) VALUES 
-- PARVATHIPURAM Constituency Mandals
(1, 'PARVATHIPURAM', 'PAR_PAR', 'Parvathipuram Mandal in Parvathipuram Constituency'),
(1, 'BALIJIPETA', 'PAR_BAL', 'Balijipeta Mandal in Parvathipuram Constituency'),
(1, 'SEETHANAGARAM', 'PAR_SEE', 'Seethanagaram Mandal in Parvathipuram Constituency'),
-- KURUPAM Constituency Mandals
(2, 'KURUPAM', 'KUR_KUR', 'Kurupam Mandal in Kurupam Constituency'),
(2, 'GL PURAM', 'KUR_GLP', 'GL Puram Mandal in Kurupam Constituency'),
(2, 'JIYYAMMAVALASA', 'KUR_JIY', 'Jiyyammavalasa Mandal in Kurupam Constituency'),
(2, 'KOMARADA', 'KUR_KOM', 'Komarada Mandal in Kurupam Constituency'),
(2, 'GARUGUBILLI', 'KUR_GAR', 'Garugubilli Mandal in Kurupam Constituency');

-- Insert Training Centers (1 per mandal with actual TC IDs)
INSERT INTO training_centers (mandal_id, tc_id, name, address, status) VALUES 
-- PARVATHIPURAM Constituency Training Centers
(1, 'TTC7430317', 'Parvathipuram Training Center', 'Parvathipuram Mandal Office', 'active'),
(2, 'TTC7430652', 'Balijipeta Training Center', 'Balijipeta Mandal Office', 'active'),
(3, 'TTC7430654', 'Seethanagaram Training Center', 'Seethanagaram Mandal Office', 'active'),
-- KURUPAM Constituency Training Centers
(4, 'TTC7430664', 'Kurupam Training Center', 'Kurupam Mandal Office', 'active'),
(5, 'TTC7430536', 'GL Puram Training Center', 'GL Puram Mandal Office', 'active'),
(6, 'TTC7430529', 'Jiyyammavalasa Training Center', 'Jiyyammavalasa Mandal Office', 'active'),
(7, 'TTC7430543', 'Komarada Training Center', 'Komarada Mandal Office', 'active'),
(8, 'TTC7430653', 'Garugubilli Training Center', 'Garugubilli Mandal Office', 'active');

-- Insert Batches (updated with exact dates)
INSERT INTO batches (mandal_id, tc_id, name, code, description, start_date, end_date) VALUES 
-- PARVATHIPURAM Mandal Batches (TTC7430317)
(1, 1, 'Batch-1', 'PAR_PAR_B1', 'Batch-1 for Parvathipuram Mandal', '2025-05-07', '2025-08-20'),
(1, 1, 'Batch-2', 'PAR_PAR_B2', 'Batch-2 for Parvathipuram Mandal', '2025-06-16', '2025-09-30'),
-- BALIJIPETA Mandal Batches (TTC7430652)
(2, 2, 'Batch-1', 'PAR_BAL_B1', 'Batch-1 for Balijipeta Mandal', '2025-06-16', '2025-09-30'),
(2, 2, 'Batch-2', 'PAR_BAL_B2', 'Batch-2 for Balijipeta Mandal', '2025-06-16', '2025-09-30'),
-- SEETHANAGARAM Mandal Batch (TTC7430654) - only 1 batch
(3, 3, 'Batch-1', 'PAR_SEE_B1', 'Batch-1 for Seethanagaram Mandal', '2025-06-16', '2025-09-30'),
-- KURUPAM Mandal Batches (TTC7430664)
(4, 4, 'Batch-1', 'KUR_KUR_B1', 'Batch-1 for Kurupam Mandal', '2025-06-16', '2025-09-30'),
(4, 4, 'Batch-2', 'KUR_KUR_B2', 'Batch-2 for Kurupam Mandal', '2025-05-07', '2025-08-20'),
-- GL PURAM Mandal Batches (TTC7430536)
(5, 5, 'Batch-1', 'KUR_GLP_B1', 'Batch-1 for GL Puram Mandal', '2025-05-07', '2025-08-20'),
(5, 5, 'Batch-2', 'KUR_GLP_B2', 'Batch-2 for GL Puram Mandal', '2025-06-16', '2025-09-30'),
-- JIYYAMMAVALASA Mandal Batches (TTC7430529)
(6, 6, 'Batch-1', 'KUR_JIY_B1', 'Batch-1 for Jiyyammavalasa Mandal', '2025-06-16', '2025-09-30'),
(6, 6, 'Batch-2', 'KUR_JIY_B2', 'Batch-2 for Jiyyammavalasa Mandal', '2025-06-16', '2025-09-30'),
-- KOMARADA Mandal Batches (TTC7430543)
(7, 7, 'Batch-1', 'KUR_KOM_B1', 'Batch-1 for Komarada Mandal', '2025-06-16', '2025-09-30'),
(7, 7, 'Batch-2', 'KUR_KOM_B2', 'Batch-2 for Komarada Mandal', '2025-06-16', '2025-09-30'),
-- GARUGUBILLI Mandal Batches (TTC7430653)
(8, 8, 'Batch-1', 'KUR_GAR_B1', 'Batch-1 for Garugubilli Mandal', '2025-06-16', '2025-09-30'),
(8, 8, 'Batch-2', 'KUR_GAR_B2', 'Batch-2 for Garugubilli Mandal', '2025-05-07', '2025-08-20');

-- Display summary
SELECT 'Database Setup Complete with Training Centers!' as Status;
SELECT COUNT(*) as 'Total Constituencies' FROM constituencies;
SELECT COUNT(*) as 'Total Mandals' FROM mandals;
SELECT COUNT(*) as 'Total Training Centers' FROM training_centers;
SELECT COUNT(*) as 'Total Batches' FROM batches;

-- Show Training Centers mapping
SELECT 
    c.name as 'Constituency',
    m.name as 'Mandal',
    tc.tc_id as 'TC ID',
    tc.name as 'Training Center'
FROM constituencies c
JOIN mandals m ON c.id = m.constituency_id
JOIN training_centers tc ON m.id = tc.mandal_id
ORDER BY c.name, m.name;
