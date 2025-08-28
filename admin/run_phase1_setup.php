<?php
/**
 * Phase 1 Database Setup Script
 * Run this directly in your browser to set up the enhanced batch status system
 */

$pageTitle = "Phase 1: Database Setup";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Batch Status Manager', 'url' => 'batch_status_manager.php'],
    ['title' => 'Phase 1 Setup']
];

require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = '';
$setup_results = [];

// Handle setup execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    try {
        $conn = getDBConnection();
        
        if (!$conn || $conn->connect_error) {
            throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Unknown error"));
        }
        
        $setup_results[] = "✅ Database connection successful";
        
        // Step 1: Create batch_status_log table
        $createTableSQL = "CREATE TABLE IF NOT EXISTS `batch_status_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `batch_id` int(11) NOT NULL,
            `old_status` enum('active','inactive','completed') NOT NULL,
            `new_status` enum('active','inactive','completed') NOT NULL,
            `changed_by` int(11) DEFAULT NULL COMMENT 'Admin user ID who made the change',
            `change_reason` text DEFAULT NULL COMMENT 'Reason for status change',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `batch_id` (`batch_id`),
            KEY `changed_by` (`changed_by`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        if ($conn->query($createTableSQL)) {
            $setup_results[] = "✅ batch_status_log table created successfully";
        } else {
            throw new Exception("Failed to create batch_status_log table: " . $conn->error);
        }
        
        // Step 2: Check current batch statuses
        $setup_results[] = "📊 Analyzing current batch statuses...";
        
        $batchesQuery = "SELECT id, name, code, start_date, end_date, status FROM batches ORDER BY id";
        $result = $conn->query($batchesQuery);
        
        if ($result) {
            $totalBatches = $result->num_rows;
            $batchesNeedingUpdate = 0;
            $setup_results[] = "📊 Found {$totalBatches} total batches";
            
            while ($batch = $result->fetch_assoc()) {
                $currentDate = date('Y-m-d');
                $startDate = $batch['start_date'];
                $endDate = $batch['end_date'];
                
                // Calculate expected status
                if ($currentDate < $startDate) {
                    $expectedStatus = 'inactive';
                } elseif ($currentDate >= $startDate && $currentDate <= $endDate) {
                    $expectedStatus = 'active';
                } else {
                    $expectedStatus = 'completed';
                }
                
                if ($batch['status'] !== $expectedStatus) {
                    $batchesNeedingUpdate++;
                    $setup_results[] = "⚠️  Batch {$batch['name']} ({$batch['code']}): Current={$batch['status']}, Expected={$expectedStatus}";
                }
            }
            
            $setup_results[] = "🔍 Found {$batchesNeedingUpdate} batches needing status updates";
        }
        
        // Step 3: Check GARUGUBILLI BATCH 2 specifically
        $setup_results[] = "🎯 Checking GARUGUBILLI BATCH 2 (ID 15)...";
        
        $garugubilliQuery = "SELECT id, name, code, start_date, end_date, status FROM batches WHERE id = 15";
        $result = $conn->query($garugubilliQuery);
        
        if ($result && $result->num_rows > 0) {
            $batch = $result->fetch_assoc();
            $currentDate = date('Y-m-d');
            $startDate = $batch['start_date'];
            $endDate = $batch['end_date'];
            
            if ($currentDate < $startDate) {
                $expectedStatus = 'inactive';
            } elseif ($currentDate >= $startDate && $currentDate <= $endDate) {
                $expectedStatus = 'active';
            } else {
                $expectedStatus = 'completed';
            }
            
            $setup_results[] = "📊 GARUGUBILLI BATCH 2: Current={$batch['status']}, Expected={$expectedStatus}";
            
            if ($batch['status'] !== $expectedStatus) {
                $setup_results[] = "⚠️  GARUGUBILLI BATCH 2 needs status update!";
            } else {
                $setup_results[] = "✅ GARUGUBILLI BATCH 2 status is correct";
            }
        }
        
        // Step 4: Check beneficiary statuses for GARUGUBILLI BATCH 2
        $setup_results[] = "👥 Checking GARUGUBILLI BATCH 2 beneficiaries...";
        
        $beneficiariesQuery = "SELECT status, COUNT(*) as count FROM beneficiaries WHERE batch_id = 15 GROUP BY status";
        $result = $conn->query($beneficiariesQuery);
        
        if ($result) {
            while ($ben = $result->fetch_assoc()) {
                $setup_results[] = "👥 Status '{$ben['status']}': {$ben['count']} students";
            }
        }
        
        // Step 5: Count total records
        $totalBatches = $conn->query("SELECT COUNT(*) as count FROM batches")->fetch_assoc()['count'];
        $totalBeneficiaries = $conn->query("SELECT COUNT(*) as count FROM beneficiaries")->fetch_assoc()['count'];
        $totalLogRecords = $conn->query("SELECT COUNT(*) as count FROM batch_status_log")->fetch_assoc()['count'];
        
        $setup_results[] = "📊 Database Summary:";
        $setup_results[] = "   - Total Batches: {$totalBatches}";
        $setup_results[] = "   - Total Beneficiaries: {$totalBeneficiaries}";
        $setup_results[] = "   - Batch Status Log Records: {$totalLogRecords}";
        
        $conn->close();
        
        $message = "Phase 1 Database Setup completed successfully!";
        $message_type = "success";
        
    } catch (Exception $e) {
        $setup_results[] = "❌ Error: " . $e->getMessage();
        $message = "Phase 1 Database Setup failed!";
        $message_type = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-database"></i>
                        Phase 1: Database Setup for Enhanced Batch Status System
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-warning">One-Time Setup</span>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Setup Instructions -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> What This Setup Will Do:</h5>
                        <ul class="mb-0">
                            <li>✅ Create the <code>batch_status_log</code> table for audit trail</li>
                            <li>📊 Analyze all current batch statuses vs expected statuses</li>
                            <li>🎯 Check GARUGUBILLI BATCH 2 specifically</li>
                            <li>👥 Count beneficiary statuses for the problem batch</li>
                            <li>🔍 Identify all batches needing status updates</li>
                        </ul>
                    </div>

                    <!-- Setup Button -->
                    <?php if (empty($setup_results)): ?>
                        <form method="POST" class="text-center">
                            <button type="submit" name="run_setup" class="btn btn-primary btn-lg" 
                                    onclick="return confirm('This will set up the database for the enhanced batch status system. Continue?')">
                                <i class="fas fa-play"></i>
                                Run Phase 1 Database Setup
                            </button>
                        </form>
                    <?php endif; ?>

                    <!-- Setup Results -->
                    <?php if (!empty($setup_results)): ?>
                        <div class="mt-4">
                            <h5><i class="fas fa-list"></i> Setup Results:</h5>
                            <div class="bg-light p-3 rounded">
                                <?php foreach ($setup_results as $result): ?>
                                    <div class="mb-1"><?php echo htmlspecialchars($result); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Next Steps -->
                    <?php if (!empty($setup_results) && $message_type === 'success'): ?>
                        <div class="mt-4">
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle"></i> Phase 1 Complete! Next Steps:</h5>
                                <ol class="mb-0">
                                    <li><strong>Go to</strong>: <a href="batch_status_manager.php">Batch Status Manager</a></li>
                                    <li><strong>Click</strong>: "Re-evaluate All Batch Statuses" to fix all batch statuses</li>
                                    <li><strong>Or use</strong>: "Force Status Change" for specific batches</li>
                                    <li><strong>Verify</strong>: Check that GARUGUBILLI BATCH 2 now shows correctly</li>
                                </ol>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
