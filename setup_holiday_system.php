<?php
/**
 * Holiday System Setup Script
 * 
 * This script helps you set up the complete holiday system for BC Attendance.
 * Run this in your browser to automatically configure the system.
 */

// Prevent direct access if not from web
if (php_sapi_name() === 'cli') {
    echo "This script should be run from a web browser.\n";
    exit(1);
}

// Check if we're in the right directory
if (!file_exists('config/database.php')) {
    echo "<div style='color: red; padding: 20px;'>❌ Error: Please run this script from the root directory of BC Attendance.</div>";
    exit;
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$pageTitle = 'Holiday System Setup';
$setupResults = [];
$currentStep = 1;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_step'])) {
    $currentStep = (int)$_POST['setup_step'];
    
    switch ($currentStep) {
        case 2: // Database setup
            setupDatabase();
            break;
        case 3: // Test system
            testSystem();
            break;
        case 4: // Create sample data
            createSampleData();
            break;
    }
}

function setupDatabase() {
    global $setupResults;
    
    try {
        // Step 1: Update attendance table
        $result = executeQuery("ALTER TABLE attendance MODIFY COLUMN status ENUM('present','absent','holiday') NOT NULL");
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Updated attendance table status enum'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Failed to update attendance table'];
        }
        
        // Step 2: Create holidays table
        $holidaysTable = "
        CREATE TABLE IF NOT EXISTS holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL UNIQUE,
            description VARCHAR(255) NOT NULL,
            type ENUM('sunday', 'national', 'local', 'batch_specific') DEFAULT 'sunday',
            batch_id INT NULL,
            mandal_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
            FOREIGN KEY (mandal_id) REFERENCES mandals(id) ON DELETE CASCADE,
            INDEX idx_date (date),
            INDEX idx_batch_id (batch_id),
            INDEX idx_mandal_id (mandal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $result = executeQuery($holidaysTable);
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Created holidays table'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Failed to create holidays table'];
        }
        
        // Step 3: Create batch_holidays table
        $batchHolidaysTable = "
        CREATE TABLE IF NOT EXISTS batch_holidays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            holiday_id INT NOT NULL,
            batch_id INT NOT NULL,
            holiday_date DATE NOT NULL,
            holiday_name VARCHAR(255) NOT NULL,
            description TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (holiday_id) REFERENCES holidays(id) ON DELETE CASCADE,
            FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
            INDEX idx_holiday_id (holiday_id),
            INDEX idx_batch_id (batch_id),
            INDEX idx_holiday_date (holiday_date),
            UNIQUE KEY unique_batch_holiday (batch_id, holiday_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $result = executeQuery($batchHolidaysTable);
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Created batch_holidays table'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Failed to create batch_holidays table'];
        }
        
        // Step 4: Mark Sundays as holidays
        $sundaysQuery = "
        INSERT IGNORE INTO holidays (date, description, type)
        SELECT 
            date_value,
            'Sunday Holiday',
            'sunday'
        FROM (
            SELECT DATE('2025-01-01') + INTERVAL (a.N + b.N * 10 + c.N * 100) DAY as date_value
            FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
            CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
            CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c
            WHERE DATE('2025-01-01') + INTERVAL (a.N + b.N * 10 + c.N * 100) DAY <= '2025-12-31'
        ) dates
        WHERE DAYOFWEEK(date_value) = 1";
        
        $result = executeQuery($sundaysQuery);
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Marked Sundays as holidays'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Failed to mark Sundays'];
        }
        
        // Step 5: Update existing attendance data
        $updateQuery = "
        UPDATE attendance SET status = 'present' WHERE status = 'late';
        UPDATE attendance SET status = 'absent' WHERE status = 'excused';
        UPDATE attendance SET status = 'holiday' WHERE status = 'H'";
        
        $result = executeQuery($updateQuery);
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Updated existing attendance data'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Failed to update attendance data'];
        }
        
        $setupResults[] = ['status' => '🎉', 'step' => 'Database setup completed successfully!'];
        
    } catch (Exception $e) {
        $setupResults[] = ['status' => '❌', 'step' => 'Error: ' . $e->getMessage()];
    }
}

function testSystem() {
    global $setupResults;
    
    try {
        // Test 1: Check tables exist
        $result = fetchRow("SHOW TABLES LIKE 'holidays'");
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Holidays table exists'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Holidays table missing'];
        }
        
        $result = fetchRow("SHOW TABLES LIKE 'batch_holidays'");
        if ($result) {
            $setupResults[] = ['status' => '✅', 'step' => 'Batch holidays table exists'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Batch holidays table missing'];
        }
        
        // Test 2: Check Sunday holidays
        $result = fetchRow("SELECT COUNT(*) as count FROM holidays WHERE type = 'sunday'");
        if ($result && $result['count'] > 0) {
            $setupResults[] = ['status' => '✅', 'step' => $result['count'] . ' Sundays marked as holidays'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'No Sundays found'];
        }
        
        // Test 3: Check attendance status
        $result = fetchRow("SHOW COLUMNS FROM attendance LIKE 'status'");
        if ($result && strpos($result['Type'], 'holiday') !== false) {
            $setupResults[] = ['status' => '✅', 'step' => 'Attendance status includes holiday'];
        } else {
            $setupResults[] = ['status' => '❌', 'step' => 'Attendance status enum incorrect'];
        }
        
        $setupResults[] = ['status' => '🎉', 'step' => 'System testing completed!'];
        
    } catch (Exception $e) {
        $setupResults[] = ['status' => '❌', 'step' => 'Error: ' . $e->getMessage()];
    }
}

