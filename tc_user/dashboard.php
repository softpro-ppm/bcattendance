<?php
$pageTitle = "Dashboard";
$breadcrumbs = [
    ['title' => 'Dashboard']
];

require_once 'includes/header.php';

// Get TC info from session
$tc_id = $_SESSION['tc_user_training_center_id'];
$mandal_id = $_SESSION['tc_user_mandal_id'];
$today = date('Y-m-d');

// Get basic stats with error handling
try {
    // Get batch count
    $batches_count_query = "SELECT COUNT(*) as count FROM batches WHERE tc_id = ? AND status = 'active'";
    $batches_count_result = fetchRow($batches_count_query, [$tc_id], 'i');
    $batches_count = $batches_count_result ? $batches_count_result['count'] : 0;

    // Get total students
    $beneficiaries_query = "SELECT COUNT(*) as count FROM beneficiaries ben 
                           JOIN batches b ON ben.batch_id = b.id 
                           WHERE b.tc_id = ? AND ben.status = 'active'";
    $beneficiaries_result = fetchRow($beneficiaries_query, [$tc_id], 'i');
    $beneficiaries_count = $beneficiaries_result ? $beneficiaries_result['count'] : 0;

    // Get today's attendance stats
    $attendance_stats_query = "SELECT COUNT(*) as total_marked,
                              SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                              SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count
                             FROM attendance a
                             JOIN beneficiaries ben ON a.beneficiary_id = ben.id
                             JOIN batches b ON ben.batch_id = b.id
                             WHERE b.tc_id = ? AND a.attendance_date = ?";
    $attendance_stats_result = fetchRow($attendance_stats_query, [$tc_id, $today], 'is');
    $attendance_stats = $attendance_stats_result ?: ['total_marked' => 0, 'present_count' => 0, 'absent_count' => 0];

    // Calculate attendance percentage
    $attendance_percentage = $beneficiaries_count > 0 ? 
        round(($attendance_stats['total_marked'] / $beneficiaries_count) * 100, 1) : 0;

} catch (Exception $e) {
    // Fallback values if queries fail
    $batches_count = 0;
    $beneficiaries_count = 0;
    $attendance_stats = ['total_marked' => 0, 'present_count' => 0, 'absent_count' => 0];
    $attendance_percentage = 0;
}
?>

