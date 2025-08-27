<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'Test Holiday System';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Test Holiday System']
];

require_once '../includes/header.php';

// Test results
$testResults = [];

// Test 1: Check if holidays table exists
try {
    $result = fetchRow("SHOW TABLES LIKE 'holidays'");
    if ($result) {
        $testResults[] = ['status' => '✅', 'test' => 'Holidays Table', 'result' => 'Exists'];
    } else {
        $testResults[] = ['status' => '❌', 'test' => 'Holidays Table', 'result' => 'Missing'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Holidays Table', 'result' => 'Error: ' . $e->getMessage()];
}

// Test 2: Check if batch_holidays table exists
try {
    $result = fetchRow("SHOW TABLES LIKE 'batch_holidays'");
    if ($result) {
        $testResults[] = ['status' => '✅', 'test' => 'Batch Holidays Table', 'result' => 'Exists'];
    } else {
        $testResults[] = ['status' => '❌', 'test' => 'Batch Holidays Table', 'result' => 'Missing'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Batch Holidays Table', 'result' => 'Error: ' . $e->getMessage()];
}

// Test 3: Check attendance status enum
try {
    $result = fetchRow("SHOW COLUMNS FROM attendance LIKE 'status'");
    if ($result) {
        $testResults[] = ['status' => '✅', 'test' => 'Attendance Status Enum', 'result' => $result['Type']];
    } else {
        $testResults[] = ['status' => '❌', 'test' => 'Attendance Status Enum', 'result' => 'Column not found'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Attendance Status Enum', 'result' => 'Error: ' . $e->getMessage()];
}

// Test 4: Count Sunday holidays
try {
    $result = fetchRow("SELECT COUNT(*) as count FROM holidays WHERE type = 'sunday'");
    if ($result) {
        $testResults[] = ['status' => '✅', 'test' => 'Sunday Holidays', 'result' => $result['count'] . ' Sundays marked'];
    } else {
        $testResults[] = ['status' => '❌', 'test' => 'Sunday Holidays', 'result' => 'No Sundays found'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Sunday Holidays', 'result' => 'Error: ' . $e->getMessage()];
}

// Test 5: Check attendance status distribution
try {
    $result = fetchAll("SELECT status, COUNT(*) as count FROM attendance GROUP BY status ORDER BY status");
    if ($result) {
        $statusSummary = [];
        foreach ($result as $row) {
            $statusSummary[] = $row['status'] . ': ' . $row['count'];
        }
        $testResults[] = ['status' => '✅', 'test' => 'Attendance Status Distribution', 'result' => implode(', ', $statusSummary)];
    } else {
        $testResults[] = ['status' => '❌', 'test' => 'Attendance Status Distribution', 'result' => 'No data found'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Attendance Status Distribution', 'result' => 'Error: ' . $e->getMessage()];
}

// Test 6: Check working days calculation
try {
    $result = fetchRow("
        SELECT 
            COUNT(*) as total_days,
            COUNT(CASE WHEN status IN ('present', 'absent') THEN 1 END) as working_days,
            COUNT(CASE WHEN status = 'holiday' THEN 1 END) as holiday_days
        FROM attendance 
        WHERE attendance_date BETWEEN '2025-05-01' AND '2025-05-31'
    ");
    if ($result) {
        $testResults[] = ['status' => '✅', 'test' => 'Working Days Calculation (May 2025)', 'result' => 'Total: ' . $result['total_days'] . ', Working: ' . $result['working_days'] . ', Holidays: ' . $result['holiday_days']];
    } else {
        $testResults[] = ['status' => '❌', 'test' => 'Working Days Calculation (May 2025)', 'result' => 'No data found'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Working Days Calculation (May 2025)', 'result' => 'Error: ' . $e->getMessage()];
}

// Test 7: Check if specific holidays exist
try {
    $result = fetchRow("SELECT COUNT(*) as count FROM holidays WHERE type != 'sunday'");
    if ($result) {
        $testResults[] = ['status' => '✅', 'test' => 'Custom Holidays', 'result' => $result['count'] . ' custom holidays found'];
    } else {
        $testResults[] = ['status' => '⚠️', 'test' => 'Custom Holidays', 'result' => 'No custom holidays found (this is normal)'];
    }
} catch (Exception $e) {
    $testResults[] = ['status' => '❌', 'test' => 'Custom Holidays', 'result' => 'Error: ' . $e->getMessage()];
}

?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-vial"></i>
                        Holiday System Test Results
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> System Status Check</h5>
                        <p class="mb-0">This page tests if the holiday system is properly configured and working.</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th width="50">Status</th>
                                    <th>Test</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($testResults as $test): ?>
                                    <tr>
                                        <td class="text-center"><?php echo $test['status']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($test['test']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($test['result']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Next Steps</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Run <code>complete_holiday_system.sql</code> if tables are missing</li>
                                        <li><i class="fas fa-calendar"></i> Use <a href="manage_holidays.php">Manage Holidays</a> to add custom holidays</li>
                                        <li><i class="fas fa-calendar-alt"></i> Use <a href="attendance_calendar.php">Attendance Calendar</a> to view student calendars</li>
                                        <li><i class="fas fa-chart-bar"></i> Check <a href="batch_reports.php">Batch Reports</a> for accurate working days</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Expected Results</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-table text-primary"></i> Both tables should exist</li>
                                        <li><i class="fas fa-list text-primary"></i> Status enum should be: present, absent, holiday</li>
                                        <li><i class="fas fa-sun text-warning"></i> Sundays should be marked as holidays</li>
                                        <li><i class="fas fa-calculator text-info"></i> Working days should exclude holidays</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="dashboard.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <a href="manage_holidays.php" class="btn btn-success">
                            <i class="fas fa-calendar-plus"></i> Manage Holidays
                        </a>
                        <a href="attendance_calendar.php" class="btn btn-info">
                            <i class="fas fa-calendar-alt"></i> View Calendar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
