<?php
session_start();
require_once 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $epasts = mysqli_real_escape_string($savienojums, trim($_POST['epasts']));
    $parole = trim($_POST['parole']);
    
    $res = mysqli_query($savienojums, "SELECT * FROM klienti WHERE epasts='$epasts'");
    if($klients = mysqli_fetch_assoc($res)){
        if(password_verify($parole, $klients['parole'])){
            $_SESSION['klients_id'] = $klients['id'];
            $_SESSION['klients_vards'] = $klients['vards'];
            $_SESSION['klients_epasts'] = $klients['epasts'];
            $_SESSION['toast'] = ['msg' => 'Laipni lūgti, '.$klients['vards'].'!', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['msg' => 'Nepareiza parole!', 'type' => 'error'];
        }
    } else {
        $_SESSION['toast'] = ['msg' => 'Lietotājs ar šādu e-pastu netika atrasts!', 'type' => 'error'];
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: '.$redirect);
exit;