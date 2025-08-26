<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';
$upload_results = [];
$preview_data = [];
$show_preview = false;

// Handle final processing (after confirmation)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'process_confirmed') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $filepath = $_POST['filepath'] ?? '';
    $filename = $_POST['filename'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($filepath) || !file_exists($filepath)) {
        $error = 'File not found. Please upload the file again.';
    } else {
        $results = processBulkUpload($filepath, $filename, true); // true = actually insert
        $upload_results = $results;
        
        if ($results['success_count'] > 0) {
            $success = "Upload completed! {$results['success_count']} records uploaded successfully.";
            if ($results['error_count'] > 0) {
                $success .= " {$results['error_count']} records failed.";
            }
        } else {
            $error = "Upload failed. No records were processed successfully.";
        }
        
        // Clean up the uploaded file
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}

// Handle file upload for preview
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel_file'])) {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $file = $_FILES['excel_file'];
        
        if ($file['error'] == UPLOAD_ERR_OK) {
            $allowed_types = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv'];
            $file_type = $file['type'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (in_array($file_extension, ['csv'])) {
                $upload_dir = '../uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = date('Y-m-d_H-i-s') . '_' . $file['name'];
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // First, validate the CSV and show preview
                    $preview_results = processBulkUpload($filepath, $filename, false); // false = validation only
                    $preview_data = $preview_results;
                    $show_preview = true;
                } else {
                    $error = 'Failed to upload file.';
                }
            } else {
                $error = 'Please upload a CSV file (.csv). For Excel files, save as CSV format first.';
            }
        } else {
            $error = 'File upload error: ' . $file['error'];
        }
    }
}

// Function to process bulk upload
function processBulkUpload($filepath, $filename, $actually_insert = false) {
    $results = [
        'total_count' => 0,
        'success_count' => 0,
        'error_count' => 0,
        'errors' => [],
        'preview_data' => [],
        'filepath' => $filepath,
        'filename' => $filename
    ];
    
    $expected_columns = ['constituency', 'mandal', 'tc_id', 'batch', 'batch_start_date', 'batch_end_date', 'mobile_number', 'aadhar_number', 'full_name'];
    
    // Get file extension to determine processing method
    $file_extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    
    try {
        if ($file_extension == 'csv') {
            // Process CSV file
            if (($handle = fopen($filepath, "r")) !== FALSE) {
                $header = fgetcsv($handle); // Read header row
                
                // Validate header
                if (!validateHeaders($header, $expected_columns)) {
                    $results['errors'][] = "Invalid file format. Expected columns: " . implode(', ', $expected_columns);
                    return $results;
                }
                
                $row_number = 1;
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row_number++;
                    $results['total_count']++;
                    
                    try {
                        if ($actually_insert) {
                            // Actually insert into database
                            if (processBeneficiaryRecord($data, $row_number)) {
                                $results['success_count']++;
                            } else {
                                $results['error_count']++;
                                $results['errors'][] = "Row $row_number: Failed to process record";
                            }
                        } else {
                            // Validation only - collect preview data
                            $validation_result = validateBeneficiaryRecord($data, $row_number);
                            $results['preview_data'][] = $validation_result;
                            
                            if ($validation_result['valid']) {
                                $results['success_count']++;
                            } else {
                                $results['error_count']++;
                                $results['errors'][] = "Row $row_number: " . $validation_result['error'];
                            }
                        }
                    } catch (Exception $e) {
                        $results['error_count']++;
                        $results['errors'][] = "Row $row_number: " . $e->getMessage();
                    }
                }
                fclose($handle);
            }

        } else {
            $results['errors'][] = "Unsupported file format: $file_extension";
        }
    } catch (Exception $e) {
        $results['errors'][] = "Error reading file: " . $e->getMessage();
    }
    
    // Only log when actually inserting data
    if ($actually_insert) {
        logBulkUpload($filename, $results);
    }
    
    return $results;
}

function validateHeaders($header, $expected) {
    if (count($header) != count($expected)) {
        return false;
    }
    
    for ($i = 0; $i < count($expected); $i++) {
        if (strtolower(trim($header[$i])) != strtolower($expected[$i])) {
            return false;
        }
    }
    return true;
}

