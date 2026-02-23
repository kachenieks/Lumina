<?php
session_start();
require_once 'db.php';

$id = (int)($_GET['id'] ?? 0);
$prece = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT * FROM preces WHERE id=$id AND `aktīvs`=1"));

if($prece){
    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][] = [
        'preces_id' => $prece['id'],
        'nosaukums' => $prece['nosaukums'],
        'cena' => $prece['cena'],
        'attels' => $prece['attels_url'],
        'izmers' => $_GET['izmers'] ?? '',
    ];
    $_SESSION['toast'] = ['msg' => $prece['nosaukums'].' pievienots grozam ✓', 'type' => 'success'];
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'veikals.php';
header('Location: '.$redirect);
exit;