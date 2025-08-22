<?php
$pageTitle = "Daily Attendance";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Daily Attendance']
];

require_once 'includes/header.php';
?>

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

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    .attendance-table {
        font-size: 0.8rem;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 8px 4px;
        font-size: 0.8rem;
    }
    
    .attendance-table th:nth-child(3),
    .attendance-table td:nth-child(3) {
        display: none;
    }
    
    .attendance-table th:nth-child(1) {
        width: 50px;
    }
    
    .attendance-table th:nth-child(2) {
        width: auto;
    }
    
    .attendance-table th:nth-child(4),
    .attendance-table th:nth-child(5) {
        width: 120px;
    }
    
    /* Mobile attendance buttons */
    .attendance-btn {
        padding: 0.4rem 0.6rem;
        font-size: 0.75rem;
        min-width: 60px;
        margin: 0.25rem;
    }
    
    /* Mobile quick actions */
    .btn-toolbar .btn-group {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .btn-toolbar .btn-group .btn {
        margin-bottom: 0.25rem;
        width: 100%;
    }
    
    /* Mobile stats cards */
    .col-md-3 {
        margin-bottom: 0.5rem;
    }
    
    .card.border-primary,
    .card.border-success,
    .card.border-danger,
    .card.border-info {
        margin-bottom: 0.5rem;
    }
    
    .card.border-primary .card-body,
    .card.border-success .card-body,
    .card.border-danger .card-body,
    .card.border-info .card-body {
        padding: 0.75rem 0.5rem;
    }
    
    .card.border-primary h4,
    .card.border-success h4,
    .card.border-danger h4,
    .card.border-info h4 {
        font-size: 1.2rem;
        margin-bottom: 0.25rem;
    }
    
    .card.border-primary p,
    .card.border-success p,
    .card.border-danger p,
    .card.border-info p {
        font-size: 0.8rem;
        margin-bottom: 0;
    }
}

@media (max-width: 576px) {
    .attendance-table {
        font-size: 0.75rem;
    }
    
    .attendance-table th,
    .attendance-table td {
        padding: 6px 3px;
        font-size: 0.75rem;
    }
    
    .attendance-table th:nth-child(4) {
        display: none;
    }
    
    .attendance-table td:nth-child(4) {
        display: none;
    }
    
    .attendance-btn {
        padding: 0.3rem 0.5rem;
        font-size: 0.7rem;
        min-width: 50px;
    }
    
    .card.border-primary h4,
    .card.border-success h4,
    .card.border-danger h4,
    .card.border-info h4 {
        font-size: 1.1rem;
    }
    
    .card.border-primary p,
    .card.border-success p,
    .card.border-danger p,
    .card.border-info p {
        font-size: 0.75rem;
    }
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .attendance-btn {
        min-height: 44px;
        touch-action: manipulation;
    }
    
    .btn-toolbar .btn {
        min-height: 44px;
        touch-action: manipulation;
    }
    
    .attendance-table tbody tr {
        min-height: 44px;
    }
}

/* Mobile Form Improvements */
@media (max-width: 768px) {
    .form-control {
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .card-header {
        padding: 0.75rem 1rem;
    }
    
    .card-title {
        font-size: 1rem;
    }
    
    .alert-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
    }
}

/* Mobile Navigation Info */
.mobile-attendance-info {
    display: none;
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 0.75rem;
    margin: 0.5rem 0;
    font-size: 0.85rem;
    color: #0056b3;
}

