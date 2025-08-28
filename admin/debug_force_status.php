<?php
/**
 * Debug Script for Force Status Change
 * This will help us test if the function is working
 */

$pageTitle = "Debug Force Status Change";
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'Debug Force Status']
];

require_once '../includes/header.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['admin_user_id'])) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$message_type = '';
$debug_info = [];

// Handle test action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'test_force_status') {
        $batch_id = (int)$_POST['batch_id'];
        $new_status = $_POST['new_status'];
        $reason = sanitizeInput($_POST['reason']);
        
        $debug_info[] = "Testing force status change...";
        $debug_info[] = "Batch ID: {$batch_id}";
        $debug_info[] = "New Status: {$new_status}";
        $debug_info[] = "Reason: {$reason}";
        
        try {
            // Test if the function exists
            if (function_exists('forceBatchStatusChange')) {
                $debug_info[] = "✅ forceBatchStatusChange function exists";
                
                // Call the function
                $result = forceBatchStatusChange($batch_id, $new_status, $reason);
                
                if ($result['success']) {
                    $debug_info[] = "✅ Function executed successfully";
                    $debug_info[] = "Message: " . $result['message'];
                    $debug_info[] = "Old Status: " . $result['old_status'];
                    $debug_info[] = "New Status: " . $result['new_status'];
                    $debug_info[] = "Beneficiaries Updated: " . $result['beneficiaries_updated'];
                    
                    $message = "Force status change successful!";
                    $message_type = "success";
                } else {
                    $debug_info[] = "❌ Function failed";
                    $debug_info[] = "Error: " . $result['message'];
                    
                    $message = "Force status change failed!";
                    $message_type = "danger";
                }
            } else {
                $debug_info[] = "❌ forceBatchStatusChange function does not exist";
                $message = "Function not found!";
                $message_type = "danger";
            }
        } catch (Exception $e) {
            $debug_info[] = "❌ Exception occurred: " . $e->getMessage();
            $message = "Exception occurred!";
            $message_type = "danger";
        }
    }
}

// Get all batches for testing
$batches = fetchAll("
    SELECT id, name, code, status, start_date, end_date
    FROM batches 
    ORDER BY id 
    LIMIT 10
");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bug"></i>
                        Debug Force Status Change
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-warning">Testing Tool</span>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Test Form -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Test Force Status Change</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="test_force_status">
                                        
                                        <div class="form-group">
                                            <label for="batch_id">Select Batch:</label>
                                            <select name="batch_id" id="batch_id" class="form-control" required>
                                                <option value="">-- Select Batch --</option>
                                                <?php foreach ($batches as $batch): ?>
                                                    <option value="<?php echo $batch['id']; ?>">
                                                        <?php echo htmlspecialchars($batch['name']); ?> (<?php echo htmlspecialchars($batch['code']); ?>) - 
                                                        Current: <?php echo ucfirst($batch['status']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="new_status">New Status:</label>
                                            <select name="new_status" id="new_status" class="form-control" required>
                                                <option value="">-- Select Status --</option>
                                                <option value="inactive">Inactive (Not Started)</option>
                                                <option value="active">Active (Running)</option>
                                                <option value="completed">Completed (Ended)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="reason">Reason for Change:</label>
                                            <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Explain why you're changing this status..." required></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Test Force Status Change
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Debug Information</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($debug_info)): ?>
                                        <div class="bg-light p-3 rounded">
                                            <?php foreach ($debug_info as $info): ?>
                                                <div class="mb-1"><?php echo htmlspecialchars($info); ?></div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No debug information yet. Run a test to see results.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
