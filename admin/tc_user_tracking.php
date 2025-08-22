<?php
$pageTitle = 'TC User Tracking';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'TC User Tracking']
];

require_once '../includes/header.php';

// Get TC users with their last login and activity stats
$tc_users_query = "SELECT 
                    tc.id, tc.tc_id, tc.full_name, tc.status, tc.last_login, tc.created_at,
                    tcr.name as training_center_name,
                    m.name as mandal_name,
                    c.name as constituency_name,
                    COUNT(DISTINCT ael.id) as total_edits,
                    MAX(ael.created_at) as last_edit
                   FROM tc_users tc
                   JOIN training_centers tcr ON tc.training_center_id = tcr.id
                   JOIN mandals m ON tc.mandal_id = m.id
                   JOIN constituencies c ON m.constituency_id = c.id
                   LEFT JOIN attendance_edit_log ael ON tc.id = ael.edited_by_tc_user
                   GROUP BY tc.id, tc.tc_id, tc.full_name, tc.status, tc.last_login, tc.created_at,
                            tcr.name, m.name, c.name
                   ORDER BY tc.last_login DESC, tc.tc_id";
$tc_users_result = executeQuery($tc_users_query);
$tc_users = $tc_users_result ? $tc_users_result->fetch_all(MYSQLI_ASSOC) : [];



// Get recent TC user activity
$recent_activity_query = "SELECT 
                          ael.*,
                          tc.tc_id,
                          tc.full_name as tc_user_name,
                          tcr.name as training_center_name,
                          ben.full_name as beneficiary_name,
                          ben.beneficiary_id as bc_number,
                          b.name as batch_name
                         FROM attendance_edit_log ael
                         JOIN tc_users tc ON ael.edited_by_tc_user = tc.id
                         JOIN training_centers tcr ON tc.training_center_id = tcr.id
                         JOIN beneficiaries ben ON ael.beneficiary_id = ben.id
                         JOIN batches b ON ben.batch_id = b.id
                         ORDER BY ael.created_at DESC
                         LIMIT 50";
$recent_activity_result = executeQuery($recent_activity_query);
$recent_activity = $recent_activity_result ? $recent_activity_result->fetch_all(MYSQLI_ASSOC) : [];

// Get today's TC activity summary
$today_activity_query = "SELECT 
                          tc.tc_id,
                          tc.full_name as tc_user_name,
                          tcr.name as training_center_name,
                          COUNT(ael.id) as edits_today,
                          MIN(ael.created_at) as first_edit_today,
                          MAX(ael.created_at) as last_edit_today
                         FROM tc_users tc
                         JOIN training_centers tcr ON tc.training_center_id = tcr.id
                         LEFT JOIN attendance_edit_log ael ON tc.id = ael.edited_by_tc_user 
                                                            AND DATE(ael.created_at) = CURDATE()
                         GROUP BY tc.id, tc.tc_id, tc.full_name, tcr.name
                         HAVING edits_today > 0
                         ORDER BY edits_today DESC, last_edit_today DESC";
$today_activity_result = executeQuery($today_activity_query);
$today_activity = $today_activity_result ? $today_activity_result->fetch_all(MYSQLI_ASSOC) : [];
?>



<div class="row">
    <!-- TC Users Overview -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Training Center Users Overview
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped" id="tcUsersTable">
                        <thead>
                            <tr>
                                <th>TC ID</th>
                                <th>Training Center</th>
                                <th>Mandal</th>
                                <th>Constituency</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Total Edits</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tc_users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['tc_id']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['training_center_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['mandal_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['constituency_name']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['last_login']): ?>
                                        <span class="text-success">
                                            <?php echo date('d-m-Y H:i', strtotime($user['last_login'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">Never logged in</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $user['total_edits']; ?></span>
                                </td>
                                <td>
                                    <?php if ($user['last_edit']): ?>
                                        <?php echo date('d-m-Y H:i', strtotime($user['last_edit'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">No activity</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="viewUserActivity('<?php echo $user['tc_id']; ?>', '<?php echo htmlspecialchars($user['training_center_name']); ?>')">
                                        <i class="fas fa-eye"></i> View Activity
                                    </button>
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

<div class="row">
    <!-- Today's Activity -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-calendar-day me-2"></i>
                    Today's TC Activity (<?php echo date('d-m-Y'); ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($today_activity)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>TC ID</th>
                                    <th>Training Center</th>
                                    <th>Edits</th>
                                    <th>First Edit</th>
                                    <th>Last Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_activity as $activity): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($activity['tc_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($activity['training_center_name']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $activity['edits_today']; ?></span></td>
                                    <td><?php echo date('H:i', strtotime($activity['first_edit_today'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($activity['last_edit_today'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No TC user activity today</h6>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- System Stats -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    TC System Statistics
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h4 class="text-primary"><?php echo count($tc_users); ?></h4>
                        <small class="text-muted">Total TC Users</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="text-success">
                            <?php echo count(array_filter($tc_users, function($u) { return $u['status'] == 'active'; })); ?>
                        </h4>
                        <small class="text-muted">Active Users</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="text-info">
                            <?php echo count(array_filter($tc_users, function($u) { return $u['last_login'] !== null; })); ?>
                        </h4>
                        <small class="text-muted">Users Logged In</small>
                    </div>
                    <div class="col-6 mb-3">
                        <h4 class="text-warning">
                            <?php echo array_sum(array_column($tc_users, 'total_edits')); ?>
                        </h4>
                        <small class="text-muted">Total Edits</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Recent TC User Activity (Last 50 Activities)
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_activity)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped" id="recentActivityTable">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>TC User</th>
                                    <th>Training Center</th>
                                    <th>Beneficiary</th>
                                    <th>Batch</th>
                                    <th>Date</th>
                                    <th>Old Status</th>
                                    <th>New Status</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td>
                                        <small>
                                            <?php echo date('d-m-Y', strtotime($activity['created_at'])); ?><br>
                                            <strong><?php echo date('H:i:s', strtotime($activity['created_at'])); ?></strong>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['tc_id']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['tc_user_name']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($activity['training_center_name']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['beneficiary_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($activity['bc_number']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($activity['batch_name']); ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('d-m-Y', strtotime($activity['attendance_date'])); ?>
                                    </td>
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
                        <h5 class="text-muted">No TC user activity recorded yet</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- TC User Activity Modal -->
<div class="modal fade" id="userActivityModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">TC User Activity Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userActivityContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#tcUsersTable').DataTable({
        "pageLength": 25,
        "order": [[5, "desc"]], // Sort by last login desc
        "columnDefs": [{
            "orderable": false,
            "targets": 8 // Actions column
        }]
    });
    
    $('#recentActivityTable').DataTable({
        "pageLength": 25,
        "order": [[0, "desc"]], // Sort by time desc
        "columnDefs": [{
            "orderable": false,
            "targets": []
        }]
    });
});

function viewUserActivity(tcId, centerName) {
    $('#userActivityModal .modal-title').text(`Activity Details: ${tcId} - ${centerName}`);
    $('#userActivityContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x"></i><br>Loading...</div>');
    $('#userActivityModal').modal('show');
    
    // Load user activity details via AJAX
    $.get('tc_user_activity_details.php', {tc_id: tcId}, function(data) {
        $('#userActivityContent').html(data);
    }).fail(function() {
        $('#userActivityContent').html('<div class="alert alert-danger">Failed to load activity details.</div>');
    });
}
</script>

<?php require_once '../includes/footer.php'; ?>
