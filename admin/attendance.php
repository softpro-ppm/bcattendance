<?php
$pageTitle = 'Daily Attendance';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Daily Attendance']
];

require_once '../includes/header.php';

// Add mobile-responsive CSS for attendance table
?>
<style>
/* Mobile-first responsive design for admin attendance table */
#attendanceTable {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    font-size: 14px;
}

#attendanceTable th {
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 12px 8px;
    text-align: center;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}

#attendanceTable td {
    padding: 10px 8px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

#attendanceTable tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}

#attendanceTable tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}

/* Status buttons styling */
.status-buttons .btn {
    padding: 8px 16px;
    font-size: 14px;
    min-width: 80px;
    margin: 2px;
}

/* Mobile-first responsive styles */
@media (max-width: 768px) {
    /* Table container - allow horizontal scroll when needed */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border: none;
        border-radius: 0;
    }
    
    /* Table styling for mobile */
    #attendanceTable {
        min-width: 800px; /* Minimum width to prevent extreme squashing */
        font-size: 13px;
    }
    
    #attendanceTable th,
    #attendanceTable td {
        padding: 8px 6px;
        white-space: nowrap; /* Prevent text wrapping on mobile */
    }
    
    /* Column widths for mobile */
    #attendanceTable th:nth-child(1),
    #attendanceTable td:nth-child(1) {
        width: 40px; /* Checkbox column */
        min-width: 40px;
    }
    
    #attendanceTable th:nth-child(2),
    #attendanceTable td:nth-child(2) {
        width: 50px; /* S.No column */
        min-width: 50px;
    }
    
    #attendanceTable th:nth-child(3),
    #attendanceTable td:nth-child(3) {
        width: 140px; /* Status column */
        min-width: 140px;
    }
    
    #attendanceTable th:nth-child(4),
    #attendanceTable td:nth-child(4) {
        width: 200px; /* Name column */
        min-width: 200px;
        max-width: 200px;
    }
    
    #attendanceTable th:nth-child(5),
    #attendanceTable td:nth-child(5) {
        width: 100px; /* Mobile column */
        min-width: 100px;
    }
    
    #attendanceTable th:nth-child(6),
    #attendanceTable td:nth-child(6) {
        width: 100px; /* Constituency column */
        min-width: 100px;
    }
    
    #attendanceTable th:nth-child(7),
    #attendanceTable td:nth-child(7) {
        width: 100px; /* Mandal column */
        min-width: 100px;
    }
    
    #attendanceTable th:nth-child(8),
    #attendanceTable td:nth-child(8) {
        width: 80px; /* Batch column */
        min-width: 80px;
    }
    
    /* Optimize status buttons for mobile */
    .status-buttons .btn {
        padding: 6px 12px;
        font-size: 12px;
        min-width: 70px;
    }
    
    /* Student details optimization */
    #attendanceTable td:nth-child(4) strong {
        font-size: 13px;
        line-height: 1.3;
        display: block;
        margin-bottom: 4px;
    }
    
    #attendanceTable td:nth-child(4) small {
        font-size: 11px;
        display: block;
        margin-bottom: 2px;
    }
}

/* Extra small devices */
@media (max-width: 576px) {
    #attendanceTable {
        min-width: 750px;
        font-size: 12px;
    }
    
    #attendanceTable th,
    #attendanceTable td {
        padding: 6px 4px;
    }
    
    .status-buttons .btn {
        padding: 5px 10px;
        font-size: 11px;
        min-width: 65px;
    }
    
    /* Adjust column widths for very small screens */
    #attendanceTable th:nth-child(4),
    #attendanceTable td:nth-child(4) {
        width: 180px;
        min-width: 180px;
        max-width: 180px;
    }
}

/* Extra extra small devices */
@media (max-width: 480px) {
    #attendanceTable {
        min-width: 700px;
        font-size: 11px;
    }
    
    #attendanceTable th,
    #attendanceTable td {
        padding: 4px 2px;
    }
    
    .status-buttons .btn {
        padding: 4px 8px;
        font-size: 10px;
        min-width: 60px;
    }
    
    /* Further reduce column widths */
    #attendanceTable th:nth-child(4),
    #attendanceTable td:nth-child(4) {
        width: 160px;
        min-width: 160px;
        max-width: 160px;
    }
    
    #attendanceTable th:nth-child(5),
    #attendanceTable td:nth-child(5) {
        width: 80px;
        min-width: 80px;
    }
    
    #attendanceTable th:nth-child(6),
    #attendanceTable td:nth-child(6) {
        width: 80px;
        min-width: 80px;
    }
    
    #attendanceTable th:nth-child(7),
    #attendanceTable td:nth-child(7) {
        width: 80px;
        min-width: 80px;
    }
    
    #attendanceTable th:nth-child(8),
    #attendanceTable td:nth-child(8) {
        width: 70px;
        min-width: 70px;
    }
}

/* Desktop styles */
@media (min-width: 769px) {
    #attendanceTable {
        font-size: 14px;
    }
    
    #attendanceTable th,
    #attendanceTable td {
        padding: 12px 8px;
    }
    
    .status-buttons .btn {
        padding: 8px 16px;
        font-size: 14px;
        min-width: 80px;
    }
}

/* Ensure proper table display */
#attendanceTable th,
#attendanceTable td {
    display: table-cell !important;
}

/* Optimize badges */
.badge {
    font-size: 11px;
    padding: 4px 8px;
    font-weight: 500;
}

/* Table header sticky on mobile */
@media (max-width: 768px) {
    #attendanceTable thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 2;
    }
}
</style>

