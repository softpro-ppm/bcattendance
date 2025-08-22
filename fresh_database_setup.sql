-- BC Attendance Database Schema - Fresh Setup
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

-- Batches table
CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mandal_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE
);

-- Beneficiaries table
CREATE TABLE beneficiaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    beneficiary_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    father_name VARCHAR(100),
    mother_name VARCHAR(100),
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other') NOT NULL,
    mobile_number VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    constituency_id INT,
    mandal_id INT,
    batch_id INT,
    aadhar_number VARCHAR(12) UNIQUE,
    bank_account_number VARCHAR(20),
    ifsc_code VARCHAR(15),
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (constituency_id) REFERENCES constituencies(id) ON DELETE SET NULL,
    FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE SET NULL,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL
);

-- Attendance table
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

-- Insert default admin user
INSERT INTO admin_users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@bcattendance.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- Insert 2 Constituencies
INSERT INTO constituencies (name, code, description) VALUES 
('PARVATHIPURAM', 'PAR', 'Parvathipuram Parliamentary Constituency'),
('KURUPAM', 'KUR', 'Kurupam Parliamentary Constituency');

-- Insert 8 Mandals (3 for PARVATHIPURAM, 5 for KURUPAM)
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

-- Insert 15 Batches (2 per mandal except SEETHANAGARAM which has 1)
INSERT INTO batches (mandal_id, name, code, description, start_date, end_date) VALUES 
-- PARVATHIPURAM Mandal Batches
(1, 'BATCH 1', 'PAR_PAR_B1', 'Batch 1 for Parvathipuram Mandal', '2024-01-01', '2024-06-30'),
(1, 'BATCH 2', 'PAR_PAR_B2', 'Batch 2 for Parvathipuram Mandal', '2024-07-01', '2024-12-31'),

-- BALIJIPETA Mandal Batches
(2, 'BATCH 1', 'PAR_BAL_B1', 'Batch 1 for Balijipeta Mandal', '2024-01-01', '2024-06-30'),
(2, 'BATCH 2', 'PAR_BAL_B2', 'Batch 2 for Balijipeta Mandal', '2024-07-01', '2024-12-31'),

-- SEETHANAGARAM Mandal Batch (only 1 batch)
(3, 'BATCH 1', 'PAR_SEE_B1', 'Batch 1 for Seethanagaram Mandal', '2024-01-01', '2024-12-31'),

-- KURUPAM Mandal Batches
(4, 'BATCH 1', 'KUR_KUR_B1', 'Batch 1 for Kurupam Mandal', '2024-01-01', '2024-06-30'),
(4, 'BATCH 2', 'KUR_KUR_B2', 'Batch 2 for Kurupam Mandal', '2024-07-01', '2024-12-31'),

-- GL PURAM Mandal Batches
(5, 'BATCH 1', 'KUR_GLP_B1', 'Batch 1 for GL Puram Mandal', '2024-01-01', '2024-06-30'),
(5, 'BATCH 2', 'KUR_GLP_B2', 'Batch 2 for GL Puram Mandal', '2024-07-01', '2024-12-31'),

-- JIYYAMMAVALASA Mandal Batches
(6, 'BATCH 1', 'KUR_JIY_B1', 'Batch 1 for Jiyyammavalasa Mandal', '2024-01-01', '2024-06-30'),
(6, 'BATCH 2', 'KUR_JIY_B2', 'Batch 2 for Jiyyammavalasa Mandal', '2024-07-01', '2024-12-31'),

-- KOMARADA Mandal Batches
(7, 'BATCH 1', 'KUR_KOM_B1', 'Batch 1 for Komarada Mandal', '2024-01-01', '2024-06-30'),
(7, 'BATCH 2', 'KUR_KOM_B2', 'Batch 2 for Komarada Mandal', '2024-07-01', '2024-12-31'),

-- GARUGUBILLI Mandal Batches
(8, 'BATCH 1', 'KUR_GAR_B1', 'Batch 1 for Garugubilli Mandal', '2024-01-01', '2024-06-30'),
(8, 'BATCH 2', 'KUR_GAR_B2', 'Batch 2 for Garugubilli Mandal', '2024-07-01', '2024-12-31');

-- Display summary of created data
SELECT 'Database Setup Complete!' as Status;
SELECT COUNT(*) as 'Total Constituencies' FROM constituencies;
SELECT COUNT(*) as 'Total Mandals' FROM mandals;
SELECT COUNT(*) as 'Total Batches' FROM batches;

-- Show constituency-wise breakdown
SELECT 
    c.name as 'Constituency',
    COUNT(DISTINCT m.id) as 'Mandals',
    COUNT(b.id) as 'Batches'
FROM constituencies c
LEFT JOIN mandals m ON c.id = m.constituency_id
LEFT JOIN batches b ON m.id = b.mandal_id
GROUP BY c.id, c.name
ORDER BY c.name;
