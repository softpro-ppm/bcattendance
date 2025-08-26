<?php
/**
 * Fix Batch Status Script
 * This script manually updates the status of a specific batch
 * Run this to fix the KUR_GAR_B2 batch status
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== Batch Status Fix Script ===\n\n";

try {
    $conn = getDBConnection();
    
    // First, let's check the current status of the specific batch
    $batchCode = 'KUR_GAR_B2';
    
    $checkQuery = "SELECT id, name, code, start_date, end_date, status FROM batches WHERE code = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('s', $batchCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $batch = $result->fetch_assoc();
    $stmt->close();
    
    if (!$batch) {
        echo "❌ Batch with code '$batchCode' not found!\n";
        exit(1);
    }
    
    echo "📋 Current Batch Information:\n";
    echo "   ID: {$batch['id']}\n";
    echo "   Name: {$batch['name']}\n";
    echo "   Code: {$batch['code']}\n";
    echo "   Start Date: {$batch['start_date']}\n";
    echo "   End Date: {$batch['end_date']}\n";
    echo "   Current Status: {$batch['status']}\n";
    echo "   Current Date: " . date('Y-m-d') . "\n\n";
    
    // Check if the batch should be active
    $currentDate = date('Y-m-d');
    $shouldBeActive = $batch['end_date'] >= $currentDate;
    
    echo "🔍 Status Analysis:\n";
    echo "   End Date: {$batch['end_date']}\n";
    echo "   Current Date: $currentDate\n";
    echo "   Should be Active: " . ($shouldBeActive ? 'YES' : 'NO') . "\n\n";
    
    if ($shouldBeActive && $batch['status'] !== 'active') {
        echo "🔄 Updating batch status to 'active'...\n";
        
        // Update batch status
        $updateBatchQuery = "UPDATE batches SET status = 'active', updated_at = NOW() WHERE id = ?";
        $batchStmt = $conn->prepare($updateBatchQuery);
        $batchStmt->bind_param('i', $batch['id']);
        
        if ($batchStmt->execute()) {
            echo "✅ Batch status updated successfully!\n";
            
            // Update beneficiaries status
            $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = 'active', updated_at = NOW() WHERE batch_id = ? AND status = 'completed'";
            $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
            $beneficiariesStmt->bind_param('i', $batch['id']);
            
            if ($beneficiariesStmt->execute()) {
                $affectedBeneficiaries = $beneficiariesStmt->affected_rows;
                echo "✅ Updated $affectedBeneficiaries beneficiaries to 'active' status!\n";
            } else {
                echo "❌ Failed to update beneficiaries: " . $beneficiariesStmt->error . "\n";
            }
            $beneficiariesStmt->close();
            
        } else {
            echo "❌ Failed to update batch status: " . $batchStmt->error . "\n";
        }
        $batchStmt->close();
        
    } elseif (!$shouldBeActive && $batch['status'] !== 'completed') {
        echo "🔄 Updating batch status to 'completed'...\n";
        
        // Update batch status
        $updateBatchQuery = "UPDATE batches SET status = 'completed', updated_at = NOW() WHERE id = ?";
        $batchStmt = $conn->prepare($updateBatchQuery);
        $batchStmt->bind_param('i', $batch['id']);
        
        if ($batchStmt->execute()) {
            echo "✅ Batch status updated successfully!\n";
            
            // Update beneficiaries status
            $updateBeneficiariesQuery = "UPDATE beneficiaries SET status = 'completed', updated_at = NOW() WHERE batch_id = ? AND status = 'active'";
            $beneficiariesStmt = $conn->prepare($updateBeneficiariesQuery);
            $beneficiariesStmt->bind_param('i', $batch['id']);
            
            if ($beneficiariesStmt->execute()) {
                $affectedBeneficiaries = $beneficiariesStmt->affected_rows;
                echo "✅ Updated $affectedBeneficiaries beneficiaries to 'completed' status!\n";
            } else {
                echo "❌ Failed to update beneficiaries: " . $beneficiariesStmt->error . "\n";
            }
            $beneficiariesStmt->close();
            
        } else {
            echo "❌ Failed to update batch status: " . $batchStmt->error . "\n";
        }
        $batchStmt->close();
        
    } else {
        echo "✅ Batch status is already correct!\n";
    }
    
    // Verify the update
    echo "\n🔍 Verifying the update...\n";
    
    $verifyQuery = "SELECT id, name, code, start_date, end_date, status FROM batches WHERE code = ?";
    $stmt = $conn->prepare($verifyQuery);
    $stmt->bind_param('s', $batchCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $updatedBatch = $result->fetch_assoc();
    $stmt->close();
    
    echo "📋 Updated Batch Information:\n";
    echo "   ID: {$updatedBatch['id']}\n";
    echo "   Name: {$updatedBatch['name']}\n";
    echo "   Code: {$updatedBatch['code']}\n";
    echo "   Start Date: {$updatedBatch['start_date']}\n";
    echo "   End Date: {$updatedBatch['end_date']}\n";
    echo "   New Status: {$updatedBatch['status']}\n\n";
    
    // Check beneficiary count
    $beneficiaryQuery = "SELECT 
        COUNT(*) as total_beneficiaries,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_beneficiaries,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_beneficiaries
        FROM beneficiaries WHERE batch_id = ?";
    
    $stmt = $conn->prepare($beneficiaryQuery);
    $stmt->bind_param('i', $updatedBatch['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $beneficiaryStats = $result->fetch_assoc();
    $stmt->close();
    
    echo "👥 Beneficiary Statistics:\n";
    echo "   Total Beneficiaries: {$beneficiaryStats['total_beneficiaries']}\n";
    echo "   Active Beneficiaries: {$beneficiaryStats['active_beneficiaries']}\n";
    echo "   Completed Beneficiaries: {$beneficiaryStats['completed_beneficiaries']}\n\n";
    
    echo "✅ Batch status fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== End of Script ===\n";
?>
