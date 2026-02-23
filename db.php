<?php
$serveris = "localhost";
$lietotajs = "grobina1_blazkova";
$parole = "UVKr6R##3TDV";
$db = "grobina1_blazkova";

$savienojums = mysqli_connect($serveris, $lietotajs, $parole, $db);

if (!$savienojums) {
    die("Nav izveidots savienojums ar datu bāzi!: " . mysqli_connect_error());
}

mysqli_set_charset($savienojums, "utf8");
?>
