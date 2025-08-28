<?php
$pageTitle = 'Training Batches';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Training Batches']
];

require_once 'includes/header.php';

// Get TC info from session
$tc_id = $_SESSION['tc_user_training_center_id'];
$mandal_id = $_SESSION['tc_user_mandal_id'];

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$offset = ($current_page - 1) * $records_per_page;

// Build search query for batches
$whereClause = "WHERE b.tc_id = ?";
$params = [$tc_id];
$types = 'i';

if (!empty($search_term)) {
    $whereClause .= " AND (b.name LIKE ? OR b.code LIKE ? OR b.description LIKE ?)";
    $searchParam = "%$search_term%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= 'sss';
}

// Get total count for pagination
$countQuery = "
    SELECT COUNT(*) as total
    FROM batches b
    $whereClause
";
$totalResult = fetchRow($countQuery, $params, $types);
$total_records = $totalResult['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

// Get batches with related information
$query = "
    SELECT 
        b.*,
        m.name as mandal_name,
        c.name as constituency_name,
        tc.tc_id as tc_code,
        tc.name as tc_name,
        COUNT(ben.id) as beneficiary_count
    FROM batches b
    JOIN mandals m ON b.mandal_id = m.id
    JOIN constituencies c ON m.constituency_id = c.id
    JOIN training_centers tc ON b.tc_id = tc.id
    LEFT JOIN beneficiaries ben ON b.id = ben.batch_id AND (ben.status = 'active' OR ben.status = 'completed')
    $whereClause
    GROUP BY b.id
    ORDER BY b.status DESC, b.start_date DESC, b.name
    LIMIT $records_per_page OFFSET $offset
";

$batches = fetchAll($query, $params, $types) ?: [];

// Calculate summary statistics
$stats = [
    'total_batches' => $total_records,
    'active_batches' => 0,
    'completed_batches' => 0,
    'total_students' => 0
];

foreach ($batches as $batch) {
    $stats['total_students'] += $batch['beneficiary_count'];
    if ($batch['status'] == 'active') $stats['active_batches']++;
    if ($batch['status'] == 'completed') $stats['completed_batches']++;
}
?>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stats-number" data-stat="total_batches"><?php echo number_format($stats['total_batches']); ?></div>
                <div class="stats-label">Total Batches</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-play-circle"></i>
                </div>
                <div class="stats-number" data-stat="active_batches"><?php echo number_format($stats['active_batches']); ?></div>
                <div class="stats-label">Active Batches</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-number" data-stat="completed_batches"><?php echo number_format($stats['completed_batches']); ?></div>
                <div class="stats-label">Completed Batches</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number" data-stat="total_students"><?php echo number_format($stats['total_students']); ?></div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-layer-group"></i>
                    Training Batches
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">Read Only Access</span>
                </div>
            </div>
            <div class="card-body">


                <!-- Results Summary -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="text-muted">
                            Showing <?php echo count($batches); ?> of <?php echo $total_records; ?> batches
                        </p>
                    </div>
                    <div class="col-md-6">
                        <div class="float-right">
                            <small class="text-muted">
                                Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Batches Table -->
                <?php if (!empty($batches)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Constituency</th>
                                    <th>Mandal</th>
                                    <th>TC Code</th>
                                    <th>Batch Name</th>
                                    <th>Batch Code</th>
                                    <th>Duration</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $serial = $offset + 1;
                                foreach ($batches as $batch):
                                    // Calculate progress
                                    $start_date = new DateTime($batch['start_date']);
                                    $end_date = new DateTime($batch['end_date']);
                                    $current_date = new DateTime();
                                    
                                    $total_days = $start_date->diff($end_date)->days;
                                    if ($total_days <= 0) $total_days = 1;
                                    
                                    if ($current_date < $start_date) {
                                        $progress = 0;
                                        $progress_text = "Not Started";
                                        $progress_class = "secondary";
                                    } elseif ($current_date > $end_date) {
                                        $progress = 100;
                                        $progress_text = "Completed";
                                        $progress_class = "success";
                                    } else {
                                        $elapsed_days = $start_date->diff($current_date)->days;
                                        $progress = min(100, round(($elapsed_days / $total_days) * 100));
                                        $progress_text = $progress . "% Complete";
                                        $progress_class = $progress > 50 ? "info" : "warning";
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $serial++; ?></td>
                                    <td><?php echo htmlspecialchars($batch['constituency_name']); ?></td>
                                    <td><?php echo htmlspecialchars($batch['mandal_name']); ?></td>
                                    <td><span class="badge badge-warning"><?php echo htmlspecialchars($batch['tc_code']); ?></span></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($batch['name']); ?></strong>
                                        <?php if (!empty($batch['description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($batch['description'], 0, 50)); ?><?php echo strlen($batch['description']) > 50 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($batch['code']); ?></span></td>
                                    <td>
                                        <small>
                                            <strong>Start:</strong> <?php echo date('d M Y', strtotime($batch['start_date'])); ?><br>
                                            <strong>End:</strong> <?php echo date('d M Y', strtotime($batch['end_date'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?php echo $batch['beneficiary_count']; ?> Students</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $batch['status'] == 'active' ? 'success' : ($batch['status'] == 'completed' ? 'primary' : 'secondary'); ?>">
                                            <?php echo ucfirst($batch['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $progress_class; ?>" 
                                                 style="width: <?php echo $progress; ?>%">
                                                <?php echo $progress; ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $progress_text; ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Batches pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($current_page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1">First</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>">Last</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-layer-group fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No batches found</h5>
                        <p class="text-muted">No training batches are assigned to your center yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>