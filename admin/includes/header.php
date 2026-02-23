<?php
// Admin auth check - simple password protection
// In production use proper auth system
if (!isset($_SESSION['admin_auth'])) {
  if ($_SERVER['PHP_SELF'] !== '/lumina/Lumina/admin/login.php') {
    header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php');
    exit;
  }
}
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? $pageTitle . ' — LUMINA Admin' : 'LUMINA Admin' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/4pt/blazkova/lumina/Lumina/admin/admin.css">
</head>
<body>

<aside class="admin-sidebar">
  <a href="/4pt/blazkova/lumina/Lumina/admin/index.php" class="sidebar-logo">LUMIN<span>A</span></a>
  <nav class="sidebar-nav">
    <a href="/4pt/blazkova/lumina/Lumina/admin/index.php" class="sidebar-item <?= $adminPage === 'index' ? 'active' : '' ?>">
      <span class="sidebar-icon">📊</span> Pārskats
    </a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/portfolio.php" class="sidebar-item <?= $adminPage === 'portfolio' ? 'active' : '' ?>">
      <span class="sidebar-icon">🖼</span> Portfolio
    </a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php" class="sidebar-item <?= $adminPage === 'rezervacijas' ? 'active' : '' ?>">
      <span class="sidebar-icon">📅</span> Rezervācijas
    </a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/klienti.php" class="sidebar-item <?= $adminPage === 'klienti' ? 'active' : '' ?>">
      <span class="sidebar-icon">👤</span> Klienti
    </a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/preces.php" class="sidebar-item <?= $adminPage === 'preces' ? 'active' : '' ?>">
      <span class="sidebar-icon">🛍</span> Veikals
    </a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php" class="sidebar-item <?= $adminPage === 'galerijas' ? 'active' : '' ?>">
      <span class="sidebar-icon">🗂</span> Galerijas
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="/4pt/blazkova/lumina/Lumina/index.php" target="_blank">← Uz mājaslapu</a><br>
    <a href="/4pt/blazkova/lumina/Lumina/admin/logout.php" style="margin-top:8px;display:block;color:rgba(255,255,255,.2);">Iziet</a>
  </div>
</aside>

<main class="admin-main">
<div class="admin-topbar">
  <div class="admin-title"><?= isset($pageTitle) ? $pageTitle : 'Admin' ?></div>
  <div style="font-size:11px;color:var(--grey);">Administrators ✦</div>
</div>
<div class="admin-content">
<div class="toast-container" id="toastContainer"></div>