<div class="row">
    <!-- Statistics Cards - EXACT same structure as admin -->
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stats-number" data-stat="total_batches"><?php echo number_format($batches_count); ?></div>
                <div class="stats-label">Active Batches</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number" data-stat="total_students"><?php echo number_format($beneficiaries_count); ?></div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stats-number" data-stat="today_attendance"><?php echo $attendance_stats['total_marked']; ?>/<?php echo $beneficiaries_count; ?></div>
                <div class="stats-label">Today's Attendance</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stats-number" data-stat="attendance_rate"><?php echo $attendance_percentage; ?>%</div>
                <div class="stats-label">Attendance Rate</div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Attendance Section - Beautiful Redesign -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card attendance-overview-card">
            <div class="card-header bg-gradient-primary">
                <h3 class="card-title text-white">
                    <i class="fas fa-calendar-day me-2"></i>
                    Today's Attendance Overview
                </h3>
                <div class="card-tools">
                    <a href="attendance.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i> Mark Attendance
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if ($attendance_stats['total_marked'] == 0): ?>
                    <!-- No Attendance State -->
                    <div class="text-center py-5">
                        <div class="no-attendance-icon mb-4">
                            <i class="fas fa-calendar-times"></i>
                        </div>
                        <h4 class="text-muted mb-3">No attendance marked yet for today</h4>
                        <p class="text-muted mb-4">Start marking attendance to see beautiful statistics and progress tracking</p>
                        <a href="attendance.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i> Begin Marking Attendance
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Attendance Statistics -->
                    <div class="row">
                        <!-- Present Students Card -->
                        <div class="col-md-4 mb-4">
                            <div class="attendance-stat-card present-card">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $attendance_stats['present_count']; ?></div>
                                    <div class="stat-label">Present Today</div>
                                    <div class="stat-percentage">
                                        <?php 
                                        $present_percentage = $attendance_stats['total_marked'] > 0 ? 
                                            round(($attendance_stats['present_count'] / $attendance_stats['total_marked']) * 100, 1) : 0;
                                        echo $present_percentage . '%';
                                        ?>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo $present_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Absent Students Card -->
                        <div class="col-md-4 mb-4">
                            <div class="attendance-stat-card absent-card">
                                <div class="stat-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $attendance_stats['absent_count']; ?></div>
                                    <div class="stat-label">Absent Today</div>
                                    <div class="stat-percentage">
                                        <?php 
                                        $absent_percentage = $attendance_stats['total_marked'] > 0 ? 
                                            round(($attendance_stats['absent_count'] / $attendance_stats['total_marked']) * 100, 1) : 0;
                                        echo $absent_percentage . '%';
                                        ?>
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress">
                                        <div class="progress-bar bg-danger" style="width: <?php echo $absent_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Marked Card -->
                        <div class="col-md-4 mb-4">
                            <div class="attendance-stat-card total-card">
                                <div class="stat-icon">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $attendance_stats['total_marked']; ?></div>
                                    <div class="stat-label">Total Marked</div>
                                    <div class="stat-percentage">
                                        <?php echo $attendance_percentage; ?>% of Total
                                    </div>
                                </div>
                                <div class="stat-progress">
                                    <div class="progress">
                                        <div class="progress-bar bg-primary" style="width: <?php echo $attendance_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Overall Progress Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="overall-progress-section">
                                <h5 class="text-muted mb-3">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Overall Attendance Progress
                                </h5>
                                <div class="progress-group">
                                    <div class="progress-header">
                                        <span class="progress-label">Attendance Completion</span>
                                        <span class="progress-value"><?php echo $attendance_percentage; ?>%</span>
                                    </div>
                                    <div class="progress progress-lg">
                                        <div class="progress-bar bg-gradient-primary" 
                                             style="width: <?php echo $attendance_percentage; ?>%"
                                             data-width="<?php echo $attendance_percentage; ?>">
                                        </div>
                                    </div>
                                    <div class="progress-stats">
                                        <small class="text-muted">
                                            <?php echo $attendance_stats['total_marked']; ?> out of <?php echo $beneficiaries_count; ?> students marked
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Today's Batch Attendance Status -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card batch-status-card">
            <div class="card-header bg-gradient-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-list me-2"></i>
                    Today's Batch Attendance Status
                </h3>
                <div class="card-tools">
                    <a href="attendance.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus me-1"></i> Mark Attendance
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <?php
                // Get batch attendance status for today
                $batch_status_query = "SELECT 
                                        b.id as batch_id,
                                        b.name as batch_name,
                                        COUNT(ben.id) as total_beneficiaries,
                                        COUNT(a.id) as marked_attendance,
                                        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                                        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                                        CASE 
                                            WHEN COUNT(a.id) > 0 THEN 'submitted'
                                            ELSE 'pending'
                                        END as status
                                       FROM batches b
                                       LEFT JOIN beneficiaries ben ON b.id = ben.batch_id AND ben.status = 'active'
                                       LEFT JOIN attendance a ON ben.id = a.beneficiary_id AND a.attendance_date = ?
                                       WHERE b.tc_id = ? AND b.status IN ('active', 'completed')
                                       GROUP BY b.id, b.name
                                       ORDER BY 
                                           CASE WHEN COUNT(a.id) > 0 THEN 0 ELSE 1 END,
                                           b.name";
                $batch_status = fetchAll($batch_status_query, [$today, $tc_id], 'si') ?: [];
                ?>
                
                <?php if (!empty($batch_status)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover batch-status-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 60px;">#</th>
                                    <th>Batch Name</th>
                                    <th class="text-center">Progress</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($batch_status as $index => $batch): ?>
                                <tr class="batch-row">
                                    <td class="text-center">
                                        <span class="batch-number"><?php echo $index + 1; ?></span>
                                    </td>
                                    <td>
                                        <div class="batch-info">
                                            <strong class="batch-name"><?php echo htmlspecialchars($batch['batch_name']); ?></strong>
                                            <small class="text-muted d-block">
                                                <?php echo $batch['total_beneficiaries']; ?> students
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="progress-wrapper">
                                            <div class="progress progress-sm">
                                                <?php 
                                                $batch_progress = $batch['total_beneficiaries'] > 0 ? 
                                                    ($batch['marked_attendance'] / $batch['total_beneficiaries']) * 100 : 0;
                                                ?>
                                                <div class="progress-bar bg-info" style="width: <?php echo $batch_progress; ?>%"></div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo $batch['marked_attendance']; ?>/<?php echo $batch['total_beneficiaries']; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success badge-lg">
                                            <?php echo $batch['present_count']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-danger badge-lg">
                                            <?php echo $batch['absent_count']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($batch['status'] == 'submitted'): ?>
                                            <span class="badge badge-success badge-lg">
                                                <i class="fas fa-check me-1"></i>Submitted
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-warning badge-lg">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="attendance.php?batch_id=<?php echo $batch['batch_id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i> 
                                            <?php echo $batch['status'] == 'submitted' ? 'Edit' : 'Mark'; ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="no-batches-icon mb-4">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h5 class="text-muted mb-3">No active batches found</h5>
                        <p class="text-muted">Contact your administrator to set up training batches.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS for Beautiful Attendance Dashboard -->
<style>
/* Attendance Overview Card */
.attendance-overview-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.attendance-overview-card .card-header {
    border: none;
    padding: 1.5rem;
}

.attendance-overview-card .card-header h3 {
    margin: 0;
    font-weight: 600;
}

/* No Attendance State */
.no-attendance-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: #6c757d;
    font-size: 3rem;
}

/* Attendance Stat Cards */
.attendance-stat-card {
    background: #fff;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #f8f9fa;
    position: relative;
    overflow: hidden;
}

.attendance-stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.attendance-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
}

