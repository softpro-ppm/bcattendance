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

$pageTitle = 'Add Beneficiaries';
$error = '';
$success = '';
$upload_results = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Handle single beneficiary add
        if ($action == 'add_single') {
            $constituency_id = (int)$_POST['constituency_id'];
            $mandal_id = (int)$_POST['mandal_id'];
            $tc_id = (int)$_POST['tc_id'];
            $batch_id = (int)$_POST['batch_id'];
            $phone_number = sanitizeInput($_POST['phone_number']);
            $aadhar_number = sanitizeInput($_POST['aadhar_number']);
            $full_name = sanitizeInput($_POST['full_name']);
            $batch_start_date = $_POST['batch_start_date'];
            $batch_end_date = $_POST['batch_end_date'];
            
            if (empty($full_name) || empty($aadhar_number) || empty($constituency_id) || empty($mandal_id) || empty($tc_id) || empty($batch_id)) {
                $error = 'Full name, Aadhar number, and location details are required.';
            } else {
                // Check if aadhar number already exists
                $existing = fetchRow("SELECT id FROM beneficiaries WHERE aadhar_number = ?", [$aadhar_number], 's');
                if ($existing) {
                    $error = 'Beneficiary with this Aadhar number already exists.';
                } else {
                    $query = "INSERT INTO beneficiaries (constituency_id, mandal_id, tc_id, batch_id, phone_number, aadhar_number, full_name, batch_start_date, batch_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    if (executeQuery($query, [$constituency_id, $mandal_id, $tc_id, $batch_id, $phone_number, $aadhar_number, $full_name, $batch_start_date, $batch_end_date], 'iiiisssss')) {
                        $success = 'Beneficiary added successfully.';
                        // Clear form
                        $_POST = [];
                    } else {
                        $error = 'Failed to add beneficiary.';
                    }
                }
            }
        }
        
        // Handle bulk upload
        if ($action == 'bulk_upload' && isset($_FILES['excel_file'])) {
            $file = $_FILES['excel_file'];
            
            if ($file['error'] == UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($file_extension, ['csv', 'xls', 'xlsx'])) {
                    $upload_dir = '../uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $filename = date('Y-m-d_H-i-s') . '_' . $file['name'];
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        $results = processBulkUpload($filepath, $filename);
                        $upload_results = $results;
                        
                        if ($results['success_count'] > 0) {
                            $success = "Bulk upload completed! {$results['success_count']} records uploaded successfully.";
                            if ($results['error_count'] > 0) {
                                $success .= " {$results['error_count']} records failed.";
                            }
                        } else {
                            $error = "Upload failed. No records were processed successfully.";
                        }
                    } else {
                        $error = 'Failed to upload file.';
                    }
                } else {
                    $error = 'Please upload a valid Excel file (.csv, .xls, .xlsx).';
                }
            } else {
                $error = 'File upload error.';
            }
        }
    }
}

