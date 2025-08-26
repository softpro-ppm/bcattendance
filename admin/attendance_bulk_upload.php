<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';
$show_preview = false;
$preview_data = [];
$validation_results = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action']) && $_POST['action'] === 'process_confirmed') {
        // Process confirmed upload - actual database insertion
        if (isset($_SESSION['attendance_preview_data'])) {
            $results = processAttendanceBulkUpload($_SESSION['attendance_preview_data'], true);
            
            if ($results['success']) {
                setSuccessMessage("Attendance import completed successfully! {$results['successful']} records imported, {$results['failed']} failed.");
                
                // Log the import
                logAttendanceImport($results);
                
                // Clear session data
                unset($_SESSION['attendance_preview_data']);
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            } else {
                setErrorMessage("Import failed: " . $results['error']);
            }
        }
    } else {
        // Initial file upload and validation
        if (isset($_FILES['attendance_file']) && $_FILES['attendance_file']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['attendance_file'];
            $filename = $uploadedFile['name'];
            $tmpPath = $uploadedFile['tmp_name'];
            
            // Validate file extension
            $allowedExtensions = ['csv', 'xlsx', 'xls'];
            $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                $error = 'Please upload a CSV or Excel file.';
            } else {
                // Create unique filename and move file
                $uniqueFilename = date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
                $uploadDir = '../uploads/';
                $uploadPath = $uploadDir . $uniqueFilename;
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (move_uploaded_file($tmpPath, $uploadPath)) {
                    // Preview and validate the data
                    $results = processAttendanceBulkUpload($uploadPath, false);
                    
                    if ($results['success']) {
                        $preview_data = $results['preview_data'];
                        $validation_results = $results['validation_results'];
                        $show_preview = true;
                        
                        // Store in session for confirmation
                        $_SESSION['attendance_preview_data'] = $uploadPath;
                    } else {
                        $error = $results['error'];
                    }
                } else {
                    $error = 'Failed to upload file. Please try again.';
                }
            }
        } else {
            $error = 'Please select a file to upload.';
        }
    }
}

