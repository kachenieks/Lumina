<?php
// Log out client
session_name('lumina_klient');
session_start();
session_unset();
session_destroy();
header('Location: /4pt/blazkova/lumina/Lumina/login.php');
exit;
?>
