<?php
// Cache busting: Column order updated - Mark Attendance moved before Student Details
$pageTitle = "Daily Attendance";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Daily Attendance']
];

require_once 'includes/header.php';
?>
<!-- Force refresh for column order update -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
/* Mobile-first responsive design for attendance table */
.attendance-table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    font-size: 14px;
}

.attendance-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 12px 8px;
    text-align: center;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}

.attendance-table td {
    padding: 10px 8px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

.attendance-table tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,.05);
}

.attendance-table tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}

/* Quick Stats Cards Styling */
.card.border-primary {
    border-color: #007bff !important;
}

.card.border-success {
    border-color: #28a745 !important;
}

.card.border-danger {
    border-color: #dc3545 !important;
}

.card.border-info {
    border-color: #17a2b8 !important;
}

.card.border-primary .card-title {
    color: #007bff !important;
}

.card.border-success .card-title {
    color: #28a745 !important;
}

.card.border-danger .card-title {
    color: #dc3545 !important;
}

.card.border-info .card-title {
    color: #17a2b8 !important;
}

/* Hover effects for stats cards */
.card.border-primary:hover,
.card.border-success:hover,
.card.border-danger:hover,
.card.border-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
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
    .attendance-table {
        min-width: 450px; /* Minimum width for 3 columns */
        font-size: 13px;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 8px 6px;
        white-space: nowrap; /* Prevent text wrapping on mobile */
    }
    
    /* Column widths for mobile - now only 3 columns */
    .attendance-table th:nth-child(1),
    .attendance-table td:nth-child(1) {
        width: 50px; /* S.No column */
        min-width: 50px;
    }
    
    .attendance-table th:nth-child(2),
    .attendance-table td:nth-child(2) {
        width: 160px; /* Mark Attendance column */
        min-width: 160px;
    }
    
    .attendance-table th:nth-child(3),
    .attendance-table td:nth-child(3) {
        width: 300px; /* Student Details column - wider now */
        min-width: 300px;
        max-width: 300px;
    }
    
    /* Optimize status buttons for mobile */
    .status-buttons .btn {
        padding: 6px 12px;
        font-size: 12px;
        min-width: 70px;
    }
    
    /* Student details optimization */
    .attendance-table td:nth-child(3) strong {
        font-size: 13px;
        line-height: 1.3;
        display: block;
        margin-bottom: 4px;
    }
    
    .attendance-table td:nth-child(3) small {
        font-size: 11px;
        display: block;
        margin-bottom: 2px;
    }
    
    /* Mobile info display */
    .mobile-info {
        display: none; /* Hide on mobile since we're showing full table */
    }
}

/* Extra small devices */
@media (max-width: 576px) {
    .attendance-table {
        min-width: 400px;
        font-size: 12px;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 6px 4px;
    }
    
    .status-buttons .btn {
        padding: 5px 10px;
        font-size: 11px;
        min-width: 65px;
    }
    
    /* Adjust column widths for very small screens - now only 3 columns */
    .attendance-table th:nth-child(3),
    .attendance-table td:nth-child(3) {
        width: 250px;
        min-width: 250px;
        max-width: 250px;
    }
}

/* Extra extra small devices */
@media (max-width: 480px) {
    .attendance-table {
        min-width: 350px;
        font-size: 11px;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 4px 2px;
    }
    
    .status-buttons .btn {
        padding: 4px 8px;
        font-size: 10px;
        min-width: 60px;
    }
    
    /* Further reduce column widths - now only 3 columns */
    .attendance-table th:nth-child(3),
    .attendance-table td:nth-child(3) {
        width: 220px;
        min-width: 220px;
        max-width: 220px;
    }
}

/* Desktop styles */
@media (min-width: 769px) {
    .attendance-table {
        font-size: 14px;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 12px 8px;
    }
    
    .status-buttons .btn {
        padding: 8px 16px;
        font-size: 14px;
        min-width: 80px;
    }
    
    /* Hide mobile info on desktop */
    .mobile-info {
        display: none !important;
    }
}

/* Mobile info display styling (hidden by default) */
.mobile-info {
    display: none;
    margin-top: 8px;
    padding: 8px 10px;
    background-color: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #007bff;
    font-size: 13px;
}

.mobile-info .batch-info {
    display: inline-block;
    margin-right: 20px;
    margin-bottom: 4px;
}

.mobile-info .status-info {
    display: inline-block;
    margin-bottom: 4px;
}