// Function to process attendance bulk upload
function processAttendanceBulkUpload($filePath, $actuallyInsert = false) {
    try {
        // Read CSV file
        $csvData = array_map('str_getcsv', file($filePath));
        
        if (empty($csvData)) {
            return ['success' => false, 'error' => 'File is empty or invalid.'];
        }
        
        $headers = array_shift($csvData); // Remove header row
        $results = [
            'success' => true,
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'errors' => [],
            'preview_data' => [],
            'validation_results' => []
        ];
        
        // Parse headers to find date columns
        $beneficiaryColumns = 2; // First 2 columns: aadhar_number, full_name
        $dateColumns = array_slice($headers, $beneficiaryColumns);
        
        foreach ($csvData as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // Account for header and 0-based index
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            $results['total']++;
            
            // Extract beneficiary info (simplified format)
            if (count($row) < $beneficiaryColumns) {
                $results['failed']++;
                $results['errors'][] = "Row $rowNumber: Insufficient columns (need at least Aadhar and Name)";
                continue;
            }
            
            $beneficiaryData = [
                'aadhar_number' => trim($row[0]),
                'full_name' => trim($row[1])
            ];
            
            // Validate and process this beneficiary's attendance
            $attendanceData = array_slice($row, $beneficiaryColumns);
            $beneficiaryResult = processAttendanceForBeneficiary($beneficiaryData, $attendanceData, $dateColumns, $rowNumber, $actuallyInsert);
            
            if ($beneficiaryResult['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
                $results['errors'] = array_merge($results['errors'], $beneficiaryResult['errors']);
            }
            
            // Store preview data (first 10 rows only)
            if (count($results['preview_data']) < 10) {
                $results['preview_data'][] = [
                    'row_number' => $rowNumber,
                    'beneficiary' => $beneficiaryData,
                    'attendance_count' => $beneficiaryResult['attendance_count'] ?? 0,
                    'valid' => $beneficiaryResult['success'],
                    'errors' => $beneficiaryResult['errors'] ?? []
                ];
            }
        }
        
        $results['validation_results'] = [
            'total_beneficiaries' => $results['total'],
            'valid_beneficiaries' => $results['successful'],
            'invalid_beneficiaries' => $results['failed']
        ];
        
        return $results;
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Error processing file: ' . $e->getMessage()];
    }
}

// Function to process attendance for a single beneficiary
function processAttendanceForBeneficiary($beneficiaryData, $attendanceData, $dateColumns, $rowNumber, $actuallyInsert = false) {
    $errors = [];
    $attendanceCount = 0;
    
    // Find beneficiary in database by Aadhar number
    $beneficiary = fetchRow("SELECT * FROM beneficiaries WHERE aadhar_number = ?", [$beneficiaryData['aadhar_number']], 's');
    
    if (!$beneficiary) {
        return [
            'success' => false,
            'errors' => ["Row $rowNumber: Beneficiary with Aadhar '{$beneficiaryData['aadhar_number']}' not found in database"],
            'attendance_count' => 0
        ];
    }
    
    // Verify name matches (optional - warn if different)
    if (strtolower(trim($beneficiary['full_name'])) !== strtolower(trim($beneficiaryData['full_name']))) {
        $errors[] = "Row $rowNumber: Name mismatch warning - Database: '{$beneficiary['full_name']}', CSV: '{$beneficiaryData['full_name']}'";
    }
    
    // Process each date column
    foreach ($dateColumns as $index => $dateStr) {
        if ($index >= count($attendanceData)) {
            break; // No more attendance data
        }
        
        $status = trim($attendanceData[$index]);
        
        // Skip empty status (future dates)
        if (empty($status)) {
            continue;
        }
        
        // Validate and standardize status
        $validStatuses = ['P', 'A', 'H', 'present', 'absent'];
        if (!in_array($status, $validStatuses)) {
            $errors[] = "Row $rowNumber: Invalid status '$status' for date $dateStr. Allowed values: P, A, H, present, absent.";
            continue;
        }
        
        // Convert to standardized format
        $standardizedStatus = $status;
        switch ($status) {
            case 'P':
                $standardizedStatus = 'present';
                break;
            case 'A':
            case 'H':
                $standardizedStatus = 'absent';
                break;
            // 'present' and 'absent' remain as-is
        }
        
        // Parse date
        $date = DateTime::createFromFormat('d/m/y', $dateStr);
        if (!$date) {
            $errors[] = "Row $rowNumber: Invalid date format '$dateStr'. Expected DD/MM/YY.";
            continue;
        }
        
        $attendanceCount++;
        
        // Insert attendance record if actuallyInsert is true
        if ($actuallyInsert) {
            $query = "INSERT INTO attendance (beneficiary_id, attendance_date, status, batch_id, constituency_id, mandal_id, tc_id, created_by) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?) 
                     ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = CURRENT_TIMESTAMP";
            
            $params = [
                $beneficiary['id'],
                $date->format('Y-m-d'),
                $standardizedStatus,  // Use standardized format
                $beneficiary['batch_id'],
                $beneficiary['constituency_id'],
                $beneficiary['mandal_id'],
                $beneficiary['tc_id'],
                $_SESSION['admin_user_id']
            ];
            
            $result = executeQuery($query, $params, 'issiiiii');
            
            if (!$result) {
                $errors[] = "Row $rowNumber: Failed to insert attendance for date " . $date->format('d/m/Y');
            }
        }
    }
    
    return [
        'success' => empty($errors),
        'errors' => $errors,
        'attendance_count' => $attendanceCount
    ];
}

// Function to log attendance import
function logAttendanceImport($results) {
    $query = "INSERT INTO attendance_import_log (filename, total_records, successful_records, failed_records, total_beneficiaries, uploaded_by, status, error_log) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $filename = basename($_SESSION['attendance_preview_data'] ?? 'unknown');
    $status = ($results['failed'] == 0) ? 'completed' : (($results['successful'] > 0) ? 'partial' : 'failed');
    $errorLog = !empty($results['errors']) ? implode("\n", $results['errors']) : null;
    
    $params = [
        $filename,
        $results['total'],
        $results['successful'],
        $results['failed'],
        $results['total'], // total_beneficiaries same as total for now
        $_SESSION['admin_user_id'],
        $status,
        $errorLog
    ];
    
    executeQuery($query, $params, 'siiiiiss');
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history"></i> Historical Data Import
                    </h3>
                </div>
                
                <div class="card-body">
                    <?php echo displayFlashMessages(); ?>
                    
                    <?php if (!$show_preview): ?>
                    
                    <!-- Upload Form -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>Upload Historical Attendance Data</strong><br>
                                Upload your existing attendance records in CSV format. The system will validate and import all attendance data.
                            </div>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="form-group">
                                    <label for="attendance_file">Select Attendance File</label>
                                    <input type="file" class="form-control-file" id="attendance_file" name="attendance_file" accept=".csv,.xlsx,.xls" required>
                                    <small class="form-text text-muted">Supported formats: CSV, Excel (.xlsx, .xls)</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Upload & Preview
                                </button>
                            </form>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-download"></i> Sample Template</h5>
                                </div>
                                <div class="card-body">
                                    <p>Download the sample template to see the required format:</p>
                                    <a href="download_attendance_sample.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-download"></i> Download Sample CSV
                                    </a>
                                    
                                    <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border-radius: 3px; font-size: 0.85em;">
                                        <strong>💡 Tip:</strong> For Excel files, save as CSV format before uploading. Format Aadhar column as TEXT to avoid scientific notation.
                                    </div>
                                    
                                    <hr>
                                    <small>
                                        <strong>CSV Format Requirements:</strong><br>
                                        • <strong>Required columns:</strong> aadhar_number, full_name, status<br>
                                        • <strong>Date columns:</strong> Daily attendance (DD/MM/YY format)<br>
                                        • <strong>Status codes:</strong> P (Present), A (Absent), H (Holiday)<br>
                                        • <strong>Status values:</strong> active, inactive (for beneficiary status)<br>
                                        • <strong>Auto-matching:</strong> System finds beneficiary by Aadhar number<br>
                                        • <strong>Multiple beneficiaries</strong> from different batches supported
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($show_preview): ?>
<!-- Preview Modal -->
<div class="modal fade show" id="previewModal" tabindex="-1" style="display: block; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1050; overflow-y: auto;">
    <div class="modal-dialog modal-xl" style="max-width: 95%; margin: 1rem auto;">
        <div class="modal-content" style="display: flex; flex-direction: column; height: calc(100vh - 2rem);">
            <div class="modal-header" style="flex-shrink: 0;">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check"></i> Attendance Import Preview
                </h5>
            </div>
            
            <div class="modal-body" style="flex: 1; overflow-y: auto; max-height: calc(100vh - 200px);">
                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="preview-stats d-flex justify-content-around">
                            <div class="stat-card text-center p-3 bg-primary text-white rounded">
                                <h3><?php echo $validation_results['total_beneficiaries']; ?></h3>
                                <small>Total Beneficiaries</small>
                            </div>
                            <div class="stat-card text-center p-3 bg-success text-white rounded">
                                <h3><?php echo $validation_results['valid_beneficiaries']; ?></h3>
                                <small>Valid Records</small>
                            </div>
                            <div class="stat-card text-center p-3 bg-danger text-white rounded">
                                <h3><?php echo $validation_results['invalid_beneficiaries']; ?></h3>
                                <small>Invalid Records</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Validation Errors -->
                <?php if (!empty($validation_results) && $validation_results['invalid_beneficiaries'] > 0): ?>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Validation Issues Found</h6>
                    <div style="max-height: 200px; overflow-y: auto;">
                        <?php foreach (array_slice($results['errors'] ?? [], 0, 20) as $error): ?>
                            <div class="error-item small mb-1">• <?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                        <?php if (count($results['errors'] ?? []) > 20): ?>
                            <small class="text-muted">... and <?php echo count($results['errors']) - 20; ?> more errors</small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Preview Table -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light sticky-top">
                            <tr>
                                <th>Row</th>
                                <th>Status</th>
                                <th>Beneficiary</th>
                                <th>Aadhar</th>
                                <th>Batch Info</th>
                                <th>Attendance Records</th>
                                <th>Issues</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($preview_data as $row): ?>
                            <tr class="<?php echo $row['valid'] ? 'table-success' : 'table-danger'; ?>">
                                <td><?php echo $row['row_number']; ?></td>
                                <td>
                                    <?php if ($row['valid']): ?>
                                        <span class="badge badge-success">Valid</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Invalid</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['beneficiary']['full_name']); ?></td>
                                <td><small><?php echo htmlspecialchars($row['beneficiary']['aadhar_number']); ?></small></td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($row['beneficiary']['constituency']); ?> / 
                                        <?php echo htmlspecialchars($row['beneficiary']['mandal']); ?><br>
                                        <?php echo htmlspecialchars($row['beneficiary']['batch']); ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo $row['attendance_count']; ?> records</span>
                                </td>
                                <td>
                                    <?php if (!empty($row['errors'])): ?>
                                        <small class="text-danger">
                                            <?php echo htmlspecialchars(implode('; ', array_slice($row['errors'], 0, 2))); ?>
                                            <?php if (count($row['errors']) > 2): ?>
                                                <br><em>+<?php echo count($row['errors']) - 2; ?> more...</em>
                                            <?php endif; ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-success">No issues</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="modal-footer" style="flex-shrink: 0;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="process_confirmed">
                    <?php if ($validation_results['valid_beneficiaries'] > 0): ?>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Proceed with Import (<?php echo $validation_results['valid_beneficiaries']; ?> records)
                        </button>
                    <?php endif; ?>
                </form>
                
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.modal-xl { max-width: 95%; }
.modal-content { max-height: calc(100vh - 2rem); }
.modal-body { overflow-y: auto; }
.table-responsive { border: 1px solid #dee2e6; }
.preview-stats .stat-card { margin: 0 10px; min-width: 120px; }
.error-item { font-family: monospace; }

@media (max-width: 768px) {
    .modal-dialog { margin: 0.5rem; }
    .modal-content { height: calc(100vh - 1rem); }
}
</style>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
