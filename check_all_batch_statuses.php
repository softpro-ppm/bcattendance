<?php
/**
 * Check All Batch Statuses Script
 * This script checks and fixes all batch statuses across the system
 * Ensures consistency between end dates and batch statuses
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== All Batch Statuses Check Script ===\n\n";

try {
    $conn = getDBConnection();
    
    // Get all batches with their current status
    $query = "SELECT id, name, code, start_date, end_date, status FROM batches ORDER BY start_date";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    $allBatches = [];
    
    while ($row = $result->fetch_assoc()) {
        $allBatches[] = $row;
    }
    $stmt->close();
    
    $currentDate = date('Y-m-d');
    $totalBatches = count($allBatches);
    $statusChanges = 0;
    $issues = [];
    
    echo "📊 Total Batches Found: $totalBatches\n";
    echo "📅 Current Date: $currentDate\n\n";
    
    echo "🔍 Analyzing batch statuses...\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($allBatches as $batch) {
        $shouldBeActive = $batch['end_date'] >= $currentDate;
        $statusCorrect = ($shouldBeActive && $batch['status'] === 'active') || 
                        (!$shouldBeActive && $batch['status'] === 'completed');
        
        echo "Batch: {$batch['name']} ({$batch['code']})\n";
        echo "   Start: {$batch['start_date']} | End: {$batch['end_date']} | Current: {$batch['status']}\n";
        echo "   Should be: " . ($shouldBeActive ? 'Active' : 'Completed') . " | Status: " . ($statusCorrect ? '✅ Correct' : '❌ Incorrect') . "\n";
        
        if (!$statusCorrect) {
            $issues[] = $batch;
            $statusChanges++;
        }
        
        echo "\n";
    }
    
    echo str_repeat("-", 80) . "\n";
    echo "📈 Summary:\n";
    echo "   Total Batches: $totalBatches\n";
    echo "   Correct Status: " . ($totalBatches - $statusChanges) . "\n";
    echo "   Incorrect Status: $statusChanges\n\n";
    
    if (empty($issues)) {
        echo "🎉 All batch statuses are correct! No fixes needed.\n";
    } else {
        echo "🔧 Fixing incorrect batch statuses...\n\n";
        
        foreach ($issues as $batch) {
            $shouldBeActive = $batch['end_date'] >= $currentDate;
            $newStatus = $shouldBeActive ? 'active' : 'completed';
            
            echo "🔄 Fixing {$batch['name']} ({$batch['code']}):\n";
            echo "   Current: {$batch['status']} → New: $newStatus\n";
            
            // Update batch status
            $updateBatchQuery = "UPDATE batches SET status = ?, updated_at = NOW() WHERE id = ?";
            $batchStmt = $conn->prepare($updateBatchQuery);
            $batchStmt->bind_param('si', $newStatus, $batch['id']);
            
            if ($batchStmt->execute()) {
                echo "   ✅ Batch status updated successfully!\n";
                
                // Update beneficiaries status
                $oldStatus = $batch['status'];
                $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = ?, updated_at = NOW() WHERE batch_id = ? AND status = ?";
                $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
                $beneficiariesStmt->bind_param('sis', $newStatus, $batch['id'], $oldStatus);
                
                if ($beneficiariesStmt->execute()) {
                    $affectedBeneficiaries = $beneficiariesStmt->affected_rows;
                    echo "   ✅ Updated $affectedBeneficiaries beneficiaries from '$oldStatus' to '$newStatus'!\n";
                } else {
                    echo "   ❌ Failed to update beneficiaries: " . $beneficiariesStmt->error . "\n";
                }
                $beneficiariesStmt->close();
                
            } else {
                echo "   ❌ Failed to update batch status: " . $batchStmt->error . "\n";
            }
            $batchStmt->close();
            
            echo "\n";
        }
        
        echo "✅ All batch status fixes completed!\n";
    }
    
    // Now let's run the enhanced checkAndMarkCompletedBatches function
    echo "\n🔄 Running enhanced batch status check function...\n";
    $result = checkAndMarkCompletedBatches();
    
    if ($result['success']) {
        echo "✅ Enhanced function result: {$result['message']}\n";
    } else {
        echo "❌ Enhanced function error: {$result['message']}\n";
    }
    
    // Final verification
    echo "\n🔍 Final verification of all batches...\n";
    
    $finalQuery = "SELECT 
        COUNT(*) as total_batches,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_batches,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_batches,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_batches
        FROM batches";
    
    $stmt = $conn->prepare($finalQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $finalStats = $result->fetch_assoc();
    $stmt->close();
    
    echo "📊 Final Batch Statistics:\n";
    echo "   Total Batches: {$finalStats['total_batches']}\n";
    echo "   Active Batches: {$finalStats['active_batches']}\n";
    echo "   Completed Batches: {$finalStats['completed_batches']}\n";
    echo "   Inactive Batches: {$finalStats['inactive_batches']}\n\n";
    
    // Check beneficiary statistics
    $beneficiaryQuery = "SELECT 
        COUNT(*) as total_beneficiaries,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_beneficiaries,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_beneficiaries,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_beneficiaries
        FROM beneficiaries";
    
    $stmt = $conn->prepare($beneficiaryQuery);
    $stmt->execute();
    $result = $stmt->get_result();
    $beneficiaryStats = $result->fetch_assoc();
    $stmt->close();
    
    echo "👥 Final Beneficiary Statistics:\n";
    echo "   Total Beneficiaries: {$beneficiaryStats['total_beneficiaries']}\n";
    echo "   Active Beneficiaries: {$beneficiaryStats['active_beneficiaries']}\n";
    echo "   Completed Beneficiaries: {$beneficiaryStats['completed_beneficiaries']}\n";
    echo "   Inactive Beneficiaries: {$beneficiaryStats['inactive_beneficiaries']}\n\n";
    
    echo "🎯 Script completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== End of Script ===\n";
?>