function createSampleData() {
    global $setupResults;
    
    try {
        // Add some sample holidays
        $sampleHolidays = [
            ['2025-08-15', 'Independence Day', 'national'],
            ['2025-01-26', 'Republic Day', 'national'],
            ['2025-10-02', 'Gandhi Jayanti', 'national']
        ];
        
        foreach ($sampleHolidays as $holiday) {
            $result = executeQuery("INSERT IGNORE INTO holidays (date, description, type) VALUES (?, ?, ?)", $holiday);
            if ($result) {
                $setupResults[] = ['status' => '✅', 'step' => 'Added sample holiday: ' . $holiday[1]];
            } else {
                $setupResults[] = ['status' => '❌', 'step' => 'Failed to add: ' . $holiday[1]];
            }
        }
        
        $setupResults[] = ['status' => '🎉', 'step' => 'Sample data created successfully!'];
        
    } catch (Exception $e) {
        $setupResults[] = ['status' => '❌', 'step' => 'Error: ' . $e->getMessage()];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - BC Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .setup-step { display: none; }
        .setup-step.active { display: block; }
        .step-indicator { margin-bottom: 30px; }
        .step-indicator .step { 
            display: inline-block; 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            background: #e9ecef; 
            color: #6c757d; 
            text-align: center; 
            line-height: 40px; 
            margin: 0 10px; 
        }
        .step-indicator .step.active { background: #007bff; color: white; }
        .step-indicator .step.completed { background: #28a745; color: white; }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-calendar-alt"></i>
                            Holiday System Setup
                        </h3>
                        <p class="mb-0">Complete setup in 4 easy steps</p>
                    </div>
                    <div class="card-body">
                        <!-- Step Indicator -->
                        <div class="step-indicator text-center">
                            <span class="step <?php echo $currentStep >= 1 ? 'active' : ''; ?>">1</span>
                            <span class="step <?php echo $currentStep >= 2 ? 'completed' : ''; ?>">2</span>
                            <span class="step <?php echo $currentStep >= 3 ? 'completed' : ''; ?>">3</span>
                            <span class="step <?php echo $currentStep >= 4 ? 'completed' : ''; ?>">4</span>
                        </div>

                        <!-- Step 1: Welcome -->
                        <div class="setup-step <?php echo $currentStep == 1 ? 'active' : ''; ?>">
                            <div class="text-center mb-4">
                                <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
                                <h4>Welcome to Holiday System Setup</h4>
                                <p class="text-muted">This wizard will help you set up the complete holiday system for BC Attendance.</p>
                            </div>
                            
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> What will be set up?</h6>
                                <ul class="mb-0">
                                    <li>✅ Database tables for holidays</li>
                                    <li>✅ Automatic Sunday detection</li>
                                    <li>✅ Holiday management interface</li>
                                    <li>✅ Student attendance calendar</li>
                                    <li>✅ Working days calculation</li>
                                </ul>
                            </div>
                            
                            <form method="POST" class="text-center">
                                <input type="hidden" name="setup_step" value="2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play"></i> Start Setup
                                </button>
                            </form>
                        </div>

                        <!-- Step 2: Database Setup -->
                        <div class="setup-step <?php echo $currentStep == 2 ? 'active' : ''; ?>">
                            <div class="text-center mb-4">
                                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                <h4>Database Setup</h4>
                                <p class="text-muted">Creating necessary tables and updating existing data...</p>
                            </div>
                            
                            <?php if (!empty($setupResults)): ?>
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-check-circle"></i> Setup Results:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($setupResults as $result): ?>
                                            <li><?php echo $result['status']; ?> <?php echo htmlspecialchars($result['step']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="text-center">
                                <input type="hidden" name="setup_step" value="3">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-arrow-right"></i> Continue to Testing
                                </button>
                            </form>
                        </div>

                        <!-- Step 3: System Testing -->
                        <div class="setup-step <?php echo $currentStep == 3 ? 'active' : ''; ?>">
                            <div class="text-center mb-4">
                                <i class="fas fa-vial fa-3x text-primary mb-3"></i>
                                <h4>System Testing</h4>
                                <p class="text-muted">Verifying all components are working correctly...</p>
                            </div>
                            
                            <?php if (!empty($setupResults)): ?>
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Test Results:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($setupResults as $result): ?>
                                            <li><?php echo $result['status']; ?> <?php echo htmlspecialchars($result['step']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="text-center">
                                <input type="hidden" name="setup_step" value="4">
                                <button type="submit" class="btn btn-info btn-lg">
                                    <i class="fas fa-arrow-right"></i> Add Sample Data
                                </button>
                            </form>
                        </div>

                        <!-- Step 4: Sample Data -->
                        <div class="setup-step <?php echo $currentStep == 4 ? 'active' : ''; ?>">
                            <div class="text-center mb-4">
                                <i class="fas fa-star fa-3x text-primary mb-3"></i>
                                <h4>Sample Data</h4>
                                <p class="text-muted">Adding sample holidays to get you started...</p>
                            </div>
                            
                            <?php if (!empty($setupResults)): ?>
                                <div class="alert alert-success">
                                    <h6><i class="fas fa-check-circle"></i> Sample Data Results:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($setupResults as $result): ?>
                                            <li><?php echo $result['status']; ?> <?php echo htmlspecialchars($result['step']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            
                            <div class="alert alert-success">
                                <h6><i class="fas fa-trophy"></i> Setup Complete!</h6>
                                <p class="mb-0">Your holiday system is now ready to use.</p>
                            </div>
                            
                            <div class="text-center">
                                <a href="admin/manage_holidays.php" class="btn btn-success btn-lg mr-3">
                                    <i class="fas fa-calendar-plus"></i> Manage Holidays
                                </a>
                                <a href="admin/attendance_calendar.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-alt"></i> View Calendar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="admin/dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