.mobile-info .batch-info .badge,
.mobile-info .status-info .badge {
    font-size: 11px;
    padding: 4px 8px;
    margin-left: 6px;
    font-weight: 500;
}

.mobile-info i {
    color: #6c757d;
    margin-right: 4px;
}

/* Ensure proper table display */
.attendance-table th,
.attendance-table td {
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
    .attendance-table thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 2;
    }
}
</style>

<?php



// Get current date and TC info
$current_date = date('Y-m-d');
$tc_id = $_SESSION['tc_user_training_center_id'];

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    $batch_id = $_POST['batch_id'];
    $attendance_data = $_POST['attendance'] ?? [];
    

    
    if (!empty($attendance_data)) {
        try {
            $saved_count = 0;
            $errors = [];
            
            foreach ($attendance_data as $beneficiary_id => $status) {
                // Process all students, defaulting empty status to 'absent'
                $final_status = !empty($status) ? $status : 'absent';
                
                try {
                    // Check if attendance already exists
                    $check_query = "SELECT id FROM attendance WHERE beneficiary_id = ? AND attendance_date = ?";
                    $existing = fetchRow($check_query, [$beneficiary_id, $current_date], 'is');
                    
                    if ($existing) {
                        // Update existing attendance
                        $update_query = "UPDATE attendance SET status = ?, updated_at = NOW() 
                                       WHERE beneficiary_id = ? AND attendance_date = ?";
                        $result = executeQuery($update_query, [$final_status, $beneficiary_id, $current_date], 'sis');
                    } else {
                        // Insert new attendance
                        $insert_query = "INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                                       VALUES (?, ?, ?, NOW())";
                        $result = executeQuery($insert_query, [$beneficiary_id, $current_date, $final_status], 'iss');
                    }
                    
                    $saved_count++;
                } catch (Exception $individual_error) {
                    $errors[] = "Error for beneficiary ID {$beneficiary_id}: " . $individual_error->getMessage();
                }
            }
            
            if (!empty($errors)) {
                echo "<div class='alert alert-warning'><strong>Some errors occurred:</strong><br>" . implode('<br>', $errors) . "</div>";
            }
            

            
            $message = "Attendance saved successfully for " . date('d M Y') . " ({$saved_count} students processed)";
            $message_type = "success";
        } catch (Exception $e) {
            $message = "Error saving attendance: " . $e->getMessage();
            $message_type = "danger";
        }
    }
}

// Get batches for this training center
$batches_query = "SELECT * FROM batches WHERE tc_id = ? AND status IN ('active', 'completed') ORDER BY status DESC, name";
$batches = fetchAll($batches_query, [$tc_id], 'i') ?: [];

// Get selected batch (default to first batch)
$selected_batch_id = $_GET['batch_id'] ?? ($_POST['batch_id'] ?? ($batches[0]['id'] ?? 0));

// Get beneficiaries for selected batch with their attendance for today
$beneficiaries = [];
if ($selected_batch_id) {
    // Use same query structure as admin to ensure consistency
    // Check if selected batch is completed
    $batchStatusQuery = "SELECT status FROM batches WHERE id = ?";
    $batchStatusResult = fetchRow($batchStatusQuery, [$selected_batch_id], 'i');
    $batchStatus = $batchStatusResult ? $batchStatusResult['status'] : 'active';
    
    // Build status condition based on batch status
    $statusCondition = ($batchStatus === 'completed') ? 
        "(ben.status = 'active' OR ben.status = 'completed')" : 
        "ben.status = 'active'";
    
    $beneficiaries_query = "SELECT ben.*, 
                                   b.name as batch_name, 
                                   b.code as batch_code,
                                   a.status as attendance_status,
                                   a.check_in_time,
                                   a.check_out_time,
                                   a.remarks
                           FROM beneficiaries ben
                           LEFT JOIN batches b ON ben.batch_id = b.id
                           LEFT JOIN attendance a ON ben.id = a.beneficiary_id AND a.attendance_date = ?
                           WHERE ben.batch_id = ? AND $statusCondition AND b.status IN ('active', 'completed')
                           ORDER BY ben.full_name";
    $beneficiaries = fetchAll($beneficiaries_query, [$current_date, $selected_batch_id], 'si') ?: [];
    

    

    // Attendance data is now fetched directly in the main query via LEFT JOIN
}