<?php
// Check and mark completed batches automatically
$batchCompletionResult = checkAndMarkCompletedBatches();

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $attendanceDate = $_POST['attendance_date'];
    $attendanceData = $_POST['attendance'] ?? [];
    $currentUserId = $_SESSION['admin_user_id'];
    $selectedBatch = $_POST['selected_batch'] ?? '';
    
    // Validate attendance date
    $dateValidation = isValidAttendanceDate($attendanceDate, $selectedBatch);
    if (!$dateValidation['valid']) {
        setErrorMessage($dateValidation['reason']);
    } else {
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($attendanceData as $beneficiaryId => $data) {
        $status = $data['status'] ?? 'absent';
        $checkInTime = !empty($data['check_in_time']) ? $data['check_in_time'] : null;
        $checkOutTime = !empty($data['check_out_time']) ? $data['check_out_time'] : null;
        $remarks = !empty($data['remarks']) ? sanitizeInput($data['remarks']) : null;
        
        // Check if attendance already exists for this date
        $existingAttendance = fetchRow(
            "SELECT id FROM attendance WHERE beneficiary_id = ? AND attendance_date = ?",
            [$beneficiaryId, $attendanceDate],
            'is'
        );
        
        if ($existingAttendance) {
            // Update existing attendance
            $query = "UPDATE attendance SET status = ?, check_in_time = ?, check_out_time = ?, remarks = ?, marked_by = ?, updated_at = NOW() WHERE beneficiary_id = ? AND attendance_date = ?";
            $result = executeQuery($query, [$status, $checkInTime, $checkOutTime, $remarks, $currentUserId, $beneficiaryId, $attendanceDate], 'ssssiis');
        } else {
            // Insert new attendance
            $query = "INSERT INTO attendance (beneficiary_id, attendance_date, status, check_in_time, check_out_time, remarks, marked_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $result = executeQuery($query, [$beneficiaryId, $attendanceDate, $status, $checkInTime, $checkOutTime, $remarks, $currentUserId], 'isssssi');
        }
        
        if ($result) {
            $successCount++;
        } else {
            $errorCount++;
        }
    }
    
    if ($successCount > 0) {
        setSuccessMessage("Successfully marked attendance for $successCount beneficiaries.");
    }
    if ($errorCount > 0) {
        setErrorMessage("Failed to mark attendance for $errorCount beneficiaries.");
        }
    }
}

// Get filters
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedConstituency = $_GET['constituency'] ?? '';
$selectedMandal = $_GET['mandal'] ?? '';
$selectedBatch = $_GET['batch'] ?? '';

// Pagination settings
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Records per page - default 100, with options for 500, 1000, and 'all'
$recordsPerPageParam = $_GET['records_per_page'] ?? '100';
if ($recordsPerPageParam === 'all') {
    $recordsPerPage = PHP_INT_MAX; // Show all records
    $showAll = true;
} else {
    $recordsPerPage = max(10, min(1000, intval($recordsPerPageParam))); // Min 10, Max 1000
    $showAll = false;
}

$offset = ($page - 1) * $recordsPerPage;

// Get filter options - Only load constituencies initially, mandals and batches will be loaded via AJAX
$constituencies = fetchAll("SELECT id, name FROM constituencies WHERE status = 'active' ORDER BY name");

// Load mandals and batches only if they are pre-selected (for maintaining state)
$mandals = [];
$batches = [];

if (!empty($selectedConstituency)) {
    $mandals = fetchAll("SELECT id, name FROM mandals WHERE constituency_id = ? AND status = 'active' ORDER BY name", [$selectedConstituency], 'i');
}

if (!empty($selectedMandal)) {
    $batches = fetchAll("SELECT id, name FROM batches WHERE mandal_id = ? AND status IN ('active', 'completed') ORDER BY status DESC, name", [$selectedMandal], 'i');
}

// Build query for beneficiaries
$whereConditions = [];
$params = [];
$types = '';

// Check if selected batch is completed
$batchStatus = '';
if (!empty($selectedBatch)) {
    $batchQuery = "SELECT status FROM batches WHERE id = ?";
    $batchResult = fetchRow($batchQuery, [$selectedBatch], 'i');
    $batchStatus = $batchResult ? $batchResult['status'] : 'active';
}

// If batch is completed, include both active and completed beneficiaries
// If batch is active, only include active beneficiaries
if ($batchStatus === 'completed') {
    $whereConditions[] = "(b.status = 'active' OR b.status = 'completed')";
} else {
    $whereConditions[] = "b.status = 'active'";
}

if (!empty($selectedConstituency)) {
    $whereConditions[] = "b.constituency_id = ?";
    $params[] = $selectedConstituency;
    $types .= 'i';
}

if (!empty($selectedMandal)) {
    $whereConditions[] = "b.mandal_id = ?";
    $params[] = $selectedMandal;
    $types .= 'i';
}

if (!empty($selectedBatch)) {
    $whereConditions[] = "b.batch_id = ?";
    $params[] = $selectedBatch;
    $types .= 'i';
}

$whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

// First get total count for pagination
$countQuery = "SELECT COUNT(DISTINCT b.id) as total
FROM beneficiaries b
LEFT JOIN constituencies c ON b.constituency_id = c.id
LEFT JOIN mandals m ON b.mandal_id = m.id
LEFT JOIN batches bt ON b.batch_id = bt.id
$whereClause";

$countParams = $params;
$countTypes = $types;
$totalRecords = fetchRow($countQuery, $countParams, $countTypes)['total'] ?? 0;

// Calculate total pages (handle "All" option)
if ($showAll) {
    $totalPages = 1;
    $recordsPerPage = $totalRecords; // Set to actual record count for proper display
} else {
    $totalPages = ceil($totalRecords / $recordsPerPage);
}

// Get beneficiaries with existing attendance for the selected date (with pagination)
$query = "SELECT 
    b.id,
    b.beneficiary_id,
    b.full_name,
    b.mobile_number,
    c.name as constituency_name,
    m.name as mandal_name,
    bt.name as batch_name,
    a.status as attendance_status,
    a.check_in_time,
    a.check_out_time,
    a.remarks
FROM beneficiaries b
LEFT JOIN constituencies c ON b.constituency_id = c.id
LEFT JOIN mandals m ON b.mandal_id = m.id
LEFT JOIN batches bt ON b.batch_id = bt.id
LEFT JOIN attendance a ON b.id = a.beneficiary_id AND a.attendance_date = ?
$whereClause
ORDER BY b.beneficiary_id" . ($showAll ? "" : " LIMIT $recordsPerPage OFFSET $offset") . ";";

