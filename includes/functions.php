<?php
// Common utility functions

// Sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format date
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = 'Y-m-d H:i:s') {
    if (empty($datetime)) return '';
    return date($format, strtotime($datetime));
}

// Generate pagination
function generatePagination($currentPage, $totalPages, $baseUrl) {
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage - 1) . '">Previous</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($start > 2) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $pagination .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $pagination .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($currentPage + 1) . '">Next</a></li>';
    } else {
        $pagination .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

// Get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
            return 'badge-success';
        case 'inactive':
            return 'badge-danger';
        case 'completed':
            return 'badge-info';
        case 'present':
            return 'badge-success';
        case 'absent':
            return 'badge-danger';

        default:
            return 'badge-secondary';
    }
}

// Success message
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Error message
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// Display flash messages
function displayFlashMessages() {
    $output = '';
    
    if (isset($_SESSION['success_message'])) {
        $output .= '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        $output .= '<i class="fas fa-check-circle"></i> ';
        $output .= htmlspecialchars($_SESSION['success_message']);
        $output .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        $output .= '<span aria-hidden="true">&times;</span>';
        $output .= '</button>';
        $output .= '</div>';
        unset($_SESSION['success_message']);
    }
    
    if (isset($_SESSION['error_message'])) {
        $output .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        $output .= '<i class="fas fa-exclamation-triangle"></i> ';
        $output .= htmlspecialchars($_SESSION['error_message']);
        $output .= '<button type="button" class="close" data-dismiss="alert" aria-label="Close">';
        $output .= '<span aria-hidden="true">&times;</span>';
        $output .= '</button>';
        $output .= '</div>';
        unset($_SESSION['error_message']);
    }
    
    return $output;
}

// CSV Export function
function exportToCSV($filename, $data, $headers = []) {
    // Clean any existing output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Expires: 0');
    header('Pragma: public');
    
    // Create file pointer and write CSV
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Write data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}



// Attendance validation functions
function isValidAttendanceDate($date, $batchId = null) {
    // Check if date is a Sunday (0 = Sunday in PHP)
    if (date('w', strtotime($date)) == 0) {
        return [
            'valid' => false,
            'reason' => 'Attendance cannot be marked on Sundays'
        ];
    }
    
    // Check if date is in the future
    if (strtotime($date) > strtotime(date('Y-m-d'))) {
        return [
            'valid' => false,
            'reason' => 'Attendance cannot be marked for future dates'
        ];
    }
    
    // If batch ID is provided, check batch-specific restrictions
    if ($batchId) {
        $batch = fetchRow("SELECT start_date, end_date, name FROM batches WHERE id = ? AND status IN ('active', 'completed')", [$batchId], 'i');
        
        if (!$batch) {
            return [
                'valid' => false,
                'reason' => 'Invalid or inactive batch'
            ];
        }
        
        // Check if date is before batch start date
        if (strtotime($date) < strtotime($batch['start_date'])) {
            return [
                'valid' => false,
                'reason' => "Attendance cannot be marked before batch start date (" . formatDate($batch['start_date'], 'd/m/Y') . ")"
            ];
        }
        
        // Note: Batch end date restriction removed to allow attendance marking after batch completion
        // This enables administrative corrections and late submissions
        
        // Check if date is a batch-specific holiday
        $holiday = fetchRow("SELECT holiday_name FROM batch_holidays WHERE batch_id = ? AND holiday_date = ?", [$batchId, $date], 'is');
        
        if ($holiday) {
            return [
                'valid' => false,
                'reason' => "Cannot mark attendance on holiday: " . $holiday['holiday_name']
            ];
        }
    }
    
    return [
        'valid' => true,
        'reason' => null
    ];
}

function getBatchHolidays($batchId) {
    return fetchAll("SELECT * FROM batch_holidays WHERE batch_id = ? ORDER BY holiday_date", [$batchId], 'i');
}

