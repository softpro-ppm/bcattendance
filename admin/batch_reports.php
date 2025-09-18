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
                                        <button type="button" class="btn btn-warning btn-sm" id="debug_export">
                                            <i class="fas fa-bug"></i> Debug Export
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
                                                    <th>Sr. No.</th>
                                                    <th data-sort="b.full_name" style="cursor: pointer;">
                                                        Student Name <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th data-sort="b.aadhar_number" style="cursor: pointer;">
                                                        Aadhar Number <i class="fas fa-sort"></i>
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
                                                    Attendance % <i class="fas fa-sort"></i><br>
                                                    <small class="text-muted">(Working Days)</small>
                                                </th>
                                                    <th data-sort="b.status" style="cursor: pointer;">
                                                        Status <i class="fas fa-sort"></i>
                                                    </th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table_body">
                                                <tr>
                                                    <td colspan="10" class="text-center">Loading...</td>
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

<!-- Student Calendar Modal -->
<div class="modal fade" id="studentCalendarModal" tabindex="-1" role="dialog" aria-labelledby="studentCalendarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentCalendarModalLabel">
                    <i class="fas fa-calendar-alt"></i> Student Attendance Calendar
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Student Info -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6 id="modalStudentName"></h6>
                            <p class="mb-1" id="modalStudentBatch"></p>
                            <p class="mb-0" id="modalStudentPeriod"></p>
                        </div>
                    </div>
                </div>
                
                <!-- Month Navigation -->
                <div class="row mb-3">
                    <div class="col-12 text-center">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" onclick="previousMonth()">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>
                            <button type="button" class="btn btn-primary" id="currentMonthDisplay">
                                Loading...
                            </button>
                            <button type="button" class="btn btn-outline-primary" onclick="nextMonth()">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar Legend -->
                <div class="calendar-legend mb-3">
                    <div class="d-flex justify-content-center flex-wrap">
                        <div class="legend-item mr-3">
                            <span class="legend-color present-cell"></span>
                            <span>Present</span>
                        </div>
                        <div class="legend-item mr-3">
                            <span class="legend-color absent-cell"></span>
                            <span>Absent</span>
                        </div>
                        <div class="legend-item mr-3">
                            <span class="legend-color holiday-cell"></span>
                            <span>Holiday</span>
                        </div>
                        <div class="legend-item mr-3">
                            <span class="legend-color sunday-cell"></span>
                            <span>Sunday</span>
                        </div>
                        <div class="legend-item mr-3">
                            <span class="legend-color not-marked-cell"></span>
                            <span>Not Marked</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-color outside-batch-cell"></span>
                            <span>Outside Batch Period</span>
                        </div>
                    </div>
                </div>
                
                <!-- Calendar Grid -->
                <div id="calendarContainer">
                    <!-- Calendar will be loaded here -->
                </div>
                
                <!-- Monthly Summary -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Monthly Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="summary-item">
                                            <div class="summary-number text-success" id="modalPresentCount">0</div>
                                            <div class="summary-label">Present</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="summary-item">
                                            <div class="summary-number text-danger" id="modalAbsentCount">0</div>
                                            <div class="summary-label">Absent</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="summary-item">
                                            <div class="summary-number text-warning" id="modalHolidayCount">0</div>
                                            <div class="summary-label">Holidays</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <strong>Working Days:</strong> <span id="modalWorkingDays">0</span>
                                    </div>
                                    <div class="col-6">
                                        <strong>Attendance %:</strong> <span id="modalAttendancePercentage">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <button class="btn btn-success btn-sm mr-2" onclick="printCalendar()">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <button class="btn btn-info btn-sm" onclick="exportCalendarData()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
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

/* Calendar Styles */
.calendar-container {
    max-width: 100%;
    overflow-x: auto;
}

