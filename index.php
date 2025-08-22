<?php
// Redirect to admin dashboard or login
session_start();

if (isset($_SESSION['admin_user_id'])) {
    header('Location: admin/dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>