function addBatchHoliday($batchId, $holidayDate, $holidayName, $description = '', $createdBy = null) {
    // Check if holiday already exists
    $existing = fetchRow("SELECT id FROM batch_holidays WHERE batch_id = ? AND holiday_date = ?", [$batchId, $holidayDate], 'is');
    
    if ($existing) {
        return [
            'success' => false,
            'message' => 'Holiday already exists for this date'
        ];
    }
    
    $query = "INSERT INTO batch_holidays (batch_id, holiday_date, holiday_name, description, created_by) VALUES (?, ?, ?, ?, ?)";
    $result = executeQuery($query, [$batchId, $holidayDate, $holidayName, $description, $createdBy], 'isssi');
    
    if ($result) {
        return [
            'success' => true,
            'message' => 'Holiday added successfully'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to add holiday'
        ];
    }
}

function deleteBatchHoliday($holidayId, $batchId = null) {
    $whereClause = "id = ?";
    $params = [$holidayId];
    $types = 'i';
    
    if ($batchId) {
        $whereClause .= " AND batch_id = ?";
        $params[] = $batchId;
        $types .= 'i';
    }
    
    $query = "DELETE FROM batch_holidays WHERE $whereClause";
    $result = executeQuery($query, $params, $types);
    
    return $result;
}

// Format status display for reports
function formatStatusDisplay($status) {
    switch($status) {
        case 'P':
        case 'present':
            return 'Present';
        case 'A':
        case 'absent':
            return 'Absent';
        case 'H':
        case 'excused':
            return 'Holiday';
        default:
            return ucfirst($status);
    }
}



