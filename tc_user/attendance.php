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
/* Custom styling for attendance table without DataTables */
.attendance-table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
}

.attendance-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    padding: 12px 8px;
    text-align: center;
    border: 1px solid #dee2e6;
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

/* Mobile responsive styles for attendance table */
@media (max-width: 768px) {
    /* Keep all columns visible but optimize for mobile */
    .attendance-table {
        font-size: 0.9rem;
        min-width: 600px; /* Ensure minimum width to prevent column squashing */
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 8px 6px;
        white-space: nowrap; /* Prevent text wrapping */
    }
    
    /* Make attendance buttons more touch-friendly */
    .status-buttons .btn {
        padding: 8px 12px;
        font-size: 0.85rem;
        min-width: 70px;
    }
    
    /* Optimize student details column */
    .attendance-table td:nth-child(3) {
        min-width: 140px;
        max-width: 180px;
    }
    
    .attendance-table td:nth-child(3) strong {
        font-size: 0.95rem;
        line-height: 1.3;
    }
    
    .attendance-table td:nth-child(3) small {
        font-size: 0.8rem;
    }
    
    /* Ensure Batch and Current Status columns are visible on mobile */
    .attendance-table th:nth-child(4),
    .attendance-table td:nth-child(4),
    .attendance-table th:nth-child(5),
    .attendance-table td:nth-child(5) {
        display: table-cell !important;
    }
    
    /* Optimize Batch column for mobile */
    .attendance-table td:nth-child(4) .badge {
        font-size: 0.75rem;
        padding: 4px 6px;
    }
    
    /* Optimize Current Status column for mobile */
    .attendance-table td:nth-child(5) .badge {
        font-size: 0.75rem;
        padding: 4px 6px;
    }
    
    .attendance-table td:nth-child(5) small {
        font-size: 0.7rem;
    }
    
    /* Ensure table container allows horizontal scrolling */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    /* Optimize column widths for mobile */
    .attendance-table th:nth-child(1),
    .attendance-table td:nth-child(1) {
        width: 60px; /* S.No column */
    }
    
    .attendance-table th:nth-child(2),
    .attendance-table td:nth-child(2) {
        width: 160px; /* Mark Attendance column */
    }
    
    .attendance-table th:nth-child(3),
    .attendance-table td:nth-child(3) {
        width: 180px; /* Student Details column */
    }
    
    .attendance-table th:nth-child(4),
    .attendance-table td:nth-child(4) {
        width: 100px; /* Batch column */
    }
    
    .attendance-table th:nth-child(5),
    .attendance-table td:nth-child(5) {
        width: 120px; /* Current Status column */
    }
}

@media (max-width: 576px) {
    /* Extra small devices */
    .attendance-table {
        font-size: 0.8rem;
        min-width: 550px; /* Slightly smaller minimum width */
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 6px 4px;
        white-space: nowrap;
    }
    
    .status-buttons .btn {
        padding: 6px 10px;
        font-size: 0.8rem;
        min-width: 65px;
    }
    
    .attendance-table td:nth-child(3) {
        min-width: 120px;
        max-width: 160px;
    }
    
    /* Ensure proper spacing on very small screens */
    .table-responsive {
        margin: 0 -15px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .attendance-table {
        margin: 0;
        width: 100%;
    }
    
    /* Optimize badges for very small screens */
    .badge {
        font-size: 0.7rem !important;
        padding: 3px 5px !important;
    }
    
    /* Ensure all columns remain visible */
    .attendance-table th,
    .attendance-table td {
        display: table-cell !important;
    }
}

/* Additional mobile optimizations */
@media (max-width: 768px) {
    /* Ensure table container is properly responsive */
    .table-responsive {
        border: none;
        border-radius: 0;
    }
    
    /* Add visual indicator for mobile scrolling */
    .table-responsive::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 20px;
        background: linear-gradient(to right, transparent, rgba(0,0,0,0.1));
        pointer-events: none;
        z-index: 1;
    }
    
    /* Optimize table header for mobile */
    .attendance-table thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 2;
    }
    
    /* Ensure proper spacing between columns */
    .attendance-table th,
    .attendance-table td {
        border-right: 1px solid #dee2e6;
    }
    
    .attendance-table th:last-child,
    .attendance-table td:last-child {
        border-right: none;
    }
}

/* Extra small devices - ensure minimum usability */
@media (max-width: 480px) {
    .attendance-table {
        min-width: 500px; /* Minimum width to prevent extreme squashing */
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 4px 2px;
        font-size: 0.75rem;
    }
    
    .status-buttons .btn {
        padding: 8px 8px;
        font-size: 0.75rem;
        min-width: 60px;
        min-height: 40px;
    }
    
    /* Optimize badges for very small screens */
    .badge {
        font-size: 0.65rem !important;
        padding: 2px 4px !important;
    }
    
    /* Ensure mobile alert is visible */
    .alert-info {
        font-size: 0.8rem;
        padding: 8px 12px;
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
$batches_query = "SELECT * FROM batches WHERE tc_id = ? AND status = 'active' ORDER BY name";
$batches = fetchAll($batches_query, [$tc_id], 'i') ?: [];

// Get selected batch (default to first batch)
$selected_batch_id = $_GET['batch_id'] ?? ($_POST['batch_id'] ?? ($batches[0]['id'] ?? 0));

// Get beneficiaries for selected batch with their attendance for today
$beneficiaries = [];
if ($selected_batch_id) {
    // Use same query structure as admin to ensure consistency
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
                           WHERE ben.batch_id = ? AND ben.status = 'active' AND b.status = 'active'
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
                      WHERE ben.batch_id = ? AND a.attendance_date = ?";
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
                            <strong>Mobile View:</strong> All columns are now visible on mobile for complete information. 
                            Table is optimized for touch interaction.
                        </div>
                        <table class="attendance-table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th style="background-color: #e3f2fd; color: #1976d2;">📝 Mark Attendance</th>
                                    <th>Student Details</th>
                                    <th>Batch</th>
                                    <th>Current Status</th>
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
                                        <strong><?php echo htmlspecialchars($beneficiary['full_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($beneficiary['mobile_number']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($beneficiary['batch_name']); ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($beneficiary['attendance_status'])): ?>
                                            <span class="badge badge-<?php 
                                                echo $beneficiary['attendance_status'] == 'present' ? 'success' : 
                                                     ($beneficiary['attendance_status'] == 'absent' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($beneficiary['attendance_status']); ?>
                                            </span>
                                            <?php if ($beneficiary['check_in_time']): ?>
                                                <br><small class="text-muted">
                                                    <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($beneficiary['check_in_time'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not Marked</span>
                                        <?php endif; ?>
                                    </td>

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
        
        // Ensure table is scrollable horizontally
        $('.table-responsive').css({
            'overflow-x': 'auto',
            '-webkit-overflow-scrolling': 'touch'
        });
        
        // Add touch-friendly scrolling indicator
        $('.table-responsive').append('<div class="text-center text-muted mt-2"><small><i class="fas fa-arrows-alt-h"></i> Swipe left/right to see all columns</small></div>');
        
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