function convertScientificNotation($value) {
    // Convert scientific notation to full number (handles Aadhar numbers)
    if (is_numeric($value)) {
        // If it's in scientific notation (contains E or e), convert it
        if (stripos($value, 'e') !== false) {
            return sprintf('%.0f', floatval($value));
        }
        // If it's a regular number, ensure it's formatted as integer
        return sprintf('%.0f', floatval($value));
    }
    return $value;
}

function generateUniqueBeneficiaryId($constituency_id, $mandal_id) {
    $base_id = 'BEN' . date('Y') . str_pad($constituency_id, 2, '0', STR_PAD_LEFT) . str_pad($mandal_id, 2, '0', STR_PAD_LEFT);
    $max_attempts = 100; // Prevent infinite loops
    $attempt = 0;
    
    do {
        $random_suffix = rand(1000, 9999);
        $beneficiary_id = $base_id . $random_suffix;
        
        // Check if this ID already exists
        $existing = fetchRow("SELECT id FROM beneficiaries WHERE beneficiary_id = ?", [$beneficiary_id], 's');
        
        if (!$existing) {
            return $beneficiary_id; // Found a unique ID
        }
        
        $attempt++;
    } while ($attempt < $max_attempts);
    
    // If we couldn't find a unique ID with random numbers, use timestamp-based approach
    $timestamp_suffix = substr(str_replace('.', '', microtime(true)), -4);
    return $base_id . $timestamp_suffix;
}

