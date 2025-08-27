<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$pageTitle = 'Manage Custom Holidays';
$breadcrumbs = [
    ['title' => 'Dashboard', 'url' => 'dashboard.php'],
    ['title' => 'System Configuration', 'url' => '#'],
    ['title' => 'Manage Custom Holidays']
];

require_once '../includes/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_holiday':
                addHoliday();
                break;
            case 'delete_holiday':
                deleteHoliday();
                break;
            // Auto-mark Sundays functionality removed

        }
    }
}

function addHoliday() {
    $date = $_POST['date'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $batchIds = $_POST['batch_ids'] ?? [];
    $isAllBatches = isset($_POST['is_all_batches']) ? 1 : 0;
    
    try {
        // Validate required fields
        if (empty($date) || empty($description) || empty($type)) {
            throw new Exception("All fields are required");
        }
        
        // Validate date format
        if (!strtotime($date)) {
            throw new Exception("Invalid date format");
        }
        
        // Check if holiday already exists for this date
        $existingHoliday = fetchRow("SELECT id FROM holidays WHERE date = ?", [$date]);
        if ($existingHoliday) {
            throw new Exception("A holiday already exists for this date");
        }
        
        // Add to holidays table
        $holidayQuery = "INSERT INTO holidays (date, description, type) VALUES (?, ?, ?)";
        $result = executeQuery($holidayQuery, [$date, $description, $type]);
        
        if (!$result) {
            throw new Exception("Failed to insert holiday into database");
        }
        
        $holidayId = $conn->insert_id; // Get the inserted holiday ID
        
        // Store batch selections in batch_holidays table
        if (!$isAllBatches && !empty($batchIds)) {
            foreach ($batchIds as $batchId) {
                $batchHolidayQuery = "INSERT INTO batch_holidays (holiday_id, batch_id, holiday_date, holiday_name, description, created_by) 
                                     VALUES (?, ?, ?, ?, ?, ?)";
                $batchHolidayResult = executeQuery($batchHolidayQuery, [
                    $holidayId, $batchId, $date, $description, $type, $_SESSION['admin_user_id']
                ]);
                
                if (!$batchHolidayResult) {
                    throw new Exception("Failed to store batch holiday relationship");
                }
            }
        }
        
        // Mark attendance as holiday
        if ($isAllBatches) {
            // Mark all active beneficiaries as holiday for this date
            $attendanceQuery = "INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                               SELECT b.id, ?, 'holiday', NOW() 
                               FROM beneficiaries b 
                               WHERE b.status = 'active'
                               ON DUPLICATE KEY UPDATE status = 'holiday'";
            $attendanceResult = executeQuery($attendanceQuery, [$date]);
            
            if (!$attendanceResult) {
                throw new Exception("Failed to update attendance records");
            }
        } else {
            // Mark specific batches as holiday
            if (!empty($batchIds)) {
                $placeholders = str_repeat('?,', count($batchIds) - 1) . '?';
                $attendanceQuery = "INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                                   SELECT b.id, ?, 'holiday', NOW() 
                                   FROM beneficiaries b 
                                   WHERE b.status = 'active' AND b.batch_id IN ($placeholders)
                                   ON DUPLICATE KEY UPDATE status = 'holiday'";
                $params = array_merge([$date], $batchIds);
                $attendanceResult = executeQuery($attendanceQuery, $params);
                
                if (!$attendanceResult) {
                    throw new Exception("Failed to update attendance records for specific batches");
                }
            }
        }
        
        $_SESSION['success'] = "Holiday '$description' added successfully for " . date('d/m/Y', strtotime($date)) . "!";
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error adding holiday: " . $e->getMessage();
        error_log("Holiday addition error: " . $e->getMessage());
    }
    
    // Ensure clean redirect
    if (headers_sent()) {
        echo "<script>window.location.href = 'manage_holidays.php';</script>";
    } else {
        header('Location: manage_holidays.php');
    }
    exit;
}

function deleteHoliday() {
    $holidayId = $_POST['holiday_id'];
    $date = $_POST['date'];
    
    try {
        // Delete batch holiday relationships first
        executeQuery("DELETE FROM batch_holidays WHERE holiday_id = ?", [$holidayId]);
        
        // Delete from holidays table
        executeQuery("DELETE FROM holidays WHERE id = ?", [$holidayId]);
        
        // Remove holiday status from attendance records (set back to absent)
        executeQuery("UPDATE attendance SET status = 'absent' WHERE attendance_date = ? AND status = 'holiday'", [$date]);
        
        $_SESSION['success'] = "Holiday deleted successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error deleting holiday: " . $e->getMessage();
    }
    
    // Ensure clean redirect
    if (headers_sent()) {
        echo "<script>window.location.href = 'manage_holidays.php';</script>";
    } else {
        header('Location: manage_holidays.php');
    }
    exit;
}



// Check if holidays table exists and get existing holidays
try {
    // First check if table exists
    $tableExists = fetchRow("SHOW TABLES LIKE 'holidays'");
    if (!$tableExists) {
        error_log("Holidays table does not exist");
        $_SESSION['error'] = "Holidays table does not exist. Please check your database setup.";
        $holidays = [];
    } else {
        // Get existing holidays with proper batch and mandal information
        $holidays = fetchAll("
            SELECT 
                h.*,
                CASE 
                    WHEN h.type = 'national' THEN 'All Mandals'
                    WHEN EXISTS (
                        SELECT 1 FROM batch_holidays bh WHERE bh.holiday_id = h.id
                    ) THEN 'Specific Batches'
                    ELSE 'All Mandals'
                END as mandal_coverage,
                GROUP_CONCAT(
                    DISTINCT CONCAT(m.name, ' - ', bt.name, ' (', bt.code, ')') 
                    ORDER BY m.name, bt.name 
                    SEPARATOR ', '
                ) as batch_details,
                GROUP_CONCAT(
                    DISTINCT m.name 
                    ORDER BY m.name 
                    SEPARATOR ', '
                ) as mandal_names
            FROM holidays h
            LEFT JOIN batch_holidays bh ON h.id = bh.holiday_id
            LEFT JOIN batches bt ON bh.batch_id = bt.id
            LEFT JOIN mandals m ON bt.mandal_id = m.id
            WHERE h.description != 'Sunday Holiday'
            GROUP BY h.id, h.date, h.description, h.type, h.status, h.created_at, h.updated_at
            ORDER BY h.date DESC
        ");
    }
} catch (Exception $e) {
    error_log("Error checking holidays table: " . $e->getMessage());
    $holidays = [];
    $_SESSION['error'] = "Error checking holidays table: " . $e->getMessage();
}

// Get batches for selection
$batches = fetchAll("SELECT id, name, mandal_id FROM batches WHERE status IN ('active', 'completed') ORDER BY status DESC, name");

// Get mandals for grouping
$mandals = fetchAll("SELECT id, name FROM mandals WHERE status = 'active' ORDER BY name");

// Group batches by mandal
$batchesByMandal = [];
foreach ($batches as $batch) {
    $mandalId = $batch['mandal_id'];
    if (!isset($batchesByMandal[$mandalId])) {
        $batchesByMandal[$mandalId] = [];
    }
    $batchesByMandal[$mandalId][] = $batch;
}
?>

<!-- Auto-Mark Sundays Section Removed -->

<!-- Display Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($_SESSION['success']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?php echo htmlspecialchars($_SESSION['error']); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Add Holiday Section -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-plus"></i>
            Add New Custom Holiday
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_holiday">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="date">Holiday Date</label>
                        <input type="date" id="date" name="date" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" class="form-control" placeholder="e.g., Local Festival" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select id="type" name="type" class="form-control">
                            <option value="national">National Holiday</option>
                            <option value="program">Program Holiday</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="is_all_batches">Apply To</label>
                        <div class="form-check">
                            <input type="checkbox" id="is_all_batches" name="is_all_batches" class="form-check-input" checked>
                            <label class="form-check-label" for="is_all_batches">All Batches</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row" id="batch-selection" style="display: none;">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Select Specific Batches</label>
                        <div class="row">
                            <?php foreach ($mandals as $mandal): ?>
                            <div class="col-md-4">
                                <h6><?php echo htmlspecialchars($mandal['name']); ?></h6>
                                <?php if (isset($batchesByMandal[$mandal['id']])): ?>
                                    <?php foreach ($batchesByMandal[$mandal['id']] as $batch): ?>
                                    <div class="form-check">
                                        <input type="checkbox" name="batch_ids[]" value="<?php echo $batch['id']; ?>" 
                                               class="form-check-input" id="batch_<?php echo $batch['id']; ?>">
                                        <label class="form-check-label" for="batch_<?php echo $batch['id']; ?>">
                                            <?php echo htmlspecialchars($batch['name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Add Holiday
            </button>
        </form>
    </div>
</div>



<!-- Existing Holidays Section -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list"></i>
            Custom Holidays
        </h3>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Note:</strong> Sunday holidays are automatically managed by the system and are not displayed here. 
            This table shows only custom holidays you've added (local festivals, national holidays, etc.).
            <br><strong>Coverage Column:</strong> Shows whether the holiday applies to all mandals or specific batches with detailed information.
        </div>
        <?php if (!empty($holidays)): ?>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Coverage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($holidays as $holiday): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($holiday['date'])); ?></td>
                        <td><?php echo date('l', strtotime($holiday['date'])); ?></td>
                        <td><?php echo htmlspecialchars($holiday['description']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $holiday['type'] === 'national' ? 'danger' : ($holiday['type'] === 'program' ? 'primary' : 'secondary'); ?>">
                                <?php echo ucfirst($holiday['type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($holiday['mandal_coverage'] === 'All Mandals'): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-globe"></i> All Mandals
                                </span>
                                <br><small class="text-muted">Applies to all active mandals</small>
                            <?php else: ?>
                                <span class="badge badge-info" title="<?php echo htmlspecialchars($holiday['batch_details'] ?? 'No batches specified'); ?>">
                                    <i class="fas fa-map-marker-alt"></i> Specific Batches
                                </span>
                                <?php if (!empty($holiday['batch_details'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($holiday['batch_details']); ?></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_holiday">
                                <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                <input type="hidden" name="date" value="<?php echo $holiday['date']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure? This will remove holiday status from all attendance records for this date.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="text-center py-4">
            <i class="fas fa-calendar fa-3x text-muted mb-3"></i>
            <p class="text-muted">No custom holidays configured yet.</p>
            <small class="text-muted">Sunday holidays are automatically managed by the system.</small>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('is_all_batches').addEventListener('change', function() {
    const batchSelection = document.getElementById('batch-selection');
    if (this.checked) {
        batchSelection.style.display = 'none';
        // Uncheck all batch checkboxes
        document.querySelectorAll('input[name="batch_ids[]"]').forEach(cb => cb.checked = false);
    } else {
        batchSelection.style.display = 'block';
    }
});

// Auto-mark Sundays functionality removed
</script>

<?php require_once '../includes/footer.php'; ?>
