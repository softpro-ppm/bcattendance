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

$pageTitle = 'Manage Mandals & Training Centers';

// Handle AJAX live search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'live_search') {
    header('Content-Type: application/json');
    
    $search = sanitizeInput($_POST['search'] ?? '');
    
    // Build query for live search
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE m.name LIKE ? OR m.code LIKE ? OR c.name LIKE ? OR tc.tc_id LIKE ? OR tc.name LIKE ?";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
        $types = 'sssss';
    }
    
    try {
        // Get mandals with training centers and batch counts for current search
        $query = "
            SELECT 
                m.*,
                c.name as constituency_name,
                tc.id as tc_id_db,
                tc.tc_id,
                tc.name as tc_name,
                tc.address as tc_address,
                tc.contact_person as tc_contact_person,
                tc.phone_number as tc_phone_number,
                tc.status as tc_status,
                COUNT(b.id) as batch_count
            FROM mandals m
            LEFT JOIN constituencies c ON m.constituency_id = c.id
            LEFT JOIN training_centers tc ON m.id = tc.mandal_id
            LEFT JOIN batches b ON m.id = b.mandal_id
            $whereClause
            GROUP BY m.id
            ORDER BY c.name, m.name
        ";
        
        $mandals = fetchAll($query, $params, $types);
        
        // Generate table HTML
        $html = '';
        $serial = 1;
        foreach ($mandals as $mandal) {
            $html .= '<tr>';
            $html .= '<td>' . $serial++ . '</td>';
            $html .= '<td>' . htmlspecialchars($mandal['constituency_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($mandal['name']) . '</td>';
            $html .= '<td><span class="badge badge-info">' . htmlspecialchars($mandal['code']) . '</span></td>';
            $html .= '<td><span class="badge badge-warning">' . htmlspecialchars($mandal['tc_id']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($mandal['tc_name']) . '</td>';
            $html .= '<td>';
            if ($mandal['tc_contact_person']) {
                $html .= '<strong>' . htmlspecialchars($mandal['tc_contact_person']) . '</strong><br>';
            }
            if ($mandal['tc_phone_number']) {
                $html .= '<small>' . htmlspecialchars($mandal['tc_phone_number']) . '</small>';
            }
            $html .= '</td>';
            $html .= '<td><span class="badge badge-secondary">' . $mandal['batch_count'] . ' Batches</span></td>';
            $html .= '<td><span class="badge ' . getStatusBadgeClass($mandal['status']) . '">' . ucfirst($mandal['status']) . '</span></td>';
            $html .= '<td>';
            $html .= '<button type="button" class="btn btn-sm btn-warning" onclick="editMandal(' . htmlspecialchars(json_encode($mandal)) . ')"><i class="fas fa-edit"></i></button>';
            if ($mandal['batch_count'] == 0) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteMandal(' . $mandal['id'] . ', \'' . addslashes($mandal['name']) . '\')"><i class="fas fa-trash"></i></button>';
            } else {
                $html .= '<button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has associated batches"><i class="fas fa-trash"></i></button>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total' => count($mandals)
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
        
        if ($action == 'add_mandal') {
            $constituency_id = (int)$_POST['constituency_id'];
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            $description = sanitizeInput($_POST['description']);
            $status = sanitizeInput($_POST['status']);
            $tc_id = sanitizeInput($_POST['tc_id']);
            $tc_name = sanitizeInput($_POST['tc_name']);
            $tc_address = sanitizeInput($_POST['tc_address']);
            $tc_contact_person = sanitizeInput($_POST['tc_contact_person']);
            $tc_phone_number = sanitizeInput($_POST['tc_phone_number']);
            
            if (empty($name) || empty($code) || empty($tc_id) || empty($tc_name)) {
                setErrorMessage('Mandal name, code, TC ID and TC name are required.');
            } else {
                // Check if mandal already exists
                $existing = fetchRow("SELECT id FROM mandals WHERE name = ? OR code = ?", [$name, $code], 'ss');
                $existing_tc = fetchRow("SELECT id FROM training_centers WHERE tc_id = ?", [$tc_id], 's');
                
                if ($existing) {
                    setErrorMessage('Mandal with this name or code already exists.');
                } elseif ($existing_tc) {
                    setErrorMessage('Training center with this TC ID already exists.');
                } else {
                    // Start transaction
                    $conn = getDBConnection();
                    $conn->autocommit(FALSE);
                    
                    try {
                        // Insert mandal
                        $mandal_query = "INSERT INTO mandals (constituency_id, name, code, description, status) VALUES (?, ?, ?, ?, ?)";
                        if (!executeQuery($mandal_query, [$constituency_id, $name, $code, $description, $status], 'issss')) {
                            throw new Exception('Failed to add mandal.');
                        }
                        
                        $mandal_id = getLastInsertId();
                        
                        // Insert training center
                        $tc_query = "INSERT INTO training_centers (mandal_id, tc_id, name, address, contact_person, phone_number) VALUES (?, ?, ?, ?, ?, ?)";
                        if (!executeQuery($tc_query, [$mandal_id, $tc_id, $tc_name, $tc_address, $tc_contact_person, $tc_phone_number], 'isssss')) {
                            throw new Exception('Failed to add training center.');
                        }
                        
                        $conn->commit();
                        setSuccessMessage('Mandal and Training Center added successfully.');
                    } catch (Exception $e) {
                        $conn->rollback();
                        setErrorMessage($e->getMessage());
                    }
                    
                    $conn->autocommit(TRUE);
                }
            }
        } elseif ($action == 'edit_mandal') {
            $mandal_id = (int)$_POST['mandal_id'];
            $tc_id_db = (int)$_POST['tc_id_db'];
            $constituency_id = (int)$_POST['constituency_id'];
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            $description = sanitizeInput($_POST['description']);
            $status = sanitizeInput($_POST['status']);
            $tc_id = sanitizeInput($_POST['tc_id']);
            $tc_name = sanitizeInput($_POST['tc_name']);
            $tc_address = sanitizeInput($_POST['tc_address']);
            $tc_contact_person = sanitizeInput($_POST['tc_contact_person']);
            $tc_phone_number = sanitizeInput($_POST['tc_phone_number']);
            
            if (empty($name) || empty($code) || empty($tc_id) || empty($tc_name)) {
                setErrorMessage('Mandal name, code, TC ID and TC name are required.');
            } else {
                // Check if mandal already exists (excluding current one)
                $existing = fetchRow("SELECT id FROM mandals WHERE (name = ? OR code = ?) AND id != ?", [$name, $code, $mandal_id], 'ssi');
                $existing_tc = fetchRow("SELECT id FROM training_centers WHERE tc_id = ? AND id != ?", [$tc_id, $tc_id_db], 'si');
                
                if ($existing) {
                    setErrorMessage('Mandal with this name or code already exists.');
                } elseif ($existing_tc) {
                    setErrorMessage('Training center with this TC ID already exists.');
                } else {
                    // Start transaction
                    $conn = getDBConnection();
                    $conn->autocommit(FALSE);
                    
                    try {
                        // Update mandal
                        $mandal_query = "UPDATE mandals SET constituency_id = ?, name = ?, code = ?, description = ?, status = ? WHERE id = ?";
                        if (!executeQuery($mandal_query, [$constituency_id, $name, $code, $description, $status, $mandal_id], 'issssi')) {
                            throw new Exception('Failed to update mandal.');
                        }
                        
                        // Update training center
                        $tc_query = "UPDATE training_centers SET tc_id = ?, name = ?, address = ?, contact_person = ?, phone_number = ? WHERE id = ?";
                        if (!executeQuery($tc_query, [$tc_id, $tc_name, $tc_address, $tc_contact_person, $tc_phone_number, $tc_id_db], 'sssssi')) {
                            throw new Exception('Failed to update training center.');
                        }
                        
                        $conn->commit();
                        setSuccessMessage('Mandal and Training Center updated successfully.');
                    } catch (Exception $e) {
                        $conn->rollback();
                        setErrorMessage($e->getMessage());
                    }
                    
                    $conn->autocommit(TRUE);
                }
            }
        } elseif ($action == 'delete_mandal') {
            $mandal_id = (int)$_POST['mandal_id'];
            
            // Check if mandal has batches
            $batches = fetchRow("SELECT COUNT(*) as count FROM batches WHERE mandal_id = ?", [$mandal_id], 'i');
            if ($batches['count'] > 0) {
                setErrorMessage('Cannot delete mandal. It has associated batches.');
            } else {
                // Start transaction to delete mandal and training center
                $conn = getDBConnection();
                $conn->autocommit(FALSE);
                
                try {
                    // Delete training center first (due to foreign key)
                    $tc_query = "DELETE FROM training_centers WHERE mandal_id = ?";
                    executeQuery($tc_query, [$mandal_id], 'i');
                    
                    // Delete mandal
                    $mandal_query = "DELETE FROM mandals WHERE id = ?";
                    if (!executeQuery($mandal_query, [$mandal_id], 'i')) {
                        throw new Exception('Failed to delete mandal.');
                    }
                    
                    $conn->commit();
                    setSuccessMessage('Mandal and Training Center deleted successfully.');
                } catch (Exception $e) {
                    $conn->rollback();
                    setErrorMessage('Failed to delete mandal and training center.');
                }
                
                $conn->autocommit(TRUE);
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
    $search_condition = " WHERE m.name LIKE ? OR m.code LIKE ? OR c.name LIKE ? OR tc.tc_id LIKE ? OR tc.name LIKE ?";
    $search_params = ["%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%", "%$search_term%"];
    $search_types = 'sssss';
}

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT m.id) as total 
    FROM mandals m
    LEFT JOIN constituencies c ON m.constituency_id = c.id
    LEFT JOIN training_centers tc ON m.id = tc.mandal_id
    $search_condition";
$total_records = fetchRow($count_query, $search_params, $search_types)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get all constituencies for dropdown
$constituencies = fetchAll("SELECT id, name FROM constituencies WHERE status = 'active' ORDER BY name");

// Get all mandals with training centers and counts (with pagination and search)
$mandals_with_data = fetchAll("
    SELECT 
        m.*,
        c.name as constituency_name,
        tc.id as tc_id_db,
        tc.tc_id,
        tc.name as tc_name,
        tc.address as tc_address,
        tc.contact_person as tc_contact_person,
        tc.phone_number as tc_phone_number,
        tc.status as tc_status,
        COUNT(b.id) as batch_count
    FROM mandals m
    LEFT JOIN constituencies c ON m.constituency_id = c.id
    LEFT JOIN training_centers tc ON m.id = tc.mandal_id
    LEFT JOIN batches b ON m.id = b.mandal_id
    $search_condition
    GROUP BY m.id
    ORDER BY c.name, m.name
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
                        <i class="fas fa-building"></i> Mandals & Training Centers Management
                    </h3>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addMandalModal">
                        <i class="fas fa-plus"></i> Add New Mandal & Training Center
                    </button>
                </div>
                
                <div class="card-body">
                    <?php echo displayFlashMessages(); ?>
                    
                    <!-- Search and Pagination Controls -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="liveSearch" placeholder="Search mandals, constituencies, training centers...">
                                <div class="input-group-append">
                                    <div class="btn btn-outline-secondary" id="searchStatus">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted" id="recordCount">
                                Total: <?php echo $total_records; ?> mandals
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
                                    <th>Mandal Code</th>
                                    <th>TC ID</th>
                                    <th>Training Center</th>
                                    <th>TC Contact</th>
                                    <th>Batches</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if (empty($mandals_with_data)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No mandals found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $serial = $offset + 1; ?>
                                    <?php foreach ($mandals_with_data as $mandal): ?>
                                        <tr>
                                            <td><?php echo $serial++; ?></td>
                                            <td><?php echo htmlspecialchars($mandal['constituency_name']); ?></td>
                                            <td><?php echo htmlspecialchars($mandal['name']); ?></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($mandal['code']); ?></span></td>
                                            <td><span class="badge badge-warning"><?php echo htmlspecialchars($mandal['tc_id']); ?></span></td>
                                            <td><?php echo htmlspecialchars($mandal['tc_name']); ?></td>
                                            <td>
                                                <?php if ($mandal['tc_contact_person']): ?>
                                                    <strong><?php echo htmlspecialchars($mandal['tc_contact_person']); ?></strong><br>
                                                <?php endif; ?>
                                                <?php if ($mandal['tc_phone_number']): ?>
                                                    <small><?php echo htmlspecialchars($mandal['tc_phone_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo $mandal['batch_count']; ?> Batches
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($mandal['status']); ?>">
                                                    <?php echo ucfirst($mandal['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="editMandal(<?php echo htmlspecialchars(json_encode($mandal)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($mandal['batch_count'] == 0): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteMandal(<?php echo $mandal['id']; ?>, '<?php echo addslashes($mandal['name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has associated batches">
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

<!-- Add Mandal Modal -->
<div class="modal fade" id="addMandalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add_mandal">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Add New Mandal & Training Center
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-building"></i> Mandal Information</h6>
                            
                            <div class="form-group">
                                <label for="add_constituency_id">Constituency *</label>
                                <select class="form-control" id="add_constituency_id" name="constituency_id" required>
                                    <option value="">Select Constituency</option>
                                    <?php foreach ($constituencies as $constituency): ?>
                                        <option value="<?php echo $constituency['id']; ?>">
                                            <?php echo htmlspecialchars($constituency['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_name">Mandal Name *</label>
                                <input type="text" class="form-control" id="add_name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_code">Mandal Code *</label>
                                <input type="text" class="form-control" id="add_code" name="code" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_description">Description</label>
                                <textarea class="form-control" id="add_description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_status">Status</label>
                                <select class="form-control" id="add_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6><i class="fas fa-school"></i> Training Center Information</h6>
                            
                            <div class="form-group">
                                <label for="add_tc_id">TC ID *</label>
                                <input type="text" class="form-control" id="add_tc_id" name="tc_id" required>
                                <small class="form-text text-muted">e.g., TTC7430317</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_tc_name">Training Center Name *</label>
                                <input type="text" class="form-control" id="add_tc_name" name="tc_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_tc_address">Address</label>
                                <textarea class="form-control" id="add_tc_address" name="tc_address" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="add_tc_contact_person">Contact Person</label>
                                <input type="text" class="form-control" id="add_tc_contact_person" name="tc_contact_person">
                            </div>
                            
                            <div class="form-group">
                                <label for="add_tc_phone_number">Phone Number</label>
                                <input type="tel" class="form-control" id="add_tc_phone_number" name="tc_phone_number" maxlength="10" pattern="[0-9]{10}" title="Please enter exactly 10 digits">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Mandal & Training Center
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Mandal Modal -->
<div class="modal fade" id="editMandalModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit_mandal">
                <input type="hidden" name="mandal_id" id="edit_mandal_id">
                <input type="hidden" name="tc_id_db" id="edit_tc_id_db">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Mandal & Training Center
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-building"></i> Mandal Information</h6>
                            
                            <div class="form-group">
                                <label for="edit_constituency_id">Constituency *</label>
                                <select class="form-control" id="edit_constituency_id" name="constituency_id" required>
                                    <option value="">Select Constituency</option>
                                    <?php foreach ($constituencies as $constituency): ?>
                                        <option value="<?php echo $constituency['id']; ?>">
                                            <?php echo htmlspecialchars($constituency['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_name">Mandal Name *</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_code">Mandal Code *</label>
                                <input type="text" class="form-control" id="edit_code" name="code" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6><i class="fas fa-school"></i> Training Center Information</h6>
                            
                            <div class="form-group">
                                <label for="edit_tc_id">TC ID *</label>
                                <input type="text" class="form-control" id="edit_tc_id" name="tc_id" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_tc_name">Training Center Name *</label>
                                <input type="text" class="form-control" id="edit_tc_name" name="tc_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_tc_address">Address</label>
                                <textarea class="form-control" id="edit_tc_address" name="tc_address" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_tc_contact_person">Contact Person</label>
                                <input type="text" class="form-control" id="edit_tc_contact_person" name="tc_contact_person">
                            </div>
                            
                            <div class="form-group">
                                <label for="edit_tc_phone_number">Phone Number</label>
                                <input type="tel" class="form-control" id="edit_tc_phone_number" name="tc_phone_number" maxlength="10" pattern="[0-9]{10}" title="Please enter exactly 10 digits">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Mandal & Training Center
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteMandalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete_mandal">
                <input type="hidden" name="mandal_id" id="delete_mandal_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i> Delete Mandal & Training Center
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to delete the mandal "<span id="delete_mandal_name"></span>" and its associated training center?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        This action cannot be undone and will also delete the associated training center.
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
function editMandal(mandal) {
    document.getElementById('edit_mandal_id').value = mandal.id;
    document.getElementById('edit_tc_id_db').value = mandal.tc_id_db;
    document.getElementById('edit_constituency_id').value = mandal.constituency_id;
    document.getElementById('edit_name').value = mandal.name;
    document.getElementById('edit_code').value = mandal.code;
    document.getElementById('edit_description').value = mandal.description || '';
    document.getElementById('edit_status').value = mandal.status;
    document.getElementById('edit_tc_id').value = mandal.tc_id || '';
    document.getElementById('edit_tc_name').value = mandal.tc_name || '';
    document.getElementById('edit_tc_address').value = mandal.tc_address || '';
    document.getElementById('edit_tc_contact_person').value = mandal.tc_contact_person || '';
    document.getElementById('edit_tc_phone_number').value = mandal.tc_phone_number || '';
    
    $('#editMandalModal').modal('show');
}

function deleteMandal(id, name) {
    document.getElementById('delete_mandal_id').value = id;
    document.getElementById('delete_mandal_name').textContent = name;
    
    $('#deleteMandalModal').modal('show');
}

// Auto-generate TC name from mandal name
document.getElementById('add_name').addEventListener('input', function() {
    const mandalName = this.value.trim();
    if (mandalName) {
        document.getElementById('add_tc_name').value = mandalName + ' Training Center';
    }
});

// Auto-generate code from name
document.getElementById('add_name').addEventListener('input', function() {
    const name = this.value.trim();
    const code = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
    document.getElementById('add_code').value = code;
});

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
        
        fetch('mandals.php', {
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
                tableBody.innerHTML = '<tr><td colspan="10" class="text-center">No mandals found.</td></tr>';
            } else {
                tableBody.innerHTML = html;
            }
        }
    }
    
    function updateRecordCount(total) {
        const countElement = document.getElementById('recordCount');
        if (countElement) {
            countElement.textContent = `Total: ${total.toLocaleString()} mandals`;
        }
    }
});

// Auto-hide alerts after 5 seconds
$(document).ready(function() {
    $('.alert').delay(5000).fadeOut('slow');
});
</script>
