<?php
session_start();
unset($_SESSION['admin_auth']);
header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php');
exit;
?>
