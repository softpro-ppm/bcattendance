<?php
$pageTitle = 'Reports';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Reports']
];

require_once 'includes/header.php';

// Get TC info from session
$tc_id = $_SESSION['tc_user_training_center_id'];
$mandal_id = $_SESSION['tc_user_mandal_id'];

// Get filter parameters
$report_type = $_GET['report_type'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$batch_filter = $_GET['batch_id'] ?? '';

// Get batches for this training center
$batches_query = "SELECT * FROM batches WHERE tc_id = ? AND status = 'active' ORDER BY name";
$batches = fetchAll($batches_query, [$tc_id], 'i') ?: [];

$report_data = [];
$report_title = '';

if ($report_type) {
    switch ($report_type) {
        case 'daily_summary':
            $report_title = 'Daily Attendance Summary';
            $summary_query = "SELECT 
                                a.attendance_date,
                                COUNT(*) as total_marked,
                                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                                SUM(CASE WHEN a.status = 'excused' THEN 1 ELSE 0 END) as excused_count
                              FROM attendance a
                              JOIN beneficiaries ben ON a.beneficiary_id = ben.id
                              JOIN batches b ON ben.batch_id = b.id
                              WHERE b.tc_id = ? AND a.attendance_date BETWEEN ? AND ?";
            $params = [$tc_id, $date_from, $date_to];
            $types = 'iss';
            
            if ($batch_filter) {
                $summary_query .= " AND ben.batch_id = ?";
                $params[] = $batch_filter;
                $types .= 'i';
            }
            
            $summary_query .= " GROUP BY a.attendance_date ORDER BY a.attendance_date DESC";
            $report_data = fetchAll($summary_query, $params, $types) ?: [];
            break;

        case 'student_attendance':
            $report_title = 'Student Attendance Report';
            $students_query = "SELECT ben.full_name as student_name, ben.id, b.name as batch_name
                              FROM beneficiaries ben
                              JOIN batches b ON ben.batch_id = b.id
                              WHERE b.tc_id = ? AND (ben.status = 'active' OR ben.status = 'completed')";
            $params = [$tc_id];
            $types = 'i';
            
            if ($batch_filter) {
                $students_query .= " AND ben.batch_id = ?";
                $params[] = $batch_filter;
                $types .= 'i';
            }
            
            $students = fetchAll($students_query, $params, $types) ?: [];
            $report_data = [];
            
            foreach ($students as $student) {
                $attendance_query = "SELECT 
                                    COUNT(*) as total_days,
                                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
                                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days
                                   FROM attendance 
                                   WHERE beneficiary_id = ? AND attendance_date BETWEEN ? AND ?";
                $attendance_result = fetchRow($attendance_query, [$student['id'], $date_from, $date_to], 'iss');
                
                if ($attendance_result && $attendance_result['total_days'] > 0) {
                    $student['total_days'] = $attendance_result['total_days'];
                    $student['present_days'] = $attendance_result['present_days'];
                    $student['absent_days'] = $attendance_result['absent_days'];
                    $student['late_days'] = $attendance_result['late_days'];
                    $student['attendance_percentage'] = round(($attendance_result['present_days'] / $attendance_result['total_days']) * 100, 2);
                    $report_data[] = $student;
                }
            }
            break;

        case 'batch_summary':
            $report_title = 'Batch-wise Summary';
            $batches_query = "SELECT * FROM batches WHERE tc_id = ? AND status = 'active'";
            $params = [$tc_id];
            $types = 'i';
            
            if ($batch_filter) {
                $batches_query .= " AND id = ?";
                $params[] = $batch_filter;
                $types .= 'i';
            }
            
            $batches_data = fetchAll($batches_query, $params, $types) ?: [];
            $report_data = [];
            
            foreach ($batches_data as $batch) {
                $students_count_query = "SELECT COUNT(*) as count FROM beneficiaries WHERE batch_id = ? AND (status = 'active' OR status = 'completed')";
                $students_count = fetchRow($students_count_query, [$batch['id']], 'i')['count'];
                
                $attendance_query = "SELECT 
                                    COUNT(*) as total_attendance_records,
                                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as total_present,
                                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as total_absent
                                   FROM attendance a
                                   JOIN beneficiaries ben ON a.beneficiary_id = ben.id
                                   WHERE ben.batch_id = ? AND a.attendance_date BETWEEN ? AND ?";
                $attendance_result = fetchRow($attendance_query, [$batch['id'], $date_from, $date_to], 'iss');
                
                $batch['batch_name'] = $batch['name'];
                $batch['batch_code'] = $batch['code'];
                $batch['total_students'] = $students_count;
                $batch['total_attendance_records'] = $attendance_result['total_attendance_records'] ?? 0;
                $batch['total_present'] = $attendance_result['total_present'] ?? 0;
                $batch['total_absent'] = $attendance_result['total_absent'] ?? 0;
                $batch['avg_attendance_rate'] = $attendance_result['total_attendance_records'] > 0 ? 
                    round(($attendance_result['total_present'] / $attendance_result['total_attendance_records']) * 100, 2) : 0;
                
                $report_data[] = $batch;
            }
            break;
    }
}

// Calculate summary statistics for display
$reportData = [
    'summary' => [
        'total_records' => count($report_data),
        'present_count' => 0,
        'absent_count' => 0,
        'attendance_rate' => 0
    ]
];

if ($report_type == 'daily_summary') {
    foreach ($report_data as $day) {
        $reportData['summary']['present_count'] += $day['present_count'] ?? 0;
        $reportData['summary']['absent_count'] += $day['absent_count'] ?? 0;
    }
} elseif ($report_type == 'student_attendance') {
    foreach ($report_data as $student) {
        $reportData['summary']['present_count'] += $student['present_days'] ?? 0;
        $reportData['summary']['absent_count'] += $student['absent_days'] ?? 0;
    }
}

$total_attendance = $reportData['summary']['present_count'] + $reportData['summary']['absent_count'];
$reportData['summary']['attendance_rate'] = $total_attendance > 0 ? 
    round(($reportData['summary']['present_count'] / $total_attendance) * 100, 2) : 0;
?>

<!-- Report Summary Stats -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['total_records']); ?></div>
                <div class="stats-label">Total Records</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['present_count']); ?></div>
                <div class="stats-label">Present</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stats-number"><?php echo number_format($reportData['summary']['absent_count']); ?></div>
                <div class="stats-label">Absent</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <div class="stats-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stats-number"><?php echo $reportData['summary']['attendance_rate']; ?>%</div>
                <div class="stats-label">Attendance Rate</div>
            </div>
        </div>
    </div>
