<?php
session_name('lumina_admin');
session_start();
session_unset();
session_destroy();
header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php');
exit;
?>