.present-card::before {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
}

.absent-card::before {
    background: linear-gradient(90deg, #dc3545 0%, #fd7e14 100%);
}

.total-card::before {
    background: linear-gradient(90deg, #007bff 0%, #6610f2 100%);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    color: #fff;
}

.present-card .stat-icon {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.absent-card .stat-icon {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
}

.total-card .stat-icon {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
}

.stat-content {
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 1rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.stat-percentage {
    font-size: 0.9rem;
    color: #495057;
    font-weight: 600;
}

.stat-progress {
    margin-top: 1rem;
}

.stat-progress .progress {
    height: 8px;
    border-radius: 10px;
    background-color: #f8f9fa;
}

.stat-progress .progress-bar {
    border-radius: 10px;
    transition: width 1s ease;
}

/* Overall Progress Section */
.overall-progress-section {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 2rem;
    border: 1px solid #e9ecef;
}

.progress-group {
    margin-bottom: 0;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.progress-label {
    font-weight: 600;
    color: #495057;
}

.progress-value {
    font-weight: 700;
    color: #007bff;
    font-size: 1.1rem;
}

.progress-lg {
    height: 12px;
    border-radius: 10px;
    background-color: #e9ecef;
}

.progress-stats {
    margin-top: 0.75rem;
    text-align: center;
}

/* Batch Status Card */
.batch-status-card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.batch-status-card .card-header {
    border: none;
    padding: 1.5rem;
}

.batch-status-card .card-header h3 {
    margin: 0;
    font-weight: 600;
}

.batch-status-table {
    margin-bottom: 0;
}

.batch-status-table th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
    padding: 1.25rem 0.75rem;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.batch-status-table td {
    padding: 1.25rem 0.75rem;
    vertical-align: middle;
    border-top: 1px solid #f8f9fa;
}

.batch-row:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
    transition: all 0.2s ease;
}

.batch-number {
    display: inline-block;
    width: 30px;
    height: 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 50%;
    text-align: center;
    line-height: 30px;
    font-weight: 600;
    font-size: 0.9rem;
}

.batch-info .batch-name {
    color: #2c3e50;
    font-size: 1.1rem;
}

.progress-wrapper {
    text-align: center;
}

.progress-wrapper .progress {
    height: 8px;
    border-radius: 10px;
    background-color: #e9ecef;
    margin-bottom: 0.5rem;
}

.progress-wrapper .progress-bar {
    border-radius: 10px;
    transition: width 1s ease;
}

.badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
}

.no-batches-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: #6c757d;
    font-size: 3rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .attendance-stat-card {
        margin-bottom: 1rem;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .overall-progress-section {
        padding: 1.5rem;
    }
    
    .batch-status-table th,
    .batch-status-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }
    
    .batch-number {
        width: 25px;
        height: 25px;
        line-height: 25px;
        font-size: 0.8rem;
    }
}

/* Animation for progress bars */
.progress-bar {
    animation: progressAnimation 1.5s ease-out;
}

@keyframes progressAnimation {
    from { width: 0%; }
    to { width: var(--progress-width); }
}
</style>

<!-- JavaScript for Enhanced Interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate progress bars on page load
    const progressBars = document.querySelectorAll('.progress-bar');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 300);
    });
    
    // Add hover effects to attendance stat cards
    const statCards = document.querySelectorAll('.attendance-stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add hover effects to batch rows
    const batchRows = document.querySelectorAll('.batch-row');
    batchRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>