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

$pageTitle = 'Manage Constituencies';

// Handle AJAX live search
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'live_search') {
    header('Content-Type: application/json');
    
    $search = sanitizeInput($_POST['search'] ?? '');
    
    // Build query for live search
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE c.name LIKE ? OR c.code LIKE ? OR c.description LIKE ?";
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
        $types = 'sss';
    }
    
    try {
        // Get constituencies with mandal counts for current search
        $query = "
            SELECT c.*, COUNT(m.id) as mandal_count 
            FROM constituencies c 
            LEFT JOIN mandals m ON c.id = m.constituency_id 
            $whereClause
            GROUP BY c.id 
            ORDER BY c.name
        ";
        
        $constituencies = fetchAll($query, $params, $types);
        
        // Generate table HTML
        $html = '';
        $serial = 1;
        foreach ($constituencies as $constituency) {
            $html .= '<tr>';
            $html .= '<td>' . $serial++ . '</td>';
            $html .= '<td>' . htmlspecialchars($constituency['name']) . '</td>';
            $html .= '<td><span class="badge badge-info">' . htmlspecialchars($constituency['code']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($constituency['description']) . '</td>';
            $html .= '<td><span class="badge badge-secondary">' . $constituency['mandal_count'] . ' Mandals</span></td>';
            $html .= '<td><span class="badge ' . getStatusBadgeClass($constituency['status']) . '">' . ucfirst($constituency['status']) . '</span></td>';
            $html .= '<td>' . formatDateTime($constituency['created_at'], 'd M Y') . '</td>';
            $html .= '<td>';
            $html .= '<button type="button" class="btn btn-sm btn-warning" onclick="editConstituency(' . htmlspecialchars(json_encode($constituency)) . ')"><i class="fas fa-edit"></i></button>';
            if ($constituency['mandal_count'] == 0) {
                $html .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteConstituency(' . $constituency['id'] . ', \'' . addslashes($constituency['name']) . '\')"><i class="fas fa-trash"></i></button>';
            } else {
                $html .= '<button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has associated mandals"><i class="fas fa-trash"></i></button>';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        echo json_encode([
            'success' => true,
            'html' => $html,
            'total' => count($constituencies)
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
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            $description = sanitizeInput($_POST['description']);
            $status = sanitizeInput($_POST['status']);
            
            if (empty($name) || empty($code)) {
                setErrorMessage('Name and code are required.');
            } else {
                // Check if constituency already exists
                $existing = fetchRow("SELECT id FROM constituencies WHERE name = ? OR code = ?", [$name, $code], 'ss');
                if ($existing) {
                    setErrorMessage('Constituency with this name or code already exists.');
                } else {
                    $query = "INSERT INTO constituencies (name, code, description, status) VALUES (?, ?, ?, ?)";
                    if (executeQuery($query, [$name, $code, $description, $status], 'ssss')) {
                        setSuccessMessage('Constituency added successfully.');
                    } else {
                        setErrorMessage('Failed to add constituency. Please try again.');
                    }
                }
            }
        } elseif ($action == 'edit') {
            $id = (int)$_POST['id'];
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            $description = sanitizeInput($_POST['description']);
            $status = sanitizeInput($_POST['status']);
            
            if (empty($name) || empty($code)) {
                setErrorMessage('Name and code are required.');
            } else {
                // Check if constituency already exists (excluding current one)
                $existing = fetchRow("SELECT id FROM constituencies WHERE (name = ? OR code = ?) AND id != ?", [$name, $code, $id], 'ssi');
                if ($existing) {
                    setErrorMessage('Constituency with this name or code already exists.');
                } else {
                    $query = "UPDATE constituencies SET name = ?, code = ?, description = ?, status = ? WHERE id = ?";
                    if (executeQuery($query, [$name, $code, $description, $status, $id], 'ssssi')) {
                        setSuccessMessage('Constituency updated successfully.');
                    } else {
                        setErrorMessage('Failed to update constituency. Please try again.');
                    }
                }
            }
        } elseif ($action == 'delete') {
            $id = (int)$_POST['id'];
            
            // Check if constituency has mandals
            $mandals = fetchRow("SELECT COUNT(*) as count FROM mandals WHERE constituency_id = ?", [$id], 'i');
            if ($mandals['count'] > 0) {
                setErrorMessage('Cannot delete constituency. It has associated mandals.');
            } else {
                $query = "DELETE FROM constituencies WHERE id = ?";
                if (executeQuery($query, [$id], 'i')) {
                    setSuccessMessage('Constituency deleted successfully.');
                } else {
                    setErrorMessage('Failed to delete constituency. Please try again.');
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
    $search_condition = " WHERE c.name LIKE ? OR c.code LIKE ? OR c.description LIKE ?";
    $search_params = ["%$search_term%", "%$search_term%", "%$search_term%"];
    $search_types = 'sss';
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM constituencies c" . $search_condition;
$total_records = fetchRow($count_query, $search_params, $search_types)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get all constituencies (for modals)
$constituencies = fetchAll("SELECT * FROM constituencies ORDER BY name");

// Get constituencies with mandal counts (with pagination and search)
$constituencies_with_counts = fetchAll("
    SELECT c.*, COUNT(m.id) as mandal_count 
    FROM constituencies c 
    LEFT JOIN mandals m ON c.id = m.constituency_id 
    $search_condition
    GROUP BY c.id 
    ORDER BY c.name
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
                        <i class="fas fa-map-marker-alt"></i> Constituencies Management
                    </h3>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addConstituencyModal">
                        <i class="fas fa-plus"></i> Add New Constituency
                    </button>
                </div>
                
                <div class="card-body">
                    <?php echo displayFlashMessages(); ?>
                    
                    <!-- Search and Pagination Controls -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="liveSearch" placeholder="Search constituencies by name, code, or description...">
                                <div class="input-group-append">
                                    <div class="btn btn-outline-secondary" id="searchStatus">
                                        <i class="fas fa-search"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted" id="recordCount">
                                Total: <?php echo $total_records; ?> constituencies
                            </small>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>S.No</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Mandals</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php if (empty($constituencies_with_counts)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No constituencies found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php $serial = $offset + 1; ?>
                                    <?php foreach ($constituencies_with_counts as $constituency): ?>
                                        <tr>
                                            <td><?php echo $serial++; ?></td>
                                            <td><?php echo htmlspecialchars($constituency['name']); ?></td>
                                            <td><span class="badge badge-info"><?php echo htmlspecialchars($constituency['code']); ?></span></td>
                                            <td><?php echo htmlspecialchars($constituency['description']); ?></td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?php echo $constituency['mandal_count']; ?> Mandals
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($constituency['status']); ?>">
                                                    <?php echo ucfirst($constituency['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDateTime($constituency['created_at'], 'd M Y'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="editConstituency(<?php echo htmlspecialchars(json_encode($constituency)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($constituency['mandal_count'] == 0): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteConstituency(<?php echo $constituency['id']; ?>, '<?php echo addslashes($constituency['name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has associated mandals">
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

<!-- Add Constituency Modal -->
<div class="modal fade" id="addConstituencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Add New Constituency
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="add_name">Name *</label>
                        <input type="text" class="form-control" id="add_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_code">Code *</label>
                        <input type="text" class="form-control" id="add_code" name="code" required>
                        <small class="form-text text-muted">Unique identifier for the constituency</small>
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
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Constituency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Constituency Modal -->
<div class="modal fade" id="editConstituencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit"></i> Edit Constituency
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_name">Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_code">Code *</label>
                        <input type="text" class="form-control" id="edit_code" name="code" required>
                        <small class="form-text text-muted">Unique identifier for the constituency</small>
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
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Constituency
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConstituencyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">
                
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash"></i> Delete Constituency
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                
                <div class="modal-body">
                    <p>Are you sure you want to delete the constituency "<span id="delete_name"></span>"?</p>
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
function editConstituency(constituency) {
    document.getElementById('edit_id').value = constituency.id;
    document.getElementById('edit_name').value = constituency.name;
    document.getElementById('edit_code').value = constituency.code;
    document.getElementById('edit_description').value = constituency.description || '';
    document.getElementById('edit_status').value = constituency.status;
    
    $('#editConstituencyModal').modal('show');
}

function deleteConstituency(id, name) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_name').textContent = name;
    
    $('#deleteConstituencyModal').modal('show');
}

// Auto-generate code from name
document.getElementById('add_name').addEventListener('input', function() {
    const name = this.value.trim();
    const code = name.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
    document.getElementById('add_code').value = code;
});

// Reset forms when modals are closed
$('#addConstituencyModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
});

$('#editConstituencyModal').on('hidden.bs.modal', function () {
    $(this).find('form')[0].reset();
});

// Auto-hide alerts after 5 seconds
$(document).ready(function() {
    $('.alert').delay(5000).fadeOut('slow');
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
        
        fetch('constituencies.php', {
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
                tableBody.innerHTML = '<tr><td colspan="8" class="text-center">No constituencies found.</td></tr>';
            } else {
                tableBody.innerHTML = html;
            }
        }
    }
    
    function updateRecordCount(total) {
        const countElement = document.getElementById('recordCount');
        if (countElement) {
            countElement.textContent = `Total: ${total.toLocaleString()} constituencies`;
        }
    }
});
</script>
