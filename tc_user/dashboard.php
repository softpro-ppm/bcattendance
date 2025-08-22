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

<!-- Statistics Cards - Mobile Optimized -->
<div class="row dashboard-stats">
    <div class="col-6 col-md-3">
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

    <div class="col-6 col-md-3">
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

    <div class="col-6 col-md-3">
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

    <div class="col-6 col-md-3">
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

<!-- Quick Actions Section - Mobile Optimized -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="row dashboard-quick-actions">
                    <div class="col-6 col-md-3">
                        <a href="attendance.php" class="btn btn-primary btn-block">
                            <i class="fas fa-calendar-check"></i><br>
                            <span class="d-none d-sm-inline">Mark Attendance</span>
                            <span class="d-sm-none">Attendance</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="students.php" class="btn btn-info btn-block">
                            <i class="fas fa-user-graduate"></i><br>
                            <span class="d-none d-sm-inline">View Students</span>
                            <span class="d-sm-none">Students</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="batches.php" class="btn btn-success btn-block">
                            <i class="fas fa-layer-group"></i><br>
                            <span class="d-none d-sm-inline">View Batches</span>
                            <span class="d-sm-none">Batches</span>
                        </a>
                    </div>
                    <div class="col-6 col-md-3">
                        <a href="reports.php" class="btn btn-warning btn-block">
                            <i class="fas fa-chart-line"></i><br>
                            <span class="d-none d-sm-inline">View Reports</span>
                            <span class="d-sm-none">Reports</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Today's Attendance Summary - Mobile Optimized -->
