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

$pageTitle = 'Batch Reports';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Batch Reports']
];



// Get filter options
$constituencies = fetchAll("SELECT id, name FROM constituencies WHERE status = 'active' ORDER BY name");
$mandals = fetchAll("SELECT id, name FROM mandals WHERE status = 'active' ORDER BY name");
$batches = fetchAll("SELECT id, name, code FROM batches WHERE status IN ('active', 'completed') ORDER BY status DESC, name");

require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Batch Reports
                    </h3>
                </div>
                
                <div class="card-body">
                    <!-- First Row: Dropdown Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="constituency_filter" class="form-label">Constituency</label>
                            <select class="form-control" id="constituency_filter">
                                <option value="">All Constituencies</option>
                                <?php foreach ($constituencies as $constituency): ?>
                                    <option value="<?php echo $constituency['id']; ?>">
                                        <?php echo htmlspecialchars($constituency['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="mandal_filter" class="form-label">Mandal</label>
                            <select class="form-control" id="mandal_filter">
                                <option value="">All Mandals</option>
                                <?php foreach ($mandals as $mandal): ?>
                                    <option value="<?php echo $mandal['id']; ?>">
                                        <?php echo htmlspecialchars($mandal['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="batch_filter" class="form-label">Batch</label>
                            <select class="form-control" id="batch_filter">
                                <option value="">All Batches</option>
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?php echo $batch['id']; ?>">
                                        <?php echo htmlspecialchars($batch['name'] . ' (' . $batch['code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="search_input" class="form-label">Search Students</label>
                            <input type="text" class="form-control" id="search_input" placeholder="Name, ID, Mobile...">
                        </div>
                    </div>
                    
                    <!-- Second Row: Statistics Cards -->
                    <div class="row mb-4" id="stats_cards">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-number" id="total_students">0</div>
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
                                    <div class="stats-number" id="active_students">0</div>
                                    <div class="stats-label">Active Students</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="stats-number" id="completed_students">0</div>
                                    <div class="stats-label">Completed Students</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-percentage"></i>
                                    </div>
                                    <div class="stats-number" id="avg_attendance">0%</div>
                                    <div class="stats-label">Avg Attendance</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Third Row: Data Table -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-table"></i> Student Details
                                    </h5>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success btn-sm" id="export_excel">
                                            <i class="fas fa-file-excel"></i> Export Excel
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm" id="refresh_data">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Table Controls -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <label for="rows_per_page" class="form-label mb-0">Rows per page:</label>
                                                <select class="form-control form-control-sm" id="rows_per_page" style="width: auto;">
                                                    <option value="10">10</option>
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <small class="text-muted" id="record_count">
                                                Total: 0 students
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <!-- Data Table -->
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped" id="students_table">
                                            <thead>
                                                <tr>
                                                    <th data-sort="b.full_name" style="cursor: pointer;">
                                                        Student Name <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="b.beneficiary_id" style="cursor: pointer;">
                                                        Student ID <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="b.mobile_number" style="cursor: pointer;">
                                                        Mobile <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="bt.name" style="cursor: pointer;">
                                                        Batch <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="m.name" style="cursor: pointer;">
                                                        Mandal <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="c.name" style="cursor: pointer;">
                                                        Constituency <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="attendance_percentage" style="cursor: pointer;">
                                                        Attendance % <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="b.status" style="cursor: pointer;">
                                                        Status <i class="fas fa-sort"></i>
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody id="table_body">
                                                <tr>
                                                    <td colspan="8" class="text-center">Loading...</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <div class="row mt-3">
                                        <div class="col-12">
                                            <nav aria-label="Page navigation">
                                                <ul class="pagination justify-content-center" id="pagination">
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loading_overlay" class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
</div>

<style>
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.8);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

.table th[data-sort] {
    cursor: pointer;
    user-select: none;
}

.table th[data-sort]:hover {
    background-color: #e9ecef;
}

.sort-icon {
    margin-left: 5px;
    opacity: 0.5;
}

.sort-icon.active {
    opacity: 1;
    color: #667eea;
}

.pagination .page-link {
    color: #667eea;
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

.stats-card {
    transition: transform 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.gap-2 {
    gap: 0.5rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .stats-card .stats-number {
        font-size: 2rem;
    }
    
    .stats-card .stats-icon {
        font-size: 2.5rem;
    }
}

/* Enhanced table styling */
.table th[data-sort]:hover {
    background-color: #e9ecef;
    transition: background-color 0.2s ease;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Loading animation */
.spinner-border {
    width: 3rem;
    height: 3rem;
}
</style>

<script>
let currentPage = 1;
let currentSort = 'b.full_name';
let currentSortOrder = 'ASC';
let currentFilters = {};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    
    // Initialize dropdowns
    loadMandals();
    loadBatches();
    
    // Load initial data with a small delay to ensure DOM is ready
    setTimeout(() => {
        loadData();
    }, 100);
});

function setupEventListeners() {
    // Filter change events
    document.getElementById('constituency_filter').addEventListener('change', function() {
        currentFilters.constituency = this.value;
        currentFilters.mandal = ''; // Reset mandal when constituency changes
        currentFilters.batch = ''; // Reset batch when constituency changes
        currentPage = 1;
        
        // Reset mandal and batch dropdowns
        document.getElementById('mandal_filter').value = '';
        document.getElementById('batch_filter').value = '';
        
        // Load mandals for selected constituency
        if (this.value) {
            loadMandals(this.value);
        } else {
            // Reset mandal dropdown to show all
            loadMandals();
        }
        
        // Reset batch dropdown to show all
        loadBatches();
        
        loadData();
    });
    
    document.getElementById('mandal_filter').addEventListener('change', function() {
        currentFilters.mandal = this.value;
        currentFilters.batch = ''; // Reset batch when mandal changes
        currentPage = 1;
        
        // Reset batch dropdown
        document.getElementById('batch_filter').value = '';
        
        // Load batches for selected mandal
        if (this.value) {
            loadBatches(this.value);
        } else {
            // Reset batch dropdown to show all
            loadBatches();
        }
        
        loadData();
    });
    
    document.getElementById('batch_filter').addEventListener('change', function() {
        currentFilters.batch = this.value;
        currentPage = 1;
        loadData();
    });
    
    // Search input with debounce
    let searchTimeout;
    document.getElementById('search_input').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentFilters.search = this.value;
            currentPage = 1;
            loadData();
        }, 500);
    });
    
    // Rows per page change
    document.getElementById('rows_per_page').addEventListener('change', function() {
        currentPage = 1;
        loadData();
    });
    
    // Refresh button
    document.getElementById('refresh_data').addEventListener('click', function() {
        loadData();
    });
    
    // Export button
    document.getElementById('export_excel').addEventListener('click', function() {
        exportToExcel();
    });
    
    // Sort headers
    document.querySelectorAll('th[data-sort]').forEach(th => {
        th.addEventListener('click', function() {
            const sortField = this.getAttribute('data-sort');
            if (currentSort === sortField) {
                currentSortOrder = currentSortOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentSort = sortField;
                currentSortOrder = 'ASC';
            }
            loadData();
        });
    });
}

function loadData() {
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'get_batch_data');
    formData.append('page', currentPage);
    formData.append('limit', document.getElementById('rows_per_page').value);
    formData.append('sort_by', currentSort);
    formData.append('sort_order', currentSortOrder);
    
    // Add filters
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key]) {
            formData.append(key, currentFilters[key]);
        }
    });
    
    fetch('batch_reports_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayData(data.data);
            displayPagination(data.pages, data.current_page);
            updateRecordCount(data.total);
            loadStats();
        } else {
            showError('Failed to load data: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load data');
    })
    .finally(() => {
        hideLoading();
    });
}

