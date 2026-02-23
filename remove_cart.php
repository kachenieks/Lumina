<?php
session_start();
$i = (int)($_GET['i'] ?? -1);
if(isset($_SESSION['cart'][$i])){
    array_splice($_SESSION['cart'], $i, 1);
    $_SESSION['toast'] = ['msg' => 'Prece noņemta no groza.', 'type' => 'success'];
}
header('Location: '.$_SERVER['HTTP_REFERER'] ?? 'index.php');
exit;