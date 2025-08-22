# Training Center (TC) User Credentials

## Login Information
- **Login URL**: http://localhost/v2bc_attendance/tc_login.php
- **Common Password**: `institute` (for all TC users)

## TC User Accounts Created

| TC ID | Training Center | Mandal | Constituency | Username | Password |
|-------|-----------------|--------|--------------|----------|----------|
| TTC7430317 | Parvathipuram Training Center | PARVATHIPURAM | PARVATHIPURAM | TTC7430317 | institute |
| TTC7430652 | Balijipeta Training Center | BALIJIPETA | PARVATHIPURAM | TTC7430652 | institute |
| TTC7430654 | Seethanagaram Training Center | SEETHANAGARAM | PARVATHIPURAM | TTC7430654 | institute |
| TTC7430664 | Kurupam Training Center | KURUPAM | KURUPAM | TTC7430664 | institute |
| TTC7430536 | GL Puram Training Center | GL PURAM | KURUPAM | TTC7430536 | institute |
| TTC7430529 | Jiyyammavalasa Training Center | JIYYAMMAVALASA | KURUPAM | TTC7430529 | institute |
| TTC7430543 | Komarada Training Center | KOMARADA | KURUPAM | TTC7430543 | institute |
| TTC7430653 | Garugubilli Training Center | GARUGUBILLI | KURUPAM | TTC7430653 | institute |

## TC User Features

### Allowed Actions:
- ✅ View individual training center dashboard
- ✅ Mark/edit attendance for current date only
- ✅ View beneficiaries (read-only)
- ✅ View batches (read-only)

### Restrictions:
- ❌ Cannot edit past date attendance
- ❌ Cannot delete any records
- ❌ Cannot modify beneficiaries, batches, or master data
- ❌ Cannot access admin features
- ❌ Limited to their own training center data only

### Admin Tracking Features:
- 📊 All TC user activities are logged
- 📊 Admin can view TC user login history
- 📊 Admin can track attendance edits with timestamps
- 📊 Admin can monitor daily activity per TC user

## How to Use

### For TC Users:
1. Go to TC Login page: http://localhost/v2bc_attendance/tc_login.php
2. Enter your TC ID (e.g., TTC7430317)
3. Enter password: `institute`
4. Access your dashboard and mark attendance

### For Admin:
1. Login to admin panel
2. Go to "TC User Tracking" in the sidebar
3. Monitor all TC user activities and login history
4. View detailed activity logs for each TC user

## Security Features:
- Separate authentication system from admin
- Session management specific to TC users
- CSRF protection
- Audit trail for all attendance modifications
- Time-based restrictions (current date only)
- Role-based access control

## Database Tables:
- `tc_users` - TC user accounts
- `attendance_edit_log` - Tracks all attendance modifications by TC users

---
*System implemented on: <?php echo date('Y-m-d H:i:s'); ?>*
*All 8 Training Center users are ready to use*
