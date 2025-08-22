<?php
$pageTitle = 'System Settings';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'System Settings']
];

require_once '../includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    
    switch ($action) {
        case 'add':
            handleAdd($type);
            break;
        case 'edit':
            handleEdit($type);
            break;
        case 'delete':
            handleDelete($type);
            break;
    }
}

function handleAdd($type) {
    $name = sanitizeInput($_POST['name']);
    $code = sanitizeInput($_POST['code']);
    $description = sanitizeInput($_POST['description']);
    
    switch ($type) {
        case 'constituency':
            $query = "INSERT INTO constituencies (name, code, description) VALUES (?, ?, ?)";
            $result = executeQuery($query, [$name, $code, $description], 'sss');
            break;
            
        case 'mandal':
            $constituencyId = $_POST['constituency_id'];
            $query = "INSERT INTO mandals (constituency_id, name, code, description) VALUES (?, ?, ?, ?)";
            $result = executeQuery($query, [$constituencyId, $name, $code, $description], 'isss');
            break;
            
        case 'batch':
            $mandalId = $_POST['mandal_id'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            $query = "INSERT INTO batches (mandal_id, name, code, description, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)";
            $result = executeQuery($query, [$mandalId, $name, $code, $description, $startDate, $endDate], 'isssss');
            break;
    }
    
    if ($result) {
        setSuccessMessage(ucfirst($type) . ' added successfully!');
    } else {
        setErrorMessage('Error adding ' . $type . '. Please try again.');
    }
}

function handleEdit($type) {
    $id = $_POST['id'];
    $name = sanitizeInput($_POST['name']);
    $code = sanitizeInput($_POST['code']);
    $description = sanitizeInput($_POST['description']);
    $status = $_POST['status'];
    
    switch ($type) {
        case 'constituency':
            $query = "UPDATE constituencies SET name = ?, code = ?, description = ?, status = ? WHERE id = ?";
            $result = executeQuery($query, [$name, $code, $description, $status, $id], 'ssssi');
            break;
            
        case 'mandal':
            $constituencyId = $_POST['constituency_id'];
            $query = "UPDATE mandals SET constituency_id = ?, name = ?, code = ?, description = ?, status = ? WHERE id = ?";
            $result = executeQuery($query, [$constituencyId, $name, $code, $description, $status, $id], 'issssi');
            break;
            
        case 'batch':
            $mandalId = $_POST['mandal_id'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            $query = "UPDATE batches SET mandal_id = ?, name = ?, code = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?";
            $result = executeQuery($query, [$mandalId, $name, $code, $description, $startDate, $endDate, $status, $id], 'issssssi');
            break;
    }
    
    if ($result) {
        setSuccessMessage(ucfirst($type) . ' updated successfully!');
    } else {
        setErrorMessage('Error updating ' . $type . '. Please try again.');
    }
}

function handleDelete($type) {
    $id = $_POST['id'];
    
    switch ($type) {
        case 'constituency':
            $query = "DELETE FROM constituencies WHERE id = ?";
            break;
        case 'mandal':
            $query = "DELETE FROM mandals WHERE id = ?";
            break;
        case 'batch':
            $query = "DELETE FROM batches WHERE id = ?";
            break;
    }
    
    $result = executeQuery($query, [$id], 'i');
    
    if ($result) {
        setSuccessMessage(ucfirst($type) . ' deleted successfully!');
    } else {
        setErrorMessage('Error deleting ' . $type . '. It may be in use.');
    }
}

// Get current tab
$activeTab = $_GET['tab'] ?? 'constituencies';

// Get data for each section
$constituencies = fetchAll("SELECT * FROM constituencies ORDER BY name");
$mandals = fetchAll("SELECT m.*, c.name as constituency_name FROM mandals m LEFT JOIN constituencies c ON m.constituency_id = c.id ORDER BY m.name");
$batches = fetchAll("SELECT b.*, m.name as mandal_name, c.name as constituency_name FROM batches b LEFT JOIN mandals m ON b.mandal_id = m.id LEFT JOIN constituencies c ON m.constituency_id = c.id ORDER BY b.name");
?>

<!-- Settings Navigation -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cogs"></i>
            System Settings
        </h3>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab == 'constituencies' ? 'active' : ''; ?>" 
                   href="?tab=constituencies">
                    <i class="fas fa-map-marker-alt"></i> Constituencies
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab == 'mandals' ? 'active' : ''; ?>" 
                   href="?tab=mandals">
                    <i class="fas fa-building"></i> Mandals
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeTab == 'batches' ? 'active' : ''; ?>" 
                   href="?tab=batches">
                    <i class="fas fa-layer-group"></i> Batches
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Constituencies Tab -->
<?php if ($activeTab == 'constituencies'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-map-marker-alt"></i>
            Manage Constituencies
        </h3>
        <div class="card-tools">
            <button class="btn btn-primary" onclick="showAddModal('constituency')">
                <i class="fas fa-plus"></i> Add Constituency
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($constituencies as $constituency): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($constituency['name']); ?></td>
                        <td><?php echo htmlspecialchars($constituency['code']); ?></td>
                        <td><?php echo htmlspecialchars($constituency['description']); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($constituency['status']); ?>">
                                <?php echo ucfirst($constituency['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($constituency['created_at']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editItem('constituency', <?php echo htmlspecialchars(json_encode($constituency)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem('constituency', <?php echo $constituency['id']; ?>, '<?php echo htmlspecialchars($constituency['name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Mandals Tab -->
<?php if ($activeTab == 'mandals'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-building"></i>
            Manage Mandals
        </h3>
        <div class="card-tools">
            <button class="btn btn-primary" onclick="showAddModal('mandal')">
                <i class="fas fa-plus"></i> Add Mandal
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Constituency</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mandals as $mandal): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($mandal['name']); ?></td>
                        <td><?php echo htmlspecialchars($mandal['code']); ?></td>
                        <td><?php echo htmlspecialchars($mandal['constituency_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($mandal['description']); ?></td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($mandal['status']); ?>">
                                <?php echo ucfirst($mandal['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($mandal['created_at']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editItem('mandal', <?php echo htmlspecialchars(json_encode($mandal)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem('mandal', <?php echo $mandal['id']; ?>, '<?php echo htmlspecialchars($mandal['name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Batches Tab -->
<?php if ($activeTab == 'batches'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-layer-group"></i>
            Manage Batches
        </h3>
        <div class="card-tools">
            <button class="btn btn-primary" onclick="showAddModal('batch')">
                <i class="fas fa-plus"></i> Add Batch
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Mandal</th>
                        <th>Constituency</th>
                        <th>Duration</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($batch['name']); ?></td>
                        <td><?php echo htmlspecialchars($batch['code']); ?></td>
                        <td><?php echo htmlspecialchars($batch['mandal_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($batch['constituency_name'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($batch['start_date'] && $batch['end_date']): ?>
                                <?php echo formatDate($batch['start_date'], 'M d, Y'); ?> - 
                                <?php echo formatDate($batch['end_date'], 'M d, Y'); ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?php echo getStatusBadgeClass($batch['status']); ?>">
                                <?php echo ucfirst($batch['status']); ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($batch['created_at']); ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editItem('batch', <?php echo htmlspecialchars(json_encode($batch)); ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteItem('batch', <?php echo $batch['id']; ?>, '<?php echo htmlspecialchars($batch['name']); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add/Edit Modal -->
<div id="settingsModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
    <div class="modal-content" style="background-color: #fff; margin: 5% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 600px;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h4 id="modalTitle">Add Item</h4>
            <span class="close" onclick="closeModal()" style="font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
        </div>
        <div class="modal-body">
            <form id="settingsForm" method="POST">
                <input type="hidden" id="modalAction" name="action" value="add">
                <input type="hidden" id="modalType" name="type" value="">
                <input type="hidden" id="modalId" name="id" value="">
                
                <div class="form-group">
                    <label for="modalName" class="form-label">Name *</label>
                    <input type="text" id="modalName" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="modalCode" class="form-label">Code *</label>
                    <input type="text" id="modalCode" name="code" class="form-control" required>
                </div>
                
                <div id="constituencyField" class="form-group" style="display: none;">
                    <label for="modalConstituencyId" class="form-label">Constituency *</label>
                    <select id="modalConstituencyId" name="constituency_id" class="form-control">
                        <option value="">Select Constituency</option>
                        <?php foreach ($constituencies as $constituency): ?>
                            <option value="<?php echo $constituency['id']; ?>"><?php echo htmlspecialchars($constituency['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="mandalField" class="form-group" style="display: none;">
                    <label for="modalMandalId" class="form-label">Mandal *</label>
                    <select id="modalMandalId" name="mandal_id" class="form-control">
                        <option value="">Select Mandal</option>
                        <?php foreach ($mandals as $mandal): ?>
                            <option value="<?php echo $mandal['id']; ?>" data-constituency="<?php echo $mandal['constituency_id']; ?>"><?php echo htmlspecialchars($mandal['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="dateFields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modalStartDate" class="form-label">Start Date</label>
                                <input type="date" id="modalStartDate" name="start_date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="modalEndDate" class="form-label">End Date</label>
                                <input type="date" id="modalEndDate" name="end_date" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="modalDescription" class="form-label">Description</label>
                    <textarea id="modalDescription" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div id="statusField" class="form-group" style="display: none;">
                    <label for="modalStatus" class="form-label">Status</label>
                    <select id="modalStatus" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$inlineJS = "
    function showAddModal(type) {
        document.getElementById('modalAction').value = 'add';
        document.getElementById('modalType').value = type;
        document.getElementById('modalId').value = '';
        document.getElementById('modalTitle').textContent = 'Add ' + capitalizeFirst(type);
        
        // Reset form
        document.getElementById('settingsForm').reset();
        
        // Show/hide fields based on type
        setupModalFields(type);
        
        document.getElementById('settingsModal').style.display = 'block';
    }
    
    function editItem(type, data) {
        document.getElementById('modalAction').value = 'edit';
        document.getElementById('modalType').value = type;
        document.getElementById('modalId').value = data.id;
        document.getElementById('modalTitle').textContent = 'Edit ' + capitalizeFirst(type);
        
        // Populate form fields
        document.getElementById('modalName').value = data.name || '';
        document.getElementById('modalCode').value = data.code || '';
        document.getElementById('modalDescription').value = data.description || '';
        
        if (type === 'mandal') {
            document.getElementById('modalConstituencyId').value = data.constituency_id || '';
        }
        
        if (type === 'batch') {
            document.getElementById('modalMandalId').value = data.mandal_id || '';
            document.getElementById('modalStartDate').value = data.start_date || '';
            document.getElementById('modalEndDate').value = data.end_date || '';
        }
        
        if (data.status) {
            document.getElementById('modalStatus').value = data.status;
        }
        
        // Show/hide fields based on type
        setupModalFields(type, true);
        
        document.getElementById('settingsModal').style.display = 'block';
    }
    
    function setupModalFields(type, isEdit = false) {
        // Hide all optional fields first
        document.getElementById('constituencyField').style.display = 'none';
        document.getElementById('mandalField').style.display = 'none';
        document.getElementById('dateFields').style.display = 'none';
        document.getElementById('statusField').style.display = isEdit ? 'block' : 'none';
        
        // Show relevant fields based on type
        if (type === 'mandal') {
            document.getElementById('constituencyField').style.display = 'block';
        } else if (type === 'batch') {
            document.getElementById('mandalField').style.display = 'block';
            document.getElementById('dateFields').style.display = 'block';
        }
    }
    
    function deleteItem(type, id, name) {
        if (confirm('Are you sure you want to delete \"' + name + '\"?\\n\\nThis action cannot be undone and may affect related data.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type=\"hidden\" name=\"action\" value=\"delete\">' +
                           '<input type=\"hidden\" name=\"type\" value=\"' + type + '\">' +
                           '<input type=\"hidden\" name=\"id\" value=\"' + id + '\">';
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function closeModal() {
        document.getElementById('settingsModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('settingsModal');
        if (event.target === modal) {
            closeModal();
        }
    }
    
    // Cascade dropdowns in modal
    document.getElementById('modalConstituencyId').addEventListener('change', function() {
        const constituencyId = this.value;
        const mandalSelect = document.getElementById('modalMandalId');
        
        // Reset mandal options
        Array.from(mandalSelect.options).forEach(option => {
            if (option.value) {
                option.style.display = constituencyId && option.dataset.constituency != constituencyId ? 'none' : 'block';
            }
        });
        
        mandalSelect.value = '';
    });
";

require_once '../includes/footer.php';
?>
