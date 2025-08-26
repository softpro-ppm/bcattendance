-- Clean Student Data and Attendance Data
-- This script will remove all beneficiaries and attendance records
-- while preserving constituency, mandal, training center, and batch data

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clear attendance-related tables first (child tables before parent tables)
DELETE FROM attendance_edit_log;
DELETE FROM attendance_import_log;
DELETE FROM attendance_restrictions;
DELETE FROM deleted_attendance_back;

-- Clear main attendance table
DELETE FROM attendance;

-- Clear beneficiaries table
DELETE FROM beneficiaries;

-- Clear any backup tables if they exist
DELETE FROM beneficiaries_backup;
DELETE FROM beneficiaries_backup_full;
DELETE FROM deleted_students_backup;

-- Clear bulk upload logs
DELETE FROM bulk_upload_log;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify the cleanup
SELECT 'Cleanup completed successfully!' as status;

-- Show remaining data counts
SELECT 
    (SELECT COUNT(*) FROM constituencies) as constituencies_count,
    (SELECT COUNT(*) FROM mandals) as mandals_count,
    (SELECT COUNT(*) FROM training_centers) as training_centers_count,
    (SELECT COUNT(*) FROM batches) as batches_count,
    (SELECT COUNT(*) FROM beneficiaries) as beneficiaries_count,
    (SELECT COUNT(*) FROM attendance) as attendance_count;

-- Note: All student data and attendance data has been removed
-- Your constituency, mandal, training center, and batch data remains intact
-- You can now upload fresh student data and historical attendance data
