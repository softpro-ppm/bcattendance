<?php
$pageTitle = "Students";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Students']
];

require_once 'includes/header.php';

// Get TC info
$tc_id = $_SESSION['tc_user_training_center_id'];
$mandal_id = $_SESSION['tc_user_mandal_id'];

// Get filter parameters
$batch_filter = $_GET['batch_id'] ?? '';
$search = $_GET['search'] ?? '';

// Get batches for this training center
$batches_query = "SELECT * FROM batches WHERE tc_id = ? AND status = 'active' ORDER BY name";
$batches = fetchAll($batches_query, [$tc_id], 'i') ?: [];

// Build beneficiaries query with filters - using correct column names
$beneficiaries_query = "SELECT ben.*, b.name as batch_name, b.code as batch_code
                       FROM beneficiaries ben
                       JOIN batches b ON ben.batch_id = b.id
                       WHERE b.tc_id = ? AND (ben.status = 'active' OR ben.status = 'completed')";

$params = [$tc_id];
$types = 'i';

if (!empty($batch_filter)) {
    $beneficiaries_query .= " AND ben.batch_id = ?";
    $params[] = $batch_filter;
    $types .= 'i';
}

if (!empty($search)) {
    $beneficiaries_query .= " AND (ben.full_name LIKE ? OR ben.beneficiary_id LIKE ? OR ben.mobile_number LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= 'sss';
}

$beneficiaries_query .= " ORDER BY 
    CASE 
        WHEN b.name LIKE '%Batch 1%' OR b.name LIKE '%batch 1%' THEN 1
        WHEN b.name LIKE '%Batch 2%' OR b.name LIKE '%batch 2%' THEN 2  
        WHEN b.name LIKE '%Batch 3%' OR b.name LIKE '%batch 3%' THEN 3
        WHEN b.name LIKE '%Batch 4%' OR b.name LIKE '%batch 4%' THEN 4
        WHEN b.name LIKE '%Batch 5%' OR b.name LIKE '%batch 5%' THEN 5
        ELSE 999
    END, 
    b.name, ben.full_name";
$beneficiaries = fetchAll($beneficiaries_query, $params, $types) ?: [];

// Get summary stats
$total_students = count($beneficiaries);
$batch_count = count($batches);
$male_count = count(array_filter($beneficiaries, function($b) { return ($b['gender'] ?? '') == 'Male'; }));
$female_count = count(array_filter($beneficiaries, function($b) { return ($b['gender'] ?? '') == 'Female'; }));
?>

<!-- Filter Section -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter mr-2"></i>
                    Filter Students
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info"><?php echo $total_students; ?> Students</span>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="batch_id">Filter by Batch:</label>
                            <select name="batch_id" id="batch_id" class="form-control">
                                <option value="">All Batches</option>
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?php echo $batch['id']; ?>" <?php echo ($batch['id'] == $batch_filter) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($batch['name']); ?> (<?php echo htmlspecialchars($batch['code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">Search Students:</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   placeholder="Search by name, ID, or mobile..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search mr-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stats-number"><?php echo $total_students; ?></div>
                <div class="stats-label">Total Students</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-male"></i>
                </div>
                <div class="stats-number"><?php echo $male_count; ?></div>
                <div class="stats-label">Male Students</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-female"></i>
                </div>
                <div class="stats-number"><?php echo $female_count; ?></div>
                <div class="stats-label">Female Students</div>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="card stats-card">
            <div class="card-body">
                <div class="stats-icon">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stats-number"><?php echo $batch_count; ?></div>
                <div class="stats-label">Active Batches</div>
            </div>
        </div>
    </div>
</div>

<!-- Students List -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>
                    Students List
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">Read Only Access</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($beneficiaries)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped data-table">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Student Details</th>
                                    <th>Batch</th>
                                    <th>Aadhar Number</th>
                                    <th>Contact</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beneficiaries as $index => $student): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($student['batch_name']); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['aadhar_number'])): ?>
                                            <?php echo htmlspecialchars($student['aadhar_number']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not available</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if (!empty($student['mobile_number'])): ?>
                                                <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($student['mobile_number']); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($student['address'])): ?>
                                                <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars(substr($student['address'], 0, 50)); ?><?php echo strlen($student['address']) > 50 ? '...' : ''; ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo ($student['status'] ?? 'active') == 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($student['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No students found</h5>
                        <p class="text-muted">
                            <?php if (!empty($search) || !empty($batch_filter)): ?>
                                Try adjusting your filters or search criteria.
                            <?php else: ?>
                                No students are assigned to your training center yet.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search) || !empty($batch_filter)): ?>
                            <a href="students.php" class="btn btn-primary">
                                <i class="fas fa-times mr-1"></i>Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<?php require_once 'includes/footer.php'; ?>