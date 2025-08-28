<?php
$pageTitle = "Batch Status Manager";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Batch Status Manager']
];

require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 're_evaluate_all') {
        // Re-evaluate all batch statuses
        $result = reEvaluateAllBatchStatuses();
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    } elseif ($action === 're_evaluate_specific') {
        $batch_id = (int)$_POST['batch_id'];
        $result = reEvaluateBatchStatus($batch_id);
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    } elseif ($action === 'force_status_change') {
        $batch_id = (int)$_POST['batch_id'];
        $new_status = $_POST['new_status'];
        $reason = sanitizeInput($_POST['reason']);
        
        $result = forceBatchStatusChange($batch_id, $new_status, $reason);
        if ($result['success']) {
            $message = $result['message'];
            $message_type = 'success';
        } else {
            $message = $result['message'];
            $message_type = 'danger';
        }
    }
}

// Get all batches with current status
$batches = fetchAll("
    SELECT 
        b.*,
        m.name as mandal_name,
        c.name as constituency_name,
        tc.tc_id as tc_code,
        tc.name as tc_name,
        COUNT(ben.id) as beneficiary_count,
        SUM(CASE WHEN ben.status = 'active' THEN 1 ELSE 0 END) as active_beneficiaries,
        SUM(CASE WHEN ben.status = 'completed' THEN 1 ELSE 0 END) as completed_beneficiaries
    FROM batches b
    JOIN mandals m ON b.mandal_id = m.id
    JOIN constituencies c ON m.constituency_id = c.id
    JOIN training_centers tc ON b.tc_id = tc.id
    LEFT JOIN beneficiaries ben ON b.id = ben.batch_id
    GROUP BY b.id
    ORDER BY b.status DESC, c.name, m.name, b.name
");

// Get status change log
$statusLog = fetchAll("
    SELECT 
        bsl.*,
        b.name as batch_name,
        b.code as batch_code,
        au.username as changed_by_user
    FROM batch_status_log bsl
    JOIN batches b ON bsl.batch_id = b.id
    LEFT JOIN admin_users au ON bsl.changed_by = au.id
    ORDER BY bsl.created_at DESC
    LIMIT 50
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i>
                        Batch Status Manager
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-info">Universal Control</span>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="re_evaluate_all">
                                <button type="submit" class="btn btn-primary btn-lg btn-block" onclick="return confirm('This will re-evaluate ALL batch statuses. Continue?')">
                                    <i class="fas fa-sync-alt"></i>
                                    Re-evaluate All Batch Statuses
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                                            <button type="button" class="btn btn-success btn-lg btn-block" data-toggle="modal" data-target="#batchStatusModal">
                    <i class="fas fa-edit"></i>
                    Force Status Change
                </button>
                        </div>
                        <div class="col-md-4">
                                            <button type="button" class="btn btn-info btn-lg btn-block" data-toggle="modal" data-target="#statusLogModal">
                    <i class="fas fa-history"></i>
                    View Status History
                </button>
                        </div>
                    </div>

                    <!-- Batch Status Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Batch Name</th>
                                    <th>Location</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Current Status</th>
                                    <th>Students</th>
                                    <th>Expected Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batches as $index => $batch): ?>
                                    <?php
                                    $currentDate = date('Y-m-d');
                                    $startDate = $batch['start_date'];
                                    $endDate = $batch['end_date'];
                                    
                                    // Calculate expected status
                                    if ($currentDate < $startDate) {
                                        $expectedStatus = 'inactive';
                                        $statusClass = 'secondary';
                                    } elseif ($currentDate >= $startDate && $currentDate <= $endDate) {
                                        $expectedStatus = 'active';
                                        $statusClass = 'success';
                                    } else {
                                        $expectedStatus = 'completed';
                                        $statusClass = 'info';
                                    }
                                    
                                    // Check if status needs updating
                                    $needsUpdate = ($batch['status'] !== $expectedStatus);
                                    ?>
                                    <tr class="<?php echo $needsUpdate ? 'table-warning' : ''; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($batch['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($batch['code']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($batch['constituency_name']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($batch['mandal_name']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($startDate, 'd M Y'); ?></td>
                                        <td><?php echo formatDate($endDate, 'd M Y'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusBadgeClass($batch['status']); ?>">
                                                <?php echo ucfirst($batch['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success"><?php echo $batch['active_beneficiaries']; ?> Active</span><br>
                                            <span class="badge badge-info"><?php echo $batch['completed_beneficiaries']; ?> Completed</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($expectedStatus); ?>
                                            </span>
                                            <?php if ($needsUpdate): ?>
                                                <br><small class="text-warning">Needs Update</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($needsUpdate): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="re_evaluate_specific">
                                                    <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="fas fa-sync"></i> Update
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-success"><i class="fas fa-check"></i> OK</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Force Status Change Modal -->
<div class="modal fade" id="batchStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i>
                    Force Batch Status Change
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="force_status_change">
                    
                    <div class="form-group">
                        <label for="batch_id">Select Batch:</label>
                        <select name="batch_id" id="batch_id" class="form-control" required>
                            <option value="">-- Select Batch --</option>
                            <?php foreach ($batches as $batch): ?>
                                <option value="<?php echo $batch['id']; ?>">
                                    <?php echo htmlspecialchars($batch['name']); ?> (<?php echo htmlspecialchars($batch['code']); ?>) - 
                                    Current: <?php echo ucfirst($batch['status']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_status">New Status:</label>
                        <select name="new_status" id="new_status" class="form-control" required>
                            <option value="">-- Select Status --</option>
                            <option value="inactive">Inactive (Not Started)</option>
                            <option value="active">Active (Running)</option>
                            <option value="completed">Completed (Ended)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reason">Reason for Change:</label>
                        <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Explain why you're changing this status..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to force this status change? This will affect all students in the batch.')">
                        <i class="fas fa-exclamation-triangle"></i>
                        Force Change
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status History Modal -->
<div class="modal fade" id="statusLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history"></i>
                    Batch Status Change History
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (!empty($statusLog)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Batch</th>
                                    <th>Old Status</th>
                                    <th>New Status</th>
                                    <th>Changed By</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($statusLog as $log): ?>
                                    <tr>
                                        <td><?php echo formatDateTime($log['created_at'], 'd M Y H:i'); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($log['batch_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['batch_code']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusBadgeClass($log['old_status']); ?>">
                                                <?php echo ucfirst($log['old_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo getStatusBadgeClass($log['new_status']); ?>">
                                                <?php echo ucfirst($log['new_status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($log['changed_by_user'] ?? 'System'); ?></td>
                                        <td><?php echo htmlspecialchars($log['change_reason']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <p>No status changes recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Debugging -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Batch Status Manager loaded');
    
    // Debug modal triggers
    const forceStatusBtn = document.querySelector('[data-target="#batchStatusModal"]');
    const viewHistoryBtn = document.querySelector('[data-target="#statusLogModal"]');
    
    if (forceStatusBtn) {
        console.log('Force Status button found');
        forceStatusBtn.addEventListener('click', function() {
            console.log('Force Status button clicked');
        });
    } else {
        console.log('Force Status button NOT found');
    }
    
    if (viewHistoryBtn) {
        console.log('View History button found');
        viewHistoryBtn.addEventListener('click', function() {
            console.log('View History button clicked');
        });
    } else {
        console.log('View History button NOT found');
    }
    
    // Test modal functionality
    $('#batchStatusModal').on('show.bs.modal', function () {
        console.log('Force Status modal opening');
    });
    
    $('#statusLogModal').on('show.bs.modal', function () {
        console.log('Status History modal opening');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
