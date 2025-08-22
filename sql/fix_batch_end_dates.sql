-- Fix Batch End Dates - Calculate based on 90 working days
-- This script ensures all batches have proper end dates for attendance calculations

USE bc_attendance;

-- First, let's see current batch dates
SELECT id, name, code, start_date, end_date, status FROM batches WHERE status = 'active';

-- We'll use the PHP function to calculate proper end dates
-- For now, let's set a reasonable end date that we can adjust later
UPDATE batches 
SET end_date = DATE_ADD(start_date, INTERVAL 120 DAY)
WHERE status = 'active' 
AND (end_date IS NULL OR end_date = '0000-00-00' OR end_date = '1970-01-01');

-- Show the updated batch dates
SELECT id, name, code, start_date, end_date, status FROM batches WHERE status = 'active';

-- Show final status
SELECT 'Batch end dates updated with temporary values. Use PHP function for precise calculation.' as status;
