-- BC Attendance Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS bc_attendance;
USE bc_attendance;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
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
CREATE TABLE IF NOT EXISTS constituencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Mandals table
CREATE TABLE IF NOT EXISTS mandals (
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
CREATE TABLE IF NOT EXISTS batches (
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
CREATE TABLE IF NOT EXISTS beneficiaries (
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
CREATE TABLE IF NOT EXISTS attendance (
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

-- Insert sample constituencies
INSERT INTO constituencies (name, code, description) VALUES 
('Constituency A', 'CONST_A', 'Description for Constituency A'),
('Constituency B', 'CONST_B', 'Description for Constituency B'),
('Constituency C', 'CONST_C', 'Description for Constituency C');

-- Insert sample mandals
INSERT INTO mandals (constituency_id, name, code, description) VALUES 
(1, 'Mandal A1', 'MAN_A1', 'Mandal A1 in Constituency A'),
(1, 'Mandal A2', 'MAN_A2', 'Mandal A2 in Constituency A'),
(2, 'Mandal B1', 'MAN_B1', 'Mandal B1 in Constituency B'),
(2, 'Mandal B2', 'MAN_B2', 'Mandal B2 in Constituency B');

-- Insert sample batches
INSERT INTO batches (mandal_id, name, code, description, start_date, end_date) VALUES 
(1, 'Batch 2024-01', 'BATCH_A1_01', 'First batch of 2024 for Mandal A1', '2024-01-01', '2024-03-31'),
(1, 'Batch 2024-02', 'BATCH_A1_02', 'Second batch of 2024 for Mandal A1', '2024-04-01', '2024-06-30'),
(2, 'Batch 2024-01', 'BATCH_A2_01', 'First batch of 2024 for Mandal A2', '2024-01-01', '2024-03-31'),
(3, 'Batch 2024-01', 'BATCH_B1_01', 'First batch of 2024 for Mandal B1', '2024-01-01', '2024-03-31');

-- Insert sample beneficiaries
INSERT INTO beneficiaries (beneficiary_id, first_name, last_name, father_name, date_of_birth, gender, mobile_number, email, constituency_id, mandal_id, batch_id, aadhar_number) VALUES 
('BEN001', 'John', 'Doe', 'Robert Doe', '1990-05-15', 'male', '9876543210', 'john.doe@email.com', 1, 1, 1, '123456789012'),
('BEN002', 'Jane', 'Smith', 'Michael Smith', '1992-08-20', 'female', '9876543211', 'jane.smith@email.com', 1, 1, 1, '123456789013'),
('BEN003', 'David', 'Johnson', 'William Johnson', '1988-12-10', 'male', '9876543212', 'david.johnson@email.com', 1, 2, 3, '123456789014'),
('BEN004', 'Sarah', 'Williams', 'James Williams', '1991-03-25', 'female', '9876543213', 'sarah.williams@email.com', 2, 3, 4, '123456789015');
