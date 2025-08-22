<?php
$pageTitle = 'Profile';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Profile']
];

require_once 'includes/header.php';

// Get current user info
$user_id = $_SESSION['tc_user_id'];
$tc_id = $_SESSION['tc_user_training_center_id'];
$mandal_id = $_SESSION['tc_user_mandal_id'];

// Get detailed user information
$user_query = "
    SELECT 
        u.*,
        tc.name as tc_name,
        tc.tc_id as tc_code,
        tc.address as tc_address,
        tc.contact_person,
        tc.contact_phone,
        tc.contact_email,
        m.name as mandal_name,
        c.name as constituency_name
    FROM tc_users u
    JOIN training_centers tc ON u.training_center_id = tc.id
    JOIN mandals m ON tc.mandal_id = m.id
    JOIN constituencies c ON m.constituency_id = c.id
    WHERE u.id = ?
";

$user = fetchRow($user_query, [$user_id], 'i');

// Get statistics for this TC
$stats_queries = [
    'total_batches' => "SELECT COUNT(*) as count FROM batches WHERE tc_id = ?",
    'active_batches' => "SELECT COUNT(*) as count FROM batches WHERE tc_id = ? AND status = 'active'",
    'total_students' => "SELECT COUNT(*) as count FROM beneficiaries ben JOIN batches b ON ben.batch_id = b.id WHERE b.tc_id = ? AND ben.status = 'active'",
    'attendance_today' => "SELECT COUNT(*) as count FROM attendance a JOIN beneficiaries ben ON a.beneficiary_id = ben.id JOIN batches b ON ben.batch_id = b.id WHERE b.tc_id = ? AND a.attendance_date = CURDATE()"
];

$stats = [];
foreach ($stats_queries as $key => $query) {
    $result = fetchRow($query, [$tc_id], 'i');
    $stats[$key] = $result['count'] ?? 0;
}

// Get recent login history (last 10 logins)
$login_history_query = "
    SELECT 
        login_time,
        logout_time,
        ip_address,
        user_agent
    FROM tc_activity_log 
    WHERE tc_user_id = ? AND activity_type = 'login'
    ORDER BY login_time DESC 
    LIMIT 10
";
$login_history = fetchAll($login_history_query, [$user_id], 'i') ?: [];

// Get recent activities
$activity_query = "
    SELECT 
        activity_type,
        activity_description,
        activity_timestamp,
        ip_address
    FROM tc_activity_log 
    WHERE tc_user_id = ? AND activity_type != 'login'
    ORDER BY activity_timestamp DESC 
    LIMIT 20
";
$recent_activities = fetchAll($activity_query, [$user_id], 'i') ?: [];
?>

