<?php
session_start();
require_once '../config/database.php';
require_once '../includes/session.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Sample data for the Excel file
$sample_data = [
    ['constituency', 'mandal', 'tc_id', 'batch', 'batch_start_date', 'batch_end_date', 'mobile_number', 'aadhar_number', 'full_name'],
    ['PARVATHIPURAM', 'PARVATHIPURAM', 'TTC7430317', 'BATCH 1', '07-05-25', '20-08-25', '9876543210', '123456789012', 'John Doe'],
    ['PARVATHIPURAM', 'BALIJIPETA', 'TTC7430652', 'BATCH 1', '16-06-25', '30-09-25', '9876543211', '123456789013', 'Jane Smith'],
    ['KURUPAM', 'KURUPAM', 'TTC7430664', 'BATCH 2', '07-05-25', '20-08-25', '9876543212', '123456789014', 'Robert Johnson'],
    ['KURUPAM', 'GL PURAM', 'TTC7430536', 'BATCH 1', '07-05-25', '20-08-25', '9876543213', '123456789015', 'Mary Wilson']
];

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="beneficiaries_sample_format.csv"');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');

// Create CSV output
$output = fopen('php://output', 'w');

// Write data to CSV
foreach ($sample_data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