// Get dashboard statistics
function getDashboardStats() {
    $stats = [];
    
    // Total beneficiaries (all statuses)
    $result = fetchRow("SELECT COUNT(*) as total FROM beneficiaries");
    $stats['total_beneficiaries'] = $result ? $result['total'] : 0;
    
    // Ongoing students (All Students - Completed Students)
    // This includes both active and inactive students who are NOT in completed batches
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM beneficiaries b
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE bt.end_date >= CURDATE()
    ");
    $stats['ongoing_students'] = $result ? $result['total'] : 0;
    
    // Completed students (students in batches where end_date < current_date)
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM beneficiaries b
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE bt.end_date < CURDATE()
    ");
    $stats['completed_students'] = $result ? $result['total'] : 0;
    
    // Total constituencies
    $result = fetchRow("SELECT COUNT(*) as total FROM constituencies WHERE status = 'active'");
    $stats['total_constituencies'] = $result ? $result['total'] : 0;
    
    // Total mandals
    $result = fetchRow("SELECT COUNT(*) as total FROM mandals WHERE status = 'active'");
    $stats['total_mandals'] = $result ? $result['total'] : 0;
    
    // Total batches (all statuses)
    $result = fetchRow("SELECT COUNT(*) as total FROM batches");
    $stats['total_batches'] = $result ? $result['total'] : 0;
    
    // Active batches only
    $result = fetchRow("SELECT COUNT(*) as total FROM batches WHERE status = 'active'");
    $stats['active_batches'] = $result ? $result['total'] : 0;
    
    // Active students today (students in active batches - end_date >= current_date)
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM beneficiaries b
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE bt.end_date >= CURDATE()
    ");
    $stats['active_students_today'] = $result ? $result['total'] : 0;
    
    // Today's attendance (only from students in active batches)
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM attendance a 
        INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE a.attendance_date = CURDATE() AND bt.end_date >= CURDATE()
    ");
    $stats['today_attendance'] = $result ? $result['total'] : 0;
    
    // Present today (only from students in active batches)
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM attendance a 
        INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE a.attendance_date = CURDATE() 
        AND bt.end_date >= CURDATE()
        AND (a.status = 'present' OR a.status = 'P')
    ");
    $stats['present_today'] = $result ? $result['total'] : 0;
    
    // Absent today (only from students in active batches)
    $result = fetchRow("
        SELECT COUNT(*) as total 
        FROM attendance a 
        INNER JOIN beneficiaries b ON a.beneficiary_id = b.id 
        INNER JOIN batches bt ON b.batch_id = bt.id
        WHERE a.attendance_date = CURDATE() 
        AND bt.end_date >= CURDATE()
        AND (a.status = 'absent' OR a.status = 'A')
    ");
    $stats['absent_today'] = $result ? $result['total'] : 0;
    
    return $stats;
}

/**
 * Holiday Management Functions
 */

/**
 * Check if a date is a holiday (Sunday or manual holiday)
 */
function isHoliday($date) {
    // Check if it's Sunday
    $dayOfWeek = date('N', strtotime($date));
    if ($dayOfWeek == 7) { // Sunday
        return true;
    }
    
    // Check if it's a manual holiday
    $holiday = fetchRow("SELECT id FROM holidays WHERE date = ? AND status = 'active'", [$date]);
    return $holiday !== false;
}

// Auto-mark Sundays functionality removed

function safeUploadAttendance($beneficiaryId, $date, $status) {
    // Check if this is a Sunday
    $isSunday = (date('N', strtotime($date)) == '7');
    
    if ($isSunday) {
        // For Sundays, always ensure it's marked as holiday
        executeQuery("INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                     VALUES (?, ?, 'H', NOW()) 
                     ON DUPLICATE KEY UPDATE status = 'H'", [$beneficiaryId, $date]);
        return 'H'; // Return 'H' to indicate it was converted to holiday
    } else {
        // For non-Sundays, allow the status to be set as provided
        executeQuery("INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                     VALUES (?, ?, ?, NOW()) 
                     ON DUPLICATE KEY UPDATE status = ?", [$beneficiaryId, $date, $status, $status]);
        return $status; // Return the original status
    }
}

/**
 * Mark a specific date as holiday for all or specific batches
 */
function markDateAsHoliday($date, $description, $type = 'program', $batchIds = []) {
    try {
        // Add to holidays table
        executeQuery("INSERT INTO holidays (date, description, type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE description = ?", 
                    [$date, $description, $type, $description]);
        
        if (empty($batchIds)) {
            // Mark all active beneficiaries as holiday
            executeQuery("INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                         SELECT b.id, ?, 'H', NOW() 
                         FROM beneficiaries b 
                         WHERE b.status = 'active'
                         ON DUPLICATE KEY UPDATE status = 'H'", [$date]);
        } else {
            // Mark specific batches as holiday
            $placeholders = str_repeat('?,', count($batchIds) - 1) . '?';
            executeQuery("INSERT INTO attendance (beneficiary_id, attendance_date, status, created_at) 
                         SELECT b.id, ?, 'H', NOW() 
                         FROM beneficiaries b 
                         WHERE b.status = 'active' AND b.batch_id IN ($placeholders)
                         ON DUPLICATE KEY UPDATE status = 'H'", 
                         array_merge([$date], $batchIds));
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error marking date as holiday: " . $e->getMessage());
        return false;
    }
}

/**
 * Process bulk holiday upload from CSV
 */
function processBulkHolidayUpload($filename) {
    try {
        $handle = fopen($filename, 'r');
        if (!$handle) {
            throw new Exception("Could not open file");
        }
        
        $count = 0;
        $row = 1; // Skip header row
        
        while (($data = fgetcsv($handle)) !== false) {
            if ($row == 1) {
                $row++;
                continue; // Skip header
            }
            
            if (count($data) >= 3) {
                $date = $data[0];
                $description = $data[1];
                $batchIds = explode(',', $data[2]); // Comma-separated batch IDs
                
                // Validate date
                if (!strtotime($date)) {
                    continue;
                }
                
                // Mark as holiday
                if (markDateAsHoliday($date, $description, 'program', $batchIds)) {
                    $count++;
                }
            }
            $row++;
        }
        
        fclose($handle);
        return $count;
        
    } catch (Exception $e) {
        error_log("Error processing bulk holiday upload: " . $e->getMessage());
        return false;
    }
}

/**
 * Get holiday information for a specific date
 */
function getHolidayInfo($date) {
    return fetchRow("SELECT * FROM holidays WHERE date = ? AND status = 'active'", [$date]);
}

/**
 * Get all holidays for a date range
 */
function getHolidaysInRange($startDate, $endDate) {
    return fetchAll("SELECT * FROM holidays WHERE date BETWEEN ? AND ? AND status = 'active' ORDER BY date", 
                   [$startDate, $endDate], 'ss');
}

/**
 * Remove holiday status from a date
 */
function removeHoliday($date) {
    try {
        // Delete from holidays table
        executeQuery("DELETE FROM holidays WHERE date = ?", [$date]);
        
        // Remove holiday status from attendance records (set back to absent)
        executeQuery("UPDATE attendance SET status = 'absent' WHERE attendance_date = ? AND status = 'H'", [$date]);
        
        return true;
    } catch (Exception $e) {
        error_log("Error removing holiday: " . $e->getMessage());
        return false;
    }
}

// Check and mark completed batches OR reactivate extended batches
function checkAndMarkCompletedBatches() {
    try {
        $conn = getDBConnection();
        
        $messages = [];
        $totalChanges = 0;
        
        // 1. Get batches that have ended but are still marked as active
        $completedQuery = "SELECT id, name, end_date FROM batches 
                          WHERE status = 'active' AND end_date < CURDATE()";
        
        $stmt = $conn->prepare($completedQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $completedBatches = [];
        
        while ($row = $result->fetch_assoc()) {
            $completedBatches[] = $row;
        }
        $stmt->close();
        
        // 2. Get batches that have been extended and should be reactivated
        $reactivateQuery = "SELECT id, name, end_date FROM batches 
                           WHERE status = 'completed' AND end_date >= CURDATE()";
        
        $stmt = $conn->prepare($reactivateQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $reactivateBatches = [];
        
        while ($row = $result->fetch_assoc()) {
            $reactivateBatches[] = $row;
        }
        $stmt->close();
        
        // Process completed batches
        if (!empty($completedBatches)) {
            $updatedBatches = 0;
            $updatedBeneficiaries = 0;
            
            foreach ($completedBatches as $batch) {
                // Mark batch as completed
                $updateBatchQuery = "UPDATE batches SET status = 'completed', updated_at = NOW() WHERE id = ?";
                $batchStmt = $conn->prepare($updateBatchQuery);
                $batchStmt->bind_param('i', $batch['id']);
                
                if ($batchStmt->execute()) {
                    $updatedBatches++;
                    
                    // Mark all beneficiaries in this batch as completed
                    $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = 'completed', updated_at = NOW() WHERE batch_id = ? AND status = 'active'";
                    $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
                    $beneficiariesStmt->bind_param('i', $batch['id']);
                    
                    if ($beneficiariesStmt->execute()) {
                        $updatedBeneficiaries += $beneficiariesStmt->affected_rows;
                    }
                    $beneficiariesStmt->close();
                }
                $batchStmt->close();
            }
            
            if ($updatedBatches > 0) {
                $messages[] = "Marked $updatedBatches batches and $updatedBeneficiaries beneficiaries as completed";
                $totalChanges += $updatedBatches;
            }
        }
        
        // Process reactivated batches
        if (!empty($reactivateBatches)) {
            $reactivatedBatches = 0;
            $reactivatedBeneficiaries = 0;
            
            foreach ($reactivateBatches as $batch) {
                // Mark batch as active
                $updateBatchQuery = "UPDATE batches SET status = 'active', updated_at = NOW() WHERE id = ?";
                $batchStmt = $conn->prepare($updateBatchQuery);
                $batchStmt->bind_param('i', $batch['id']);
                
                if ($batchStmt->execute()) {
                    $reactivatedBatches++;
                    
                    // Mark all beneficiaries in this batch as active
                    $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = 'active', updated_at = NOW() WHERE batch_id = ? AND status = 'completed'";
                    $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
                    $beneficiariesStmt->bind_param('i', $batch['id']);
                    
                    if ($beneficiariesStmt->execute()) {
                        $reactivatedBeneficiaries += $beneficiariesStmt->affected_rows;
                    }
                    $beneficiariesStmt->close();
                }
                $batchStmt->close();
            }
            
            if ($reactivatedBatches > 0) {
                $messages[] = "Reactivated $reactivatedBatches batches and $reactivatedBeneficiaries beneficiaries";
                $totalChanges += $reactivatedBatches;
            }
        }
        
        if ($totalChanges == 0) {
            return ['success' => true, 'message' => 'No batch status changes needed', 'count' => 0];
        }
        
        return [
            'success' => true, 
            'message' => implode('; ', $messages),
            'count' => $totalChanges
        ];
        
    } catch (Exception $e) {
        error_log("Error updating batch statuses: " . $e->getMessage());
        return [
            'success' => false, 
            'message' => 'Error updating batch statuses: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}

// Enhanced function to re-evaluate a specific batch status based on current dates
function reEvaluateBatchStatus($batchId) {
    try {
        $conn = getDBConnection();
        
        // Get current batch information
        $batchQuery = "SELECT id, name, start_date, end_date, status FROM batches WHERE id = ?";
        $stmt = $conn->prepare($batchQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('i', $batchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $batch = $result->fetch_assoc();
        $stmt->close();
        
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found'];
        }
        
        $currentDate = date('Y-m-d');
        $startDate = $batch['start_date'];
        $endDate = $batch['end_date'];
        $oldStatus = $batch['status'];
        $newStatus = '';
        $statusChange = false;
        
        // Determine new status based on current dates
        if ($currentDate < $startDate) {
            $newStatus = 'inactive'; // Not started yet
        } elseif ($currentDate >= $startDate && $currentDate <= $endDate) {
            $newStatus = 'active'; // Currently running
        } else {
            $newStatus = 'completed'; // Has ended
        }
        
        // Check if status needs to change
        if ($oldStatus !== $newStatus) {
            $statusChange = true;
            
            // Update batch status
            $updateBatchQuery = "UPDATE batches SET status = ?, updated_at = NOW() WHERE id = ?";
            $batchStmt = $conn->prepare($updateBatchQuery);
            $batchStmt->bind_param('si', $newStatus, $batchId);
            
            if (!$batchStmt->execute()) {
                throw new Exception("Failed to update batch status");
            }
            $batchStmt->close();
            
            // Update beneficiary statuses based on new batch status
            $beneficiaryStatus = ($newStatus === 'active') ? 'active' : 'completed';
            $oldBeneficiaryStatus = ($newStatus === 'active') ? 'completed' : 'active';
            
            $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = ?, updated_at = NOW() WHERE batch_id = ? AND status = ?";
            $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
            $beneficiariesStmt->bind_param('sis', $beneficiaryStatus, $batchId, $oldBeneficiaryStatus);
            
            if (!$beneficiariesStmt->execute()) {
                throw new Exception("Failed to update beneficiary statuses");
            }
            
            $affectedBeneficiaries = $beneficiariesStmt->affected_rows;
            $beneficiariesStmt->close();
            
            // Log the status change
            $logQuery = "INSERT INTO batch_status_log (batch_id, old_status, new_status, changed_by, change_reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
            $logStmt = $conn->prepare($logQuery);
            if ($logStmt) {
                $adminUserId = $_SESSION['admin_user_id'] ?? 0;
                $changeReason = "Automatic status update after date modification";
                $logStmt->bind_param('issis', $batchId, $oldStatus, $newStatus, $adminUserId, $changeReason);
                $logStmt->execute();
                $logStmt->close();
            }
            
            return [
                'success' => true,
                'message' => "Batch '{$batch['name']}' status changed from '{$oldStatus}' to '{$newStatus}'. {$affectedBeneficiaries} beneficiaries updated.",
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'beneficiaries_updated' => $affectedBeneficiaries,
                'status_changed' => true
            ];
        } else {
            return [
                'success' => true,
                'message' => "Batch '{$batch['name']}' status remains '{$oldStatus}' (no change needed)",
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'beneficiaries_updated' => 0,
                'status_changed' => false
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error re-evaluating batch status: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error re-evaluating batch status: ' . $e->getMessage(),
            'old_status' => '',
            'new_status' => '',
            'beneficiaries_updated' => 0,
            'status_changed' => false
        ];
    }
}

// Function to re-evaluate all batch statuses
function reEvaluateAllBatchStatuses() {
    try {
        $conn = getDBConnection();
        
        // Get all batches
        $batchesQuery = "SELECT id FROM batches";
        $stmt = $conn->prepare($batchesQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $batches = [];
        
        while ($row = $result->fetch_assoc()) {
            $batches[] = $row['id'];
        }
        $stmt->close();
        
        $totalChanges = 0;
        $messages = [];
        
        // Re-evaluate each batch
        foreach ($batches as $batchId) {
            $result = reEvaluateBatchStatus($batchId);
            if ($result['success'] && $result['status_changed']) {
                $totalChanges++;
                $messages[] = $result['message'];
            }
        }
        
        if ($totalChanges == 0) {
            return ['success' => true, 'message' => 'All batch statuses are up to date', 'count' => 0];
        }
        
        return [
            'success' => true,
            'message' => "Re-evaluated $totalChanges batches. " . implode('; ', $messages),
            'count' => $totalChanges
        ];
        
    } catch (Exception $e) {
        error_log("Error re-evaluating all batch statuses: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error re-evaluating all batch statuses: ' . $e->getMessage(),
            'count' => 0
        ];
    }
}

// Function to force a specific batch status change
function forceBatchStatusChange($batchId, $newStatus, $reason) {
    try {
        $conn = getDBConnection();
        
        // Get current batch information
        $batchQuery = "SELECT id, name, status FROM batches WHERE id = ?";
        $stmt = $conn->prepare($batchQuery);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('i', $batchId);
        $stmt->execute();
        $result = $stmt->get_result();
        $batch = $result->fetch_assoc();
        $stmt->close();
        
        if (!$batch) {
            return ['success' => false, 'message' => 'Batch not found'];
        }
        
        $oldStatus = $batch['status'];
        
        if ($oldStatus === $newStatus) {
            return ['success' => true, 'message' => "Batch '{$batch['name']}' status is already '{$newStatus}'", 'count' => 0];
        }
        
        // Update batch status
        $updateBatchQuery = "UPDATE batches SET status = ?, updated_at = NOW() WHERE id = ?";
        $batchStmt = $conn->prepare($updateBatchQuery);
        $batchStmt->bind_param('si', $newStatus, $batchId);
        
        if (!$batchStmt->execute()) {
            throw new Exception("Failed to update batch status");
        }
        $batchStmt->close();
        
        // Update beneficiary statuses based on new batch status
        $beneficiaryStatus = ($newStatus === 'active') ? 'active' : 'completed';
        $oldBeneficiaryStatus = ($newStatus === 'active') ? 'completed' : 'active';
        
        $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = ?, updated_at = NOW() WHERE batch_id = ? AND status = ?";
        $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
        $beneficiariesStmt->bind_param('sis', $beneficiaryStatus, $batchId, $oldBeneficiaryStatus);
        
        if (!$beneficiariesStmt->execute()) {
            throw new Exception("Failed to update beneficiary statuses");
        }
        
        $affectedBeneficiaries = $beneficiariesStmt->affected_rows;
        $beneficiariesStmt->close();
        
        // Log the forced status change
        $logQuery = "INSERT INTO batch_status_log (batch_id, old_status, new_status, changed_by, change_reason, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $logStmt = $conn->prepare($logQuery);
        if ($logStmt) {
            $adminUserId = $_SESSION['admin_user_id'] ?? 0;
            $logStmt->bind_param('issis', $batchId, $oldStatus, $newStatus, $adminUserId, $reason);
            $logStmt->execute();
            $logStmt->close();
        }
        
        return [
            'success' => true,
            'message' => "Batch '{$batch['name']}' status forcefully changed from '{$oldStatus}' to '{$newStatus}'. {$affectedBeneficiaries} beneficiaries updated. Reason: {$reason}",
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'beneficiaries_updated' => $affectedBeneficiaries,
            'status_changed' => true
        ];
        
    } catch (Exception $e) {
        error_log("Error forcing batch status change: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error forcing batch status change: ' . $e->getMessage(),
            'old_status' => '',
            'new_status' => '',
            'beneficiaries_updated' => 0,
            'status_changed' => false
        ];
    }
}
?>
