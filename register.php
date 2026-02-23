<?php
session_start();
require_once 'db.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $vards = mysqli_real_escape_string($savienojums, trim($_POST['vards']));
    $uzvards = mysqli_real_escape_string($savienojums, trim($_POST['uzvards']));
    $epasts = mysqli_real_escape_string($savienojums, trim($_POST['epasts']));
    $talrunis = mysqli_real_escape_string($savienojums, trim($_POST['talrunis']));
    $parole = password_hash(trim($_POST['parole']), PASSWORD_DEFAULT);
    
    // Check if email exists
    $check = mysqli_query($savienojums, "SELECT id FROM klienti WHERE epasts='$epasts'");
    if(mysqli_num_rows($check) > 0){
        $_SESSION['toast'] = ['msg' => 'Šāds e-pasts jau ir reģistrēts!', 'type' => 'error'];
    } else {
        $sql = "INSERT INTO klienti (vards, uzvards, epasts, talrunis, parole) VALUES ('$vards','$uzvards','$epasts','$talrunis','$parole')";
        if(mysqli_query($savienojums, $sql)){
            $new_id = mysqli_insert_id($savienojums);
            $_SESSION['klients_id'] = $new_id;
            $_SESSION['klients_vards'] = $vards;
            $_SESSION['klients_epasts'] = $epasts;
            $_SESSION['toast'] = ['msg' => 'Konts izveidots! Laipni lūgti, '.$vards.'!', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['msg' => 'Kļūda! Mēģiniet vēlreiz.', 'type' => 'error'];
        }
    }
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';
header('Location: '.$redirect);
exit;