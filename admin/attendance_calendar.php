<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'Student Attendance Calendar';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Attendance Calendar']
];

require_once '../includes/header.php';

// Get filters
$selectedStudent = $_GET['student_id'] ?? '';
$selectedBatch = $_GET['batch_id'] ?? '';
$selectedMonth = $_GET['month'] ?? date('Y-m');
$selectedYear = $_GET['year'] ?? date('Y');

// Parse month and year
$month = (int)date('m', strtotime($selectedMonth . '-01'));
$year = (int)$selectedYear;

// Get all batches for filter
$batches = fetchAll("
    SELECT id, name, code, mandal_id 
    FROM batches 
    WHERE status IN ('active', 'completed') 
    ORDER BY name
");

// Get all students for filter
$studentsQuery = "
    SELECT b.id, b.full_name, b.beneficiary_id, bt.name as batch_name, bt.code as batch_code, m.name as mandal_name
    FROM beneficiaries b
    JOIN batches bt ON b.batch_id = bt.id
    JOIN mandals m ON bt.mandal_id = m.id
    WHERE b.status IN ('active', 'completed')
";

if ($selectedBatch) {
    $studentsQuery .= " AND b.batch_id = ?";
    $studentsParams = [$selectedBatch];
    $studentsTypes = 'i';
} else {
    $studentsParams = [];
    $studentsTypes = '';
}

$studentsQuery .= " ORDER BY b.full_name";
$students = fetchAll($studentsQuery, $studentsParams, $studentsTypes);

// Get selected student details
$studentDetails = null;
if ($selectedStudent) {
    $studentDetails = fetchRow("
        SELECT b.*, bt.name as batch_name, bt.code as batch_code, bt.start_date, bt.end_date,
               m.name as mandal_name, c.name as constituency_name
        FROM beneficiaries b
        JOIN batches bt ON b.batch_id = bt.id
        JOIN mandals m ON bt.mandal_id = m.id
        JOIN constituencies c ON m.constituency_id = c.id
        WHERE b.id = ?
    ", [$selectedStudent], 'i');
}

// Get attendance data for the selected month
$attendanceData = [];
if ($selectedStudent && $studentDetails) {
    $startDate = date('Y-m-01', strtotime("$year-$month-01"));
    $endDate = date('Y-m-t', strtotime("$year-$month-01"));
    
    $attendanceQuery = "
        SELECT a.attendance_date, a.status
        FROM attendance a
        WHERE a.beneficiary_id = ? 
        AND a.attendance_date BETWEEN ? AND ?
        ORDER BY a.attendance_date
    ";
    
    $attendanceData = fetchAll($attendanceQuery, [$selectedStudent, $startDate, $endDate], 'iss');
    
    // Convert to associative array for easy lookup
    $attendanceLookup = [];
    foreach ($attendanceData as $att) {
        $attendanceLookup[$att['attendance_date']] = $att['status'];
    }
    $attendanceData = $attendanceLookup;
}

// Get holidays for the month
$holidays = [];
if ($selectedStudent && $studentDetails) {
    $holidayQuery = "
        SELECT h.date, h.description, h.type
        FROM holidays h
        WHERE h.date BETWEEN ? AND ?
        AND (
            h.type = 'national' 
            OR EXISTS (
                SELECT 1 FROM batch_holidays bh 
                WHERE bh.holiday_id = h.id 
                AND bh.batch_id = ?
            )
        )
        ORDER BY h.date
    ";
    
    $holidays = fetchAll($holidayQuery, [$startDate, $endDate, $studentDetails['batch_id']], 'ssi');
    
    // Convert to associative array
    $holidayLookup = [];
    foreach ($holidays as $holiday) {
        $holidayLookup[$holiday['date']] = $holiday;
    }
    $holidays = $holidayLookup;
}

// Calendar helper functions
function getDaysInMonth($month, $year) {
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

function getFirstDayOfMonth($month, $year) {
    return date('w', strtotime("$year-$month-01"));
}

function isSunday($date) {
    return date('w', strtotime($date)) == 0;
}

function getAttendanceStatus($date, $attendanceData, $holidays, $studentDetails) {
    $dateStr = date('Y-m-d', strtotime($date));
    
    // Check if it's a holiday
    if (isset($holidays[$dateStr])) {
        return [
            'status' => 'holiday',
            'tooltip' => $holidays[$dateStr]['description'],
            'class' => 'holiday-cell'
        ];
    }
    
    // Check if it's Sunday
    if (isSunday($dateStr)) {
        return [
            'status' => 'sunday',
            'tooltip' => 'Sunday Holiday',
            'class' => 'sunday-cell'
        ];
    }
    
    // Check if it's within batch date range
    if ($studentDetails) {
        $batchStart = $studentDetails['start_date'];
        $batchEnd = $studentDetails['end_date'];
        
        if ($dateStr < $batchStart || $dateStr > $batchEnd) {
            return [
                'status' => 'outside_batch',
                'tooltip' => 'Outside batch period',
                'class' => 'outside-batch-cell'
            ];
        }
    }
    
    // Check attendance
    if (isset($attendanceData[$dateStr])) {
        $status = $attendanceData[$dateStr];
        if ($status === 'present') {
            return [
                'status' => 'present',
                'tooltip' => 'Present',
                'class' => 'present-cell'
            ];
        } elseif ($status === 'absent') {
            return [
                'status' => 'absent',
                'tooltip' => 'Absent',
                'class' => 'absent-cell'
            ];
        } elseif ($status === 'holiday') {
            return [
                'status' => 'holiday',
                'tooltip' => 'Holiday',
                'class' => 'holiday-cell'
            ];
        }
    }
    
    // No attendance marked
    return [
        'status' => 'not_marked',
        'tooltip' => 'No attendance marked',
        'class' => 'not-marked-cell'
    ];
}

// Get month name
$monthName = date('F Y', strtotime("$year-$month-01"));
$prevMonth = date('Y-m', strtotime("$year-$month-01 -1 month"));
$nextMonth = date('Y-m', strtotime("$year-$month-01 +1 month"));
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt"></i>
                        Student Attendance Calendar
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="batch_filter">Select Batch</label>
                            <select id="batch_filter" class="form-control" onchange="filterStudents()">
                                <option value="">All Batches</option>
                                <?php foreach ($batches as $batch): ?>
                                    <option value="<?php echo $batch['id']; ?>" <?php echo $selectedBatch == $batch['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($batch['name'] . ' (' . $batch['code'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="student_filter">Select Student</label>
                            <select id="student_filter" class="form-control" onchange="loadStudentCalendar()">
                                <option value="">Choose Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo $selectedStudent == $student['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['full_name'] . ' - ' . $student['batch_code']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="month_filter">Month</label>
                            <input type="month" id="month_filter" class="form-control" value="<?php echo $selectedMonth; ?>" onchange="loadStudentCalendar()">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <a href="?student_id=<?php echo $selectedStudent; ?>&batch_id=<?php echo $selectedBatch; ?>&month=<?php echo $prevMonth; ?>" 
                                   class="btn btn-outline-primary mr-2">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </a>
                                <a href="?student_id=<?php echo $selectedStudent; ?>&batch_id=<?php echo $selectedBatch; ?>&month=<?php echo $nextMonth; ?>" 
                                   class="btn btn-outline-primary">
                                    Next <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <?php if ($studentDetails): ?>
                        <!-- Student Info -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-user"></i> <?php echo htmlspecialchars($studentDetails['full_name']); ?></h5>
                                    <p class="mb-1">
                                        <strong>Student ID:</strong> <?php echo htmlspecialchars($studentDetails['beneficiary_id']); ?> |
                                        <strong>Batch:</strong> <?php echo htmlspecialchars($studentDetails['batch_name'] . ' (' . $studentDetails['batch_code'] . ')'); ?> |
                                        <strong>Mandal:</strong> <?php echo htmlspecialchars($studentDetails['mandal_name']); ?> |
                                        <strong>Constituency:</strong> <?php echo htmlspecialchars($studentDetails['constituency_name']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Batch Period:</strong> <?php echo date('d M Y', strtotime($studentDetails['start_date'])); ?> to <?php echo date('d M Y', strtotime($studentDetails['end_date'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar -->
                        <div class="row">
                            <div class="col-12">
                                <div class="calendar-container">
                                    <h4 class="text-center mb-3"><?php echo $monthName; ?></h4>
                                    
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
                                    <div class="calendar-grid">
                                        <!-- Day Headers -->
                                        <div class="calendar-row calendar-header">
                                            <div class="calendar-cell">Sun</div>
                                            <div class="calendar-cell">Mon</div>
                                            <div class="calendar-cell">Tue</div>
                                            <div class="calendar-cell">Wed</div>
                                            <div class="calendar-cell">Thu</div>
                                            <div class="calendar-cell">Fri</div>
                                            <div class="calendar-cell">Sat</div>
                                        </div>

                                        <?php
                                        $daysInMonth = getDaysInMonth($month, $year);
                                        $firstDay = getFirstDayOfMonth($month, $year);
                                        $currentDay = 1;
                                        $currentDate = 1;
                                        
                                        // Calculate total weeks needed
                                        $totalWeeks = ceil(($firstDay + $daysInMonth) / 7);
                                        
                                        for ($week = 0; $week < $totalWeeks; $week++): ?>
                                            <div class="calendar-row">
                                                <?php for ($dayOfWeek = 0; $dayOfWeek < 7; $dayOfWeek++): ?>
                                                    <?php
                                                    $isCurrentMonth = ($week == 0 && $dayOfWeek < $firstDay) || ($currentDate > $daysInMonth);
                                                    $date = null;
                                                    
                                                    if (!$isCurrentMonth) {
                                                        $date = sprintf('%04d-%02d-%02d', $year, $month, $currentDate);
                                                        $attendanceInfo = getAttendanceStatus($date, $attendanceData, $holidays, $studentDetails);
                                                        $currentDate++;
                                                    }
                                                    ?>
                                                    
                                                    <div class="calendar-cell <?php echo $isCurrentMonth ? 'empty-cell' : $attendanceInfo['class']; ?>"
                                                         <?php if (!$isCurrentMonth): ?>
                                                         data-toggle="tooltip" 
                                                         title="<?php echo htmlspecialchars($attendanceInfo['tooltip']); ?>"
                                                         <?php endif; ?>>
                                                        <?php if (!$isCurrentMonth): ?>
                                                            <div class="date-number"><?php echo $currentDate - 1; ?></div>
                                                            <?php if (isset($attendanceInfo['status'])): ?>
                                                                <div class="status-indicator">
                                                                    <?php if ($attendanceInfo['status'] === 'present'): ?>
                                                                        <i class="fas fa-check text-success"></i>
                                                                    <?php elseif ($attendanceInfo['status'] === 'absent'): ?>
                                                                        <i class="fas fa-times text-danger"></i>
                                                                    <?php elseif ($attendanceInfo['status'] === 'holiday'): ?>
                                                                        <i class="fas fa-star text-warning"></i>
                                                                    <?php elseif ($attendanceInfo['status'] === 'sunday'): ?>
                                                                        <i class="fas fa-church text-info"></i>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endfor; ?>
                                    </div>

                                    <!-- Monthly Summary -->
                                    <div class="row mt-4">
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Monthly Summary</h6>
                                                </div>
                                                <div class="card-body">
                                                    <?php
                                                    $presentCount = 0;
                                                    $absentCount = 0;
                                                    $holidayCount = 0;
                                                    $sundayCount = 0;
                                                    $notMarkedCount = 0;
                                                    
                                                    for ($day = 1; $day <= $daysInMonth; $day++) {
                                                        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                                        $attendanceInfo = getAttendanceStatus($date, $attendanceData, $holidays, $studentDetails);
                                                        
                                                        switch ($attendanceInfo['status']) {
                                                            case 'present':
                                                                $presentCount++;
                                                                break;
                                                            case 'absent':
                                                                $absentCount++;
                                                                break;
                                                            case 'holiday':
                                                                $holidayCount++;
                                                                break;
                                                            case 'sunday':
                                                                $sundayCount++;
                                                                break;
                                                            case 'not_marked':
                                                                $notMarkedCount++;
                                                                break;
                                                        }
                                                    }
                                                    
                                                    $workingDays = $presentCount + $absentCount;
                                                    $attendancePercentage = $workingDays > 0 ? round(($presentCount / $workingDays) * 100, 2) : 0;
                                                    ?>
                                                    
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <div class="summary-item">
                                                                <div class="summary-number text-success"><?php echo $presentCount; ?></div>
                                                                <div class="summary-label">Present</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="summary-item">
                                                                <div class="summary-number text-danger"><?php echo $absentCount; ?></div>
                                                                <div class="summary-label">Absent</div>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="summary-item">
                                                                <div class="summary-number text-warning"><?php echo $holidayCount + $sundayCount; ?></div>
                                                                <div class="summary-label">Holidays</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mt-3">
                                                        <div class="col-6">
                                                            <strong>Working Days:</strong> <?php echo $workingDays; ?>
                                                        </div>
                                                        <div class="col-6">
                                                            <strong>Attendance %:</strong> <?php echo $attendancePercentage; ?>%
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h6 class="mb-0">Actions</h6>
                                                </div>
                                                <div class="card-body">
                                                    <button class="btn btn-primary btn-sm mr-2" onclick="exportCalendar()">
                                                        <i class="fas fa-download"></i> Export Calendar
                                                    </button>
                                                    <button class="btn btn-success btn-sm mr-2" onclick="printCalendar()">
                                                        <i class="fas fa-print"></i> Print
                                                    </button>
                                                    <button class="btn btn-info btn-sm" onclick="shareCalendar()">
                                                        <i class="fas fa-share"></i> Share
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- No Student Selected -->
                        <div class="text-center py-5">
                            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No Student Selected</h5>
                            <p class="text-muted">Please select a student to view their attendance calendar.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
    min-height: 80px;
    padding: 8px;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
}

.calendar-cell:last-child {
    border-right: none;
}

.date-number {
    font-weight: bold;
    font-size: 14px;
}

.status-indicator {
    text-align: center;
    margin-top: auto;
}

/* Cell Colors */
.present-cell {
    background-color: #d4edda;
    border-left: 4px solid #28a745;
}

.absent-cell {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}

.holiday-cell {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.sunday-cell {
    background-color: #e2e3e5;
    border-left: 4px solid #6c757d;
}

.not-marked-cell {
    background-color: #f8f9fa;
    border-left: 4px solid #adb5bd;
}

.outside-batch-cell {
    background-color: #f8f9fa;
    border-left: 4px solid #6c757d;
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
    width: 20px;
    height: 20px;
    border-radius: 4px;
    margin-right: 8px;
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
    padding: 10px;
}

.summary-number {
    font-size: 24px;
    font-weight: bold;
}

.summary-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
}

/* Responsive */
@media (max-width: 768px) {
    .calendar-cell {
        min-height: 60px;
        padding: 4px;
    }
    
    .date-number {
        font-size: 12px;
    }
    
    .legend-item {
        margin-bottom: 10px;
    }
}
</style>

<script>
function filterStudents() {
    const batchId = document.getElementById('batch_filter').value;
    const studentFilter = document.getElementById('student_filter');
    
    // Clear current student selection
    studentFilter.value = '';
    
    // Reload page with batch filter
    window.location.href = `?batch_id=${batchId}`;
}

function loadStudentCalendar() {
    const studentId = document.getElementById('student_filter').value;
    const month = document.getElementById('month_filter').value;
    const batchId = document.getElementById('batch_filter').value;
    
    if (studentId) {
        window.location.href = `?student_id=${studentId}&batch_id=${batchId}&month=${month}`;
    }
}

function exportCalendar() {
    // Implementation for exporting calendar
    alert('Export functionality will be implemented here');
}

function printCalendar() {
    window.print();
}

function shareCalendar() {
    // Implementation for sharing calendar
    alert('Share functionality will be implemented here');
}

// Initialize tooltips
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>

<?php require_once '../includes/footer.php'; ?>