<div class="row">
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check"></i>
                    Today's Attendance Summary
                </h3>
            </div>
            <div class="card-body">
                <div class="row attendance-summary">
                    <div class="col-4 col-sm-4">
                        <div class="text-center">
                            <h3 class="text-success"><?php echo number_format($attendance_stats['present_count']); ?></h3>
                            <p class="mb-0">Present</p>
                        </div>
                    </div>
                    <div class="col-4 col-sm-4">
                        <div class="text-center">
                            <h3 class="text-danger"><?php echo number_format($attendance_stats['absent_count']); ?></h3>
                            <p class="mb-0">Absent</p>
                        </div>
                    </div>
                    <div class="col-4 col-sm-4">
                        <div class="text-center">
                            <h3 class="text-primary"><?php echo number_format($attendance_stats['total_marked']); ?></h3>
                            <p class="mb-0">Total Marked</p>
                        </div>
                    </div>
                </div>
                
                <?php if ($beneficiaries_count > 0): ?>
                <div class="mt-3">
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-success" 
                             role="progressbar" 
                             style="width: <?php echo $attendance_percentage; ?>%" 
                             aria-valuenow="<?php echo $attendance_percentage; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                            <?php echo $attendance_percentage; ?>%
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block text-center">
                        <?php echo number_format($attendance_stats['total_marked']); ?> out of <?php echo number_format($beneficiaries_count); ?> students marked
                    </small>
                </div>
                <?php endif; ?>
                
                <div class="mt-3 text-center">
                    <a href="attendance.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Manage Attendance
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity - Mobile Optimized -->
    <div class="col-12 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history"></i>
                    Recent Activity
                </h3>
            </div>
            <div class="card-body">
                <?php
                // Get recent attendance entries
                try {
                    $recent_query = "SELECT a.attendance_date, a.status, ben.full_name, b.name as batch_name
                                   FROM attendance a
                                   JOIN beneficiaries ben ON a.beneficiary_id = ben.id
                                   JOIN batches b ON ben.batch_id = b.id
                                   WHERE b.tc_id = ? 
                                   ORDER BY a.created_at DESC 
                                   LIMIT 5";
                    $recent_activities = fetchAll($recent_query, [$tc_id], 'i');
                } catch (Exception $e) {
                    $recent_activities = [];
                }
                ?>
                
                <?php if (!empty($recent_activities)): ?>
                <div class="recent-activities">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="activity-item d-flex align-items-center mb-2 p-2 border-bottom">
                        <div class="activity-icon mr-3">
                            <?php if ($activity['status'] === 'present'): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-details flex-grow-1">
                            <div class="activity-text">
                                <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong> 
                                marked as <strong><?php echo ucfirst($activity['status']); ?></strong>
                            </div>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($activity['batch_name']); ?> • 
                                <?php echo date('M j', strtotime($activity['attendance_date'])); ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <i class="fas fa-info-circle fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No recent activity</p>
                </div>
                <?php endif; ?>
                
                <div class="mt-3 text-center">
                    <a href="reports.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-chart-line"></i> View Full Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Training Center Information - Mobile Optimized -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-building"></i>
                    Training Center Information
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-id-card text-primary"></i> TC ID:</strong>
                            <span class="ml-2"><?php echo htmlspecialchars($_SESSION['tc_user_tc_id']); ?></span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-building text-success"></i> Training Center:</strong>
                            <span class="ml-2"><?php echo htmlspecialchars($_SESSION['tc_user_training_center_name']); ?></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-map-marker-alt text-warning"></i> Mandal:</strong>
                            <span class="ml-2"><?php echo htmlspecialchars($_SESSION['tc_user_mandal_name']); ?></span>
                        </div>
                        <div class="info-item mb-3">
                            <strong><i class="fas fa-calendar text-info"></i> Today:</strong>
                            <span class="ml-2"><?php echo date('F j, Y'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 text-center">
                    <a href="profile.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-user-cog"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard Mobile Styles */
.dashboard-stats .row {
    margin: 0 -0.25rem;
}

.dashboard-stats .col-6 {
    padding: 0.25rem;
    margin-bottom: 0.5rem;
}

.dashboard-stats .stats-card {
    margin-bottom: 0;
    height: 100%;
    transition: transform 0.3s ease;
}

.dashboard-stats .stats-card:hover {
    transform: translateY(-2px);
}

.dashboard-stats .stats-card .card-body {
    padding: 1rem;
    text-align: center;
    position: relative;
}

.dashboard-stats .stats-number {
    font-size: 1.8rem;
    margin-bottom: 0.25rem;
    font-weight: 700;
}

.dashboard-stats .stats-label {
    font-size: 0.85rem;
    margin-bottom: 0;
    color: rgba(255, 255, 255, 0.9);
}

.dashboard-stats .stats-icon {
    position: absolute;
    right: 0.5rem;
    top: 0.5rem;
    font-size: 1.5rem;
    opacity: 0.3;
}

/* Quick Actions Mobile Styles */
.dashboard-quick-actions .col-6 {
    padding: 0.25rem;
    margin-bottom: 0.5rem;
}

.dashboard-quick-actions .btn {
    padding: 1rem 0.5rem;
    height: auto;
    white-space: normal;
    line-height: 1.2;
    min-height: 80px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.dashboard-quick-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.dashboard-quick-actions .btn i {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 1.5rem;
}

/* Attendance Summary Mobile Styles */
.attendance-summary .col-4,
.attendance-summary .col-6 {
    padding: 0.25rem;
}

.attendance-summary h3 {
    font-size: 1.5rem;
    margin-bottom: 0.25rem;
    font-weight: 700;
}

.attendance-summary p {
    font-size: 0.85rem;
    margin-bottom: 0;
}

/* Recent Activities Mobile Styles */
.activity-item {
    border-radius: 8px;
    transition: background-color 0.2s;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-icon {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.activity-text {
    font-size: 0.9rem;
    line-height: 1.3;
}

/* Info Items Mobile Styles */
.info-item {
    padding: 0.5rem;
    border-radius: 6px;
    background-color: #f8f9fa;
    border-left: 3px solid #28a745;
}

.info-item strong {
    color: #495057;
}

.info-item i {
    width: 20px;
    text-align: center;
}

/* Progress Bar Mobile Styles */
.progress {
    border-radius: 12px;
    background-color: #e9ecef;
}

.progress-bar {
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Mobile Responsive Adjustments */
@media (max-width: 768px) {
    .dashboard-stats .col-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    .dashboard-stats .stats-card {
        margin-bottom: 0.5rem;
    }
    
    .dashboard-stats .stats-number {
        font-size: 1.5rem;
    }
    
    .dashboard-stats .stats-icon {
        font-size: 1.2rem;
        right: 0.25rem;
        top: 0.25rem;
    }
    
    .dashboard-quick-actions .col-6 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    .dashboard-quick-actions .btn {
        min-height: 70px;
        padding: 0.75rem 0.5rem;
    }
    
    .dashboard-quick-actions .btn i {
        font-size: 1.2rem;
        margin-bottom: 0.25rem;
    }
    
    .attendance-summary h3 {
        font-size: 1.3rem;
    }
    
    .info-item {
        margin-bottom: 0.75rem;
        padding: 0.75rem;
    }
}

@media (max-width: 576px) {
    .dashboard-stats .stats-number {
        font-size: 1.3rem;
    }
    
    .dashboard-stats .stats-label {
        font-size: 0.75rem;
    }
    
    .dashboard-stats .stats-icon {
        font-size: 1rem;
    }
    
    .dashboard-quick-actions .btn {
        min-height: 60px;
        padding: 0.5rem 0.25rem;
    }
    
    .dashboard-quick-actions .btn i {
        font-size: 1rem;
    }
    
    .attendance-summary h3 {
        font-size: 1.2rem;
    }
    
    .activity-text {
        font-size: 0.85rem;
    }
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    .dashboard-stats .stats-card {
        min-height: 80px;
        touch-action: manipulation;
    }
    
    .dashboard-quick-actions .btn {
        min-height: 80px;
        touch-action: manipulation;
    }
    
    .activity-item {
        min-height: 44px;
    }
}

/* Landscape Orientation */
@media (max-width: 768px) and (orientation: landscape) {
    .dashboard-stats .stats-number {
        font-size: 1.4rem;
    }
    
    .dashboard-quick-actions .btn {
        min-height: 60px;
    }
}
</style>

<script>
// Mobile Dashboard Enhancement
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Dashboard loaded, checking mobile status...');
    console.log('📱 Viewport width:', window.innerWidth);
    console.log('📱 Viewport height:', window.innerHeight);
    
    // Force display of dashboard elements on mobile
    if (window.innerWidth <= 768) {
        console.log('📱 Mobile device detected, ensuring dashboard visibility...');
        
        // Add visual debug indicators
        document.body.insertAdjacentHTML('afterbegin', 
            '<div style="background: #ffeb3b; color: #000; padding: 10px; text-align: center; font-weight: bold; position: fixed; top: 0; left: 0; right: 0; z-index: 9999;">📱 MOBILE MODE ACTIVE - Dashboard should be visible</div>'
        );
        
        // Force display of all dashboard elements
        const dashboardElements = [
            '.dashboard-stats',
            '.dashboard-quick-actions', 
            '.attendance-summary',
            '.recent-activities',
            '.info-item'
        ];
        
        dashboardElements.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            console.log(`🔍 Looking for ${selector}:`, elements.length, 'elements found');
            elements.forEach((el, index) => {
                el.style.display = 'block';
                el.style.visibility = 'visible';
                el.style.opacity = '1';
                el.style.border = '2px solid red'; // Debug border
                console.log(`✅ Made ${selector}[${index}] visible`);
            });
        });
        
        // Ensure stats cards are properly styled
        const statsCards = document.querySelectorAll('.stats-card');
        console.log('🔍 Stats cards found:', statsCards.length);
        statsCards.forEach((card, index) => {
            card.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            card.style.color = 'white';
            card.style.display = 'block';
            card.style.border = '2px solid green'; // Debug border
            console.log(`✅ Styled stats card[${index}]`);
        });
        
        // Ensure quick action buttons are visible
        const quickActionBtns = document.querySelectorAll('.dashboard-quick-actions .btn');
        console.log('🔍 Quick action buttons found:', quickActionBtns.length);
        quickActionBtns.forEach((btn, index) => {
            btn.style.display = 'flex';
            btn.style.visibility = 'visible';
            btn.style.opacity = '1';
            btn.style.border = '2px solid blue'; // Debug border
            console.log(`✅ Made quick action button[${index}] visible`);
        });
        
        // Check if elements are actually visible
        setTimeout(() => {
            console.log('🔍 Final visibility check:');
            dashboardElements.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach((el, index) => {
                    const rect = el.getBoundingClientRect();
                    const isVisible = rect.width > 0 && rect.height > 0;
                    console.log(`${selector}[${index}] visible:`, isVisible, 'dimensions:', rect.width, 'x', rect.height);
                });
            });
        }, 1000);
        
        console.log('📱 Mobile dashboard enhancement complete');
    } else {
        console.log('🖥️ Desktop mode detected');
    }
    
    // Handle orientation change
    window.addEventListener('orientationchange', function() {
        console.log('📱 Orientation changed, reloading...');
        setTimeout(function() {
            location.reload();
        }, 500);
    });
    
    // Handle resize
    window.addEventListener('resize', function() {
        console.log('📱 Window resized to:', window.innerWidth, 'x', window.innerHeight);
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>