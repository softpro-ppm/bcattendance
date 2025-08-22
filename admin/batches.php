<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/session.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

$pageTitle = 'Manage Batches';

// Handle AJAX live search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'live_search') {
    header('Content-Type: application/json');
    
    $search = sanitizeInput($_POST['search'] ?? '');
    
    // Build query for live search
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE b.name LIKE ? OR b.code LIKE ? OR m.name LIKE ? OR c.name LIKE ? OR tc.tc_id LIKE ?";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
        $types = 'sssss';
    }
    
    try {
        // Get batches with related information for current search
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
            LEFT JOIN beneficiaries ben ON b.id = ben.batch_id
            $whereClause
            GROUP BY b.id
            ORDER BY c.name, m.name, b.name
        ";
        
        $batches = fetchAll($query, $params, $types);
        
        // Generate table HTML
        $html = '';
        $serial = 1;
        foreach ($batches as $batch) {
            $html .= '<tr>';
            $html .= '<td>' . $serial++ . '</td>';
            $html .= '<td>' . htmlspecialchars($batch['constituency_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($batch['mandal_name']) . '</td>';
            $html .= '<td><span class="badge badge-warning">' . htmlspecialchars($batch['tc_code']) . '</span></td>';
            $html .= '<td><strong>' . htmlspecialchars($batch['name']) . '</strong></td>';
            $html .= '<td><span class="badge badge-info">' . htmlspecialchars($batch['code']) . '</span></td>';
            $html .= '<td>';
            $html .= '<small>';
            $html .= '<strong>Start:</strong> ' . formatDate($batch['start_date'], 'd M Y') . '<br>';
            $html .= '<strong>End:</strong> ' . formatDate($batch['end_date'], 'd M Y');
            $html .= '</small>';
            $html .= '</td>';
            $html .= '<td><span class="badge badge-secondary">' . $batch['beneficiary_count'] . ' Beneficiaries</span></td>';
            $html .= '<td><span class="badge ' . getStatusBadgeClass($batch['status']) . '">' . ucfirst($batch['status']) . '</span></td>';
            $html .= '<td>';
            $html .= '<button type="button" class="btn btn-sm btn-warning" onclick="editBatch(' . htmlspecialchars(json_encode($batch)) . ')"><i class="fas fa-edit"></i></button>';
            if ($batch['beneficiary_count'] == 0) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteBatch(' . $batch['id'] . ', \'' . addslashes($batch['name']) . '\')"><i class="fas fa-trash"></i></button>';
            } else {
                $html .= '<button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has associated beneficiaries"><i class="fas fa-trash"></i></button>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total' => count($batches)
        ]);
        exit;
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// Pagination settings
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search_term = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$offset = ($current_page - 1) * $records_per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        setErrorMessage('Invalid request. Please try again.');
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'add') {
            $mandal_id = (int)$_POST['mandal_id'];
            $tc_id = (int)$_POST['tc_id'];
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            $description = sanitizeInput($_POST['description']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $status = sanitizeInput($_POST['status']);
            
            if (empty($name) || empty($code) || empty($start_date) || empty($end_date)) {
                setErrorMessage('Name, code, start date and end date are required.');
            } elseif (strtotime($start_date) >= strtotime($end_date)) {
                setErrorMessage('End date must be after start date.');
            } else {
                // Check if batch already exists
                $existing = fetchRow("SELECT id FROM batches WHERE name = ? AND mandal_id = ? AND tc_id = ?", [$name, $mandal_id, $tc_id], 'sii');
                if ($existing) {
                    setErrorMessage('Batch with this name already exists for this mandal and training center.');
                } else {
                    $query = "INSERT INTO batches (mandal_id, tc_id, name, code, description, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    if (executeQuery($query, [$mandal_id, $tc_id, $name, $code, $description, $start_date, $end_date, $status], 'iissssss')) {
                        setSuccessMessage('Batch added successfully.');
                    } else {
                        setErrorMessage('Failed to add batch. Please try again.');
                    }
                }
            }
        } elseif ($action == 'edit') {
            $id = (int)$_POST['id'];
            $mandal_id = (int)$_POST['mandal_id'];
            $tc_id = (int)$_POST['tc_id'];
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            $description = sanitizeInput($_POST['description']);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $status = sanitizeInput($_POST['status']);
            
            if (empty($name) || empty($code) || empty($start_date) || empty($end_date)) {
                setErrorMessage('Name, code, start date and end date are required.');
            } elseif (strtotime($start_date) >= strtotime($end_date)) {
                setErrorMessage('End date must be after start date.');
            } else {
                // Check if batch already exists (excluding current one)
                $existing = fetchRow("SELECT id FROM batches WHERE name = ? AND mandal_id = ? AND tc_id = ? AND id != ?", [$name, $mandal_id, $tc_id, $id], 'siii');
                if ($existing) {
                    setErrorMessage('Batch with this name already exists for this mandal and training center.');
                } else {
                    $query = "UPDATE batches SET mandal_id = ?, tc_id = ?, name = ?, code = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?";
                    if (executeQuery($query, [$mandal_id, $tc_id, $name, $code, $description, $start_date, $end_date, $status, $id], 'iissssssi')) {
                        setSuccessMessage('Batch updated successfully.');
                    } else {
                        setErrorMessage('Failed to update batch. Please try again.');
                    }
                }
            }
        } elseif ($action == 'delete') {
            $id = (int)$_POST['id'];
            
            // Check if batch has beneficiaries
            $beneficiaries = fetchRow("SELECT COUNT(*) as count FROM beneficiaries WHERE batch_id = ?", [$id], 'i');
            if ($beneficiaries && $beneficiaries['count'] > 0) {
                setErrorMessage('Cannot delete batch. It has ' . $beneficiaries['count'] . ' associated beneficiaries.');
            } else {
                $query = "DELETE FROM batches WHERE id = ?";
                if (executeQuery($query, [$id], 'i')) {
                    setSuccessMessage('Batch deleted successfully.');
                } else {
                    setErrorMessage('Failed to delete batch. Please try again.');
                }
            }
        }
    }
    
    // Redirect to prevent form resubmission (POST-Redirect-GET pattern)
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Build search condition
$search_condition = '';
$search_params = [];
$search_types = '';

if (!empty($search_term)) {
    $search_condition = " WHERE b.name LIKE ? OR b.code LIKE ? OR m.name LIKE ? OR c.name LIKE ? OR tc.tc_id LIKE ?";
    $search_params = ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"];
    $search_types = 'sssss';
}

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT b.id) as total 
    FROM batches b
    JOIN mandals m ON b.mandal_id = m.id
    JOIN constituencies c ON m.constituency_id = c.id
    JOIN training_centers tc ON b.tc_id = tc.id
    $search_condition";
