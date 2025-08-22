<?php
session_start();
session_unset();
session_destroy();
header('Location: tc_login.php');
exit();
?>
