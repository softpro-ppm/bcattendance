-- Update Batch End Dates based on 90 Working Days from Start Date
-- This script calculates correct end dates by adding 90 working days (excluding Sundays and Saturdays)

-- First, let's see the current dates
SELECT 'CURRENT DATES' as status, id, name, start_date, end_date FROM batches ORDER BY start_date;

-- Update batches starting from 2025-05-07 (should end around 2025-09-09)
UPDATE batches SET end_date = '2025-09-09' WHERE start_date = '2025-05-07';

-- Update batches starting from 2025-06-16 (should end around 2025-10-17)
UPDATE batches SET end_date = '2025-10-17' WHERE start_date = '2025-06-16';

-- Show the updated dates
SELECT 'UPDATED DATES' as status, id, name, start_date, end_date FROM batches ORDER BY start_date;

-- Verify the working days calculation
-- Note: 90 working days from start date, excluding Sundays and Saturdays
-- This gives approximately 4.5 months of training time