$total_records = fetchRow($count_query, $search_params, $search_types)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get all mandals with training centers for dropdown
$mandals_with_tc = fetchAll("
    SELECT 
        m.id as mandal_id, 
        m.name as mandal_name,
        c.name as constituency_name,
        tc.id as tc_id,
        tc.tc_id as tc_code,
        tc.name as tc_name
    FROM mandals m
    JOIN constituencies c ON m.constituency_id = c.id
    JOIN training_centers tc ON m.id = tc.mandal_id
    WHERE m.status = 'active' AND tc.status = 'active'
    ORDER BY c.name, m.name
");

// Get all batches with related information (with pagination and search)
$batches_with_data = fetchAll("
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
    LEFT JOIN beneficiaries ben ON b.id = ben.batch_id
    $search_condition
    GROUP BY b.id
    ORDER BY c.name, m.name, b.name
    LIMIT $offset, $records_per_page
", $search_params, $search_types);

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<?php include '../includes/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-layer-group"></i> Batches Management
                    </h3>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addBatchModal">
                        <i class="fas fa-plus"></i> Add New Batch
                    </button>
                </div>
                
                <div class="card-body">
                    <?php echo displayFlashMessages(); ?>
                    
                    <!-- Search and Pagination Controls -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="liveSearch" placeholder="Search batches, mandals, constituencies...">
                                <div class="input-group-append">
                                    <div class="btn btn-outline-secondary" id="searchStatus">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted" id="recordCount">
                                Total: <?php echo $total_records; ?> batches
                            </small>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Constituency</th>
                                    <th>Mandal</th>
                                    <th>TC ID</th>
                                    <th>Batch Name</th>
                                    <th>Batch Code</th>
                                    <th>Duration</th>
                                    <th>Beneficiaries</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if (empty($batches_with_data)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No batches found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $serial = $offset + 1; ?>
                                    <?php foreach ($batches_with_data as $batch): ?>
                                        <tr>
                                            <td><?php echo $serial++; ?></td>
                                            <td><?php echo htmlspecialchars($batch['constituency_name']); ?></td>
                                            <td><?php echo htmlspecialchars($batch['mandal_name']); ?></td>
                                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($batch['tc_code']); ?></span></td>
                                            <td><strong><?php echo htmlspecialchars($batch['name']); ?></strong></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($batch['code']); ?></span></td>
                                            <td>
                                                <small>
                                                    <strong>Start:</strong> <?php echo formatDate($batch['start_date'], 'd M Y'); ?><br>
                                                    <strong>End:</strong> <?php echo formatDate($batch['end_date'], 'd M Y'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo $batch['beneficiary_count']; ?> Beneficiaries
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($batch['status']); ?>">
                                                    <?php echo ucfirst($batch['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="editBatch(<?php echo htmlspecialchars(json_encode($batch)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($batch['beneficiary_count'] == 0): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteBatch(<?php echo $batch['id']; ?>, '<?php echo addslashes($batch['name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has associated beneficiaries">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <?php 
                                $base_url = $_SERVER['PHP_SELF'] . '?';
                                if (!empty($search_term)) {
                                    $base_url .= 'search=' . urlencode($search_term) . '&';
                                }
                                echo generatePagination($current_page, $total_pages, $base_url);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Batch Modal -->
<div class="modal fade" id="addBatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Add New Batch
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_mandal_tc">Mandal & Training Center *</label>
                                <select class="form-control" id="add_mandal_tc" name="mandal_tc" required onchange="updateMandalTC('add')">
                                    <option value="">Select Mandal & Training Center</option>
                                    <?php foreach ($mandals_with_tc as $mandal): ?>
                                        <option value="<?php echo $mandal['mandal_id'] . '|' . $mandal['tc_id']; ?>">
                                            <?php echo htmlspecialchars($mandal['constituency_name'] . ' - ' . $mandal['mandal_name'] . ' (' . $mandal['tc_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="mandal_id" id="add_mandal_id">
                                <input type="hidden" name="tc_id" id="add_tc_id">
                            </div>
                            
                            <div class="form-group">
                                <label for="add_name">Batch Name *</label>
                                <input type="text" class="form-control" id="add_name" name="name" required>
                                <small class="form-text text-muted">e.g., Batch 1, Batch 2</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_code">Batch Code *</label>
                                <input type="text" class="form-control" id="add_code" name="code" required>
                                <small class="form-text text-muted">Unique identifier for the batch</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_status">Status</label>
                                <select class="form-control" id="add_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="add_start_date">Start Date *</label>
                                <input type="date" class="form-control" id="add_start_date" name="start_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_end_date">End Date *</label>
                                <input type="date" class="form-control" id="add_end_date" name="end_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_description">Description</label>
                                <textarea class="form-control" id="add_description" name="description" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Batch Modal -->
<div class="modal fade" id="editBatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Batch
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_mandal_tc">Mandal & Training Center *</label>
                                <select class="form-control" id="edit_mandal_tc" name="mandal_tc" required onchange="updateMandalTC('edit')">
                                    <option value="">Select Mandal & Training Center</option>
                                    <?php foreach ($mandals_with_tc as $mandal): ?>
                                        <option value="<?php echo $mandal['mandal_id'] . '|' . $mandal['tc_id']; ?>">
                                            <?php echo htmlspecialchars($mandal['constituency_name'] . ' - ' . $mandal['mandal_name'] . ' (' . $mandal['tc_code'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="mandal_id" id="edit_mandal_id">
                                <input type="hidden" name="tc_id" id="edit_tc_id">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_name">Batch Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_code">Batch Code *</label>
                                <input type="text" class="form-control" id="edit_code" name="code" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_start_date">Start Date *</label>
                                <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_end_date">End Date *</label>
                                <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i> Delete Batch
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to delete the batch "<span id="delete_name"></span>"?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action cannot be undone.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function updateMandalTC(prefix) {
    const select = document.getElementById(prefix + '_mandal_tc');
    const values = select.value.split('|');
    
    if (values.length === 2) {
        document.getElementById(prefix + '_mandal_id').value = values[0];
        document.getElementById(prefix + '_tc_id').value = values[1];
    }
}

function editBatch(batch) {
    document.getElementById('edit_id').value = batch.id;
    document.getElementById('edit_mandal_tc').value = batch.mandal_id + '|' + batch.tc_id;
    document.getElementById('edit_mandal_id').value = batch.mandal_id;
    document.getElementById('edit_tc_id').value = batch.tc_id;
    document.getElementById('edit_name').value = batch.name;
    document.getElementById('edit_code').value = batch.code;
    document.getElementById('edit_description').value = batch.description || '';
    document.getElementById('edit_start_date').value = batch.start_date;
    document.getElementById('edit_end_date').value = batch.end_date;
    document.getElementById('edit_status').value = batch.status;
    
    $('#editBatchModal').modal('show');
}

function deleteBatch(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    $('#deleteBatchModal').modal('show');
}

// Auto-generate code from name and selected mandal
function generateBatchCode() {
    const name = document.getElementById('add_name').value.trim();
    const mandalTc = document.getElementById('add_mandal_tc').value;
    
    if (name && mandalTc) {
        const mandalId = mandalTc.split('|')[0];
        const code = 'B' + mandalId + '_' + name.toUpperCase().replace(/[^A-Z0-9]/g, '');
        document.getElementById('add_code').value = code;
    }
}

document.getElementById('add_name').addEventListener('input', generateBatchCode);
document.getElementById('add_mandal_tc').addEventListener('change', generateBatchCode);

// AJAX Live Search Implementation
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('liveSearch');
    const searchStatus = document.getElementById('searchStatus');
    const tableBody = document.getElementById('tableBody');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            // Debounce the search
            searchTimeout = setTimeout(function() {
                performSearch(searchTerm);
            }, 300);
        });
    }
    
    function performSearch(searchTerm) {
        // Update search status
        searchStatus.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        // Create FormData for AJAX request
        const formData = new FormData();
        formData.append('action', 'live_search');
        formData.append('search', searchTerm);
        
        fetch('batches.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTable(data.html);
                updateRecordCount(data.total);
            } else {
                console.error('Search failed:', data.error);
            }
            searchStatus.innerHTML = '<i class="fas fa-search"></i>';
        })
        .catch(error => {
            console.error('Search error:', error);
            searchStatus.innerHTML = '<i class="fas fa-search"></i>';
        });
    }
    
    function updateTable(html) {
        if (tableBody) {
            if (html.trim() === '') {
                tableBody.innerHTML = '<tr><td colspan="10" class="text-center">No batches found.</td></tr>';
            } else {
                tableBody.innerHTML = html;
            }
        }
    }
    
    function updateRecordCount(total) {
        const countElement = document.getElementById('recordCount');
        if (countElement) {
            countElement.textContent = `Total: ${total.toLocaleString()} batches`;
        }
    }
});

// Auto-hide alerts after 5 seconds
$(document).ready(function() {
    $('.alert').delay(5000).fadeOut('slow');
});
</script>