<!-- Profile Header -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user"></i>
                    Training Center Profile
                </h3>
                <div class="card-tools">
                    <span class="badge badge-success">Active TC User</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center">
                        <div class="profile-user-img img-fluid img-circle" 
                             style="width: 128px; height: 128px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
                                    display: flex; align-items: center; justify-content: center; margin: 0 auto; border-radius: 50%;">
                            <i class="fas fa-building fa-3x text-white"></i>
                        </div>
                        <h3 class="profile-username text-center mt-3"><?php echo htmlspecialchars($user['tc_name']); ?></h3>
                        <p class="text-muted text-center">
                            <span class="badge badge-warning"><?php echo htmlspecialchars($user['tc_code']); ?></span><br>
                            Training Center User
                        </p>
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 30%;">User ID:</th>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Role:</th>
                                        <td><span class="badge badge-info">TC User</span></td>
                                    </tr>
                                    <tr>
                                        <th>TC Code:</th>
                                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($user['tc_code']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Training Center:</th>
                                        <td><?php echo htmlspecialchars($user['tc_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Mandal:</th>
                                        <td><?php echo htmlspecialchars($user['mandal_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Constituency:</th>
                                        <td><?php echo htmlspecialchars($user['constituency_name']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th style="width: 30%;">Status:</th>
                                        <td><span class="badge badge-success"><?php echo ucfirst($user['status']); ?></span></td>
                                    </tr>
                                    <tr>
                                        <th>Created:</th>
                                        <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Last Login:</th>
                                        <td>
                                            <?php 
                                            if (!empty($login_history)) {
                                                echo date('d M Y H:i', strtotime($login_history[0]['login_time']));
                                            } else {
                                                echo 'Never';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Contact Person:</th>
                                        <td><?php echo htmlspecialchars($user['contact_person'] ?: 'Not specified'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact Phone:</th>
                                        <td><?php echo htmlspecialchars($user['contact_phone'] ?: 'Not specified'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact Email:</th>
                                        <td><?php echo htmlspecialchars($user['contact_email'] ?: 'Not specified'); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['total_batches']); ?></div>
                <div class="stats-label">Total Batches</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['active_batches']); ?></div>
                <div class="stats-label">Active Batches</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['total_students']); ?></div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stats-number"><?php echo number_format($stats['attendance_today']); ?></div>
                <div class="stats-label">Today's Attendance</div>
            </div>
        </div>
    </div>
</div>

<!-- Permissions and Restrictions -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-shield-alt"></i>
                    Permissions & Access
                </h3>
            </div>
            <div class="card-body">
                <div class="permissions-list">
                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-eye text-success"></i>
                                <strong>Dashboard Access</strong>
                            </div>
                            <span class="badge badge-success">Allowed</span>
                        </div>
                        <small class="text-muted">View training center dashboard and statistics</small>
                    </div>

                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-calendar-check text-success"></i>
                                <strong>Daily Attendance</strong>
                            </div>
                            <span class="badge badge-success">Allowed</span>
                        </div>
                        <small class="text-muted">Mark and edit attendance for current date only</small>
                    </div>

                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-users text-info"></i>
                                <strong>View Students</strong>
                            </div>
                            <span class="badge badge-info">Read Only</span>
                        </div>
                        <small class="text-muted">View student information for your TC batches</small>
                    </div>

                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-layer-group text-info"></i>
                                <strong>View Batches</strong>
                            </div>
                            <span class="badge badge-info">Read Only</span>
                        </div>
                        <small class="text-muted">View training batches assigned to your TC</small>
                    </div>

                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-chart-bar text-info"></i>
                                <strong>Generate Reports</strong>
                            </div>
                            <span class="badge badge-info">Limited</span>
                        </div>
                        <small class="text-muted">Generate reports for your TC data only</small>
                    </div>

                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-edit text-danger"></i>
                                <strong>Edit Master Data</strong>
                            </div>
                            <span class="badge badge-danger">Denied</span>
                        </div>
                        <small class="text-muted">Cannot modify constituencies, mandals, batches, or students</small>
                    </div>

                    <div class="permission-item mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-trash text-danger"></i>
                                <strong>Delete Data</strong>
                            </div>
                            <span class="badge badge-danger">Denied</span>
                        </div>
                        <small class="text-muted">Cannot delete any data from the system</small>
                    </div>

                    <div class="permission-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-history text-warning"></i>
                                <strong>Historical Attendance</strong>
                            </div>
                            <span class="badge badge-warning">Restricted</span>
                        </div>
                        <small class="text-muted">Cannot edit attendance for past dates</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history"></i>
                    Recent Login History
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($login_history)): ?>
                    <div class="timeline">
                        <?php foreach (array_slice($login_history, 0, 5) as $login): ?>
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="timeline-marker">
                                        <i class="fas fa-sign-in-alt text-success"></i>
                                    </div>
                                    <div class="timeline-content ml-3">
                                        <h6 class="mb-1">Login Session</h6>
                                        <p class="mb-1">
                                            <strong>Time:</strong> <?php echo date('d M Y H:i:s', strtotime($login['login_time'])); ?>
                                            <?php if ($login['logout_time']): ?>
                                                <br><strong>Logout:</strong> <?php echo date('d M Y H:i:s', strtotime($login['logout_time'])); ?>
                                            <?php else: ?>
                                                <br><span class="badge badge-success">Current Session</span>
                                            <?php endif; ?>
                                        </p>
                                        <small class="text-muted">
                                            IP: <?php echo htmlspecialchars($login['ip_address']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($login_history) > 5): ?>
                        <div class="text-center">
                            <small class="text-muted">
                                Showing 5 of <?php echo count($login_history); ?> recent logins
                            </small>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <p>No login history available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i>
                    Recent Activities
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($recent_activities)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Description</th>
                                    <th>Time</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_activities, 0, 10) as $activity): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $icon = 'fas fa-info-circle';
                                            $badge_class = 'badge-secondary';
                                            
                                            switch ($activity['activity_type']) {
                                                case 'attendance_mark':
                                                    $icon = 'fas fa-calendar-check';
                                                    $badge_class = 'badge-success';
                                                    break;
                                                case 'attendance_edit':
                                                    $icon = 'fas fa-edit';
                                                    $badge_class = 'badge-warning';
                                                    break;
                                                case 'report_generate':
                                                    $icon = 'fas fa-chart-bar';
                                                    $badge_class = 'badge-info';
                                                    break;
                                                case 'data_view':
                                                    $icon = 'fas fa-eye';
                                                    $badge_class = 'badge-primary';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <i class="<?php echo $icon; ?>"></i>
                                                <?php echo ucwords(str_replace('_', ' ', $activity['activity_type'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                                        <td><?php echo date('d M Y H:i:s', strtotime($activity['activity_timestamp'])); ?></td>
                                        <td><small class="text-muted"><?php echo htmlspecialchars($activity['ip_address']); ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($recent_activities) > 10): ?>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Showing 10 of <?php echo count($recent_activities); ?> recent activities
                            </small>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p>No recent activities found</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>