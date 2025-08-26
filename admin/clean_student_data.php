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

$error = '';
$success = '';
$show_confirmation = false;
$data_counts = [];

// Get current data counts
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $data_counts = [
        'constituencies' => fetchRow("SELECT COUNT(*) as count FROM constituencies")['count'] ?? 0,
        'mandals' => fetchRow("SELECT COUNT(*) as count FROM mandals")['count'] ?? 0,
        'training_centers' => fetchRow("SELECT COUNT(*) as count FROM training_centers")['count'] ?? 0,
        'batches' => fetchRow("SELECT COUNT(*) as count FROM batches")['count'] ?? 0,
        'beneficiaries' => fetchRow("SELECT COUNT(*) as count FROM beneficiaries")['count'] ?? 0,
        'attendance' => fetchRow("SELECT COUNT(*) as count FROM attendance")['count'] ?? 0,
        'attendance_import_logs' => fetchRow("SELECT COUNT(*) as count FROM attendance_import_log")['count'] ?? 0,
        'bulk_upload_logs' => fetchRow("SELECT COUNT(*) as count FROM bulk_upload_log")['count'] ?? 0
    ];
}

// Handle cleanup confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_cleanup') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } else {
        try {
            // Start transaction
            $conn = getDBConnection();
            $conn->begin_transaction();
            
            // Disable foreign key checks temporarily
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            // Clear attendance-related tables first (child tables before parent tables)
            try {
                $conn->query("DELETE FROM attendance_edit_log");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            try {
                $conn->query("DELETE FROM attendance_import_log");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            try {
                $conn->query("DELETE FROM attendance_restrictions");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            try {
                $conn->query("DELETE FROM deleted_attendance_back");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            // Clear main tables
            $conn->query("DELETE FROM attendance");
            $conn->query("DELETE FROM beneficiaries");
            
            // Clear any backup tables if they exist
            try {
                $conn->query("DELETE FROM beneficiaries_backup");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            try {
                $conn->query("DELETE FROM beneficiaries_backup_full");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            try {
                $conn->query("DELETE FROM deleted_students_backup");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            // Clear bulk upload logs
            try {
                $conn->query("DELETE FROM bulk_upload_log");
            } catch (Exception $e) {
                // Table doesn't exist, skip it
            }
            
            // Re-enable foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            // Commit transaction
            $conn->commit();
            
            $success = "Student data and attendance data cleared successfully!";
            
            // Refresh data counts
            $data_counts = [
                'constituencies' => fetchRow("SELECT COUNT(*) as count FROM constituencies")['count'] ?? 0,
                'mandals' => fetchRow("SELECT COUNT(*) as count FROM mandals")['count'] ?? 0,
                'training_centers' => fetchRow("SELECT COUNT(*) as count FROM training_centers")['count'] ?? 0,
                'batches' => fetchRow("SELECT COUNT(*) as count FROM batches")['count'] ?? 0,
                'beneficiaries' => fetchRow("SELECT COUNT(*) as count FROM beneficiaries")['count'] ?? 0,
                'attendance' => fetchRow("SELECT COUNT(*) as count FROM attendance")['count'] ?? 0,
                'attendance_import_logs' => fetchRow("SELECT COUNT(*) as count FROM attendance_import_log")['count'] ?? 0,
                'bulk_upload_logs' => fetchRow("SELECT COUNT(*) as count FROM bulk_upload_log")['count'] ?? 0
            ];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($conn)) {
                $conn->rollback();
            }
            $error = "Error during cleanup: " . $e->getMessage();
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean Student Data - BC Attendance Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cleanup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .data-summary {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .data-row:last-child {
            border-bottom: none;
        }
        
        .count-badge {
            background: #007bff;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.875rem;
        }
        
        .count-badge.warning {
            background: #ffc107;
            color: #212529;
        }
        
        .count-badge.danger {
            background: #dc3545;
        }
        
        .btn-cleanup {
            background: #dc3545;
            border-color: #dc3545;
        }
        
        .btn-cleanup:hover {
            background: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container">
        <div class="cleanup-container">
            <h2><i class="fas fa-trash-alt"></i> Clean Student Data</h2>
            
            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> ⚠️ WARNING: This action cannot be undone!</h4>
                <p><strong>This will permanently delete:</strong></p>
                <ul>
                    <li>All student/beneficiary records</li>
                    <li>All attendance records</li>
                    <li>All attendance import logs</li>
                    <li>All bulk upload logs</li>
                </ul>
                <p><strong>This will NOT delete:</strong></p>
                <ul>
                    <li>Constituency data</li>
                    <li>Mandal data</li>
                    <li>Training center data</li>
                    <li>Batch data</li>
                </ul>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-summary">
                <h4><i class="fas fa-chart-bar"></i> Current Data Summary</h4>
                <div class="data-row">
                    <span>Constituencies:</span>
                    <span class="count-badge"><?php echo $data_counts['constituencies']; ?></span>
                </div>
                <div class="data-row">
                    <span>Mandals:</span>
                    <span class="count-badge"><?php echo $data_counts['mandals']; ?></span>
                </div>
                <div class="data-row">
                    <span>Training Centers:</span>
                    <span class="count-badge"><?php echo $data_counts['training_centers']; ?></span>
                </div>
                <div class="data-row">
                    <span>Batches:</span>
                    <span class="count-badge"><?php echo $data_counts['batches']; ?></span>
                </div>
                <div class="data-row">
                    <span>Students/Beneficiaries:</span>
                    <span class="count-badge <?php echo $data_counts['beneficiaries'] > 0 ? 'warning' : ''; ?>"><?php echo $data_counts['beneficiaries']; ?></span>
                </div>
                <div class="data-row">
                    <span>Attendance Records:</span>
                    <span class="count-badge <?php echo $data_counts['attendance'] > 0 ? 'danger' : ''; ?>"><?php echo $data_counts['attendance']; ?></span>
                </div>
                <div class="data-row">
                    <span>Attendance Import Logs:</span>
                    <span class="count-badge"><?php echo $data_counts['attendance_import_logs']; ?></span>
                </div>
                <div class="data-row">
                    <span>Bulk Upload Logs:</span>
                    <span class="count-badge"><?php echo $data_counts['bulk_upload_logs']; ?></span>
                </div>
            </div>
            
            <?php if ($data_counts['beneficiaries'] > 0 || $data_counts['attendance'] > 0): ?>
                <div class="alert alert-warning">
                    <h5><i class="fas fa-info-circle"></i> Data Found</h5>
                    <p>There are currently <strong><?php echo $data_counts['beneficiaries']; ?> students</strong> and <strong><?php echo $data_counts['attendance']; ?> attendance records</strong> in the system.</p>
                    <p>Click the button below to remove all student and attendance data.</p>
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete ALL student data and attendance data? This action cannot be undone!');">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="confirm_cleanup">
                    
                    <button type="submit" class="btn btn-cleanup btn-lg">
                        <i class="fas fa-trash-alt"></i> Clean All Student & Attendance Data
                    </button>
                </form>
            <?php else: ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> No Data to Clean</h5>
                    <p>All student and attendance data has already been cleared. You can now:</p>
                    <ul>
                        <li><a href="bulk_upload.php">Upload fresh student data</a></li>
                        <li><a href="attendance_bulk_upload.php">Upload historical attendance data</a></li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="mt-4">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>
