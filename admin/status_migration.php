<?php
$pageTitle = 'Attendance Status Migration';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Status Migration']
];

require_once '../includes/header.php';

$migrationComplete = false;
$migrationResults = [];

// Handle migration execution
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['run_migration'])) {
    try {
        // Get current status distribution
        $currentStats = fetchAll("
            SELECT 
                status,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
            FROM attendance 
            GROUP BY status
            ORDER BY count DESC
        ");
        
        // Check what needs to be updated
        $toUpdate = fetchAll("
            SELECT 
                status as current_status,
                CASE 
                    WHEN status = 'P' THEN 'present'
                    WHEN status = 'A' THEN 'absent'
                    WHEN status = 'H' THEN 'absent'
                    ELSE status
                END as new_status,
                COUNT(*) as record_count
            FROM attendance 
            WHERE status IN ('P', 'A', 'H')
            GROUP BY status
        ");
        
        if (!empty($toUpdate)) {
            // Run the migration
            $updateQuery = "
                UPDATE attendance 
                SET status = CASE 
                    WHEN status = 'P' THEN 'present'
                    WHEN status = 'A' THEN 'absent'
                    WHEN status = 'H' THEN 'absent'
                    ELSE status
                END
                WHERE status IN ('P', 'A', 'H')
            ";
            
            $affectedRows = executeQuery($updateQuery);
            
            // Get post-migration stats
            $newStats = fetchAll("
                SELECT 
                    status,
                    COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
                FROM attendance 
                GROUP BY status
                ORDER BY count DESC
            ");
            
            // Final summary
            $finalSummary = fetchRow("
                SELECT 
                    COUNT(*) as total_records,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                    ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) as attendance_percentage
                FROM attendance
            ");
            
            $migrationResults = [
                'before' => $currentStats,
                'changes' => $toUpdate,
                'affected_rows' => $affectedRows,
                'after' => $newStats,
                'summary' => $finalSummary
            ];
            
            $migrationComplete = true;
            setSuccessMessage("Migration completed successfully! Updated $affectedRows records.");
        } else {
            setInfoMessage("No migration needed - all status values are already standardized.");
        }
        
    } catch (Exception $e) {
        setErrorMessage("Migration failed: " . $e->getMessage());
    }
}

// Get current status overview for display
$currentOverview = fetchAll("
    SELECT 
        status,
        COUNT(*) as count,
        ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM attendance)), 2) as percentage
    FROM attendance 
    GROUP BY status
    ORDER BY count DESC
");

$needsMigration = fetchRow("
    SELECT COUNT(*) as count 
    FROM attendance 
    WHERE status IN ('P', 'A', 'H')
");
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sync-alt"></i>
                    Attendance Status Standardization
                </h3>
            </div>
            <div class="card-body">
                
                <?php if (!$migrationComplete): ?>
                
                <div class="alert alert-info">
                    <h5><i class="icon fas fa-info"></i> Migration Purpose</h5>
                    This migration standardizes all attendance status values to ensure consistent reporting:
                    <ul class="mb-0 mt-2">
                        <li><code>'P'</code> → <code>'present'</code></li>
                        <li><code>'A'</code> → <code>'absent'</code></li>
                        <li><code>'H'</code> → <code>'absent'</code> (holidays treated as absent)</li>
                    </ul>
                </div>

                <h5>Current Status Distribution:</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentOverview as $stat): ?>
                            <tr>
                                <td>
                                    <code><?php echo htmlspecialchars($stat['status']); ?></code>
                                    <?php if (in_array($stat['status'], ['P', 'A', 'H'])): ?>
                                        <span class="badge badge-warning">Needs Migration</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($stat['count']); ?></td>
                                <td><?php echo $stat['percentage']; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($needsMigration['count'] > 0): ?>
                <div class="alert alert-warning">
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Migration Required</h5>
                    <strong><?php echo number_format($needsMigration['count']); ?></strong> records need to be migrated to standardized format.
                    <br><small>This will fix your dashboard statistics and ensure accurate reports.</small>
                </div>

                <form method="POST">
                    <div class="text-center">
                        <button type="submit" name="run_migration" class="btn btn-primary btn-lg">
                            <i class="fas fa-sync-alt"></i> Run Migration
                        </button>
                    </div>
                </form>
                
                <?php else: ?>
                <div class="alert alert-success">
                    <h5><i class="icon fas fa-check"></i> No Migration Needed</h5>
                    All attendance status values are already standardized!
                </div>
                <?php endif; ?>

                <?php else: ?>
                
                <!-- Migration Results -->
                <div class="alert alert-success">
                    <h5><i class="icon fas fa-check"></i> Migration Completed Successfully!</h5>
                    Updated <strong><?php echo number_format($migrationResults['affected_rows']); ?></strong> records.
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h5>Changes Made:</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Records</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($migrationResults['changes'] as $change): ?>
                                <tr>
                                    <td><code><?php echo $change['current_status']; ?></code></td>
                                    <td><code><?php echo $change['new_status']; ?></code></td>
                                    <td><?php echo number_format($change['record_count']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Final Summary:</h5>
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td>Total Records:</td>
                                    <td><strong><?php echo number_format($migrationResults['summary']['total_records']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Present:</td>
                                    <td><strong><?php echo number_format($migrationResults['summary']['present_count']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Absent:</td>
                                    <td><strong><?php echo number_format($migrationResults['summary']['absent_count']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Attendance Rate:</td>
                                    <td><strong><?php echo $migrationResults['summary']['attendance_percentage']; ?>%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-success">
                        <i class="fas fa-chart-line"></i> View Updated Dashboard
                    </a>
                    <a href="reports.php" class="btn btn-info ml-2">
                        <i class="fas fa-file-alt"></i> Check Reports
                    </a>
                </div>

                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