function loadStats() {
    const formData = new FormData();
    formData.append('action', 'get_batch_stats');
    
    // Add filters
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key]) {
            formData.append(key, currentFilters[key]);
        }
    });
    
    fetch('batch_reports_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStats(data.stats);
        }
    })
    .catch(error => {
        console.error('Error loading stats:', error);
    });
}

function displayData(data) {
    const tbody = document.getElementById('table_body');
    
    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No students found</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach(student => {
        const attendanceClass = student.attendance_percentage >= 80 ? 'text-success' : 
                              student.attendance_percentage >= 60 ? 'text-warning' : 'text-danger';
        
        html += `
            <tr>
                <td><strong>${escapeHtml(student.full_name)}</strong></td>
                <td><span class="badge badge-info">${escapeHtml(student.beneficiary_id)}</span></td>
                <td>${escapeHtml(student.mobile_number || 'N/A')}</td>
                <td>
                    <span class="badge badge-primary">${escapeHtml(student.batch_name)}</span><br>
                    <small class="text-muted">${escapeHtml(student.batch_code)}</small>
                </td>
                <td>${escapeHtml(student.mandal_name)}</td>
                <td>${escapeHtml(student.constituency_name)}</td>
                <td>
                    <span class="${attendanceClass} font-weight-bold">
                        ${student.attendance_percentage}%
                    </span><br>
                    <small class="text-muted">${student.present_days}/${student.total_days} days</small>
                </td>
                <td>
                    <span class="badge ${getStatusBadgeClass(student.beneficiary_status)}">
                        ${escapeHtml(student.beneficiary_status)}
                    </span>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

function displayPagination(totalPages, currentPage) {
    const pagination = document.getElementById('pagination');
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Previous button
    html += `
        <li class="page-item ${currentPage == 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${currentPage - 1})">Previous</a>
        </li>
    `;
    
    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        html += `
            <li class="page-item ${i == currentPage ? 'active' : ''}">
                <a class="page-link" href="#" onclick="goToPage(${i})">${i}</a>
            </li>
        `;
    }
    
    // Next button
    html += `
        <li class="page-item ${currentPage == totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="goToPage(${currentPage + 1})">Next</a>
        </li>
    `;
    
    pagination.innerHTML = html;
}

function goToPage(page) {
    if (page < 1) return;
    currentPage = page;
    loadData();
}

function updateStats(stats) {
    document.getElementById('total_students').textContent = stats.total_students || 0;
    document.getElementById('active_students').textContent = stats.active_students || 0;
    document.getElementById('completed_students').textContent = stats.completed_students || 0;
    document.getElementById('avg_attendance').textContent = (stats.avg_attendance_percentage || 0) + '%';
}

function updateRecordCount(total) {
    document.getElementById('record_count').textContent = `Total: ${total} students`;
}

function exportToExcel() {
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'get_batch_data');
    formData.append('limit', 10000); // Get all data for export
    formData.append('sort_by', currentSort);
    formData.append('sort_order', currentSortOrder);
    
    // Add filters
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key]) {
            formData.append(key, currentFilters[key]);
        }
    });
    
    fetch('batch_reports_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            generateExcel(data.data);
        } else {
            showError('Failed to export data: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to export data');
    })
    .finally(() => {
        hideLoading();
    });
}

function generateExcel(data) {
    // Create CSV content
    let csv = 'Student Name,Student ID,Mobile,Batch,Batch Code,Mandal,Constituency,Attendance %,Present Days,Total Days,Status\n';
    
    data.forEach(student => {
        csv += `"${student.full_name}","${student.beneficiary_id}","${student.mobile_number || ''}","${student.batch_name}","${student.batch_code}","${student.mandal_name}","${student.constituency_name}","${student.attendance_percentage}%","${student.present_days}","${student.total_days}","${student.beneficiary_status}"\n`;
    });
    
    // Create and download file
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `batch_report_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function showLoading() {
    document.getElementById('loading_overlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loading_overlay').style.display = 'none';
}

function showError(message) {
    // You can implement a better error display system
    alert(message);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'active': return 'badge-success';
        case 'completed': return 'badge-info';
        case 'dropped': return 'badge-danger';
        case 'inactive': return 'badge-secondary';
        default: return 'badge-secondary';
    }
}

function loadMandals(constituencyId = '') {
    const mandalSelect = document.getElementById('mandal_filter');
    
    if (!constituencyId) {
        // Load all mandals
        fetch('get_mandals.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mandalSelect.innerHTML = '<option value="">All Mandals</option>';
                data.mandals.forEach(mandal => {
                    mandalSelect.innerHTML += `<option value="${mandal.id}">${mandal.name}</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading mandals:', error));
    } else {
        // Load mandals for specific constituency
        fetch(`get_mandals.php?constituency_id=${constituencyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mandalSelect.innerHTML = '<option value="">All Mandals</option>';
                data.mandals.forEach(mandal => {
                    mandalSelect.innerHTML += `<option value="${mandal.id}">${mandal.name}</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading mandals:', error));
    }
}

function loadBatches(mandalId = '') {
    const batchSelect = document.getElementById('batch_filter');
    
    if (!mandalId) {
        // Load all batches
        fetch('get_batches.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                batchSelect.innerHTML = '<option value="">All Batches</option>';
                data.batches.forEach(batch => {
                    batchSelect.innerHTML += `<option value="${batch.id}">${batch.name} (${batch.code})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading batches:', error));
    } else {
        // Load batches for specific mandal
        fetch(`get_batches.php?mandal_id=${mandalId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                batchSelect.innerHTML = '<option value="">All Batches</option>';
                data.batches.forEach(batch => {
                    batchSelect.innerHTML += `<option value="${batch.id}">${batch.name} (${batch.code})</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading batches:', error));
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