// Function to process bulk upload
function processBulkUpload($filepath, $filename) {
    $results = [
        'total_count' => 0,
        'success_count' => 0,
        'error_count' => 0,
        'errors' => []
    ];
    
    // Read CSV file
    if (($handle = fopen($filepath, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Read header row
        $expected_columns = ['constituency', 'mandal', 'tc_id', 'batch', 'batch_start_date', 'batch_end_date', 'phone_number', 'aadhar_number', 'full_name'];
        
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
                if (processBeneficiaryRecord($data, $row_number)) {
                    $results['success_count']++;
                } else {
                    $results['error_count']++;
                    $results['errors'][] = "Row $row_number: Failed to process record";
                }
            } catch (Exception $e) {
                $results['error_count']++;
                $results['errors'][] = "Row $row_number: " . $e->getMessage();
            }
        }
        fclose($handle);
    }
    
    // Log the upload
    logBulkUpload($filename, $results);
    
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

function processBeneficiaryRecord($data, $row_number) {
    // Extract data
    $constituency_name = trim($data[0]);
    $mandal_name = trim($data[1]);
    $tc_id = trim($data[2]);
    $batch_name = trim($data[3]);
    $batch_start_date = trim($data[4]);
    $batch_end_date = trim($data[5]);
    $phone_number = trim($data[6]);
    $aadhar_number = trim($data[7]);
    $full_name = trim($data[8]);
    
    // Validate required fields
    if (empty($constituency_name) || empty($mandal_name) || empty($tc_id) || empty($batch_name) || empty($aadhar_number) || empty($full_name)) {
        throw new Exception("Missing required fields");
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
    
    // Validate dates
    $start_date = date('Y-m-d', strtotime($batch_start_date));
    $end_date = date('Y-m-d', strtotime($batch_end_date));
    
    // Check if beneficiary already exists
    $existing = fetchRow("SELECT id FROM beneficiaries WHERE aadhar_number = ?", [$aadhar_number], 's');
    if ($existing) {
        throw new Exception("Beneficiary with Aadhar '$aadhar_number' already exists");
    }
    
    // Insert beneficiary
    $query = "INSERT INTO beneficiaries (constituency_id, mandal_id, tc_id, batch_id, phone_number, aadhar_number, full_name, batch_start_date, batch_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $result = executeQuery($query, [
        $constituency['id'],
        $mandal['id'],
        $training_center['id'],
        $batch['id'],
        $phone_number,
        $aadhar_number,
        $full_name,
        $start_date,
        $end_date
    ], 'iiiisssss');
    
    return $result !== false;
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

// Get all constituencies, mandals, training centers, and batches for dropdowns
$mandals_with_tc = fetchAll("
    SELECT 
        m.id as mandal_id, 
        m.name as mandal_name,
        m.constituency_id,
        c.name as constituency_name,
        tc.id as tc_id,
        tc.tc_id as tc_code,
        tc.name as tc_name
    FROM mandals m
    JOIN constituencies c ON m.constituency_id = c.id
    JOIN training_centers tc ON m.id = tc.mandal_id
    WHERE m.status = 'active' AND tc.status = 'active'
    ORDER BY c.name, m.name
");

$batches_with_details = fetchAll("
    SELECT 
        b.id as batch_id,
        b.name as batch_name,
        b.start_date,
        b.end_date,
        b.mandal_id,
        b.tc_id,
        m.name as mandal_name,
        c.name as constituency_name,
        tc.tc_id as tc_code
    FROM batches b
    JOIN mandals m ON b.mandal_id = m.id
    JOIN constituencies c ON m.constituency_id = c.id
    JOIN training_centers tc ON b.tc_id = tc.id
    WHERE b.status IN ('active', 'completed')
    ORDER BY b.status DESC, c.name, m.name, b.name
");

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-user-plus"></i> Add Beneficiaries</h2>
                <a href="beneficiaries.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Beneficiaries List
                </a>
            </div>
            
            <!-- Alert Messages -->
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
            
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="addBeneficiaryTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="single-tab" data-toggle="tab" href="#single" role="tab">
                        <i class="fas fa-user"></i> Add Single Beneficiary
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="bulk-tab" data-toggle="tab" href="#bulk" role="tab">
                        <i class="fas fa-upload"></i> Bulk Upload
                    </a>
                </li>
            </ul>
            
            <!-- Tab Content -->
            <div class="tab-content" id="addBeneficiaryTabsContent">
                <!-- Single Beneficiary Tab -->
                <div class="tab-pane fade show active" id="single" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-user-plus"></i> Add New Beneficiary
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="add_single">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="full_name">Full Name *</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="aadhar_number">Aadhar Number *</label>
                                            <input type="text" class="form-control" id="aadhar_number" name="aadhar_number" 
                                                   maxlength="12" value="<?php echo htmlspecialchars($_POST['aadhar_number'] ?? ''); ?>" required>
                                            <small class="form-text text-muted">Must be unique (12 digits)</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone_number">Phone Number</label>
                                            <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                                   maxlength="10" pattern="[0-9]{10}" title="Please enter exactly 10 digits" value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="constituency_mandal">Constituency & Mandal *</label>
                                            <select class="form-control" id="constituency_mandal" name="constituency_mandal" required onchange="updateLocationDetails()">
                                                <option value="">Select Constituency & Mandal</option>
                                                <?php foreach ($mandals_with_tc as $mandal): ?>
                                                    <option value="<?php echo $mandal['constituency_id'] . '|' . $mandal['mandal_id'] . '|' . $mandal['tc_id']; ?>">
                                                        <?php echo htmlspecialchars($mandal['constituency_name'] . ' - ' . $mandal['mandal_name'] . ' (' . $mandal['tc_code'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="constituency_id" id="constituency_id">
                                            <input type="hidden" name="mandal_id" id="mandal_id">
                                            <input type="hidden" name="tc_id" id="tc_id">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="batch_id">Batch *</label>
                                            <select class="form-control" id="batch_id" name="batch_id" required onchange="updateBatchDates()">
                                                <option value="">Select Batch</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Batch Duration</label>
                                            <div class="d-flex">
                                                <input type="date" class="form-control mr-2" name="batch_start_date" id="batch_start_date" readonly>
                                                <input type="date" class="form-control" name="batch_end_date" id="batch_end_date" readonly>
                                            </div>
                                            <small class="form-text text-muted">Auto-filled from selected batch</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Add Beneficiary
                                    </button>
                                    <button type="reset" class="btn btn-secondary ml-2">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Bulk Upload Tab -->
                <div class="tab-pane fade" id="bulk" role="tabpanel">
                    <div class="card mt-3">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-upload"></i> Bulk Upload Beneficiaries
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle"></i> Excel File Format Requirements</h6>
                                        <p class="mb-2">Your Excel file must have exactly these columns in this order:</p>
                                        <div class="bg-light p-2 rounded mb-2">
                                            <code>constituency | mandal | tc_id | batch | batch_start_date | batch_end_date | phone_number | aadhar_number | full_name</code>
                                        </div>
                                        <p class="mb-2"><strong>Example:</strong></p>
                                        <div class="bg-light p-2 rounded mb-2">
                                            <small><code>PARVATHIPURAM | PARVATHIPURAM | TTC7430317 | Batch 1 | 07-05-25 | 20-08-25 | 9876543210 | 123456789012 | John Doe</code></small>
                                        </div>
                                        <a href="download_sample.php" class="btn btn-success btn-sm">
                                            <i class="fas fa-download"></i> Download Sample Excel File
                                        </a>
                                    </div>
                                    
                                    <form method="POST" enctype="multipart/form-data" id="bulkUploadForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="bulk_upload">
                                        
                                        <div class="form-group">
                                            <label for="excel_file">Choose Excel File *</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" id="excel_file" name="excel_file" accept=".csv,.xls,.xlsx" required>
                                                <label class="custom-file-label" for="excel_file">Choose file...</label>
                                            </div>
                                            <small class="form-text text-muted">Supported formats: .csv, .xls, .xlsx</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-upload"></i> Upload Beneficiaries
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6>Quick Tips</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success"></i> Use exact constituency/mandal names</li>
                                                <li><i class="fas fa-check text-success"></i> Use correct TC IDs (TTC7430317, etc.)</li>
                                                <li><i class="fas fa-check text-success"></i> Use exact batch names (Batch 1, Batch 2)</li>
                                                <li><i class="fas fa-check text-success"></i> Aadhar numbers must be unique</li>
                                                <li><i class="fas fa-check text-success"></i> Date format: DD-MM-YY</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($upload_results)): ?>
                                <div class="mt-4">
                                    <h6><i class="fas fa-chart-bar"></i> Upload Results</h6>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h4 class="text-primary"><?php echo $upload_results['total_count']; ?></h4>
                                                    <p class="mb-0">Total Records</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h4 class="text-success"><?php echo $upload_results['success_count']; ?></h4>
                                                    <p class="mb-0">Successful</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h4 class="text-danger"><?php echo $upload_results['error_count']; ?></h4>
                                                    <p class="mb-0">Failed</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card text-center">
                                                <div class="card-body">
                                                    <h4 class="text-info"><?php echo round(($upload_results['success_count'] / max($upload_results['total_count'], 1)) * 100, 1); ?>%</h4>
                                                    <p class="mb-0">Success Rate</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($upload_results['errors'])): ?>
                                        <div class="mt-3">
                                            <h6><i class="fas fa-exclamation-triangle text-warning"></i> Error Details</h6>
                                            <div class="alert alert-warning" style="max-height: 300px; overflow-y: auto;">
                                                <?php foreach ($upload_results['errors'] as $error): ?>
                                                    <div><?php echo htmlspecialchars($error); ?></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Store batch data for JavaScript use
const batchData = <?php echo json_encode($batches_with_details); ?>;

function updateLocationDetails() {
    const select = document.getElementById('constituency_mandal');
    const values = select.value.split('|');
    
    if (values.length === 3) {
        document.getElementById('constituency_id').value = values[0];
        document.getElementById('mandal_id').value = values[1];
        document.getElementById('tc_id').value = values[2];
        
        // Update batch dropdown
        updateBatchDropdown(values[1], values[2]);
    } else {
        // Clear all fields
        document.getElementById('constituency_id').value = '';
        document.getElementById('mandal_id').value = '';
        document.getElementById('tc_id').value = '';
        document.getElementById('batch_id').innerHTML = '<option value="">Select Batch</option>';
        document.getElementById('batch_start_date').value = '';
        document.getElementById('batch_end_date').value = '';
    }
}

function updateBatchDropdown(mandalId, tcId) {
    const batchSelect = document.getElementById('batch_id');
    batchSelect.innerHTML = '<option value="">Select Batch</option>';
    
    batchData.forEach(batch => {
        if (batch.mandal_id == mandalId && batch.tc_id == tcId) {
            const option = document.createElement('option');
            option.value = batch.batch_id;
            option.textContent = batch.batch_name;
            option.dataset.startDate = batch.start_date;
            option.dataset.endDate = batch.end_date;
            batchSelect.appendChild(option);
        }
    });
}

function updateBatchDates() {
    const batchSelect = document.getElementById('batch_id');
    const selectedOption = batchSelect.options[batchSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        document.getElementById('batch_start_date').value = selectedOption.dataset.startDate || '';
        document.getElementById('batch_end_date').value = selectedOption.dataset.endDate || '';
    } else {
        document.getElementById('batch_start_date').value = '';
        document.getElementById('batch_end_date').value = '';
    }
}

// Auto-format inputs
document.addEventListener('DOMContentLoaded', function() {
    // Aadhar number formatting
    const aadharInput = document.getElementById('aadhar_number');
    aadharInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 12) {
            this.value = this.value.slice(0, 12);
        }
    });
    
    // Phone number formatting
    const phoneInput = document.getElementById('phone_number');
    phoneInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 15) {
            this.value = this.value.slice(0, 15);
        }
    });
    
    // File input label update
    document.getElementById('excel_file').addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'Choose file...';
        this.nextElementSibling.textContent = fileName;
    });
    
    // Form submission loading state
    document.getElementById('bulkUploadForm').addEventListener('submit', function() {
        const button = this.querySelector('button[type="submit"]');
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        button.disabled = true;
    });
});
</script>
