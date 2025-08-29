-- Fix Missing batch_holidays Table
-- This script recreates the missing batch_holidays table

-- Step 1: Create the batch_holidays table
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
    
    -- Foreign key constraints
    FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_holiday_id (holiday_id),
    INDEX idx_batch_id (batch_id),
    INDEX idx_holiday_date (holiday_date),
    
    -- Unique constraint to prevent duplicate assignments
    UNIQUE KEY unique_batch_holiday (batch_id, holiday_date)
);

-- Step 2: Check if table was created successfully
SELECT 'batch_holidays table created successfully' as status;

-- Step 3: Show table structure
DESCRIBE batch_holidays;

-- Step 4: Check if any existing batch-holiday relationships exist
SELECT COUNT(*) as existing_relationships FROM batch_holidays;
