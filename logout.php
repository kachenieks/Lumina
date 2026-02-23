<?php
session_start();
unset($_SESSION['klients_id']);
unset($_SESSION['klients_vards']);
unset($_SESSION['klients_epasts']);
$_SESSION['toast'] = ['msg' => 'Veiksmīgi izgājāt no konta.', 'type' => 'success'];
header('Location: index.php');
exit;