</div>

<!-- Report Filters -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-filter"></i>
                    Report Filters
                </h3>
                <div class="card-tools">
                    <span class="badge badge-warning">TC Only Access</span>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="report_type">Report Type:</label>
                            <select name="report_type" id="report_type" class="form-control">
                                <option value="">Select Report Type</option>
                                <option value="daily_summary" <?php echo $report_type == 'daily_summary' ? 'selected' : ''; ?>>Daily Summary</option>
                                <option value="student_attendance" <?php echo $report_type == 'student_attendance' ? 'selected' : ''; ?>>Student Attendance</option>
                                <option value="batch_summary" <?php echo $report_type == 'batch_summary' ? 'selected' : ''; ?>>Batch Summary</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_from">From Date:</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="date_to">To Date:</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="batch_id">Filter by Batch:</label>
                            <select name="batch_id" id="batch_id" class="form-control">
                                <option value="">All Batches</option>
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?php echo $batch['id']; ?>" <?php echo $batch['id'] == $batch_filter ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($batch['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-chart-bar"></i> Generate
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Report Results -->
<?php if ($report_type && !empty($report_data)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i>
                    <?php echo $report_title; ?>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" onclick="exportReport()">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button type="button" class="btn btn-info btn-sm" onclick="printReport()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php if ($report_type == 'daily_summary'): ?>
                        <table class="table table-striped table-bordered" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Marked</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Excused</th>
                                    <th>Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $day): ?>
                                <tr>
                                    <td><?php echo date('d M Y', strtotime($day['attendance_date'])); ?></td>
                                    <td><?php echo $day['total_marked']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $day['present_count']; ?></span></td>
                                    <td><span class="badge badge-danger"><?php echo $day['absent_count']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $day['late_count']; ?></span></td>
                                    <td><span class="badge badge-info"><?php echo $day['excused_count']; ?></span></td>
                                    <td>
                                        <?php $rate = $day['total_marked'] > 0 ? round(($day['present_count'] / $day['total_marked']) * 100, 2) : 0; ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $rate >= 80 ? 'success' : ($rate >= 60 ? 'warning' : 'danger'); ?>" 
                                                 style="width: <?php echo $rate; ?>%">
                                                <?php echo $rate; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($report_type == 'student_attendance'): ?>
                        <table class="table table-striped table-bordered" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Batch</th>
                                    <th>Total Days</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Late</th>
                                    <th>Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['batch_name']); ?></td>
                                    <td><?php echo $student['total_days']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $student['present_days']; ?></span></td>
                                    <td><span class="badge badge-danger"><?php echo $student['absent_days']; ?></span></td>
                                    <td><span class="badge badge-warning"><?php echo $student['late_days']; ?></span></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $student['attendance_percentage'] >= 80 ? 'success' : ($student['attendance_percentage'] >= 60 ? 'warning' : 'danger'); ?>" 
                                                 style="width: <?php echo $student['attendance_percentage']; ?>%">
                                                <?php echo $student['attendance_percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                    <?php elseif ($report_type == 'batch_summary'): ?>
                        <table class="table table-striped table-bordered" id="reportTable">
                            <thead>
                                <tr>
                                    <th>Batch Name</th>
                                    <th>Batch Code</th>
                                    <th>Total Students</th>
                                    <th>Attendance Records</th>
                                    <th>Present</th>
                                    <th>Absent</th>
                                    <th>Avg Attendance Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data as $batch): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($batch['batch_name']); ?></td>
                                    <td><?php echo htmlspecialchars($batch['batch_code']); ?></td>
                                    <td><?php echo $batch['total_students']; ?></td>
                                    <td><?php echo $batch['total_attendance_records']; ?></td>
                                    <td><span class="badge badge-success"><?php echo $batch['total_present']; ?></span></td>
                                    <td><span class="badge badge-danger"><?php echo $batch['total_absent']; ?></span></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $batch['avg_attendance_rate'] >= 80 ? 'success' : ($batch['avg_attendance_rate'] >= 60 ? 'warning' : 'danger'); ?>" 
                                                 style="width: <?php echo $batch['avg_attendance_rate']; ?>%">
                                                <?php echo $batch['avg_attendance_rate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php elseif ($report_type): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No data found for the selected criteria</h5>
                <p class="text-muted">Try adjusting your date range or filters.</p>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Select a report type to generate reports</h5>
                <p class="text-muted">Choose from daily summary, student attendance, or batch summary reports.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>



<script>
function exportReport() {
    if (!document.getElementById('reportTable')) {
        alert('No report data to export. Please generate a report first.');
        return;
    }
    
    // Simple CSV export
    const table = document.getElementById('reportTable');
    let csv = [];
    
    // Headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push('"' + th.textContent.trim() + '"');
    });
    csv.push(headers.join(','));
    
    // Rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim() + '"');
        });
        csv.push(row.join(','));
    });
    
    // Download
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'tc_report_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function printReport() {
    if (!document.getElementById('reportTable')) {
        alert('No report data to print. Please generate a report first.');
        return;
    }
    
    const printWindow = window.open('', '_blank');
    const reportTitle = document.querySelector('.card-title').textContent;
    const tableHTML = document.getElementById('reportTable').outerHTML;
    
    printWindow.document.write(`
        <html>
        <head>
            <title>${reportTitle}</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .badge { padding: 2px 6px; border-radius: 3px; }
                .badge-success { background: #28a745; color: white; }
                .badge-danger { background: #dc3545; color: white; }
                .badge-warning { background: #ffc107; color: black; }
                .badge-info { background: #17a2b8; color: white; }
            </style>
        </head>
        <body>
            <h2>${reportTitle}</h2>
            <p>Generated on: ${new Date().toLocaleDateString()}</p>
            ${tableHTML}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
}
</script>

<?php require_once 'includes/footer.php'; ?>