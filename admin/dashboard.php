<?php
$pageTitle = 'Dashboard';
$breadcrumbs = [
    ['title' => 'Dashboard']
];

// No additional JS needed for simple percentage cards

require_once '../includes/header.php';

// Check and mark completed batches automatically
$batchCompletionResult = checkAndMarkCompletedBatches();

// Get dashboard statistics
$stats = getDashboardStats();

// Get batch attendance marking status for today
$batchMarkingStatus = fetchAll("
    SELECT 
        bt.id as batch_id,
        bt.name as batch_name,
        c.name as constituency_name,
        m.name as mandal_name,
        COUNT(b.id) as total_beneficiaries,
        COUNT(CASE WHEN b.status = 'active' THEN 1 END) as active_beneficiaries,
        COUNT(a.id) as marked_attendance,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        CASE 
            WHEN COUNT(a.id) > 0 THEN 'submitted'
            ELSE 'pending'
        END as status,
        MAX(a.created_at) as last_marked_time
    FROM batches bt
    LEFT JOIN mandals m ON bt.mandal_id = m.id
    LEFT JOIN constituencies c ON m.constituency_id = c.id
    LEFT JOIN beneficiaries b ON bt.id = b.batch_id
    LEFT JOIN attendance a ON b.id = a.beneficiary_id AND a.attendance_date = CURDATE()
    WHERE bt.status IN ('active', 'completed')
    GROUP BY bt.id, bt.name, c.name, m.name
    ORDER BY 
        CASE WHEN COUNT(a.id) > 0 THEN 0 ELSE 1 END,
        MAX(a.created_at) DESC,
        bt.name
");

// Simple dashboard - no complex queries needed for percentage cards
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number" data-stat="total_beneficiaries"><?php echo number_format($stats['total_beneficiaries']); ?></div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stats-number" data-stat="ongoing_students"><?php echo number_format($stats['ongoing_students']); ?></div>
                <div class="stats-label">Ongoing Students</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stats-number" data-stat="completed_students"><?php echo number_format($stats['completed_students']); ?></div>
                <div class="stats-label">Completed Students</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stats-number" data-stat="total_batches"><?php echo number_format($stats['active_batches']); ?>/<?php echo number_format($stats['total_batches']); ?></div>
                <div class="stats-label">Active/Total Batches</div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Completion Notifications -->
<?php if (isset($batchCompletionResult) && $batchCompletionResult['success'] && $batchCompletionResult['count'] > 0): ?>
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Batch Update:</strong> <?php echo htmlspecialchars($batchCompletionResult['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Today's Attendance -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-calendar-check"></i>
                    Today's Attendance
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-success" data-stat="present_today"><?php echo number_format($stats['present_today']); ?></h3>
                            <p class="mb-0">Present</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-danger" data-stat="absent_today"><?php echo number_format($stats['absent_today']); ?></h3>
                            <p class="mb-0">Absent</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-primary" data-stat="today_attendance"><?php echo number_format($stats['today_attendance']); ?></h3>
                            <p class="mb-0">Total Marked</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="attendance.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Daily Attendance
                    </a>
                </div>
                
                <!-- Attendance Information Note -->
                <div class="mt-2 p-2 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> These counts show attendance only for <strong>Active Students</strong> (students currently enrolled in active batches).
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Today's Attendance Summary -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-percentage"></i>
                    Today's Attendance Summary
                </h3>
            </div>
            <div class="card-body">
                <?php
                // Calculate today's percentages based on ACTIVE students only
                $activeStudentsToday = $stats['active_students_today'] ?? 0;
                $todayPresent = $stats['present_today'];
                $todayAbsent = $stats['absent_today'];
                
                // Calculate percentages based on active students (not total marked)
                $presentPercentage = $activeStudentsToday > 0 ? round(($todayPresent / $activeStudentsToday) * 100, 1) : 0;
                $absentPercentage = $activeStudentsToday > 0 ? round(($todayAbsent / $activeStudentsToday) * 100, 1) : 0;
                ?>
                
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-success" data-stat="present_percentage"><?php echo $presentPercentage; ?>%</h3>
                            <p class="mb-1">Present</p>
                            <small class="text-muted"><?php echo number_format($todayPresent); ?> out of <?php echo number_format($activeStudentsToday); ?> active</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-danger" data-stat="absent_percentage"><?php echo $absentPercentage; ?>%</h3>
                            <p class="mb-1">Absent</p>
                            <small class="text-muted"><?php echo number_format($todayAbsent); ?> out of <?php echo number_format($activeStudentsToday); ?> active</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <h3 class="text-info" data-stat="total_students"><?php echo number_format($stats['total_beneficiaries']); ?></h3>
                            <p class="mb-1">Total Students</p>
                            <small class="text-muted">All students (active + completed + inactive)</small>
                        </div>
                    </div>
                </div>
                
                <?php if ($activeStudentsToday === 0): ?>
                <div class="text-center mt-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No active students found for today.
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Summary Information -->
                <div class="mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i>
                        <strong>Note:</strong> Attendance percentages are calculated from <strong>Active Students only</strong>. 
                        Total Students includes all enrolled students regardless of status.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Batch Attendance Marking Status -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tasks"></i>
                    Today's Batch Attendance Status
                </h3>
                <div class="card-tools">
                    <a href="attendance.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Daily Attendance
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($batchMarkingStatus)): ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="batchStatusTable">
                        <thead>
                            <tr>
                                <th>Sl. No.</th>
                                <th>Mandal</th>
                                <th>Batch Name</th>
                                <th>Total Students</th>
                                <th>Active Students</th>
                                <th>Total Marked</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batchMarkingStatus as $index => $batch): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($batch['mandal_name'] ?? 'N/A'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($batch['batch_name']); ?></td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo number_format($batch['total_beneficiaries']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo number_format($batch['active_beneficiaries']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo number_format($batch['marked_attendance']); ?>/<?php echo number_format($batch['total_beneficiaries']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success"><?php echo number_format($batch['present_count']); ?></span>
                                </td>
                                <td>
                                    <span class="badge badge-danger"><?php echo number_format($batch['absent_count']); ?></span>
                                </td>
                                <td>
                                    <?php if ($batch['status'] === 'submitted'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-clock"></i> Not Yet Submitted
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($batch['last_marked_time']): ?>
                                        <small class="text-success">
                                            <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($batch['last_marked_time'])); ?>
                                        </small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="attendance.php?batch=<?php echo $batch['batch_id']; ?>&date=<?php echo date('Y-m-d'); ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No active batches found.</p>
                    <a href="batches.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Manage Batches
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="beneficiaries.php?action=add" class="btn btn-success btn-block">
                            <i class="fas fa-user-plus"></i><br>
                            Add New Student
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="attendance.php" class="btn btn-primary btn-block">
                            <i class="fas fa-calendar-check"></i><br>
                            Daily Attendance
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="reports.php" class="btn btn-info btn-block">
                            <i class="fas fa-chart-bar"></i><br>
                            Attendance Reports
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="settings.php" class="btn btn-warning btn-block">
                            <i class="fas fa-cogs"></i><br>
                            System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$inlineJS = "
    // Live dashboard updates every 30 seconds
    let updateInProgress = false;
    let lastUpdateTime = null;
    
    function updateDashboardData() {
        if (updateInProgress) return;
        
        updateInProgress = true;
        showLoadingIndicator();
        
        fetch('get_dashboard_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStatistics(data.stats);
                    updateBatchStatusTable(data.batchStatus);
                    showUpdateNotification('Dashboard updated with latest data');
                    lastUpdateTime = new Date();
                } else {
                    console.warn('Update failed:', data.error);
                }
            })
            .catch(error => {
                console.log('Update check failed:', error);
                showUpdateNotification('Update failed - will retry automatically', 'warning');
            })
            .finally(() => {
                updateInProgress = false;
                hideLoadingIndicator();
            });
    }
    
    function updateStatistics(stats) {
        // Update main stats cards
        updateElement('[data-stat=\"total_beneficiaries\"]', stats.total_beneficiaries);
        updateElement('[data-stat=\"total_constituencies\"]', stats.total_constituencies);
        updateElement('[data-stat=\"total_mandals\"]', stats.total_mandals);
        updateElement('[data-stat=\"total_batches\"]', stats.total_batches);
        
        // Update today's attendance
        updateElement('[data-stat=\"present_today\"]', stats.present_today);
        updateElement('[data-stat=\"absent_today\"]', stats.absent_today);
        updateElement('[data-stat=\"today_attendance\"]', stats.today_attendance);
        
        // Update percentages based on active students
        updateElement('[data-stat=\"present_percentage\"]', stats.present_percentage + '%');
        updateElement('[data-stat=\"absent_percentage\"]', stats.absent_percentage + '%');
        
        // Update total students metric
        updateElement('[data-stat=\"total_students\"]', stats.total_beneficiaries);
    }
    
    function updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            const formattedValue = typeof value === 'number' ? Number(value).toLocaleString() : value;
            if (element.textContent !== formattedValue) {
                element.style.transform = 'scale(1.1)';
                element.style.transition = 'transform 0.3s ease';
                element.textContent = formattedValue;
                setTimeout(() => {
                    element.style.transform = 'scale(1)';
                }, 300);
            }
        }
    }
    
    function updateBatchStatusTable(batchStatus) {
        const tableBody = document.querySelector('#batchStatusTable tbody');
        if (!tableBody || !batchStatus) return;
        
        let hasChanges = false;
        const currentRows = tableBody.querySelectorAll('tr');
        
        // Simple check for changes (you could make this more sophisticated)
        if (currentRows.length !== batchStatus.length) {
            hasChanges = true;
        }
        
        if (hasChanges) {
            // For now, we'll reload the page to update the table
            // In a more advanced implementation, you'd rebuild the table here
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }
    
    function showUpdateNotification(message, type = 'success') {
        // Remove existing notifications
        document.querySelectorAll('.live-update-notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed live-update-notification';
        notification.style.cssText = 'top: 80px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 8px rgba(0,0,0,0.2);';
        notification.innerHTML = \`
            <i class=\"fas fa-sync-alt\"></i> \${message}
            <button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button>
        \`;
        document.body.appendChild(notification);
        
        // Auto-hide after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 150);
            }
        }, 4000);
    }
    
    function showLoadingIndicator() {
        let indicator = document.querySelector('.live-update-loader');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'live-update-loader position-fixed';
            indicator.style.cssText = 'top: 20px; right: 20px; z-index: 9998; background: rgba(0,0,0,0.8); color: white; padding: 8px 12px; border-radius: 4px; font-size: 12px;';
            indicator.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Updating...';
            document.body.appendChild(indicator);
        }
        indicator.style.display = 'block';
    }
    
    function hideLoadingIndicator() {
        const indicator = document.querySelector('.live-update-loader');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }
    
    // Start live updates every 30 seconds
    const updateInterval = setInterval(updateDashboardData, 30000);
    
    // Show initial notification
    setTimeout(() => {
        showUpdateNotification('Live updates enabled - refreshing every 30 seconds', 'info');
    }, 2000);
    
    // Full page refresh every 10 minutes as backup
    setTimeout(function() {
        window.location.reload();
    }, 600000);
    
    // Add hover effects to stat cards
    document.querySelectorAll('.stats-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Manual refresh button
    function addManualRefreshButton() {
        const refreshBtn = document.createElement('button');
        refreshBtn.className = 'btn btn-outline-primary btn-sm position-fixed';
        refreshBtn.style.cssText = 'bottom: 20px; right: 20px; z-index: 9999;';
        refreshBtn.innerHTML = '<i class=\"fas fa-sync-alt\"></i> Refresh Now';
        refreshBtn.onclick = function() {
            this.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Updating...';
            updateDashboardData();
            setTimeout(() => {
                this.innerHTML = '<i class=\"fas fa-sync-alt\"></i> Refresh Now';
            }, 2000);
        };
        document.body.appendChild(refreshBtn);
    }
    
    // Add manual refresh button after page loads
    setTimeout(addManualRefreshButton, 1000);
";

require_once '../includes/footer.php';
?>