.calendar-grid {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.calendar-row {
    display: flex;
    border-bottom: 1px solid #dee2e6;
}

.calendar-row:last-child {
    border-bottom: none;
}

.calendar-header {
    background-color: #f8f9fa;
    font-weight: bold;
}

.calendar-cell {
    flex: 1;
    min-height: 70px;
    padding: 6px;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    font-size: 12px;
}

.calendar-cell:last-child {
    border-right: none;
}

.date-number {
    font-weight: bold;
    font-size: 12px;
}

.status-indicator {
    text-align: center;
    margin-top: auto;
    font-size: 10px;
}

.holiday-name {
    font-size: 8px;
    text-align: center;
    margin-top: 2px;
    color: #856404;
    font-weight: bold;
    line-height: 1.2;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Cell Colors */
.present-cell {
    background-color: #d4edda;
    border-left: 3px solid #28a745;
}

.absent-cell {
    background-color: #f8d7da;
    border-left: 3px solid #dc3545;
}

.holiday-cell {
    background-color: #fff3cd;
    border-left: 3px solid #ffc107;
}

.sunday-cell {
    background-color: #e2e3e5;
    border-left: 3px solid #6c757d;
}

.not-marked-cell {
    background-color: #f8f9fa;
    border-left: 3px solid #adb5bd;
}

.outside-batch-cell {
    background-color: #f8f9fa;
    border-left: 3px solid #6c757d;
    opacity: 0.6;
}

.empty-cell {
    background-color: #f8f9fa;
}

/* Legend */
.calendar-legend {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-bottom: 5px;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    margin-right: 6px;
    border: 1px solid #dee2e6;
}

.legend-color.present-cell {
    background-color: #d4edda;
    border-color: #28a745;
}

.legend-color.absent-cell {
    background-color: #f8d7da;
    border-color: #dc3545;
}

.legend-color.holiday-cell {
    background-color: #fff3cd;
    border-color: #ffc107;
}

.legend-color.sunday-cell {
    background-color: #e2e3e5;
    border-color: #6c757d;
}

.legend-color.not-marked-cell {
    background-color: #f8f9fa;
    border-color: #adb5bd;
}

.legend-color.outside-batch-cell {
    background-color: #f8f9fa;
    border-color: #6c757d;
}

/* Summary */
.summary-item {
    padding: 8px;
}

.summary-number {
    font-size: 20px;
    font-weight: bold;
}

.summary-label {
    font-size: 11px;
    color: #6c757d;
    text-transform: uppercase;
}

/* Modal specific styles */
.modal-lg {
    max-width: 800px;
}

@media (max-width: 768px) {
    .calendar-cell {
        min-height: 60px;
        padding: 4px;
    }
    
    .date-number {
        font-size: 10px;
    }
    
    .holiday-name {
        font-size: 7px;
    }
    
    .legend-item {
        margin-bottom: 8px;
    }
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
    
    // Debug export button
    document.getElementById('debug_export').addEventListener('click', function() {
        debugExport();
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
        tbody.innerHTML = '<tr><td colspan="9" class="text-center">No students found</td></tr>';
        return;
    }
    
    let html = '';
    data.forEach((student, index) => {
        const attendanceClass = student.attendance_percentage >= 80 ? 'text-success' : 
                              student.attendance_percentage >= 60 ? 'text-warning' : 'text-danger';
        
        // Calculate serial number based on current page and rows per page
        const serialNumber = ((currentPage - 1) * document.getElementById('rows_per_page').value) + index + 1;
        
        html += `
            <tr>
                <td class="text-center"><strong>${serialNumber}</strong></td>
                <td><strong>${escapeHtml(student.full_name)}</strong></td>
                <td><span class="badge badge-info">${escapeHtml(student.aadhar_number || 'N/A')}</span></td>
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
                    <small class="text-muted">${student.present_days}/${student.total_days} working days</small>
                </td>
                <td>
                    <span class="badge ${getStatusBadgeClass(student.beneficiary_status)}">
                        ${escapeHtml(student.beneficiary_status)}
                    </span>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-primary btn-sm" onclick="viewStudentCalendar(${student.id}, '${escapeHtml(student.full_name)}', '${escapeHtml(student.batch_name)}', '${student.batch_start}', '${student.batch_end}')">
                        <i class="fas fa-calendar-alt"></i> View
                    </button>
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
    
    console.log('Exporting data with filters:', currentFilters);
    
    fetch('batch_reports_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Export data received:', data);
        if (data.success) {
            if (data.data && data.data.length > 0) {
                generateExcel(data.data);
            } else {
                showError('No data available to export. Please check your filters.');
            }
        } else {
            showError('Failed to export data: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Export error:', error);
        showError('Failed to export data: ' + error.message);
    })
    .finally(() => {
        hideLoading();
    });
}

function debugExport() {
    console.log('=== DEBUG EXPORT ===');
    console.log('Current filters:', currentFilters);
    console.log('Current sort:', currentSort, currentSortOrder);
    console.log('Current page:', currentPage);
    
    // Test API connection
    const formData = new FormData();
    formData.append('action', 'get_batch_data');
    formData.append('limit', 5); // Just get 5 records for testing
    formData.append('sort_by', currentSort);
    formData.append('sort_order', currentSortOrder);
    
    // Add filters
    Object.keys(currentFilters).forEach(key => {
        if (currentFilters[key]) {
            formData.append(key, currentFilters[key]);
        }
    });
    
    console.log('Testing API call...');
    
    fetch('batch_reports_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('API Response status:', response.status);
        console.log('API Response headers:', response.headers);
        return response.text(); // Get raw text first
    })
    .then(text => {
        console.log('Raw API response:', text);
        try {
            const data = JSON.parse(text);
            console.log('Parsed API data:', data);
            
            if (data.success) {
                console.log('API Success! Records found:', data.data ? data.data.length : 0);
                console.log('Total records:', data.total);
                console.log('Sample record:', data.data ? data.data[0] : 'No data');
                showSuccess(`Debug: API working! Found ${data.total} total records, ${data.data ? data.data.length : 0} in this page.`);
            } else {
                console.log('API Error:', data.error);
                showError('Debug: API Error - ' + data.error);
            }
        } catch (e) {
            console.log('JSON Parse Error:', e);
            console.log('Raw response was:', text);
            showError('Debug: Invalid JSON response from API');
        }
    })
    .catch(error => {
        console.error('Debug API error:', error);
        showError('Debug: Network error - ' + error.message);
    });
}

function generateExcel(data) {
    // Create CSV content with proper headers
    let csv = 'Sr. No.,Student Name,Aadhar Number,Mobile,Batch,Batch Code,Mandal,Constituency,Attendance %,Present Days,Working Days,Status\n';
    
    data.forEach((student, index) => {
        const serialNumber = index + 1;
        csv += `"${serialNumber}","${student.full_name}","${student.aadhar_number || ''}","${student.mobile_number || ''}","${student.batch_name}","${student.batch_code}","${student.mandal_name}","${student.constituency_name}","${student.attendance_percentage}%","${student.present_days}","${student.total_days}","${student.beneficiary_status}"\n`;
    });
    
    // Create and download file with proper Excel extension
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    
    // Generate filename with current date and time
    const now = new Date();
    const timestamp = now.toISOString().split('T')[0] + '_' + now.toTimeString().split(' ')[0].replace(/:/g, '-');
    link.setAttribute('download', `batch_report_${timestamp}.csv`);
    
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    showSuccess('Report exported successfully!');
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

function showSuccess(message) {
    // Create a temporary success message
    const successDiv = document.createElement('div');
    successDiv.className = 'alert alert-success alert-dismissible fade show';
    successDiv.style.position = 'fixed';
    successDiv.style.top = '20px';
    successDiv.style.right = '20px';
    successDiv.style.zIndex = '9999';
    successDiv.style.minWidth = '300px';
    successDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    document.body.appendChild(successDiv);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (successDiv.parentNode) {
            successDiv.parentNode.removeChild(successDiv);
        }
    }, 3000);
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

// Calendar Modal Functions
let currentStudentId = null;
let currentCalendarMonth = null;
let currentCalendarYear = null;

function viewStudentCalendar(studentId, studentName, batchName, batchStartDate, batchEndDate) {
    currentStudentId = studentId;
    
    // Set modal content
    document.getElementById('modalStudentName').innerHTML = `<i class="fas fa-user"></i> ${studentName}`;
    document.getElementById('modalStudentBatch').innerHTML = `<strong>Batch:</strong> ${batchName}`;
    document.getElementById('modalStudentPeriod').innerHTML = `<strong>Period:</strong> ${formatDate(batchStartDate)} to ${formatDate(batchEndDate)}`;
    
    // Set initial month to batch start date
    const startDate = new Date(batchStartDate);
    currentCalendarMonth = startDate.getMonth();
    currentCalendarYear = startDate.getFullYear();
    
    // Load calendar
    loadStudentCalendar();
    
    // Show modal
    $('#studentCalendarModal').modal('show');
}

function loadStudentCalendar() {
    if (!currentStudentId || currentCalendarMonth === null || currentCalendarYear === null) return;
    
    const formData = new FormData();
    formData.append('action', 'get_student_calendar');
    formData.append('student_id', currentStudentId);
    formData.append('month', currentCalendarMonth + 1);
    formData.append('year', currentCalendarYear);
    
    fetch('batch_reports_api.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayCalendar(data.calendar);
            updateModalSummary(data.summary);
            updateMonthDisplay();
        } else {
            showError('Failed to load calendar: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to load calendar');
    });
}

function displayCalendar(calendarData) {
    const container = document.getElementById('calendarContainer');
    
    let html = '<div class="calendar-grid">';
    
    // Day headers
    html += '<div class="calendar-row calendar-header">';
    ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(day => {
        html += `<div class="calendar-cell">${day}</div>`;
    });
    html += '</div>';
    
    // Calendar rows
    calendarData.forEach(week => {
        html += '<div class="calendar-row">';
        week.forEach(day => {
            if (day.isCurrentMonth) {
                html += `
                    <div class="calendar-cell ${day.class}" data-toggle="tooltip" title="${day.tooltip}">
                        <div class="date-number">${day.date}</div>
                        ${day.statusIcon ? `<div class="status-indicator">${day.statusIcon}</div>` : ''}
                        ${day.holidayName && day.status !== 'sunday' ? `<div class="holiday-name">${day.holidayName}</div>` : ''}
                    </div>
                `;
            } else {
                html += '<div class="calendar-cell empty-cell"></div>';
            }
        });
        html += '</div>';
    });
    
    html += '</div>';
    container.innerHTML = html;
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
}

function updateModalSummary(summary) {
    document.getElementById('modalPresentCount').textContent = summary.present || 0;
    document.getElementById('modalAbsentCount').textContent = summary.absent || 0;
    document.getElementById('modalHolidayCount').textContent = (summary.holiday || 0) + (summary.sunday || 0);
    document.getElementById('modalWorkingDays').textContent = summary.workingDays || 0;
    document.getElementById('modalAttendancePercentage').textContent = (summary.attendancePercentage || 0) + '%';
}

function updateMonthDisplay() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonthDisplay').textContent = 
        `${monthNames[currentCalendarMonth]} ${currentCalendarYear}`;
}

function previousMonth() {
    if (currentCalendarMonth === 0) {
        currentCalendarMonth = 11;
        currentCalendarYear--;
    } else {
        currentCalendarMonth--;
    }
    loadStudentCalendar();
}

function nextMonth() {
    if (currentCalendarMonth === 11) {
        currentCalendarMonth = 0;
        currentCalendarYear++;
    } else {
        currentCalendarMonth++;
    }
    loadStudentCalendar();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function printCalendar() {
    const printWindow = window.open('', '_blank');
    const modalContent = document.querySelector('.modal-body').innerHTML;
    
    printWindow.document.write(`
        <html>
            <head>
                <title>Student Calendar - Print</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .calendar-grid { border: 1px solid #000; }
                    .calendar-row { display: flex; }
                    .calendar-cell { flex: 1; border: 1px solid #ccc; padding: 5px; text-align: center; }
                    .calendar-header { background-color: #f0f0f0; font-weight: bold; }
                </body>
                </html>
            </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}

function exportCalendarData() {
    // Export calendar data as CSV
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    const monthName = monthNames[currentCalendarMonth];
    const fileName = `student_calendar_${monthName}_${currentCalendarYear}.csv`;
    
    // This would need to be implemented with actual calendar data
    alert('Export functionality will be implemented here');
}
</script>

<?php require_once '../includes/footer.php'; ?>