// Get attendance summary for today (for selected batch only)
if ($selected_batch_id) {
    $summary_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent
                      FROM attendance a
                      JOIN beneficiaries ben ON a.beneficiary_id = ben.id
                      JOIN batches b ON ben.batch_id = b.id
                      WHERE ben.batch_id = ? AND a.attendance_date = ? AND b.status IN ('active', 'completed')";
    $summary = fetchRow($summary_query, [$selected_batch_id, $current_date], 'is') ?: 
               ['total' => 0, 'present' => 0, 'absent' => 0];
} else {
    $summary = ['total' => 0, 'present' => 0, 'absent' => 0];
}

// Debug: Check which students are missing attendance records (temporary)
if ($selected_batch_id && !empty($beneficiaries)) {
    $beneficiary_ids = array_column($beneficiaries, 'id');
    $placeholders = str_repeat('?,', count($beneficiary_ids) - 1) . '?';
    
    $missing_query = "SELECT ben.id, ben.full_name 
                      FROM beneficiaries ben 
                      WHERE ben.id IN ($placeholders) 
                      AND ben.id NOT IN (
                          SELECT a.beneficiary_id 
                          FROM attendance a 
                          WHERE a.attendance_date = ? AND a.beneficiary_id IN ($placeholders)
                      )";
    
    $params = array_merge($beneficiary_ids, [$current_date], $beneficiary_ids);
    $types = str_repeat('i', count($beneficiary_ids)) . 's' . str_repeat('i', count($beneficiary_ids));
    
    $missing_students = fetchAll($missing_query, $params, $types) ?: [];
}
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Completed Batch Notification -->
<?php if ($selected_batch_id && isset($batchStatus) && $batchStatus === 'completed'): ?>
<div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i>
    <strong>Completed Batch:</strong> This batch has ended. You can still mark attendance for historical records or make-up sessions.
    <button type="button" class="btn-close" data-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Date and Batch Selection -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check"></i>
                    Daily Attendance - <?php echo date('d M Y'); ?>
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">Current Date Only</span>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-6">
                        <label for="batch_id">Select Batch:</label>
                        <select name="batch_id" id="batch_id" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Select Batch --</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?php echo $batch['id']; ?>" 
                                        <?php echo ($batch['id'] == $selected_batch_id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($batch['name']); ?> (<?php echo htmlspecialchars($batch['code']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <div class="text-center">
                            <small class="text-muted">Total Marked</small><br>
                            <strong class="text-info"><?php echo $summary['total']; ?> Students</strong>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($selected_batch_id && !empty($beneficiaries)): ?>
<!-- Quick Stats Cards - Same as Admin -->
<div class="row mb-3">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-primary mb-1" id="totalCount"><?php echo count($beneficiaries); ?></h4>
                <p class="card-text small mb-0">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-success mb-1" id="presentCount"><?php echo $summary['present']; ?></h4>
                <p class="card-text small mb-0">Present</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-danger mb-1" id="absentCount"><?php echo $summary['absent']; ?></h4>
                <p class="card-text small mb-0">Absent</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-info mb-1" id="attendanceRate">
                    <?php 
                    $rate = count($beneficiaries) > 0 ? 
                        round(($summary['total'] / count($beneficiaries)) * 100, 1) : 0;
                    echo $rate . '%';
                    ?>
                </h4>
                <p class="card-text small mb-0">Rate</p>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Form -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i>
                    Mark Attendance - <?php 
                    $selected_batch = array_filter($batches, function($b) use($selected_batch_id) { 
                        return $b['id'] == $selected_batch_id; 
                    });
                    echo htmlspecialchars(reset($selected_batch)['name']);
                    ?>
                    <span class="badge badge-info ml-2" style="font-size: 0.8em;">NEW: Mark Attendance moved to 2nd column</span>
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?php echo count($beneficiaries); ?> Students</span>
                </div>
            </div>
            
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="batch_id" value="<?php echo $selected_batch_id; ?>">
                
                <div class="card-body">
                    <!-- Quick Actions -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-success" onclick="markAllPresent()">
                                        <i class="fas fa-check"></i> Mark All Present
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="markAllAbsent()">
                                        <i class="fas fa-times"></i> Mark All Absent
                                    </button>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearAllAttendance()">
                                        <i class="fas fa-eraser"></i> Clear All
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="alert alert-info alert-sm mb-0 py-2">
                                <i class="fas fa-info-circle"></i> Use buttons below to mark attendance quickly
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <!-- Mobile-responsive attendance table - Batch and Status columns hidden on mobile -->
                        <div class="d-block d-md-none alert alert-info alert-sm mb-2">
                            <i class="fas fa-mobile-alt"></i> 
                            <strong>Mobile View:</strong> Streamlined 3-column layout for better mobile experience. Student names and details are fully visible!
                        </div>
                        <table class="attendance-table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th style="background-color: #e3f2fd; color: #1976d2;">📝 Mark Attendance</th>
                                    <th>Student Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beneficiaries as $index => $beneficiary): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <input type="hidden" 
                                               name="attendance[<?php echo $beneficiary['id']; ?>]" 
                                               value="<?php 
                                                   $status = $beneficiary['attendance_status'];
                                                   if ($status == 'present' || $status == 'P') {
                                                       echo 'present';
                                                   } else {
                                                       echo 'absent';
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
                                        <br><small class="text-muted">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($beneficiary['mobile_number']); ?>
                                        </small>
                                        
                                        <!-- Batch and Status info now shown inline since columns were removed -->
                                        <br><small class="text-muted">
                                            <i class="fas fa-graduation-cap"></i> Batch: 
                                            <span class="badge badge-primary"><?php echo htmlspecialchars($beneficiary['batch_name']); ?></span>
                                            <?php if (!empty($beneficiary['attendance_status'])): ?>
                                                | <i class="fas fa-clock"></i> Status: 
                                                <span class="badge badge-<?php 
                                                    echo $beneficiary['attendance_status'] == 'present' ? 'success' : 
                                                         ($beneficiary['attendance_status'] == 'absent' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($beneficiary['attendance_status']); ?>
                                                </span>
                                                <?php if ($beneficiary['check_in_time']): ?>
                                                    | <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($beneficiary['check_in_time'])); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                | <i class="fas fa-clock"></i> Status: 
                                                <span class="badge badge-secondary">Not Marked</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <!-- Batch column removed -->
                                    <!-- Current Status column removed -->

                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-success btn-sm" onclick="markAllPresent()">
                                        <i class="fas fa-check"></i> All Present
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm" onclick="markAllAbsent()">
                                        <i class="fas fa-times"></i> All Absent
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" name="submit_attendance" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Attendance
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($selected_batch_id): ?>
<!-- No Students Found -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No students found in this batch</h5>
                <p class="text-muted">The selected batch may not have any active students.</p>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- No Batch Selected -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Please select a batch</h5>
                <p class="text-muted">Choose a batch from the dropdown above to mark attendance.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>



<script>
// Function to update attendance counts in real-time
function updateCounts() {
    const totalInputs = document.querySelectorAll('input[name^="attendance"]').length;
    let presentCount = 0;
    let absentCount = 0;
    
    document.querySelectorAll('input[name^="attendance"]').forEach(input => {
        if (input.value === 'present') {
            presentCount++;
        } else if (input.value === 'absent') {
            absentCount++;
        }
    });
    
    const totalMarked = presentCount + absentCount;
    const rate = totalInputs > 0 ? Math.round((totalMarked / totalInputs) * 100) : 0;
    
    // Update the stats cards
    document.getElementById('totalCount').textContent = totalInputs;
    document.getElementById('presentCount').textContent = presentCount;
    document.getElementById('absentCount').textContent = absentCount;
    document.getElementById('attendanceRate').textContent = rate + '%';
    
    console.log('📊 Stats updated - Total:', totalInputs, 'Present:', presentCount, 'Absent:', absentCount, 'Rate:', rate + '%');
}

// Function to set attendance status and update counts
function setAttendanceStatus(button, status) {
    console.log('🔥 Individual button clicked:', status);
    
    // Get the row containing this button
    const row = button.closest('tr');
    const hiddenInput = row.querySelector('input[name^="attendance"]');
    
    if (hiddenInput) {
        // Update hidden input value
        hiddenInput.value = status;
        console.log('✅ Updated hidden input to:', status);
        
        // Update button appearances
        const presentButton = row.querySelector('.btn-present');
        const absentButton = row.querySelector('.btn-absent');
        
        if (status === 'present') {
            presentButton.className = 'btn btn-present btn-success';
            absentButton.className = 'btn btn-absent btn-outline-danger';
            console.log('✅ Set to PRESENT (green)');
        } else {
            presentButton.className = 'btn btn-present btn-outline-success';
            absentButton.className = 'btn btn-absent btn-danger';
            console.log('✅ Set to ABSENT (red)');
        }
        
        // Update the stats in real-time
        updateCounts();
    } else {
        console.error('❌ Hidden input not found for this row');
    }
}

// Quick mark functions for all students
function markAllPresent() {
    console.log('🟢 Marking all students as PRESENT');
    const presentButtons = document.querySelectorAll('.btn-present');
    console.log(`Found ${presentButtons.length} present buttons`);
    
    presentButtons.forEach((button, index) => {
        setAttendanceStatus(button, 'present');
        console.log(`✅ Processed button ${index + 1}/${presentButtons.length}`);
    });
    
    // Double-check: ensure all hidden inputs have 'present' value
    const hiddenInputs = document.querySelectorAll('input[name^="attendance"]');
    hiddenInputs.forEach(input => {
        if (!input.value || input.value === '') {
            input.value = 'present';
            console.log(`🔧 Fixed empty input for beneficiary: ${input.name}`);
        }
    });
    
    console.log(`🎯 Total inputs processed: ${hiddenInputs.length}`);
    
    // Update counts after bulk action
    updateCounts();
}

function markAllAbsent() {
    console.log('🔴 Marking all students as ABSENT');
    const absentButtons = document.querySelectorAll('.btn-absent');
    console.log(`Found ${absentButtons.length} absent buttons`);
    
    absentButtons.forEach((button, index) => {
        setAttendanceStatus(button, 'absent');
        console.log(`✅ Processed button ${index + 1}/${absentButtons.length}`);
    });
    
    // Double-check: ensure all hidden inputs have 'absent' value
    const hiddenInputs = document.querySelectorAll('input[name^="attendance"]');
    hiddenInputs.forEach(input => {
        if (!input.value || input.value === '') {
            input.value = 'absent';
            console.log(`🔧 Fixed empty input for beneficiary: ${input.name}`);
        }
    });
    
    console.log(`🎯 Total inputs processed: ${hiddenInputs.length}`);
    
    // Update counts after bulk action
    updateCounts();
}

function clearAllAttendance() {
    if (confirm('Are you sure you want to clear all attendance selections?')) {
        // Reset all buttons to default state (absent but unselected appearance)
        const allPresentButtons = document.querySelectorAll('.btn-present');
        const allAbsentButtons = document.querySelectorAll('.btn-absent');
        
        allPresentButtons.forEach(button => {
            button.className = 'btn btn-present btn-outline-success';
        });
        
        allAbsentButtons.forEach(button => {
            button.className = 'btn btn-absent btn-outline-danger';
        });
        
        // Clear all hidden inputs
        const hiddenInputs = document.querySelectorAll('input[name^="attendance"]');
        hiddenInputs.forEach(input => {
            input.value = '';
        });
    }
}

// Form validation and debugging
$(document).ready(function() {
    // Initialize counts when page loads
    updateCounts();
    
    // Debug: Count hidden inputs when page loads
    const hiddenInputs = $('input[name^="attendance"]');
    console.log(`🔍 Debug: Found ${hiddenInputs.length} hidden attendance inputs on page load`);
    
    // Check for problematic students
    const problematicInputs = hiddenInputs.filter('[data-debug]');
    if (problematicInputs.length > 0) {
        problematicInputs.each(function() {
            console.log(`✓ Found problematic student input: ${$(this).data('debug')} (name: ${this.name})`);
        });
    } else {
        console.log('❌ No problematic student inputs found on page');
    }
    
    // Force table column order verification
    console.log('🔧 Verifying table column order...');
    const tableHeaders = $('.attendance-table thead th');
    console.log('📋 Table headers found:', tableHeaders.length);
    tableHeaders.each(function(index) {
        console.log(`Column ${index + 1}: ${$(this).text().trim()}`);
    });
    
    // Mobile-specific optimizations
    if (window.innerWidth <= 768) {
        console.log('📱 Mobile device detected - applying optimizations');
        
        // Ensure table doesn't scroll horizontally
        $('.table-responsive').css({
            'overflow-x': 'hidden'
        });
        
        // Add mobile-friendly indicator
        $('.table-responsive').append('<div class="text-center text-muted mt-2"><small><i class="fas fa-mobile-alt"></i> Mobile-optimized layout - all information visible without scrolling</small></div>');
        
        // Optimize button sizes for touch
        $('.status-buttons .btn').css({
            'min-height': '44px', // Minimum touch target size
            'min-width': '70px'
        });
    }
    
    $('#attendanceForm').submit(function(e) {
        const marked = $('input[name^="attendance"]').filter(function() {
            return $(this).val() !== '';
        }).length;
        
        console.log(`📊 Form submission: ${hiddenInputs.length} total inputs, ${marked} with values`);
        
        if (marked === 0) {
            e.preventDefault();
            alert('Please mark attendance for at least one student.');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>