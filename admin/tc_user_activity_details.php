<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/database.php';

$tc_id = $_GET['tc_id'] ?? '';

if (empty($tc_id)) {
    echo '<div class="alert alert-danger">Invalid TC ID provided.</div>';
    exit;
}

// Get TC user details
$tc_user_result = executeQuery("SELECT tc.*, tcr.name as training_center_name, m.name as mandal_name, c.name as constituency_name
                     FROM tc_users tc
                     JOIN training_centers tcr ON tc.training_center_id = tcr.id
                     JOIN mandals m ON tc.mandal_id = m.id
                     JOIN constituencies c ON m.constituency_id = c.id
                     WHERE tc.tc_id = ?", [$tc_id], 's');

if (!$tc_user_result || $tc_user_result->num_rows === 0) {
    echo '<div class="alert alert-danger">TC user not found.</div>';
    exit;
}

$tc_user = $tc_user_result->fetch_assoc();

// Get detailed activity log
$activity_query = "SELECT 
                    ael.*,
                    ben.full_name as beneficiary_name,
                    ben.beneficiary_id as bc_number,
                    b.name as batch_name,
                    b.code as batch_code
                   FROM attendance_edit_log ael
                   JOIN beneficiaries ben ON ael.beneficiary_id = ben.id
                   JOIN batches b ON ben.batch_id = b.id
                   WHERE ael.edited_by_tc_user = ?
                   ORDER BY ael.created_at DESC
                   LIMIT 100";
$activities_result = executeQuery($activity_query, [$tc_user['id']], 'i');
$activities = $activities_result ? $activities_result->fetch_all(MYSQLI_ASSOC) : [];

// Get activity statistics
$stats_query = "SELECT 
                 DATE(ael.created_at) as activity_date,
                 COUNT(*) as total_edits,
                 COUNT(DISTINCT ael.beneficiary_id) as unique_beneficiaries,
                 COUNT(DISTINCT DATE(ael.attendance_date)) as unique_dates
                FROM attendance_edit_log ael
                WHERE ael.edited_by_tc_user = ?
                GROUP BY DATE(ael.created_at)
                ORDER BY activity_date DESC
                LIMIT 30";
$daily_stats_result = executeQuery($stats_query, [$tc_user['id']], 'i');
$daily_stats = $daily_stats_result ? $daily_stats_result->fetch_all(MYSQLI_ASSOC) : [];
?>

<div class="row">
    <!-- User Info -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0">User Information</h6>
            </div>
            <div class="card-body">
                <p><strong>TC ID:</strong> <?php echo htmlspecialchars($tc_user['tc_id']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($tc_user['full_name']); ?></p>
                <p><strong>Training Center:</strong> <?php echo htmlspecialchars($tc_user['training_center_name']); ?></p>
                <p><strong>Mandal:</strong> <?php echo htmlspecialchars($tc_user['mandal_name']); ?></p>
                <p><strong>Constituency:</strong> <?php echo htmlspecialchars($tc_user['constituency_name']); ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-<?php echo $tc_user['status'] == 'active' ? 'success' : 'danger'; ?>">
                        <?php echo ucfirst($tc_user['status']); ?>
                    </span>
                </p>
                <p><strong>Last Login:</strong> 
                    <?php if ($tc_user['last_login']): ?>
                        <?php echo date('d-m-Y H:i:s', strtotime($tc_user['last_login'])); ?>
                    <?php else: ?>
                        <span class="text-muted">Never logged in</span>
                    <?php endif; ?>
                </p>
                <p><strong>Account Created:</strong> <?php echo date('d-m-Y H:i:s', strtotime($tc_user['created_at'])); ?></p>
            </div>
        </div>
    </div>

    <!-- Activity Summary -->
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">Activity Summary</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-2">
                        <h5 class="text-primary"><?php echo count($activities); ?></h5>
                        <small>Total Edits</small>
                    </div>
                    <div class="col-6 mb-2">
                        <h5 class="text-success"><?php echo count($daily_stats); ?></h5>
                        <small>Active Days</small>
                    </div>
                    <div class="col-6 mb-2">
                        <h5 class="text-warning">
                            <?php echo count(array_unique(array_column($activities, 'beneficiary_id'))); ?>
                        </h5>
                        <small>Unique Beneficiaries</small>
                    </div>
                    <div class="col-6 mb-2">
                        <h5 class="text-info">
                            <?php echo count(array_unique(array_column($activities, 'attendance_date'))); ?>
                        </h5>
                        <small>Unique Dates</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Activity Stats -->
<?php if (!empty($daily_stats)): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">Daily Activity Statistics (Last 30 Days)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Edits</th>
                                <th>Unique Beneficiaries</th>
                                <th>Unique Attendance Dates</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_stats as $stat): ?>
                            <tr>
                                <td><?php echo date('d-m-Y', strtotime($stat['activity_date'])); ?></td>
                                <td><span class="badge bg-primary"><?php echo $stat['total_edits']; ?></span></td>
                                <td><span class="badge bg-info"><?php echo $stat['unique_beneficiaries']; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $stat['unique_dates']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detailed Activity Log -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0">Detailed Activity Log (Last 100 Activities)</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($activities)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>Edit Time</th>
                                    <th>Beneficiary</th>
                                    <th>Batch</th>
                                    <th>Attendance Date</th>
                                    <th>Old Status</th>
                                    <th>New Status</th>
                                    <th>Times</th>
                                    <th>Remarks</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <small>
                                            <?php echo date('d-m-Y', strtotime($activity['created_at'])); ?><br>
                                            <strong><?php echo date('H:i:s', strtotime($activity['created_at'])); ?></strong>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['beneficiary_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['bc_number']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['batch_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['batch_code']); ?></small>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($activity['attendance_date'])); ?></td>
                                    <td>
                                        <?php if ($activity['old_status']): ?>
                                            <span class="badge bg-secondary">
                                                <?php echo ucfirst($activity['old_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $activity['new_status'] == 'present' ? 'success' : 
                                                 ($activity['new_status'] == 'absent' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($activity['new_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if ($activity['new_check_in_time']): ?>
                                                In: <?php echo date('H:i', strtotime($activity['new_check_in_time'])); ?><br>
                                            <?php endif; ?>
                                            <?php if ($activity['new_check_out_time']): ?>
                                                Out: <?php echo date('H:i', strtotime($activity['new_check_out_time'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($activity['new_remarks']): ?>
                                            <small><?php echo htmlspecialchars(substr($activity['new_remarks'], 0, 50)); ?><?php echo strlen($activity['new_remarks']) > 50 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $activity['edit_type'] == 'create' ? 'primary' : 
                                                 ($activity['edit_type'] == 'update' ? 'info' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($activity['edit_type']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-history fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No activity recorded for this user</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