function validateBeneficiaryRecord($data, $row_number) {
    $result = [
        'row_number' => $row_number,
        'valid' => false,
        'error' => '',
        'data' => [],
        'processed_data' => []
    ];
    
    try {
        // Extract data
        $constituency_name = trim($data[0]);
        $mandal_name = trim($data[1]);
        $tc_id = trim($data[2]);
        $batch_name = trim($data[3]);
        $batch_start_date = trim($data[4]);
        $batch_end_date = trim($data[5]);
        $mobile_number = convertScientificNotation(trim($data[6]));
        $aadhar_number = convertScientificNotation(trim($data[7]));
        $full_name = trim($data[8]);
        $status = isset($data[9]) ? trim($data[9]) : 'active'; // Default to active if status not provided
        
        $result['data'] = [
            'constituency' => $constituency_name,
            'mandal' => $mandal_name,
            'tc_id' => $tc_id,
            'batch' => $batch_name,
            'batch_start_date' => $batch_start_date,
            'batch_end_date' => $batch_end_date,
            'mobile_number' => $mobile_number,
            'aadhar_number' => $aadhar_number,
            'full_name' => $full_name,
            'status' => $status
        ];
        
        // Validate required fields
        if (empty($constituency_name) || empty($mandal_name) || empty($tc_id) || empty($batch_name) || empty($aadhar_number) || empty($full_name)) {
            throw new Exception("Missing required fields");
        }
        
        // Validate status
        $validStatuses = ['active', 'inactive'];
        if (!in_array(strtolower($status), $validStatuses)) {
            throw new Exception("Invalid status '$status'. Allowed values: active, inactive");
        }
        
        // Validate phone number
        if (!empty($mobile_number) && (!ctype_digit($mobile_number) || strlen($mobile_number) !== 10)) {
            throw new Exception("Phone number must be exactly 10 digits. Got: '$mobile_number'");
        }
        
        // Validate Aadhar number
        if (empty($aadhar_number)) {
            throw new Exception("Aadhar number is required");
        }
        
        // Check if Aadhar is in scientific notation
        if (stripos($aadhar_number, 'e') !== false) {
            throw new Exception("Aadhar number is in scientific notation. Please format the Aadhar column as TEXT in Excel. Got: '$aadhar_number'");
        }
        
        // Check if Aadhar is exactly 12 digits
        if (!ctype_digit($aadhar_number) || strlen($aadhar_number) !== 12) {
            throw new Exception("Aadhar number must be exactly 12 digits. Got: '$aadhar_number' (length: " . strlen($aadhar_number) . ")");
        }
        
        // Get constituency ID
        $constituency = fetchRow("SELECT id FROM constituencies WHERE name = ?", [$constituency_name], 's');
        if (!$constituency) {
            throw new Exception("Constituency '$constituency_name' not found");
        }
        
        // Get mandal ID
        $mandal = fetchRow("SELECT id FROM mandals WHERE name = ? AND constituency_id = ?", [$mandal_name, $constituency['id']], 'si');
        if (!$mandal) {
            throw new Exception("Mandal '$mandal_name' not found in constituency '$constituency_name'");
        }
        
        // Get training center ID
        $training_center = fetchRow("SELECT id FROM training_centers WHERE tc_id = ? AND mandal_id = ?", [$tc_id, $mandal['id']], 'si');
        if (!$training_center) {
            throw new Exception("Training center '$tc_id' not found in mandal '$mandal_name'");
        }
        
        // Get batch ID
        $batch = fetchRow("SELECT id FROM batches WHERE name = ? AND mandal_id = ? AND tc_id = ?", [$batch_name, $mandal['id'], $training_center['id']], 'sii');
        if (!$batch) {
            throw new Exception("Batch '$batch_name' not found for training center '$tc_id'");
        }
        
        // Validate dates - accept both DD/MM/YY and DD-MM-YY formats
        $date_formats = ['d/m/y', 'd-m-y', 'd/m/Y', 'd-m-Y'];
        $start_date = null;
        $end_date = null;
        
        foreach ($date_formats as $format) {
            $parsed_start = DateTime::createFromFormat($format, $batch_start_date);
            $parsed_end = DateTime::createFromFormat($format, $batch_end_date);
            
            if ($parsed_start && $parsed_end) {
                $start_date = $parsed_start->format('Y-m-d');
                $end_date = $parsed_end->format('Y-m-d');
                break;
            }
        }
        
        if (!$start_date || !$end_date) {
            throw new Exception("Invalid date format. Expected DD/MM/YY or DD-MM-YY. Got: Start='$batch_start_date', End='$batch_end_date'");
        }
        
        // Check if beneficiary already exists
        $existing = fetchRow("SELECT id FROM beneficiaries WHERE aadhar_number = ?", [$aadhar_number], 's');
        if ($existing) {
            throw new Exception("Beneficiary with Aadhar '$aadhar_number' already exists");
        }
        
        $result['processed_data'] = [
            'constituency_id' => $constituency['id'],
            'mandal_id' => $mandal['id'],
            'tc_id' => $training_center['id'],
            'batch_id' => $batch['id'],
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
        
        $result['valid'] = true;
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

function processBeneficiaryRecord($data, $row_number) {
    // Extract data
    $constituency_name = trim($data[0]);
    $mandal_name = trim($data[1]);
    $tc_id = trim($data[2]);
    $batch_name = trim($data[3]);
    $batch_start_date = trim($data[4]);
    $batch_end_date = trim($data[5]);
    $mobile_number = convertScientificNotation(trim($data[6])); // Fix scientific notation
    $aadhar_number = convertScientificNotation(trim($data[7])); // Fix scientific notation
    $full_name = trim($data[8]);
    $status = isset($data[9]) ? trim($data[9]) : 'active'; // Default to active if status not provided
    
    // Validate required fields
    if (empty($constituency_name) || empty($mandal_name) || empty($tc_id) || empty($batch_name) || empty($aadhar_number) || empty($full_name)) {
        throw new Exception("Missing required fields");
    }
    
    // Validate phone number (must be exactly 10 digits)
    if (!empty($mobile_number) && (!ctype_digit($mobile_number) || strlen($mobile_number) !== 10)) {
        throw new Exception("Phone number must be exactly 10 digits. Got: '$mobile_number'");
    }
    
    // Validate Aadhar number
    if (empty($aadhar_number)) {
        throw new Exception("Aadhar number is required");
    }
    
    // Check if Aadhar is in scientific notation
    if (stripos($aadhar_number, 'e') !== false) {
        throw new Exception("Aadhar number is in scientific notation. Please format the Aadhar column as TEXT in Excel. Got: '$aadhar_number'");
    }
    
    // Check if Aadhar is exactly 12 digits
    if (!ctype_digit($aadhar_number) || strlen($aadhar_number) !== 12) {
        throw new Exception("Aadhar number must be exactly 12 digits. Got: '$aadhar_number' (length: " . strlen($aadhar_number) . ")");
    }
    
    // Get constituency ID
    $constituency = fetchRow("SELECT id FROM constituencies WHERE name = ?", [$constituency_name], 's');
    if (!$constituency) {
        throw new Exception("Constituency '$constituency_name' not found");
    }
    
    // Get mandal ID
    $mandal = fetchRow("SELECT id FROM mandals WHERE name = ? AND constituency_id = ?", [$mandal_name, $constituency['id']], 'si');
    if (!$mandal) {
        throw new Exception("Mandal '$mandal_name' not found in constituency '$constituency_name'");
    }
    
    // Get training center ID
    $training_center = fetchRow("SELECT id FROM training_centers WHERE tc_id = ? AND mandal_id = ?", [$tc_id, $mandal['id']], 'si');
    if (!$training_center) {
        throw new Exception("Training center '$tc_id' not found in mandal '$mandal_name'");
    }
    
    // Get batch ID
    $batch = fetchRow("SELECT id FROM batches WHERE name = ? AND mandal_id = ? AND tc_id = ?", [$batch_name, $mandal['id'], $training_center['id']], 'sii');
    if (!$batch) {
        throw new Exception("Batch '$batch_name' not found for training center '$tc_id'");
    }
    
    // Validate dates - accept both DD/MM/YY and DD-MM-YY formats
    $date_formats = ['d/m/y', 'd-m-y', 'd/m/Y', 'd-m-Y'];
    $start_date = null;
    $end_date = null;
    
    foreach ($date_formats as $format) {
        $parsed_start = DateTime::createFromFormat($format, $batch_start_date);
        $parsed_end = DateTime::createFromFormat($format, $batch_end_date);
        
        if ($parsed_start && $parsed_end) {
            $start_date = $parsed_start->format('Y-m-d');
            $end_date = $parsed_end->format('Y-m-d');
            break;
        }
    }
    
    if (!$start_date || !$end_date) {
        throw new Exception("Invalid date format. Expected DD/MM/YY or DD-MM-YY. Got: Start='$batch_start_date', End='$batch_end_date'");
    }
    
    // Check if beneficiary already exists
    $existing = fetchRow("SELECT id FROM beneficiaries WHERE aadhar_number = ?", [$aadhar_number], 's');
    if ($existing) {
        throw new Exception("Beneficiary with Aadhar '$aadhar_number' already exists");
    }
    
    // Generate unique beneficiary_id
    $beneficiary_id = generateUniqueBeneficiaryId($constituency['id'], $mandal['id']);
    
    // Insert beneficiary using direct mysqli (like in beneficiaries.php)
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO beneficiaries (beneficiary_id, constituency_id, mandal_id, tc_id, batch_id, mobile_number, aadhar_number, full_name, batch_start_date, batch_end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('siiiissssss', $beneficiary_id, $constituency['id'], $mandal['id'], $training_center['id'], $batch['id'], $mobile_number, $aadhar_number, $full_name, $start_date, $end_date, strtolower($status));
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        throw new Exception("Database error: " . $e->getMessage());
    }
}

function logBulkUpload($filename, $results) {
    $query = "INSERT INTO bulk_upload_log (filename, total_records, successful_records, failed_records, uploaded_by, status, error_log) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $status = $results['error_count'] > 0 ? ($results['success_count'] > 0 ? 'partial' : 'failed') : 'completed';
    $error_log = implode("\n", $results['errors']);
    
    executeQuery($query, [
        $filename,
        $results['total_count'],
        $results['success_count'],
        $results['error_count'],
        $_SESSION['admin_user_id'],
        $status,
        $error_log
    ], 'siiiiss');
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Data Import - BC Attendance Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .upload-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }
        
        .file-upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .file-upload-area.dragover {
            border-color: #007bff;
            background-color: #e3f2fd;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 1rem;
        }
        
        .format-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .sample-format {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
        }
        
        .results-section {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .error-list {
            max-height: 300px;
            overflow-y: auto;
            background: white;
            padding: 1rem;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .btn-download-sample {
            background: #28a745;
            color: white;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 0.5rem 0;
        }
        
        .btn-download-sample:hover {
            background: #218838;
            color: white;
            text-decoration: none;
        }
        
        /* Modal styles for better scrolling */
        .modal-xl {
            max-width: 90% !important;
        }
        
        .modal-content {
            max-height: 90vh !important;
            overflow: hidden !important;
        }
        
        .modal-body {
            overflow-y: auto !important;
            max-height: calc(90vh - 120px) !important;
        }
        
        .table-responsive {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        
        .preview-stats .stat-card {
            text-align: center;
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .preview-stats .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .preview-stats .stat-card.success h3 {
            color: #28a745;
        }
        
        .preview-stats .stat-card.error h3 {
            color: #dc3545;
        }
        
        .error-item {
            padding: 0.25rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .modal-xl {
                max-width: 95% !important;
                margin: 0.5rem auto !important;
            }
            
            .modal-content {
                max-height: 95vh !important;
            }
            
            .modal-body {
                max-height: calc(95vh - 100px) !important;
                padding: 0.75rem !important;
            }
            
            .table-responsive {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="upload-container">
            <h2><i class="fas fa-users-cog"></i> Student Data Import</h2>
            
            <div class="format-info">
                <h4><i class="fas fa-info-circle"></i> CSV File Format Requirements</h4>
                <p>Your CSV file must have exactly these columns in this order:</p>
                <div class="sample-format">
constituency | mandal | tc_id | batch | batch_start_date | batch_end_date | mobile_number | aadhar_number | full_name | status
                </div>
                <p><strong>Example:</strong></p>
                <div class="sample-format">
PARVATHIPURAM | BALIJIPETA | TTC7430652 | BATCH 1 | 16-06-25 | 30-09-25 | 7799773656 | 975422335686 | RAJESH GULLA | active
                </div>
                <p><strong>Date Format:</strong> <code>DD-MM-YY</code> (e.g., 16-06-25) or <code>DD/MM/YY</code> (e.g., 16/06/25)</p>
                <p><strong>Status Options:</strong> <code>active</code> or <code>inactive</code> (defaults to <code>active</code> if not specified)</p>
                <a href="download_sample.php" class="btn-download-sample">
                    <i class="fas fa-download"></i> Download Sample CSV File
                </a>
                
                <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 5px;">
                    <h5><i class="fas fa-info-circle"></i> How to Convert Excel to CSV:</h5>
                    <ol style="margin: 10px 0; padding-left: 20px;">
                        <li>Open your Excel file</li>
                        <li><strong>Important:</strong> Format Aadhar and Mobile columns as TEXT before saving</li>
                        <li>Select the Aadhar/Mobile columns → Right-click → Format Cells → Text</li>
                        <li>Click <strong>File</strong> → <strong>Save As</strong></li>
                        <li>Choose <strong>CSV (Comma delimited)</strong> format</li>
                        <li>Save and upload the CSV file</li>
                    </ol>
                    <div style="background: #fff3cd; padding: 8px; border-radius: 3px; margin-top: 10px;">
                        <strong>💡 Tip:</strong> The system automatically fixes scientific notation (9.68E+11) in Aadhar numbers, but formatting as TEXT prevents this issue.
                    </div>
                </div>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="upload-icon">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h4>Drop your CSV file here or click to browse</h4>
                    <p>Supported formats: .csv</p>
                    <p><strong>For Excel files:</strong> Save as CSV in Excel before uploading</p>
                    <input type="file" name="excel_file" id="excelFile" accept=".csv" style="display: none;" required>
                    <button type="button" class="btn btn-primary" id="browseBtn">
                        <i class="fas fa-folder-open"></i> Browse Files
                    </button>
                </div>
                
                <div id="selectedFile" style="display: none; margin: 1rem 0;">
                    <p><strong>Selected File:</strong> <span id="fileName"></span></p>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Upload Students
                    </button>
                </div>
            </form>
            
            <?php if (!empty($upload_results)): ?>
                <div class="results-section">
                    <h4><i class="fas fa-chart-bar"></i> Upload Results</h4>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h3><?php echo $upload_results['total_count']; ?></h3>
                                <p>Total Records</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card success">
                                <h3><?php echo $upload_results['success_count']; ?></h3>
                                <p>Successful</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card error">
                                <h3><?php echo $upload_results['error_count']; ?></h3>
                                <p>Failed</p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($upload_results['errors'])): ?>
                        <h5><i class="fas fa-exclamation-triangle"></i> Error Details</h5>
                        <div class="error-list">
                            <?php foreach ($upload_results['errors'] as $error): ?>
                                <div class="error-item"><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Preview Modal -->
            <?php if ($show_preview): ?>
            <div class="modal fade show" id="previewModal" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5); position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050; overflow-x: hidden; overflow-y: auto;">
                <div class="modal-dialog modal-xl" role="document" style="margin: 1.75rem auto; max-width: 90%; width: 90%;">
                    <div class="modal-content" style="max-height: 90vh; display: flex; flex-direction: column;">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="fas fa-eye"></i> CSV Preview & Validation Results</h5>
                        </div>
                        <div class="modal-body" style="flex: 1; overflow-y: auto; max-height: calc(90vh - 120px);">
                            <div class="preview-stats mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="stat-card">
                                            <h3><?php echo $preview_data['total_count']; ?></h3>
                                            <p>Total Records</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card success">
                                            <h3><?php echo $preview_data['success_count']; ?></h3>
                                            <p>Valid Records</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stat-card error">
                                            <h3><?php echo $preview_data['error_count']; ?></h3>
                                            <p>Invalid Records</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($preview_data['error_count'] > 0): ?>
                                <div class="alert alert-warning">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Validation Errors Found</h6>
                                    <div class="error-list" style="max-height: 200px; overflow-y: auto;">
                                        <?php foreach ($preview_data['errors'] as $error): ?>
                                            <div class="error-item"><?php echo htmlspecialchars($error); ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="mt-2"><strong>Recommendation:</strong> Fix these errors in your CSV file before proceeding to ensure 100% success rate.</p>
                                </div>
                            <?php endif; ?>
                            
                            <h6><i class="fas fa-table"></i> Data Preview (First 10 rows)</h6>
                            <div class="table-responsive" style="max-height: 400px;">
                                <table class="table table-sm table-bordered table-striped">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Row</th>
                                            <th>Valid</th>
                                            <th>Name</th>
                                            <th>Aadhar</th>
                                            <th>Mobile</th>
                                            <th>Constituency</th>
                                            <th>Mandal</th>
                                            <th>TC ID</th>
                                            <th>Batch</th>
                                            <th>Status</th>
                                            <th>Error</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $displayed_rows = 0;
                                        foreach ($preview_data['preview_data'] as $row): 
                                            if ($displayed_rows >= 10) break;
                                            $displayed_rows++;
                                        ?>
                                        <tr class="<?php echo $row['valid'] ? 'table-success' : 'table-danger'; ?>">
                                            <td><?php echo $row['row_number']; ?></td>
                                            <td>
                                                <?php if ($row['valid']): ?>
                                                    <span class="badge badge-success">Valid</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Invalid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['data']['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['data']['aadhar_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['data']['mobile_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['data']['constituency']); ?></td>
                                            <td><?php echo htmlspecialchars($row['data']['mandal']); ?></td>
                                            <td><?php echo htmlspecialchars($row['data']['tc_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['data']['batch']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($row['data']['status']) === 'active' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($row['data']['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!$row['valid']): ?>
                                                    <small class="text-danger"><?php echo htmlspecialchars($row['error']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php if (count($preview_data['preview_data']) > 10): ?>
                                    <p class="text-muted"><em>... and <?php echo count($preview_data['preview_data']) - 10; ?> more rows</em></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer" style="flex-shrink: 0; border-top: 1px solid #dee2e6; padding: 1rem; background: #f8f9fa;">
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="process_confirmed">
                                <input type="hidden" name="filepath" value="<?php echo htmlspecialchars($preview_data['filepath']); ?>">
                                <input type="hidden" name="filename" value="<?php echo htmlspecialchars($preview_data['filename']); ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Proceed with Upload
                                </button>
                            </form>
                            <a href="bulk_upload.php" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('excelFile');
        const selectedFileDiv = document.getElementById('selectedFile');
        const fileNameSpan = document.getElementById('fileName');
        const browseBtn = document.getElementById('browseBtn');

        // Function to handle file selection
        function handleFileSelection() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                console.log('File selected:', file.name);
                fileNameSpan.textContent = file.name;
                selectedFileDiv.style.display = 'block';
            }
        }

        // Browse button click (prevent event bubbling)
        browseBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            console.log('Browse button clicked');
            fileInput.click();
        });

        // File upload area click (but not when clicking the button)
        fileUploadArea.addEventListener('click', (e) => {
            // Only trigger if not clicking the browse button
            if (e.target !== browseBtn && !browseBtn.contains(e.target)) {
                console.log('Upload area clicked');
                fileInput.click();
            }
        });

        // File selection
        fileInput.addEventListener('change', (e) => {
            console.log('File input changed');
            handleFileSelection();
        });

        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                console.log('File dropped:', files[0].name);
                fileInput.files = files;
                handleFileSelection();
            }
        });

        // Form submission
        document.getElementById('uploadForm').addEventListener('submit', (e) => {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                submitBtn.disabled = true;
            }
        });

        // Debug: Log when DOM is ready
        document.addEventListener('DOMContentLoaded', () => {
            console.log('Bulk upload page loaded');
        });
    </script>
</body>
</html>