@media (max-width: 768px) {
    .mobile-attendance-info {
        display: block;
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
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<!-- Date and Batch Selection - Mobile Optimized -->
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
                <!-- Mobile attendance info -->
                <div class="mobile-attendance-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>TC:</strong> <?php echo htmlspecialchars($_SESSION['tc_user_training_center_name']); ?> | 
                    <strong>Date:</strong> <?php echo date('F j, Y'); ?>
                </div>
                
                <form method="GET" class="row align-items-end">
                    <div class="col-12 col-md-6">
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

                    <div class="col-12 col-md-3">
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
<!-- Quick Stats Cards - Mobile Optimized -->
<div class="row mb-3">
    <div class="col-6 col-md-3">
        <div class="card border-primary">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-primary mb-1" id="totalCount"><?php echo count($beneficiaries); ?></h4>
                <p class="card-text small mb-0">Total</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-success">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-success mb-1" id="presentCount"><?php echo $summary['present']; ?></h4>
                <p class="card-text small mb-0">Present</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-danger">
            <div class="card-body text-center p-2">
                <h4 class="card-title text-danger mb-1" id="absentCount"><?php echo $summary['absent']; ?></h4>
                <p class="card-text small mb-0">Absent</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
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

<!-- Attendance Form - Mobile Optimized -->
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
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?php echo count($beneficiaries); ?> Students</span>
                </div>
            </div>
            
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="batch_id" value="<?php echo $selected_batch_id; ?>">
                
                <div class="card-body">
                    <!-- Quick Actions - Mobile Optimized -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="btn-toolbar" role="toolbar">
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-success" onclick="markAllPresent()">
                                        <i class="fas fa-check"></i> <span class="d-none d-sm-inline">Mark All Present</span>
                                        <span class="d-sm-none">All Present</span>
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="markAllAbsent()">
                                        <i class="fas fa-times"></i> <span class="d-none d-sm-inline">Mark All Absent</span>
                                        <span class="d-sm-none">All Absent</span>
                                    </button>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearAllAttendance()">
                                        <i class="fas fa-eraser"></i> <span class="d-none d-sm-inline">Clear All</span>
                                        <span class="d-sm-none">Clear</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="alert alert-info alert-sm mb-0 py-2">
                                <i class="fas fa-info-circle"></i> Use buttons above to mark attendance quickly
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="attendance-table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Student Details</th>
                                    <th class="d-none d-md-table-cell">Batch</th>
                                    <th class="d-none d-sm-table-cell">Current Status</th>
                                    <th>Mark Attendance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beneficiaries as $index => $beneficiary): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($beneficiary['full_name']); ?></strong><br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($beneficiary['mobile_number']); ?>
                                        </small>
                                        <!-- Mobile-only batch info -->
                                        <div class="d-md-none mt-1">
                                            <span class="badge badge-primary badge-sm"><?php echo htmlspecialchars($beneficiary['batch_name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($beneficiary['batch_name']); ?></span>
                                    </td>
                                    <td class="d-none d-sm-table-cell">
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
                                    <td>
                                        <input type="hidden" 
                                               name="attendance[<?php echo $beneficiary['id']; ?>]" 
                                               value="<?php echo $beneficiary['attendance_status'] ?? ''; ?>"
                                               id="attendance_<?php echo $beneficiary['id']; ?>">
                                        
                                        <div class="btn-group-toggle" data-toggle="buttons">
                                            <label class="btn btn-outline-success btn-sm attendance-btn <?php echo ($beneficiary['attendance_status'] == 'present') ? 'active' : ''; ?>"
                                                   onclick="markAttendance(<?php echo $beneficiary['id']; ?>, 'present')">
                                                <i class="fas fa-check"></i> <span class="d-none d-sm-inline">Present</span>
                                                <span class="d-sm-none">P</span>
                                            </label>
                                            <label class="btn btn-outline-danger btn-sm attendance-btn <?php echo ($beneficiary['attendance_status'] == 'absent') ? 'active' : ''; ?>"
                                                   onclick="markAttendance(<?php echo $beneficiary['id']; ?>, 'absent')">
                                                <i class="fas fa-times"></i> <span class="d-none d-sm-inline">Absent</span>
                                                <span class="d-sm-none">A</span>
                                            </label>
                                        </div>
                                        
                                        <!-- Mobile-only status indicator -->
                                        <div class="d-sm-none mt-1">
                                            <?php if (!empty($beneficiary['attendance_status'])): ?>
                                                <small class="text-<?php echo $beneficiary['attendance_status'] == 'present' ? 'success' : 'danger'; ?>">
                                                    <i class="fas fa-<?php echo $beneficiary['attendance_status'] == 'present' ? 'check' : 'times'; ?>-circle"></i>
                                                    <?php echo ucfirst($beneficiary['attendance_status']); ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-circle"></i> Not Marked
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <button type="submit" name="submit_attendance" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Attendance
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<!-- No Batch Selected or No Students -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <?php if (empty($batches)): ?>
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h4 class="text-muted">No Active Batches Found</h4>
                    <p class="text-muted">Contact your administrator to set up training batches.</p>
                <?php elseif (!$selected_batch_id): ?>
                    <i class="fas fa-layer-group fa-3x text-info mb-3"></i>
                    <h4 class="text-muted">Select a Batch</h4>
                    <p class="text-muted">Please select a batch from the dropdown above to mark attendance.</p>
                <?php else: ?>
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Students Found</h4>
                    <p class="text-muted">The selected batch has no active students.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Attendance marking functions
function markAttendance(beneficiaryId, status) {
    const input = document.getElementById('attendance_' + beneficiaryId);
    input.value = status;
    
    // Update button states
    const row = input.closest('tr');
    const presentBtn = row.querySelector('.btn-outline-success');
    const absentBtn = row.querySelector('.btn-outline-danger');
    
    // Remove active class from all buttons
    presentBtn.classList.remove('active');
    absentBtn.classList.remove('active');
    
    // Add active class to selected button
    if (status === 'present') {
        presentBtn.classList.add('active');
    } else if (status === 'absent') {
        absentBtn.classList.add('active');
    }
    
    // Update counters
    updateCounters();
}

function markAllPresent() {
    const inputs = document.querySelectorAll('input[name^="attendance["]');
    inputs.forEach(input => {
        input.value = 'present';
    });
    
    // Update button states
    document.querySelectorAll('.btn-outline-success').forEach(btn => btn.classList.add('active'));
    document.querySelectorAll('.btn-outline-danger').forEach(btn => btn.classList.remove('active'));
    
    updateCounters();
}

function markAllAbsent() {
    const inputs = document.querySelectorAll('input[name^="attendance["]');
    inputs.forEach(input => {
        input.value = 'absent';
    });
    
    // Update button states
    document.querySelectorAll('.btn-outline-danger').forEach(btn => btn.classList.add('active'));
    document.querySelectorAll('.btn-outline-success').forEach(btn => btn.classList.remove('active'));
    
    updateCounters();
}

function clearAllAttendance() {
    const inputs = document.querySelectorAll('input[name^="attendance["]');
    inputs.forEach(input => {
        input.value = '';
    });
    
    // Update button states
    document.querySelectorAll('.btn-outline-success, .btn-outline-danger').forEach(btn => {
        btn.classList.remove('active');
    });
    
    updateCounters();
}

function updateCounters() {
    const inputs = document.querySelectorAll('input[name^="attendance["]');
    let present = 0;
    let absent = 0;
    let total = 0;
    
    inputs.forEach(input => {
        if (input.value === 'present') {
            present++;
            total++;
        } else if (input.value === 'absent') {
            absent++;
            total++;
        }
    });
    
    // Update display
    document.getElementById('presentCount').textContent = present;
    document.getElementById('absentCount').textContent = absent;
    document.getElementById('totalCount').textContent = total;
    
    // Calculate and update rate
    const totalStudents = inputs.length;
    const rate = totalStudents > 0 ? Math.round((total / totalStudents) * 100 * 10) / 10 : 0;
    document.getElementById('attendanceRate').textContent = rate + '%';
}

// Initialize counters on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCounters();
    
    // Add touch event listeners for mobile
    if ('ontouchstart' in window) {
        document.querySelectorAll('.attendance-btn').forEach(btn => {
            btn.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.95)';
            });
            
            btn.addEventListener('touchend', function() {
                this.style.transform = 'scale(1)';
            });
        });
    }
});

// Form validation
document.getElementById('attendanceForm').addEventListener('submit', function(e) {
    const inputs = document.querySelectorAll('input[name^="attendance["]');
    let hasAttendance = false;
    
    inputs.forEach(input => {
        if (input.value) {
            hasAttendance = true;
        }
    });
    
    if (!hasAttendance) {
        e.preventDefault();
        alert('Please mark attendance for at least one student before saving.');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    
    // Reset button after 5 seconds
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});
</script>

<?php
require_once 'includes/footer.php';
?>