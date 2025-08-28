# 🧪 Manual Testing Guide for Enhanced Batch Status System

## 🎯 **What We're Testing**
The new system that automatically updates batch and student statuses when you change dates.

## 📋 **Prerequisites**
- ✅ Database server running
- ✅ Web server accessible
- ✅ Admin login credentials ready

## 🚀 **Step 1: Database Setup**
1. **Connect to your database** (phpMyAdmin, MySQL Workbench, or command line)
2. **Run the SQL script**: `setup_database.sql`
3. **Verify**: `batch_status_log` table is created

## 🌐 **Step 2: Test Web Interface**
1. **Open browser** and navigate to your admin panel
2. **Go to**: System Configuration → Batch Status Manager
3. **Expected**: You should see the Batch Status Manager interface
4. **If error**: Check if the file exists and has correct permissions

## 🔧 **Step 3: Test Batch Editing (Main Test)**
1. **Navigate to**: Admin → Training Batches
2. **Find**: GARUGUBILLI BATCH 2 (should be ID 15)
3. **Current Status**: Should show "completed" (ended Aug 20, 2025)
4. **Click**: Edit button
5. **Change**: End date from "2025-08-20" to "2025-09-03"
6. **Save**: Click Update button
7. **Expected Result**: 
   - Success message mentioning automatic status update
   - Batch status changes from "completed" to "active"
   - All 53 students change from "completed" to "active"

## 📊 **Step 4: Verify Status Changes**
1. **Check Batch Status**:
   - Go back to Training Batches list
   - GARUGUBILLI BATCH 2 should now show "active"
   
2. **Check Student Statuses**:
   - Go to Admin → Students
   - Filter by GARUGUBILLI BATCH 2
   - All students should show "active" status

3. **Check TC Dashboard**:
   - Login as TC user (Garugubilli Training Center)
   - Go to Dashboard
   - BATCH 2 should now show 53 students instead of 0
   - Status should show "✓ Submitted" instead of "Pending"

## 🎛️ **Step 5: Test Batch Status Manager**
1. **Go to**: Admin → System Configuration → Batch Status Manager
2. **View**: All batches with their current vs. expected statuses
3. **Look for**: Batches marked with ⚠️ "Needs Update"
4. **Test**: Click "Re-evaluate All Batch Statuses"
5. **Expected**: All batches update to correct statuses

## 🔍 **Step 6: Check Audit Trail**
1. **In Batch Status Manager**: Click "View Status History"
2. **Expected**: See log of GARUGUBILLI BATCH 2 status change
3. **Details**: Should show "completed" → "active" with reason

## ❌ **Troubleshooting Common Issues**

### **Issue 1: Functions Not Found**
- **Symptom**: "Function reEvaluateBatchStatus does not exist"
- **Solution**: Check if `includes/functions.php` is properly included

### **Issue 2: Database Connection Error**
- **Symptom**: "Database connection failed"
- **Solution**: Verify database credentials in `config/database.php`

### **Issue 3: Permission Denied**
- **Symptom**: "Access denied" or blank page
- **Solution**: Check file permissions and admin login status

### **Issue 4: Status Not Updating**
- **Symptom**: Date changed but status remains the same
- **Solution**: Check if the `reEvaluateBatchStatus` function is being called

## ✅ **Success Criteria**
- [ ] GARUGUBILLI BATCH 2 status changes from "completed" to "active"
- [ ] All 53 students change from "completed" to "active"
- [ ] TC dashboard shows 53 students instead of 0
- [ ] Batch Status Manager shows no batches needing updates
- [ ] Audit trail shows the status change

## 🎉 **What This Means**
If all tests pass, your system now has:
- ✅ **Automatic status updates** when dates change
- ✅ **Universal batch control** via Batch Status Manager
- ✅ **Complete audit trail** for all changes
- ✅ **Real-time status monitoring** across all systems

## 🆘 **Need Help?**
If any test fails:
1. Check the error messages
2. Verify database setup
3. Check file permissions
4. Ensure all files are properly uploaded
5. Test with the simple function test: `php simple_function_test.php`