$allParams = [$selectedDate];
$allParams = array_merge($allParams, $params);
$allTypes = 's' . $types;

$beneficiaries = fetchAll($query, $allParams, $allTypes);


?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-calendar-check"></i>
            Daily Attendance - <?php echo formatDate($selectedDate, 'l, F j, Y'); ?>
        </h3>
        <div class="card-tools">
        </div>
    </div>
    <div class="card-body">
        <!-- Batch Completion Notifications -->
        <?php if (isset($batchCompletionResult) && $batchCompletionResult['success'] && $batchCompletionResult['count'] > 0): ?>
        <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Batch Update:</strong> <?php echo htmlspecialchars($batchCompletionResult['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Completed Batch Notification -->
        <?php if (!empty($selectedBatch) && isset($batchStatus) && $batchStatus === 'completed'): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Completed Batch:</strong> This batch has ended on <?php 
                $batchEndDate = fetchRow("SELECT end_date FROM batches WHERE id = ?", [$selectedBatch], 'i');
                echo $batchEndDate ? formatDate($batchEndDate['end_date'], 'd M Y') : 'unknown date';
            ?>. You can still mark attendance for historical records or make-up sessions.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Date Validation Alert -->
        <?php 
        $dateValidation = isValidAttendanceDate($selectedDate, $selectedBatch);
        if (!$dateValidation['valid']): 
        ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Attendance Restriction:</strong> <?php echo $dateValidation['reason']; ?>
            <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="openRestrictionsModal()">
                <i class="fas fa-cog"></i> Manage Restrictions
            </button>
        </div>
        <?php endif; ?>

        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date" class="form-label">Date</label>
                        <input type="date" id="date" name="date" class="form-control" 
                               value="<?php echo $selectedDate; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="constituency" class="form-label">Constituency</label>
                        <select id="constituency" name="constituency" class="form-control">
                            <option value="">All Constituencies</option>
                            <?php foreach ($constituencies as $constituency): ?>
                                <option value="<?php echo $constituency['id']; ?>" 
                                        <?php echo ($selectedConstituency == $constituency['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($constituency['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="mandal" class="form-label">Mandal</label>
                        <select id="mandal" name="mandal" class="form-control">
                            <?php if (empty($selectedConstituency)): ?>
                                <option value="">Select constituency first</option>
                            <?php else: ?>
                            <option value="">All Mandals</option>
                            <?php foreach ($mandals as $mandal): ?>
                                <option value="<?php echo $mandal['id']; ?>" 
                                        <?php echo ($selectedMandal == $mandal['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($mandal['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="batch" class="form-label">Batch</label>
                        <select id="batch" name="batch" class="form-control">
                            <?php if (empty($selectedMandal)): ?>
                                <option value="">Select mandal first</option>
                            <?php else: ?>
                            <option value="">All Batches</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?php echo $batch['id']; ?>" 
                                        <?php echo ($selectedBatch == $batch['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
            <div class="form-group">

                <a href="attendance.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear All
                        </a>

                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="search" class="form-label">Search Beneficiaries</label>
                        <div class="input-group">
                            <input type="text" id="search" class="form-control" placeholder="Search by name, ID, or mobile...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="records_per_page" class="form-label">Rows Per Page</label>
                        <select id="records_per_page" name="records_per_page" class="form-control">
                            <option value="100" <?php echo ($recordsPerPageParam === '100') ? 'selected' : ''; ?>>100</option>
                            <option value="500" <?php echo ($recordsPerPageParam === '500') ? 'selected' : ''; ?>>500</option>
                            <option value="1000" <?php echo ($recordsPerPageParam === '1000') ? 'selected' : ''; ?>>1000</option>
                            <option value="all" <?php echo ($recordsPerPageParam === 'all') ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>
                </div>
            </div>
        </form>

        <?php if (!empty($beneficiaries) && $dateValidation['valid']): ?>
        
        <!-- Attendance Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-2">
                <div class="card border-primary">
                    <div class="card-body text-center p-2">
                        <h4 class="card-title text-primary mb-1" id="totalCount">0</h4>
                        <p class="card-text small mb-0">Total (Active+Inactive)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-warning">
                    <div class="card-body text-center p-2">
                        <h4 class="card-title text-warning mb-1" id="activeCount">0</h4>
                        <p class="card-text small mb-0">Active</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-secondary">
                    <div class="card-body text-center p-2">
                        <h4 class="card-title text-secondary mb-1" id="inactiveCount">0</h4>
                        <p class="card-text small mb-0">Inactive</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-success">
                    <div class="card-body text-center p-2">
                        <h4 class="card-title text-success mb-1" id="presentCount">0</h4>
                        <p class="card-text small mb-0">Present</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-danger">
                    <div class="card-body text-center p-2">
                        <h4 class="card-title text-danger mb-1" id="absentCount">0</h4>
                        <p class="card-text small mb-0">Absent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-info">
                    <div class="card-body text-center p-2">
                        <h4 class="card-title text-info mb-1" id="attendanceRate">0%</h4>
                        <p class="card-text small mb-0">Rate</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Form -->
        <form method="POST" action="">
            <input type="hidden" name="attendance_date" value="<?php echo $selectedDate; ?>">
            <input type="hidden" name="selected_batch" value="<?php echo $selectedBatch; ?>">
            <input type="hidden" name="mark_attendance" value="1">

            <!-- Enhanced Quick Actions -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <div class="btn-toolbar" role="toolbar">
                        <div class="btn-group mr-2" role="group">
                        <button type="button" class="btn btn-success btn-sm" onclick="markAllAs('present')">
                                <i class="fas fa-check"></i> All Present
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="markAllAs('absent')">
                                <i class="fas fa-times"></i> All Absent
                        </button>
                        </div>
                        <div class="btn-group mr-2" role="group">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="markSelectedAs('present')">
                                <i class="fas fa-check"></i> Selected Present
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="markSelectedAs('absent')">
                                <i class="fas fa-times"></i> Selected Absent
                            </button>
                        </div>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleSelectAll()">
                                <i class="fas fa-check-square"></i> Select All
                        </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="showAttendanceStats()">
                            <i class="fas fa-chart-bar"></i> Quick Stats
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <!-- Mobile-responsive alert -->
                <div class="d-block d-md-none alert alert-info alert-sm mb-2">
                    <i class="fas fa-mobile-alt"></i> 
                    <strong>Mobile View:</strong> Swipe left/right to see all columns. All student information is now visible with proper horizontal scrolling!
                </div>
                <table class="table table-striped table-hover" id="attendanceTable">
                    <thead class="thead-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" onchange="selectAllRows()">
                            </th>
                            <th width="60">S.No</th>
                            <th width="150" style="background-color: #e3f2fd; color: #1976d2;">📝 Status</th>
                            <th width="250">Name</th>
                            <th width="120">Mobile</th>
                            <th width="120">Constituency</th>
                            <th width="120">Mandal</th>
                            <th width="100">Batch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($beneficiaries as $index => $beneficiary): ?>
                        <tr class="beneficiary-row" data-beneficiary-id="<?php echo $beneficiary['id']; ?>">
                            <td>
                                <input type="checkbox" class="row-checkbox" value="<?php echo $beneficiary['id']; ?>">
                            </td>
                            <td>
                                <span class="serial-number"><?php echo $offset + $index + 1; ?></span>
                            </td>
                            <td>
                                <input type="hidden" name="attendance[<?php echo $beneficiary['id']; ?>][status]" 
                                       value="<?php 
                                           $status = $beneficiary['attendance_status'];
                                           if ($status == 'present' || $status == 'P') {
                                               echo 'present';
                                           } else if ($status == 'absent' || $status == 'A') {
                                               echo 'absent';
                                           } else if (empty($status)) {
                                               echo 'absent'; // Default for no attendance record
                                           } else {
                                               echo 'absent'; // Default for any other status
                                           }
                                       ?>" 
                                       class="attendance-status-input">
                                <div class="btn-group btn-group-sm status-buttons" role="group">
                                    <button type="button" 
                                            class="btn btn-present <?php 
                                                $status = $beneficiary['attendance_status'];
                                                echo (($status == 'present' || $status == 'P') ? 'btn-success' : 'btn-outline-success'); 
                                            ?>" 
                                            onclick="setAttendanceStatus(this, 'present')">
                                        Present
                                    </button>
                                    <button type="button" 
                                            class="btn btn-absent <?php 
                                                $status = $beneficiary['attendance_status'];
                                                echo (($status == 'absent' || $status == 'A' || empty($status)) ? 'btn-danger' : 'btn-outline-danger'); 
                                            ?>" 
                                            onclick="setAttendanceStatus(this, 'absent')">
                                        Absent
                                    </button>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($beneficiary['full_name']); ?></strong>
                                <?php if (isset($batchStatus) && $batchStatus === 'completed'): ?>
                                    <br><small><span class="badge badge-warning">Completed Batch</span></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($beneficiary['mobile_number']); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($beneficiary['constituency_name'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($beneficiary['mandal_name'] ?? 'N/A'); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars($beneficiary['batch_name'] ?? 'N/A'); ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls (Hidden when showing all records) -->
            <?php if (!$showAll && $totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                <div>
                    <small class="text-muted">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $recordsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> beneficiaries
                    </small>
                </div>
                <nav aria-label="Beneficiaries pagination">
                    <ul class="pagination pagination-sm mb-0">
                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php 
                                if ($page > 1) {
                                    $params = $_GET;
                                    $params['page'] = $page - 1;
                                    echo '?' . http_build_query($params);
                                } else {
                                    echo '#';
                                }
                            ?>">Previous</a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php 
                                    $params = $_GET;
                                    $params['page'] = 1;
                                    echo '?' . http_build_query($params);
                                ?>">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif;
                        endif;
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php 
                                    $params = $_GET;
                                    $params['page'] = $i;
                                    echo '?' . http_build_query($params);
                                ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor;
                        
                        if ($endPage < $totalPages): 
                            if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php 
                                    $params = $_GET;
                                    $params['page'] = $totalPages;
                                    echo '?' . http_build_query($params);
                                ?>"><?php echo $totalPages; ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next Page -->
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php 
                                if ($page < $totalPages) {
                                    $params = $_GET;
                                    $params['page'] = $page + 1;
                                    echo '?' . http_build_query($params);
                                } else {
                                    echo '#';
                                }
                            ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

            <div class="form-group mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <button type="submit" class="btn btn-primary btn-lg" 
                                title="Save all attendance markings to database">
                    <i class="fas fa-save"></i> Save Attendance
                </button>
                        <button type="button" class="btn btn-info" onclick="exportAttendance()"
                                title="Export current attendance data as CSV file">
                            <i class="fas fa-download"></i> Export
                </button>
                    </div>
                    <div class="col-md-6 text-right">
                        <button type="button" class="btn btn-outline-success" onclick="previewChanges()"
                                title="Preview what changes will be saved">
                            <i class="fas fa-eye"></i> Preview Changes
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="autoSave()"
                                title="Auto-save feature (coming soon)">
                            <i class="fas fa-cloud"></i> Auto Save
                </button>
                    </div>
                </div>
                
                <!-- Button explanations -->
                <div class="row mt-2">
                    <div class="col-12">
                        <small class="text-muted">
                            <strong>Save Attendance:</strong> Saves all marked attendance to database • 
                            <strong>Export:</strong> Downloads attendance as CSV • 
                            <strong>Preview:</strong> Shows changes before saving • 
                            <strong>Auto Save:</strong> Future feature for automatic saving
                        </small>
                    </div>
                </div>
            </div>
        </form>

        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
            <p class="text-muted">No beneficiaries found with the current filters.</p>
            <a href="beneficiaries.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Beneficiaries
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
console.log('🚀 Loading attendance functions...');

// Global variables
let selectedRows = new Set();

// Individual button functions
function setAttendanceStatus(button, status) {
    console.log('🔥 Individual button clicked:', status);
    
    const row = button.closest('tr');
    if (!row) {
        console.log('❌ Row not found');
        return;
    }
    
    // Find the hidden input and other buttons
    const hiddenInput = row.querySelector('input[type="hidden"]');
    const presentBtn = row.querySelector('.btn-present');
    const absentBtn = row.querySelector('.btn-absent');
    
    if (!hiddenInput || !presentBtn || !absentBtn) {
        console.log('❌ Required elements missing');
        return;
    }
    
    // Update the hidden input value
    hiddenInput.value = status;
    console.log('✅ Updated hidden input to:', status);
    
    // Update button appearances
    if (status === 'present') {
        presentBtn.className = 'btn btn-present btn-success';
        absentBtn.className = 'btn btn-absent btn-outline-danger';
        console.log('✅ Set to PRESENT (green)');
    } else {
        absentBtn.className = 'btn btn-absent btn-danger';
        presentBtn.className = 'btn btn-present btn-outline-success';
        console.log('✅ Set to ABSENT (red)');
    }
    
    // Update summary
    updateCounts();
}

// Bulk action functions
    function markAllAs(status) {
    console.log('🔥 Bulk action - Mark All as:', status);
    
    const visibleRows = document.querySelectorAll('.beneficiary-row:not([style*="display: none"])');
    console.log('Found visible rows:', visibleRows.length);
    
    let processed = 0;
    visibleRows.forEach(row => {
        const button = row.querySelector(status === 'present' ? '.btn-present' : '.btn-absent');
        if (button) {
            setAttendanceStatus(button, status);
            processed++;
        }
    });
    
    console.log('✅ Processed', processed, 'rows');
}

function markSelectedAs(status) {
    console.log('🔥 Bulk action - Mark Selected as:', status);
    console.log('Selected count:', selectedRows.size);
    
    if (selectedRows.size === 0) {
        alert('Please select some beneficiaries first using the checkboxes.');
        return;
    }
    
    let processed = 0;
    selectedRows.forEach(beneficiaryId => {
        const checkbox = document.querySelector('input[value="' + beneficiaryId + '"].row-checkbox');
        if (checkbox) {
            const row = checkbox.closest('tr');
            const button = row.querySelector(status === 'present' ? '.btn-present' : '.btn-absent');
            if (button) {
                setAttendanceStatus(button, status);
                processed++;
            }
        }
    });
    
    console.log('✅ Processed', processed, 'selected rows');
}

// Select all functions
function toggleSelectAll() {
    console.log('🔥 Toggle Select All clicked');
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = !selectAllCheckbox.checked;
        selectAllRows();
    }
}

function selectAllRows() {
    console.log('🔥 Select All Rows function');
    const selectAllCheckbox = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    
    console.log('Select All checked:', selectAllCheckbox ? selectAllCheckbox.checked : false);
    console.log('Found checkboxes:', checkboxes.length);
    
    selectedRows.clear();
    
    checkboxes.forEach(checkbox => {
        const row = checkbox.closest('tr');
        if (row && row.style.display !== 'none') {
            checkbox.checked = selectAllCheckbox.checked;
            
            if (selectAllCheckbox.checked) {
                selectedRows.add(checkbox.value);
            }
        }
    });
    
    console.log('Selected rows:', selectedRows.size);
}

// Summary update - Get counts from server (all pages) instead of just visible page
function updateCounts() {
    console.log('🔥 Fetching total attendance counts from server...');
    
    // Get current filter values
    const dateInput = document.querySelector('input[name="date"]');
    const constituencySelect = document.getElementById('constituency');
    const mandalSelect = document.getElementById('mandal');
    const batchSelect = document.getElementById('batch');
    
    const params = new URLSearchParams();
    if (dateInput && dateInput.value) {
        params.append('date', dateInput.value);
    }
    if (constituencySelect && constituencySelect.value) {
        params.append('constituency', constituencySelect.value);
    }
    if (mandalSelect && mandalSelect.value) {
        params.append('mandal', mandalSelect.value);
    }
    if (batchSelect && batchSelect.value) {
        params.append('batch', batchSelect.value);
    }
    
    // Fetch total counts from server
    fetch(`get_attendance_counts.php?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.counts) {
                const counts = data.counts;
                
                // Update cards with server data
                const totalEl = document.getElementById('totalCount');
                const activeEl = document.getElementById('activeCount');
                const inactiveEl = document.getElementById('inactiveCount');
                const presentEl = document.getElementById('presentCount');
                const absentEl = document.getElementById('absentCount');
                const rateEl = document.getElementById('attendanceRate');
                
                if (totalEl) totalEl.textContent = counts.total;
                if (activeEl) activeEl.textContent = counts.active;
                if (inactiveEl) inactiveEl.textContent = counts.inactive;
                if (presentEl) presentEl.textContent = counts.present;
                if (absentEl) absentEl.textContent = counts.absent;
                if (rateEl) rateEl.textContent = counts.rate + '%';
                
                console.log('📊 Server counts updated - Total:', counts.total, 'Active:', counts.active, 'Inactive:', counts.inactive, 'Present:', counts.present, 'Absent:', counts.absent, 'Rate:', counts.rate + '%');
            } else {
                console.error('Failed to get counts from server:', data);
                // Fallback to local counting if server fails
                updateCountsLocal();
            }
        })
        .catch(error => {
            console.error('Error fetching counts:', error);
            // Fallback to local counting if server fails
            updateCountsLocal();
        });
}

// Fallback function for local counting (visible page only)
function updateCountsLocal() {
    console.log('🔥 Using local counting (fallback)...');
    
    const rows = document.querySelectorAll('.beneficiary-row:not([style*="display: none"])');
    let total = 0, active = 0, inactive = 0, present = 0, absent = 0;
    
    rows.forEach(row => {
        total++;
        
        // Check button states for current UI state
        const presentBtn = row.querySelector('.btn-present');
        const absentBtn = row.querySelector('.btn-absent');
        
        if (presentBtn && presentBtn.classList.contains('btn-success')) {
            present++;
        } else if (absentBtn && absentBtn.classList.contains('btn-danger')) {
            absent++;
        } else {
            // If no button state is set, check hidden input for database value
            const hiddenInput = row.querySelector('input[type="hidden"]');
            if (hiddenInput && hiddenInput.value === 'present') {
                present++;
                // Sync button state with hidden input
                if (presentBtn) presentBtn.className = 'btn btn-present btn-success';
                if (absentBtn) absentBtn.className = 'btn btn-absent btn-outline-danger';
            } else {
                absent++;
                // Sync button state with hidden input (default to absent)
                if (absentBtn) absentBtn.className = 'btn btn-absent btn-danger';
                if (presentBtn) presentBtn.className = 'btn btn-present btn-outline-success';
            }
        }
    });
    
    // Update cards
    const totalEl = document.getElementById('totalCount');
    const activeEl = document.getElementById('activeCount');
    const inactiveEl = document.getElementById('inactiveCount');
    const presentEl = document.getElementById('presentCount');
    const absentEl = document.getElementById('absentCount');
    const rateEl = document.getElementById('attendanceRate');
    
    if (totalEl) totalEl.textContent = total + ' (page only)';
    if (activeEl) activeEl.textContent = 'N/A (server)';
    if (inactiveEl) inactiveEl.textContent = 'N/A (server)';
    if (presentEl) presentEl.textContent = present;
    if (absentEl) absentEl.textContent = absent;
    if (rateEl) {
        const rate = total > 0 ? Math.round((present / total) * 100) : 0;
        rateEl.textContent = rate + '%';
    }
    
    console.log('📊 Local counts - Total:', total, 'Active:', active, 'Inactive:', inactive, 'Present:', present, 'Absent:', absent);
}

// Other functions
function exportAttendance() {
    console.log('🔥 Export button clicked');
    
    const dateInput = document.querySelector('input[name="date"]');
    const constituencySelect = document.getElementById('constituency');
        const mandalSelect = document.getElementById('mandal');
        const batchSelect = document.getElementById('batch');
        
    if (!dateInput || !dateInput.value) {
        alert('Please select a date first!');
        return;
    }
    
    let exportUrl = 'export_attendance.php?date=' + encodeURIComponent(dateInput.value);
    
    if (constituencySelect && constituencySelect.value) {
        exportUrl += '&constituency=' + encodeURIComponent(constituencySelect.value);
    }
    if (mandalSelect && mandalSelect.value) {
        exportUrl += '&mandal=' + encodeURIComponent(mandalSelect.value);
    }
    if (batchSelect && batchSelect.value) {
        exportUrl += '&batch=' + encodeURIComponent(batchSelect.value);
    }
    
    console.log('Opening export URL:', exportUrl);
    window.open(exportUrl, '_blank');
}

function previewChanges() {
    console.log('🔥 Preview button clicked');
    
    const rows = document.querySelectorAll('.beneficiary-row:not([style*="display: none"])');
    let presentList = [];
    let absentList = [];
    let total = 0;
    
    rows.forEach(row => {
        const nameCell = row.querySelector('td:nth-child(3)');
        const hiddenInput = row.querySelector('input[type="hidden"]');
        
        if (nameCell && hiddenInput) {
            const name = nameCell.querySelector('strong').textContent.trim();
            const beneficiaryId = nameCell.querySelector('small').textContent.trim();
            const status = hiddenInput.value;
            
            total++;
            
            if (status === 'present') {
                presentList.push(`${name} (${beneficiaryId})`);
            } else {
                absentList.push(`${name} (${beneficiaryId})`);
            }
        }
    });
    
    if (total === 0) {
        alert('No beneficiaries found to preview.');
        return;
    }
    
    let previewText = `📋 ATTENDANCE PREVIEW\n`;
    previewText += `Date: ${document.querySelector('input[name="date"]')?.value || 'Not selected'}\n`;
    previewText += `Total Beneficiaries: ${total}\n\n`;
    
    if (presentList.length > 0) {
        previewText += `✅ PRESENT (${presentList.length}):\n`;
        previewText += presentList.slice(0, 8).join('\n');
        if (presentList.length > 8) {
            previewText += `\n...and ${presentList.length - 8} more`;
        }
        previewText += '\n\n';
    }
    
    if (absentList.length > 0) {
        previewText += `❌ ABSENT (${absentList.length}):\n`;
        previewText += absentList.slice(0, 8).join('\n');
        if (absentList.length > 8) {
            previewText += `\n...and ${absentList.length - 8} more`;
        }
        previewText += '\n\n';
    }
    
    const attendance_rate = total > 0 ? Math.round((presentList.length / total) * 100) : 0;
    previewText += `📊 Attendance Rate: ${attendance_rate}%`;
    
    alert(previewText);
}

function autoSave() {
    console.log('🔥 Auto Save button clicked');
    
    // Get current attendance data
    const rows = document.querySelectorAll('.beneficiary-row:not([style*="display: none"])');
    const attendanceData = [];
    
    rows.forEach(row => {
        const beneficiaryId = row.dataset.beneficiaryId;
        const hiddenInput = row.querySelector('input[type="hidden"]');
        
        if (beneficiaryId && hiddenInput && hiddenInput.value) {
            attendanceData.push({
                beneficiaryId: beneficiaryId,
                status: hiddenInput.value
            });
        }
    });
    
    if (attendanceData.length === 0) {
        alert('No attendance data to save. Please mark attendance first.');
        return;
    }
    
    // Get current date
    const dateInput = document.querySelector('input[name="date"]');
    const currentDate = dateInput ? dateInput.value : '';
    
    if (!currentDate) {
        alert('Please select a date first.');
        return;
    }
    
    // Prepare form data
    const formData = new FormData();
    formData.append('mark_attendance', '1');
    formData.append('attendance_date', currentDate);
    
    // Add attendance data
    attendanceData.forEach((item, index) => {
        formData.append(`attendance[${item.beneficiaryId}]`, item.status);
    });
    
    // Show saving indicator
    const originalText = 'Auto Save';
    const autoSaveBtn = document.querySelector('button[onclick="autoSave()"]');
    if (autoSaveBtn) {
        autoSaveBtn.disabled = true;
        autoSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    }
    
    // Send AJAX request
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Check if save was successful (basic check)
        if (data.includes('successfully') || data.includes('Success')) {
            alert('✅ Attendance auto-saved successfully!');
        } else {
            alert('⚠️ Auto-save completed, but please verify the results.');
        }
    })
    .catch(error => {
        console.error('Auto-save error:', error);
        alert('❌ Auto-save failed. Please try manual save.');
    })
    .finally(() => {
        // Restore button state
        if (autoSaveBtn) {
            autoSaveBtn.disabled = false;
            autoSaveBtn.innerHTML = '<i class="fas fa-magic"></i> Auto Save';
        }
    });
}

function showAttendanceStats() {
    console.log('🔥 Show Stats button clicked');
    
    const total = document.getElementById('totalCount').textContent;
    const active = document.getElementById('activeCount').textContent;
    const inactive = document.getElementById('inactiveCount').textContent;
    const present = document.getElementById('presentCount').textContent;
    const absent = document.getElementById('absentCount').textContent;
    const rate = document.getElementById('attendanceRate').textContent;
    
    alert('Attendance Statistics:\n\nTotal: ' + total + '\nActive: ' + active + '\nInactive: ' + inactive + '\nPresent: ' + present + '\nAbsent: ' + absent + '\nRate: ' + rate);
}

function clearSearch() {
    console.log('🔥 Clear Search clicked');
    
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.value = '';
        const rows = document.querySelectorAll('.beneficiary-row');
        const offset = <?php echo $offset; ?>;
        rows.forEach((row, index) => {
            row.style.display = '';
            row.querySelector('.serial-number').textContent = offset + index + 1;
        });
        updateCounts();
    }
}

function performSearch() {
    const searchInput = document.getElementById('search');
    const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
    const rows = document.querySelectorAll('.beneficiary-row');
    const offset = <?php echo $offset; ?>;
    
    if (searchTerm === '') {
        // If search is empty, show all rows with correct pagination serial numbers
        rows.forEach((row, index) => {
            row.style.display = '';
            row.querySelector('.serial-number').textContent = offset + index + 1;
        });
    } else {
        // Filter based on search term but keep original serial numbers
        rows.forEach((row, index) => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
                // Keep original serial number based on pagination
                row.querySelector('.serial-number').textContent = offset + index + 1;
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    updateCounts();
}

// Auto-filtering setup
function setupAutoFiltering() {
    const dateInput = document.querySelector('input[name="date"]');
    const constituencySelect = document.getElementById('constituency');
    const mandalSelect = document.getElementById('mandal');
        const batchSelect = document.getElementById('batch');
        
    function buildFilterURL() {
        const params = new URLSearchParams();
        const recordsPerPageSelect = document.getElementById('records_per_page');
        
        if (dateInput && dateInput.value) {
            params.append('date', dateInput.value);
        }
        if (constituencySelect && constituencySelect.value) {
            params.append('constituency', constituencySelect.value);
        }
        if (mandalSelect && mandalSelect.value) {
            params.append('mandal', mandalSelect.value);
        }
        if (batchSelect && batchSelect.value) {
            params.append('batch', batchSelect.value);
        }
        if (recordsPerPageSelect && recordsPerPageSelect.value) {
            params.append('records_per_page', recordsPerPageSelect.value);
        }
        
        // Reset to page 1 when filters change
        params.append('page', '1');
        
        return window.location.pathname + '?' + params.toString();
    }
    
    function applyFilters() {
        const newUrl = buildFilterURL();
        window.location.href = newUrl;
    }
    
    // Function to load mandals based on constituency
    function loadMandals(constituencyId) {
        console.log('🔥 Loading mandals for constituency:', constituencyId);
        
        if (!constituencyId) {
            mandalSelect.innerHTML = '<option value="">Select constituency first</option>';
            batchSelect.innerHTML = '<option value="">Select mandal first</option>';
            return;
        }
        
        fetch(`get_mandals.php?constituency_id=${constituencyId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Mandals loaded:', data);
                
                mandalSelect.innerHTML = '<option value="">All Mandals</option>';
                
                if (data.success && data.mandals) {
                    data.mandals.forEach(mandal => {
                        const option = document.createElement('option');
                        option.value = mandal.id;
                        option.textContent = mandal.name;
                        mandalSelect.appendChild(option);
                    });
                }
                
                // Reset batch dropdown
                batchSelect.innerHTML = '<option value="">Select mandal first</option>';
            })
            .catch(error => {
                console.error('Error loading mandals:', error);
                mandalSelect.innerHTML = '<option value="">Error loading mandals</option>';
            });
    }
    
    // Function to load batches based on mandal
    function loadBatches(mandalId) {
        console.log('🔥 Loading batches for mandal:', mandalId);
        
        if (!mandalId) {
            batchSelect.innerHTML = '<option value="">Select mandal first</option>';
            return;
        }
        
        fetch(`get_batches.php?mandal_id=${mandalId}`)
            .then(response => response.json())
            .then(data => {
                console.log('Batches loaded:', data);
                
                batchSelect.innerHTML = '<option value="">All Batches</option>';
                
                if (data.success && data.batches) {
                    data.batches.forEach(batch => {
                        const option = document.createElement('option');
                        option.value = batch.id;
                        option.textContent = batch.name;
                        batchSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading batches:', error);
                batchSelect.innerHTML = '<option value="">Error loading batches</option>';
            });
    }
    
    // Add event listeners
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            // Update counts immediately with new date
            updateCounts();
            applyFilters();
        });
    }
    
    if (constituencySelect) {
        constituencySelect.addEventListener('change', function() {
            const constituencyId = this.value;
            
            // Load mandals dynamically
            loadMandals(constituencyId);
            
            // Update counts immediately with new filter
            setTimeout(() => {
                updateCounts();
                applyFilters();
            }, 100);
        });
    }
    
    if (mandalSelect) {
        mandalSelect.addEventListener('change', function() {
            const mandalId = this.value;
            
            // Load batches dynamically
            loadBatches(mandalId);
            
            // Update counts immediately with new filter
            setTimeout(() => {
                updateCounts();
                applyFilters();
            }, 100);
        });
    }
    
    if (batchSelect) {
        batchSelect.addEventListener('change', function() {
            // Update counts immediately with new filter
            updateCounts();
            applyFilters();
        });
    }
    
    // Records per page dropdown
    const recordsPerPageSelect = document.getElementById('records_per_page');
    if (recordsPerPageSelect) {
        recordsPerPageSelect.addEventListener('change', function() {
            console.log('🔥 Records per page changed to:', this.value);
            // Update counts and apply filters immediately
            updateCounts();
            applyFilters();
        });
    }
}

// Initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔥 Initializing attendance page...');
    
    // Set up checkbox listeners
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedRows.add(this.value);
            } else {
                selectedRows.delete(this.value);
            }
            console.log('Checkbox selection updated. Total selected:', selectedRows.size);
        });
    });
    
    // Set up Select All checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', selectAllRows);
    }
    
    // Set up search functionality  
    const searchInput = document.getElementById('search');
    if (searchInput) {
        searchInput.addEventListener('input', performSearch);
    }
    
    // Setup auto-filtering
    setupAutoFiltering();
    
    // Initial count update
    updateCounts();
    
    console.log('✅ Attendance page initialized successfully!');
});

console.log('✅ ALL ATTENDANCE FUNCTIONS LOADED!');

// Test if basic JS is working
console.log('🔥 Testing basic functionality...');
console.log('Total beneficiaries on page:', document.querySelectorAll('.beneficiary-row').length);
console.log('Total checkboxes on page:', document.querySelectorAll('.row-checkbox').length);
console.log('Search input exists:', !!document.getElementById('search'));
console.log('Select All checkbox exists:', !!document.getElementById('selectAll'));
</script>

<!-- Include restrictions CRUD JavaScript -->
<script src="restrictions.js"></script>

<!-- Enhanced styling for attendance page -->
<style>
.beneficiary-row.status-present {
    background-color: #d4edda !important;
}

.beneficiary-row.status-absent {
    background-color: #f8d7da !important;
}

.status-buttons {
    min-width: 120px;
}

.status-buttons .btn {
    font-size: 12px;
    padding: 4px 8px;
    border-width: 1px;
    transition: all 0.2s ease;
}

.status-buttons .btn:focus {
    box-shadow: none;
}

.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.card-body {
    padding: 0.75rem !important;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
}

.card-text.small {
    font-size: 0.875rem;
    color: #6c757d;
}

.serial-number {
    font-weight: 600;
    color: #6c757d;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075) !important;
}

.btn-toolbar .btn-group {
    margin-right: 10px;
}

.table-responsive {
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.thead-light th {
    background-color: #f8f9fa;
    border-color: #dee2e6;
    font-weight: 600;
    color: #495057;
}

@media (max-width: 768px) {
    .btn-toolbar {
        flex-direction: column;
    }
    
    .btn-group {
        margin-bottom: 10px;
        width: 100%;
    }
    
    .btn-group .btn {
        flex: 1;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

.modal-lg {
    max-width: 800px;
}

.restriction-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    background-color: #f8f9fa;
}

.restriction-item.active {
    border-color: #28a745;
    background-color: #d4edda;
}

.restriction-item.inactive {
    border-color: #dc3545;
    background-color: #f8d7da;
    opacity: 0.7;
}

.restriction-badge {
    font-size: 12px;
    padding: 4px 8px;
}

/* Pagination styling */
.pagination-sm .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

.pagination .page-item.disabled .page-link {
    color: #6c757d;
    pointer-events: none;
    cursor: auto;
    background-color: #fff;
    border-color: #dee2e6;
}
</style>

<!-- Attendance Restrictions CRUD Modal -->
<div class="modal fade" id="restrictionsModal" tabindex="-1" aria-labelledby="restrictionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restrictionsModalLabel">
                    <i class="fas fa-ban"></i> Manage Attendance Restrictions
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-plus"></i> Add New Restriction</h6>
                    </div>
                    <div class="card-body">
                        <form id="addRestrictionForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="restrictionType">Restriction Type</label>
                                        <select class="form-control" id="restrictionType" name="restriction_type" required>
                                            <option value="">Select Type</option>
                                            <option value="day_of_week">Day of Week</option>
                                            <option value="specific_date">Specific Date</option>
                                            <option value="date_range">Date Range</option>
                                            <option value="custom">Custom Rule</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="restrictionName">Restriction Name</label>
                                        <input type="text" class="form-control" id="restrictionName" name="restriction_name" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group" id="restrictionValueGroup">
                                        <label for="restrictionValue">Value</label>
                                        <input type="text" class="form-control" id="restrictionValue" name="restriction_value" required>
                                        <small class="form-text text-muted" id="valueHelp"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="appliesTo">Applies To</label>
                                        <select class="form-control" id="appliesTo" name="applies_to">
                                            <option value="all">All</option>
                                            <option value="constituency">Specific Constituency</option>
                                            <option value="mandal">Specific Mandal</option>
                                            <option value="batch">Specific Batch</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row" id="dateRangeGroup" style="display: none;">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="startDate">Start Date</label>
                                        <input type="date" class="form-control" id="startDate" name="start_date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="endDate">End Date</label>
                                        <input type="date" class="form-control" id="endDate" name="end_date">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="restrictionDescription">Description</label>
                                <textarea class="form-control" id="restrictionDescription" name="description" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="isActive" name="is_active" checked>
                                    <label class="form-check-label" for="isActive">
                                        Active (restriction is enforced)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <button type="button" class="btn btn-secondary" onclick="resetRestrictionForm()">Reset</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Restriction
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list"></i> Current Restrictions</h6>
                    </div>
                    <div class="card-body">
                        <div id="restrictionsList">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading restrictions...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../includes/footer.php';
?>
