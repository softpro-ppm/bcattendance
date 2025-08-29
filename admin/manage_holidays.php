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
            case 'edit_holiday':
                editHoliday();
                break;
            case 'delete_holiday':
                deleteHoliday();
                break;
            // Auto-mark Sundays functionality removed

        }
    }
}

function editHoliday() {
    $holidayId = $_POST['holiday_id'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $type = $_POST['type'];
    $batchIds = $_POST['batch_ids'] ?? [];
    $isAllBatches = isset($_POST['is_all_batches']) ? 1 : 0;
    
    try {
        // Validate required fields
        if (empty($holidayId) || empty($date) || empty($description) || empty($type)) {
            throw new Exception("All fields are required");
        }
        
        // Validate date format
        if (!strtotime($date)) {
            throw new Exception("Invalid date format");
        }
        
        // Check if holiday already exists for this date (excluding current holiday)
        $existingHoliday = fetchRow("SELECT id FROM holidays WHERE date = ? AND id != ?", [$date, $holidayId]);
        if ($existingHoliday) {
            throw new Exception("A holiday already exists for this date");
        }
        
        // Update holiday in holidays table
        $holidayQuery = "UPDATE holidays SET date = ?, description = ?, type = ? WHERE id = ?";
        $result = executeQuery($holidayQuery, [$date, $description, $type, $holidayId]);
        
        if (!$result) {
            throw new Exception("Failed to update holiday in database");
        }
        
        // Remove existing batch assignments
        $deleteQuery = "DELETE FROM batch_holidays WHERE holiday_id = ?";
        executeQuery($deleteQuery, [$holidayId]);
        
        // Store new batch selections in batch_holidays table
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
                    throw new Exception("Failed to update attendance records");
                }
            }
        }
        
        $_SESSION['success'] = "Holiday updated successfully!";
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating holiday: " . $e->getMessage();
    }
    
    // Redirect back to the page
    header('Location: manage_holidays.php');
    exit;
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
        try {
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
                    COALESCE(
                        (SELECT GROUP_CONCAT(
                            DISTINCT CONCAT(m.name, ' - ', bt.name, ' (', bt.code, ')') 
                            ORDER BY m.name, bt.name 
                            SEPARATOR ', '
                        ) FROM batch_holidays bh2 
                         JOIN batches bt ON bh2.batch_id = bt.id 
                         JOIN mandals m ON bt.mandal_id = m.id 
                         WHERE bh2.holiday_id = h.id), 'All Batches'
                    ) as batch_details,
                    COALESCE(
                        (SELECT GROUP_CONCAT(
                            DISTINCT m.name 
                            ORDER BY m.name 
                            SEPARATOR ', '
                        ) FROM batch_holidays bh2 
                         JOIN batches bt ON bh2.batch_id = bt.id 
                         JOIN mandals m ON bt.mandal_id = m.id 
                         WHERE bh2.holiday_id = h.id), 'All Mandals'
                    ) as mandal_names
                FROM holidays h
                WHERE h.description != 'Sunday Holiday'
                ORDER BY h.date DESC
            ");
            
            // If the complex query fails, fall back to simple query
            if (!$holidays || count($holidays) == 0) {
                $holidays = fetchAll("
                    SELECT 
                        id, date, description, type, status, created_at, updated_at,
                        'All Mandals' as mandal_coverage,
                        'All Batches' as batch_details,
                        'All Mandals' as mandal_names
                    FROM holidays 
                    WHERE description != 'Sunday Holiday'
                    ORDER BY date DESC
                ");
            }
        } catch (Exception $e) {
            error_log("Holiday query error: " . $e->getMessage());
            // Fallback to simple query
            $holidays = fetchAll("
                SELECT 
                    id, date, description, type, status, created_at, updated_at,
                    'All Mandals' as mandal_coverage,
                    'All Batches' as batch_details,
                    'All Mandals' as mandal_names
                FROM holidays 
                WHERE description != 'Sunday Holiday'
                ORDER BY date DESC
            ");
        }
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
        <!-- Debug Information -->
        <div class="alert alert-info mb-3">
            <strong>Debug Info:</strong> 
            Total holidays found: <?php echo count($holidays); ?> | 
            Query executed successfully<br>
            <strong>Debug Details:</strong><br>
            <?php 
            // Test database connection
            try {
                $conn = getDBConnection();
                echo "✅ Database connection: Working<br>";
                
                // Test simple query first
                $simpleTest = fetchAll("SELECT COUNT(*) as count FROM holidays");
                echo "Total records in holidays table: " . ($simpleTest ? $simpleTest[0]['count'] : 'Error') . "<br>";
                
                // Test filtered query
                $filteredTest = fetchAll("SELECT COUNT(*) as count FROM holidays WHERE description != 'Sunday Holiday'");
                echo "Holidays excluding Sundays: " . ($filteredTest ? $filteredTest[0]['count'] : 'Error') . "<br>";
                
                // Show all holidays
                $allHolidays = fetchAll("SELECT * FROM holidays ORDER BY date DESC");
                if ($allHolidays) {
                    echo "All holidays in table:<br>";
                    foreach ($allHolidays as $h) {
                        echo "- ID: {$h['id']}, Date: {$h['date']}, Description: {$h['description']}, Type: {$h['type']}<br>";
                    }
                } else {
                    echo "No holidays found in simple query<br>";
                }
                
                // Test direct query
                echo "<br><strong>Direct Query Test:</strong><br>";
                $directResult = $conn->query("SELECT * FROM holidays WHERE description != 'Sunday Holiday' ORDER BY date DESC");
                if ($directResult) {
                    $directCount = $directResult->num_rows;
                    echo "Direct query returned: {$directCount} rows<br>";
                    if ($directCount > 0) {
                        while ($row = $directResult->fetch_assoc()) {
                            echo "- ID: {$row['id']}, Date: {$row['date']}, Description: {$row['description']}<br>";
                        }
                    }
                } else {
                    echo "Direct query failed: " . $conn->error . "<br>";
                }
                
                // Check batch_holidays relationships
                echo "<br><strong>Batch Holiday Relationships:</strong><br>";
                
                // First check if table exists
                $tableExists = $conn->query("SHOW TABLES LIKE 'batch_holidays'");
                if ($tableExists && $tableExists->num_rows > 0) {
                    $batchHolidaysResult = $conn->query("SELECT bh.*, h.description, b.name as batch_name, m.name as mandal_name 
                                                       FROM batch_holidays bh 
                                                       JOIN holidays h ON bh.holiday_id = h.id 
                                                       JOIN batches b ON bh.batch_id = b.id 
                                                       JOIN mandals m ON b.mandal_id = m.id 
                                                       ORDER BY bh.holiday_date DESC");
                    if ($batchHolidaysResult) {
                        $batchCount = $batchHolidaysResult->num_rows;
                        echo "Batch holiday relationships found: {$batchCount}<br>";
                        if ($batchCount > 0) {
                            while ($row = $batchHolidaysResult->fetch_assoc()) {
                                echo "- Holiday: {$row['description']} → Batch: {$row['batch_name']} ({$row['mandal_name']})<br>";
                            }
                        } else {
                            echo "❌ No batch holiday relationships found - this is why all holidays show 'All Mandals'<br>";
                        }
                    } else {
                        echo "❌ Failed to check batch holidays: " . $conn->error . "<br>";
                    }
                } else {
                    echo "❌ <strong>batch_holidays table is missing!</strong> This is why all holidays show 'All Mandals'<br>";
                    echo "💡 <strong>Solution:</strong> Run the create_batch_holidays_table.php script to recreate the missing table<br>";
                }
                
            } catch (Exception $e) {
                echo "❌ Database error: " . $e->getMessage() . "<br>";
            }
            ?>
        </div>
        
        <?php 
        // Determine which holidays to display
        $displayHolidays = $holidays;
        $holidaySource = 'Main Query';
        
        if (empty($displayHolidays)) {
            // Try fallback query
            $fallbackHolidays = fetchAll("SELECT * FROM holidays WHERE description != 'Sunday Holiday' ORDER BY date DESC");
            if ($fallbackHolidays) {
                $displayHolidays = $fallbackHolidays;
                $holidaySource = 'Fallback Query';
            }
        }
        
        if (!empty($displayHolidays)): ?>
        <div class="table-responsive">
            <!-- Simple Holidays List (Debug) -->
            <div class="alert alert-warning mb-3">
                <strong>Raw Holidays from Database:</strong><br>
                <?php 
                $simpleHolidays = fetchAll("SELECT id, date, description, type FROM holidays WHERE description != 'Sunday Holiday' ORDER BY date DESC");
                if ($simpleHolidays) {
                    foreach ($simpleHolidays as $h) {
                        echo "ID: {$h['id']}, Date: {$h['date']}, Description: {$h['description']}, Type: {$h['type']}<br>";
                    }
                } else {
                    echo "No holidays found in simple query either";
                }
                ?>
            </div>
            
            <?php if ($holidaySource === 'Fallback Query'): ?>
            <div class="alert alert-success mb-3">
                <strong>Fallback Query Found Holidays:</strong> <?php echo count($displayHolidays); ?> holidays found using fallback query.
            </div>
            <?php endif; ?>
            
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
                    <?php foreach ($displayHolidays as $holiday): ?>
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
                            <?php if (isset($holiday['mandal_coverage']) && $holiday['mandal_coverage'] === 'All Mandals'): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-globe"></i> All Mandals
                                </span>
                                <br><small class="text-muted">Applies to all active mandals</small>
                            <?php elseif (isset($holiday['mandal_coverage']) && $holiday['mandal_coverage'] === 'Specific Batches'): ?>
                                <span class="badge badge-info" title="<?php echo htmlspecialchars($holiday['batch_details'] ?? 'No batches specified'); ?>">
                                    <i class="fas fa-map-marker-alt"></i> Specific Batches
                                </span>
                                <?php if (!empty($holiday['batch_details'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($holiday['batch_details']); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-calendar"></i> General Holiday
                                </span>
                                <br><small class="text-muted">Applies to all batches</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary btn-sm" onclick="editHoliday(<?php echo $holiday['id']; ?>, '<?php echo $holiday['date']; ?>', '<?php echo htmlspecialchars($holiday['description']); ?>', '<?php echo $holiday['type']; ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_holiday">
                                    <input type="hidden" name="holiday_id" value="<?php echo $holiday['id']; ?>">
                                    <input type="hidden" name="date" value="<?php echo $holiday['date']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure? This will remove holiday status from all attendance records for this date.')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
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

<!-- Edit Holiday Modal -->
<div class="modal fade" id="editHolidayModal" tabindex="-1" role="dialog" aria-labelledby="editHolidayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editHolidayModalLabel">Edit Holiday</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_holiday">
                <input type="hidden" name="holiday_id" id="edit_holiday_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_date">Holiday Date</label>
                                <input type="date" id="edit_date" name="date" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <input type="text" id="edit_description" name="description" class="form-control" placeholder="e.g., Local Festival" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_type">Type</label>
                                <select id="edit_type" name="type" class="form-control">
                                    <option value="national">National Holiday</option>
                                    <option value="program">Program Holiday</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_is_all_batches">Apply To</label>
                                <div class="form-check">
                                    <input type="checkbox" id="edit_is_all_batches" name="is_all_batches" class="form-check-input">
                                    <label class="form-check-label" for="edit_is_all_batches">All Batches</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row" id="edit-batch-selection" style="display: none;">
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
                                                       class="form-check-input edit-batch-checkbox" id="edit_batch_<?php echo $batch['id']; ?>">
                                                <label class="form-check-label" for="edit_batch_<?php echo $batch['id']; ?>">
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Existing script
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

// Edit holiday functionality
function editHoliday(id, date, description, type) {
    // Set form values
    document.getElementById('edit_holiday_id').value = id;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_type').value = type;
    
    // Check if holiday has specific batch assignments
    const hasSpecificBatches = false; // This will be determined by checking batch_holidays table
    
    if (hasSpecificBatches) {
        document.getElementById('edit_is_all_batches').checked = false;
        document.getElementById('edit-batch-selection').style.display = 'block';
        // TODO: Check specific batches based on existing assignments
    } else {
        document.getElementById('edit_is_all_batches').checked = true;
        document.getElementById('edit-batch-selection').style.display = 'none';
    }
    
    // Show modal
    $('#editHolidayModal').modal('show');
}

// Edit modal batch selection toggle
document.getElementById('edit_is_all_batches').addEventListener('change', function() {
    const batchSelection = document.getElementById('edit-batch-selection');
    if (this.checked) {
        batchSelection.style.display = 'none';
        // Uncheck all batch checkboxes
        document.querySelectorAll('.edit-batch-checkbox').forEach(cb => cb.checked = false);
    } else {
        batchSelection.style.display = 'block';
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
