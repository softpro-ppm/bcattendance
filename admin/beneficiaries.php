<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Function to generate unique beneficiary ID
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

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check and mark completed batches automatically
$batchCompletionResult = checkAndMarkCompletedBatches();
if ($batchCompletionResult['success'] && $batchCompletionResult['count'] > 0) {
    $success = $batchCompletionResult['message'];
}

$pageTitle = 'Student Management';
$error = '';
$success = '';

// Handle AJAX live search with pagination
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'live_search') {
    header('Content-Type: application/json');
    
    $search = sanitizeInput($_POST['search'] ?? '');
    $page = (int)($_POST['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Get filter parameters
    $filter_constituency = sanitizeInput($_POST['filter_constituency'] ?? '');
    $filter_mandal = sanitizeInput($_POST['filter_mandal'] ?? '');
    $filter_tc = sanitizeInput($_POST['filter_tc'] ?? '');
    $filter_batch = sanitizeInput($_POST['filter_batch'] ?? '');
    
    // Build query for live search with filters
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // Search condition
    if (!empty($search)) {
        $whereConditions[] = "(b.full_name LIKE ? OR b.aadhar_number LIKE ? OR b.mobile_number LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        $types .= 'sss';
    }
    
    // Filter conditions
    if (!empty($filter_constituency)) {
        $whereConditions[] = "b.constituency_id = ?";
        $params[] = $filter_constituency;
        $types .= 'i';
    }
    
    if (!empty($filter_mandal)) {
        $whereConditions[] = "b.mandal_id = ?";
        $params[] = $filter_mandal;
        $types .= 'i';
    }
    
    if (!empty($filter_tc)) {
        $whereConditions[] = "tc.tc_id = ?";
        $params[] = $filter_tc;
        $types .= 's';
    }
    
    if (!empty($filter_batch)) {
        $whereConditions[] = "b.batch_id = ?";
        $params[] = $filter_batch;
        $types .= 'i';
    }
    
    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
    
    try {
        // Get total count for pagination
        $countQuery = "
            SELECT COUNT(*) as total 
            FROM beneficiaries b
            LEFT JOIN constituencies c ON b.constituency_id = c.id
            LEFT JOIN mandals m ON b.mandal_id = m.id
            LEFT JOIN training_centers tc ON b.tc_id = tc.id
            LEFT JOIN batches batch ON b.batch_id = batch.id
            $whereClause
        ";
        $totalResult = fetchRow($countQuery, $params, $types);
        $totalRecords = $totalResult['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        // Get beneficiaries for current search with pagination
        $query = "
            SELECT 
                b.*,
                c.name as constituency_name,
                m.name as mandal_name,
                tc.tc_id as training_center_id,
                batch.name as batch_name,
                batch.start_date as batch_start_date,
                batch.end_date as batch_end_date
            FROM beneficiaries b
            LEFT JOIN constituencies c ON b.constituency_id = c.id
            LEFT JOIN mandals m ON b.mandal_id = m.id
            LEFT JOIN training_centers tc ON b.tc_id = tc.id
            LEFT JOIN batches batch ON b.batch_id = batch.id
            $whereClause
            ORDER BY m.name ASC, b.full_name ASC
            LIMIT ? OFFSET ?
        ";
        
        $paginationParams = $params;
        $paginationParams[] = $limit;
        $paginationParams[] = $offset;
        $paginationTypes = $types . 'ii';
        
        $beneficiaries = fetchAll($query, $paginationParams, $paginationTypes);
        
        // Generate table HTML with serial numbers
        $html = '';
        $serial = $offset + 1; // Continue numbering across pages
        foreach ($beneficiaries as $beneficiary) {
            $html .= '<tr>';
            $html .= '<td>' . $serial++ . '</td>';
            $html .= '<td><strong>' . htmlspecialchars($beneficiary['aadhar_number']) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($beneficiary['full_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($beneficiary['mobile_number'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($beneficiary['constituency_name'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($beneficiary['mandal_name'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($beneficiary['training_center_id'] ?? 'N/A') . '</td>';
            $html .= '<td>' . htmlspecialchars($beneficiary['batch_name'] ?? 'N/A') . '</td>';
            $html .= '<td>';
            if (!empty($beneficiary['batch_start_date']) && !empty($beneficiary['batch_end_date'])) {
                $html .= '<small>';
                $html .= '<strong>Start:</strong> ' . formatDate($beneficiary['batch_start_date'], 'd/m/Y') . '<br>';
                $html .= '<strong>End:</strong> ' . formatDate($beneficiary['batch_end_date'], 'd/m/Y');
                $html .= '</small>';
            } else {
                $html .= '<span class="text-muted">N/A</span>';
            }
            $html .= '</td>';
            $html .= '<td><span class="badge badge-' . ($beneficiary['status'] == 'active' ? 'success' : 'secondary') . '">' . ucfirst($beneficiary['status']) . '</span></td>';
            $html .= '<td>';
            $html .= '<button type="button" class="btn btn-sm btn-primary mr-1" onclick="editBeneficiary(' . htmlspecialchars(json_encode($beneficiary)) . ')" data-toggle="modal" data-target="#editBeneficiaryModal"><i class="fas fa-edit"></i></button>';
            $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteBeneficiary(' . $beneficiary['id'] . ')"><i class="fas fa-trash"></i></button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        // Generate pagination HTML
        $paginationHtml = '';
        if ($totalPages > 1) {
            $paginationHtml .= '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
            
            // Previous button
            if ($page > 1) {
                $paginationHtml .= '<li class="page-item"><button class="page-link" onclick="searchWithPagination(' . ($page - 1) . ')">Previous</button></li>';
            } else {
                $paginationHtml .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }
            
            // Page numbers
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            if ($start > 1) {
                $paginationHtml .= '<li class="page-item"><button class="page-link" onclick="searchWithPagination(1)">1</button></li>';
                if ($start > 2) {
                    $paginationHtml .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            for ($i = $start; $i <= $end; $i++) {
                if ($i == $page) {
                    $paginationHtml .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                } else {
                    $paginationHtml .= '<li class="page-item"><button class="page-link" onclick="searchWithPagination(' . $i . ')">' . $i . '</button></li>';
                }
            }
            
            if ($end < $totalPages) {
                if ($end < $totalPages - 1) {
                    $paginationHtml .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                $paginationHtml .= '<li class="page-item"><button class="page-link" onclick="searchWithPagination(' . $totalPages . ')">' . $totalPages . '</button></li>';
            }
            
            // Next button
            if ($page < $totalPages) {
                $paginationHtml .= '<li class="page-item"><button class="page-link" onclick="searchWithPagination(' . ($page + 1) . ')">Next</button></li>';
            } else {
                $paginationHtml .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }
            
            $paginationHtml .= '</ul></nav>';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'pagination' => $paginationHtml,
            'total' => $totalRecords,
            'page' => $page,
            'totalPages' => $totalPages,
            'showing' => [
                'from' => $offset + 1,
                'to' => min($offset + $limit, $totalRecords),
                'total' => $totalRecords
            ]
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
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
            } elseif (!empty($phone_number) && (!ctype_digit($phone_number) || strlen($phone_number) !== 10)) {
                $error = 'Phone number must be exactly 10 digits.';
            } else {
                // Check if aadhar number already exists
                $existing = fetchRow("SELECT id FROM beneficiaries WHERE aadhar_number = ?", [$aadhar_number], 's');
                if ($existing) {
                    $error = 'Beneficiary with this Aadhar number already exists.';
                } else {
                    // Generate unique beneficiary_id
                    $beneficiary_id = generateUniqueBeneficiaryId($constituency_id, $mandal_id);
                    
                    try {
                        $conn = getDBConnection();
                        $stmt = $conn->prepare("INSERT INTO beneficiaries (beneficiary_id, constituency_id, mandal_id, tc_id, batch_id, mobile_number, aadhar_number, full_name, batch_start_date, batch_end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        $stmt->bind_param('siiiisssss', $beneficiary_id, $constituency_id, $mandal_id, $tc_id, $batch_id, $phone_number, $aadhar_number, $full_name, $batch_start_date, $batch_end_date);
                        
                        if ($stmt->execute()) {
                            $success = 'Beneficiary added successfully.';
                        } else {
                            $error = 'Failed to add beneficiary: ' . $stmt->error;
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action == 'edit') {
            $id = (int)$_POST['id'];
            $phone_number = sanitizeInput($_POST['phone_number']);
            $aadhar_number = sanitizeInput($_POST['aadhar_number']);
            $full_name = sanitizeInput($_POST['full_name']);
            $status = sanitizeInput($_POST['status']);
            
            if (empty($full_name) || empty($aadhar_number)) {
                $error = 'Full name and Aadhar number are required.';
            } elseif (!empty($phone_number) && (!ctype_digit($phone_number) || strlen($phone_number) !== 10)) {
                $error = 'Phone number must be exactly 10 digits.';
            } else {
                // Check if aadhar number already exists (excluding current record)
                $existing = fetchRow("SELECT id FROM beneficiaries WHERE aadhar_number = ? AND id != ?", [$aadhar_number, $id], 'si');
                if ($existing) {
                    $error = 'Beneficiary with this Aadhar number already exists.';
                } else {
                    try {
                        $conn = getDBConnection();
                        $stmt = $conn->prepare("UPDATE beneficiaries SET mobile_number = ?, aadhar_number = ?, full_name = ?, status = ? WHERE id = ?");
                        
                        if (!$stmt) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        $stmt->bind_param('ssssi', $phone_number, $aadhar_number, $full_name, $status, $id);
                        
                        if ($stmt->execute()) {
                            if ($stmt->affected_rows > 0) {
                                $success = 'Beneficiary updated successfully.';
                            } else {
                                $error = 'No changes made or beneficiary not found.';
                            }
                        } else {
                            $error = 'Failed to update beneficiary: ' . $stmt->error;
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action == 'delete') {
            $id = (int)$_POST['id'];
            
            // Check if beneficiary has attendance records
            $attendance = fetchRow("SELECT COUNT(*) as count FROM attendance WHERE beneficiary_id = ?", [$id], 'i');
            if ($attendance && $attendance['count'] > 0) {
                $error = 'Cannot delete beneficiary. They have ' . $attendance['count'] . ' attendance records.';
            } else {
                try {
                    $conn = getDBConnection();
                    $stmt = $conn->prepare("DELETE FROM beneficiaries WHERE id = ?");
                    $stmt->bind_param('i', $id);
                    
                    if ($stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $success = 'Beneficiary deleted successfully.';
                        } else {
                            $error = 'Beneficiary not found or already deleted.';
                        }
                    } else {
                        $error = 'Failed to delete beneficiary: ' . $stmt->error;
                    }
                    $stmt->close();
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

        // Pagination
        $page = $_GET['page'] ?? 1;
$limit = 20;
        $offset = ($page - 1) * $limit;
        
// Get search parameter
        $search = $_GET['search'] ?? '';
$whereClause = '';
        $params = [];
        $types = '';
        
        if (!empty($search)) {
    $whereClause = "WHERE (b.full_name LIKE ? OR b.aadhar_number LIKE ? OR b.mobile_number LIKE ?)";
            $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam];
    $types = 'sss';
}
        
        // Get total count
$countQuery = "SELECT COUNT(*) as total FROM beneficiaries b $whereClause";
        $totalResult = fetchRow($countQuery, $params, $types);
        $totalRecords = $totalResult['total'];
        $totalPages = ceil($totalRecords / $limit);
        
// Get beneficiaries with location details
$query = "
    SELECT 
        b.*,
        c.name as constituency_name,
        m.name as mandal_name,
        tc.tc_id as training_center_id,
        bt.name as batch_name,
        bt.start_date as batch_start_date,
        bt.end_date as batch_end_date
                 FROM beneficiaries b 
                 LEFT JOIN constituencies c ON b.constituency_id = c.id 
                 LEFT JOIN mandals m ON b.mandal_id = m.id 
    LEFT JOIN training_centers tc ON b.tc_id = tc.id
                 LEFT JOIN batches bt ON b.batch_id = bt.id 
                 $whereClause 
                 ORDER BY m.name ASC, b.full_name ASC
    LIMIT ? OFFSET ?
";
        
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $beneficiaries = fetchAll($query, $params, $types);

// Get all constituencies, mandals, training centers, and batches for the add modal
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

// Get filter data for dropdowns
$filter_constituencies = fetchAll("SELECT DISTINCT id, name FROM constituencies ORDER BY name");
$filter_mandals = fetchAll("SELECT DISTINCT m.id, m.name, m.constituency_id FROM mandals m JOIN constituencies c ON m.constituency_id = c.id ORDER BY c.name, m.name");
$filter_training_centers = fetchAll("SELECT DISTINCT tc.tc_id, tc.mandal_id, m.constituency_id FROM training_centers tc JOIN mandals m ON tc.mandal_id = m.id WHERE tc.status = 'active' ORDER BY tc.tc_id");
$filter_batches = fetchAll("
    SELECT DISTINCT b.id, b.name, m.constituency_id, m.id as mandal_id, tc.tc_id
    FROM batches b 
    JOIN mandals m ON b.mandal_id = m.id 
    JOIN training_centers tc ON b.tc_id = tc.id
    WHERE b.status IN ('active', 'completed') 
    ORDER BY b.status DESC, b.name
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-users"></i> Beneficiaries Management
                    </h3>
                    <div>
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBeneficiaryModal">
                            <i class="fas fa-plus"></i> Add Beneficiaries
                        </button>
                        <a href="bulk_upload.php" class="btn btn-success ml-2">
                            <i class="fas fa-upload"></i> Bulk Upload
                        </a>
                    </div>
                </div>
                
                <div class="card-body">
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
                    
                    <!-- Search -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="d-flex">
                                <input type="text" id="liveSearch" class="form-control" placeholder="Search by name, Aadhar, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                                <div class="btn btn-outline-secondary ml-2" id="searchStatus">
                                    <i class="fas fa-search"></i>
                                </div>
                                <button type="button" id="clearSearch" class="btn btn-outline-danger ml-2" style="display: none;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted" id="recordCount">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalRecords); ?> of <?php echo number_format($totalRecords); ?> beneficiaries
                            </small>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header py-2">
                                    <h6 class="mb-0">
                                        <i class="fas fa-filter"></i> Filters
                                        <button type="button" id="clearFilters" class="btn btn-sm btn-outline-secondary ml-2">
                                            <i class="fas fa-times"></i> Clear All
                                        </button>
                                    </h6>
                                </div>
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <label for="filterConstituency" class="form-label">Constituency</label>
                                            <select id="filterConstituency" class="form-control">
                                                <option value="">All Constituencies</option>
                                                <?php foreach ($filter_constituencies as $constituency): ?>
                                                    <option value="<?php echo $constituency['id']; ?>">
                                                        <?php echo htmlspecialchars($constituency['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterMandal" class="form-label">Mandal</label>
                                            <select id="filterMandal" class="form-control">
                                                <option value="">All Mandals</option>
                                                <?php foreach ($filter_mandals as $mandal): ?>
                                                    <option value="<?php echo $mandal['id']; ?>" data-constituency="<?php echo $mandal['constituency_id']; ?>">
                                                        <?php echo htmlspecialchars($mandal['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterTC" class="form-label">TC ID</label>
                                            <select id="filterTC" class="form-control">
                                                <option value="">All TC IDs</option>
                                                <?php foreach ($filter_training_centers as $tc): ?>
                                                    <option value="<?php echo htmlspecialchars($tc['tc_id']); ?>" 
                                                            data-mandal="<?php echo $tc['mandal_id']; ?>"
                                                            data-constituency="<?php echo $tc['constituency_id']; ?>">
                                                        <?php echo htmlspecialchars($tc['tc_id']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="filterBatch" class="form-label">Batch</label>
                                            <select id="filterBatch" class="form-control">
                                                <option value="">All Batches</option>
                                                <?php foreach ($filter_batches as $batch): ?>
                                                    <option value="<?php echo $batch['id']; ?>" 
                                                            data-constituency="<?php echo $batch['constituency_id']; ?>" 
                                                            data-mandal="<?php echo $batch['mandal_id']; ?>"
                                                            data-tc="<?php echo htmlspecialchars($batch['tc_id']); ?>">
                                                        <?php echo htmlspecialchars($batch['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

        <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>S.No</th>
                                    <th>Aadhar Number</th>
                                    <th>Full Name</th>
                                    <th>Phone Number</th>
                                    <th>Constituency</th>
                                    <th>Mandal</th>
                                    <th>TC ID</th>
                                    <th>Batch</th>
                                    <th>Batch Dates</th>
                                    <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                                <?php if (empty($beneficiaries)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No beneficiaries found.</p>
                                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBeneficiaryModal">
                                                <i class="fas fa-plus"></i> Add First Beneficiary
                                            </button>
                                        </td>
                                    </tr>
                                <?php else: ?>
                    <?php 
                    $serial = $offset + 1; // Continue numbering across pages
                    foreach ($beneficiaries as $beneficiary): ?>
                    <tr>
                        <td><?php echo $serial++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($beneficiary['aadhar_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($beneficiary['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($beneficiary['mobile_number'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($beneficiary['constituency_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($beneficiary['mandal_name'] ?? 'N/A'); ?></td>
                                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($beneficiary['training_center_id'] ?? 'N/A'); ?></span></td>
                        <td><?php echo htmlspecialchars($beneficiary['batch_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($beneficiary['batch_start_date'] && $beneficiary['batch_end_date']): ?>
                                <small class="text-muted">
                                    <?php echo date('M d', strtotime($beneficiary['batch_start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($beneficiary['batch_end_date'])); ?>
                                </small>
                            <?php else: ?>
                                <small class="text-muted">N/A</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($beneficiary['status']); ?>">
                                <?php echo ucfirst($beneficiary['status']); ?>
                            </span>
                        </td>
                        <td>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="editBeneficiary(<?php echo htmlspecialchars(json_encode($beneficiary)); ?>)">
                                <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteBeneficiary(<?php echo $beneficiary['id']; ?>, '<?php echo addslashes($beneficiary['full_name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination will be inserted here by AJAX -->
        <div id="paginationContainer">
        <?php if ($totalPages > 1): ?>
                        <div class="d-flex justify-content-center mt-3">
                            <nav>
                                <ul class="pagination">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                    <button class="page-link" onclick="searchWithPagination(<?php echo $page - 1; ?>)">Previous</button>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <button class="page-link" onclick="searchWithPagination(<?php echo $i; ?>)"><?php echo $i; ?></button>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <li class="page-item">
                                    <button class="page-link" onclick="searchWithPagination(<?php echo $page + 1; ?>)">Next</button>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
        </div>
        <?php endif; ?>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

<!-- Add Beneficiary Modal -->
<div class="modal fade" id="addBeneficiaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Add New Beneficiary
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_full_name">Full Name *</label>
                                <input type="text" class="form-control" id="add_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_aadhar_number">Aadhar Number *</label>
                                <input type="text" class="form-control" id="add_aadhar_number" name="aadhar_number" maxlength="12" required>
                                <small class="form-text text-muted">Must be unique (12 digits)</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_phone_number">Phone Number</label>
                                <input type="tel" class="form-control" id="add_phone_number" name="phone_number" maxlength="10" pattern="[0-9]{10}" title="Please enter exactly 10 digits">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_constituency_mandal">Constituency & Mandal *</label>
                                <select class="form-control" id="add_constituency_mandal" name="constituency_mandal" required onchange="updateAddLocationDetails()">
                                    <option value="">Select Constituency & Mandal</option>
                                    <?php foreach ($mandals_with_tc as $mandal): ?>
                                        <option value="<?php echo $mandal['constituency_id'] . '|' . $mandal['mandal_id'] . '|' . $mandal['tc_id']; ?>">
                                            <?php echo htmlspecialchars($mandal['constituency_name'] . ' - ' . $mandal['mandal_name'] . ' (' . $mandal['tc_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="constituency_id" id="add_constituency_id">
                                <input type="hidden" name="mandal_id" id="add_mandal_id">
                                <input type="hidden" name="tc_id" id="add_tc_id">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_batch_id">Batch *</label>
                                <select class="form-control" id="add_batch_id" name="batch_id" required onchange="updateAddBatchDates()">
                                    <option value="">Select Batch</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Batch Duration</label>
                                <div class="d-flex">
                                    <input type="date" class="form-control mr-2" name="batch_start_date" id="add_batch_start_date" readonly>
                                    <input type="date" class="form-control" name="batch_end_date" id="add_batch_end_date" readonly>
                                </div>
                                <small class="form-text text-muted">Auto-filled from selected batch</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" onclick="debugFormSubmission(event)">
                        <i class="fas fa-save"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Beneficiary Modal -->
<div class="modal fade" id="editBeneficiaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Beneficiary
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_full_name">Full Name *</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
            </div>

                    <div class="form-group">
                        <label for="edit_aadhar_number">Aadhar Number *</label>
                        <input type="text" class="form-control" id="edit_aadhar_number" name="aadhar_number" maxlength="12" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_phone_number">Phone Number</label>
                        <input type="tel" class="form-control" id="edit_phone_number" name="phone_number" maxlength="10" pattern="[0-9]{10}" title="Please enter exactly 10 digits">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="completed">Completed</option>
                        </select>
            </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> To change location details (Constituency, Mandal, TC, Batch), please delete and re-add the beneficiary.
                </div>
            </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Beneficiary
                    </button>
                </div>
            </form>
                    </div>
                </div>
            </div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBeneficiaryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i> Delete Beneficiary
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
            </div>

                <div class="modal-body">
                    <p>Are you sure you want to delete "<span id="delete_name"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action cannot be undone.
                </div>
            </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
            </div>
        </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Store batch data for JavaScript use
const batchData = <?php echo json_encode($batches_with_details); ?>;

function updateAddLocationDetails() {
    const select = document.getElementById('add_constituency_mandal');
    const values = select.value.split('|');
    
    if (values.length === 3) {
        document.getElementById('add_constituency_id').value = values[0];
        document.getElementById('add_mandal_id').value = values[1];
        document.getElementById('add_tc_id').value = values[2];
        
        // Update batch dropdown
        updateAddBatchDropdown(values[1], values[2]);
    } else {
        // Clear all fields
        document.getElementById('add_constituency_id').value = '';
        document.getElementById('add_mandal_id').value = '';
        document.getElementById('add_tc_id').value = '';
        document.getElementById('add_batch_id').innerHTML = '<option value="">Select Batch</option>';
        document.getElementById('add_batch_start_date').value = '';
        document.getElementById('add_batch_end_date').value = '';
    }
}

function updateAddBatchDropdown(mandalId, tcId) {
    const batchSelect = document.getElementById('add_batch_id');
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

function updateAddBatchDates() {
    const batchSelect = document.getElementById('add_batch_id');
    const selectedOption = batchSelect.options[batchSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        document.getElementById('add_batch_start_date').value = selectedOption.dataset.startDate || '';
        document.getElementById('add_batch_end_date').value = selectedOption.dataset.endDate || '';
    } else {
        document.getElementById('add_batch_start_date').value = '';
        document.getElementById('add_batch_end_date').value = '';
    }
}

function editBeneficiary(beneficiary) {
    console.log('Edit beneficiary:', beneficiary); // Debug log
    
    document.getElementById('edit_id').value = beneficiary.id;
    document.getElementById('edit_full_name').value = beneficiary.full_name || '';
    document.getElementById('edit_aadhar_number').value = beneficiary.aadhar_number || '';
    document.getElementById('edit_phone_number').value = beneficiary.phone_number || beneficiary.mobile_number || '';
    document.getElementById('edit_status').value = beneficiary.status || 'active';
    
    $('#editBeneficiaryModal').modal('show');
}

function deleteBeneficiary(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    $('#deleteBeneficiaryModal').modal('show');
}

function debugFormSubmission(event) {
    console.log('=== Form Submission Debug ===');
    
    // Check all required fields
    const form = document.querySelector('#addBeneficiaryModal form');
    const formData = new FormData(form);
    
    console.log('Form data:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }
    
    // Check required fields specifically
    const fullName = document.getElementById('add_full_name').value;
    const aadharNumber = document.getElementById('add_aadhar_number').value;
    const constituencyId = document.getElementById('add_constituency_id').value;
    const mandalId = document.getElementById('add_mandal_id').value;
    const tcId = document.getElementById('add_tc_id').value;
    const batchId = document.getElementById('add_batch_id').value;
    
    console.log('Required field values:');
    console.log('Full Name:', fullName);
    console.log('Aadhar Number:', aadharNumber);
    console.log('Constituency ID:', constituencyId);
    console.log('Mandal ID:', mandalId);
    console.log('TC ID:', tcId);
    console.log('Batch ID:', batchId);
    
    // Check if all required fields are filled
    if (!fullName || !aadharNumber || !constituencyId || !mandalId || !tcId || !batchId) {
        console.error('Missing required fields!');
        event.preventDefault();
        alert('Please fill in all required fields:\n- Full Name\n- Aadhar Number\n- Constituency & Mandal\n- Batch');
        return false;
    }
    
    console.log('All required fields are filled. Form submission should proceed...');
    return true;
}

// Auto-format inputs
document.addEventListener('DOMContentLoaded', function() {
    // Add modal - Aadhar number formatting
    const addAadharInput = document.getElementById('add_aadhar_number');
    if (addAadharInput) {
        addAadharInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 12) {
                this.value = this.value.slice(0, 12);
            }
        });
    }
    
    // Add modal - Phone number formatting (10 digits only)
    const addPhoneInput = document.getElementById('add_phone_number');
    if (addPhoneInput) {
        addPhoneInput.addEventListener('input', function() {
            // Remove non-digit characters
            let value = this.value.replace(/\D/g, '');
            
            // Limit to exactly 10 digits for mobile numbers
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            this.value = value;
            
            // Add visual feedback
            if (value.length === 10) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (value.length > 0) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        addPhoneInput.addEventListener('blur', function() {
            if (this.value.length > 0 && this.value.length !== 10) {
                this.setCustomValidity('Phone number must be exactly 10 digits');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Edit modal - Aadhar number formatting
    const editAadharInput = document.getElementById('edit_aadhar_number');
    if (editAadharInput) {
        editAadharInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 12) {
                this.value = this.value.slice(0, 12);
            }
        });
    }
    
    // Edit modal - Phone number formatting (10 digits only)
    const editPhoneInput = document.getElementById('edit_phone_number');
    if (editPhoneInput) {
        editPhoneInput.addEventListener('input', function() {
            // Remove non-digit characters
            let value = this.value.replace(/\D/g, '');
            
            // Limit to exactly 10 digits for mobile numbers
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            this.value = value;
            
            // Add visual feedback
            if (value.length === 10) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else if (value.length > 0) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-valid', 'is-invalid');
            }
        });
        
        editPhoneInput.addEventListener('blur', function() {
            if (this.value.length > 0 && this.value.length !== 10) {
                this.setCustomValidity('Phone number must be exactly 10 digits');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Reset add form when modal is closed
    $('#addBeneficiaryModal').on('hidden.bs.modal', function () {
        $(this).find('form')[0].reset();
        document.getElementById('add_constituency_id').value = '';
        document.getElementById('add_mandal_id').value = '';
        document.getElementById('add_tc_id').value = '';
        document.getElementById('add_batch_id').innerHTML = '<option value="">Select Batch</option>';
        document.getElementById('add_batch_start_date').value = '';
        document.getElementById('add_batch_end_date').value = '';
    });
    
    // Live Search Implementation with Pagination
    const liveSearchInput = document.getElementById('liveSearch');
    const searchStatus = document.getElementById('searchStatus');
    const clearSearchBtn = document.getElementById('clearSearch');
    const tableBody = document.getElementById('tableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const recordCountElement = document.getElementById('recordCount');
    let searchTimeout;
    let currentPage = 1;
    
    if (liveSearchInput) {
        liveSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            // Show/hide clear button
            if (searchTerm.length > 0) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            // Reset to first page when searching
            currentPage = 1;
            
            // Debounce search
            searchTimeout = setTimeout(() => {
                performSearch(searchTerm, 1);
            }, 300);
        });
        
        clearSearchBtn.addEventListener('click', function() {
            liveSearchInput.value = '';
            clearSearchBtn.style.display = 'none';
            currentPage = 1;
            performSearch('', 1);
        });
    }

    // Filter functionality
    const filterConstituency = document.getElementById('filterConstituency');
    const filterMandal = document.getElementById('filterMandal');
    const filterTC = document.getElementById('filterTC');
    const filterBatch = document.getElementById('filterBatch');
    const clearFiltersBtn = document.getElementById('clearFilters');

    // Add event listeners for filters
    if (filterConstituency) {
        filterConstituency.addEventListener('change', function() {
            updateCascadingFilters();
            currentPage = 1;
            const searchTerm = liveSearchInput ? liveSearchInput.value.trim() : '';
            performSearch(searchTerm, 1);
        });
    }

    if (filterMandal) {
        filterMandal.addEventListener('change', function() {
            updateCascadingFilters();
            currentPage = 1;
            const searchTerm = liveSearchInput ? liveSearchInput.value.trim() : '';
            performSearch(searchTerm, 1);
        });
    }

    if (filterTC) {
        filterTC.addEventListener('change', function() {
            updateCascadingFilters();
            currentPage = 1;
            const searchTerm = liveSearchInput ? liveSearchInput.value.trim() : '';
            performSearch(searchTerm, 1);
        });
    }

    if (filterBatch) {
        filterBatch.addEventListener('change', function() {
            currentPage = 1;
            const searchTerm = liveSearchInput ? liveSearchInput.value.trim() : '';
            performSearch(searchTerm, 1);
        });
    }

    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            // Reset all filters
            filterConstituency.value = '';
            filterMandal.value = '';
            filterTC.value = '';
            filterBatch.value = '';
            
            // Show all options
            showAllFilterOptions();
            
            currentPage = 1;
            const searchTerm = liveSearchInput ? liveSearchInput.value.trim() : '';
            performSearch(searchTerm, 1);
        });
    }
    
    function performSearch(searchTerm, page = 1) {
        // Update search status
        searchStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Get filter values
        const filterConstituency = document.getElementById('filterConstituency').value;
        const filterMandal = document.getElementById('filterMandal').value;
        const filterTC = document.getElementById('filterTC').value;
        const filterBatch = document.getElementById('filterBatch').value;
        
        // Create FormData for AJAX request
        const formData = new FormData();
        formData.append('action', 'live_search');
        formData.append('search', searchTerm);
        formData.append('page', page);
        formData.append('filter_constituency', filterConstituency);
        formData.append('filter_mandal', filterMandal);
        formData.append('filter_tc', filterTC);
        formData.append('filter_batch', filterBatch);
        
        fetch('beneficiaries.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTable(data.html);
                updatePagination(data.pagination);
                updateRecordCount(data.showing);
                currentPage = data.page;
            } else {
                console.error('Search failed:', data.error);
            }
            searchStatus.innerHTML = '<i class="fas fa-search"></i>';
        })
        .catch(error => {
            console.error('Search error:', error);
            searchStatus.innerHTML = '<i class="fas fa-search"></i>';
        });
    }
    
    function updateTable(html) {
        if (tableBody) {
            if (html.trim() === '') {
                tableBody.innerHTML = '<tr><td colspan="11" class="text-center">No beneficiaries found.</td></tr>';
            } else {
            tableBody.innerHTML = html;
            }
        }
    }
    
    function updatePagination(paginationHtml) {
        if (paginationContainer) {
            if (paginationHtml.trim() === '') {
                paginationContainer.innerHTML = '';
            } else {
                paginationContainer.innerHTML = '<div class="d-flex justify-content-center mt-3">' + paginationHtml + '</div>';
            }
        }
    }
    
    function updateRecordCount(showing) {
        if (recordCountElement && showing) {
            recordCountElement.textContent = `Showing ${showing.from} to ${showing.to} of ${showing.total.toLocaleString()} beneficiaries`;
        }
    }
    
    // Global function for pagination buttons
    window.searchWithPagination = function(page) {
        const searchTerm = liveSearchInput ? liveSearchInput.value.trim() : '';
        performSearch(searchTerm, page);
    };
    
    // Cascading filter functions
    function updateCascadingFilters() {
        const selectedConstituency = filterConstituency.value;
        const selectedMandal = filterMandal.value;
        
        // Filter mandals based on constituency
        if (selectedConstituency) {
            Array.from(filterMandal.options).forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const optionConstituency = option.getAttribute('data-constituency');
                    option.style.display = optionConstituency === selectedConstituency ? 'block' : 'none';
                }
            });
            
            // Reset mandal if it doesn't belong to selected constituency
            if (filterMandal.value) {
                const currentMandalConstituency = filterMandal.selectedOptions[0]?.getAttribute('data-constituency');
                if (currentMandalConstituency !== selectedConstituency) {
                    filterMandal.value = '';
                }
            }
        } else {
            // Show all mandals when no constituency is selected
            Array.from(filterMandal.options).forEach(option => {
                option.style.display = 'block';
            });
        }
        
        // Filter TC IDs based on constituency and mandal
        Array.from(filterTC.options).forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
            } else {
                const optionConstituency = option.getAttribute('data-constituency');
                const optionMandal = option.getAttribute('data-mandal');
                
                let show = true;
                
                if (selectedConstituency && optionConstituency !== selectedConstituency) {
                    show = false;
                }
                
                if (selectedMandal && optionMandal !== selectedMandal) {
                    show = false;
                }
                
                option.style.display = show ? 'block' : 'none';
            }
        });
        
        // Reset TC if it doesn't match current filters
        if (filterTC.value) {
            const currentTCOption = filterTC.selectedOptions[0];
            const tcConstituency = currentTCOption?.getAttribute('data-constituency');
            const tcMandal = currentTCOption?.getAttribute('data-mandal');
            
            if ((selectedConstituency && tcConstituency !== selectedConstituency) ||
                (selectedMandal && tcMandal !== selectedMandal)) {
                filterTC.value = '';
            }
        }
        
        // Filter batches based on constituency, mandal, and TC
        const selectedTC = filterTC.value;
        Array.from(filterBatch.options).forEach(option => {
            if (option.value === '') {
                option.style.display = 'block';
            } else {
                const optionConstituency = option.getAttribute('data-constituency');
                const optionMandal = option.getAttribute('data-mandal');
                const optionTC = option.getAttribute('data-tc');
                
                let show = true;
                
                if (selectedConstituency && optionConstituency !== selectedConstituency) {
                    show = false;
                }
                
                if (selectedMandal && optionMandal !== selectedMandal) {
                    show = false;
                }
                
                if (selectedTC && optionTC !== selectedTC) {
                    show = false;
                }
                
                option.style.display = show ? 'block' : 'none';
            }
        });
        
        // Reset batch if it doesn't match current filters
        if (filterBatch.value) {
            const currentBatchOption = filterBatch.selectedOptions[0];
            const batchConstituency = currentBatchOption?.getAttribute('data-constituency');
            const batchMandal = currentBatchOption?.getAttribute('data-mandal');
            const batchTC = currentBatchOption?.getAttribute('data-tc');
            
            if ((selectedConstituency && batchConstituency !== selectedConstituency) ||
                (selectedMandal && batchMandal !== selectedMandal) ||
                (selectedTC && batchTC !== selectedTC)) {
                filterBatch.value = '';
            }
        }
    }
    
    function showAllFilterOptions() {
        // Show all options in all filters
        [filterMandal, filterTC, filterBatch].forEach(select => {
            Array.from(select.options).forEach(option => {
                option.style.display = 'block';
            });
        });
    }

    // Initial search to show first page
    if (liveSearchInput) {
        performSearch('', 1);
    }
});
</script>