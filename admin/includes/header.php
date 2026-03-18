<?php
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

<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<!-- ── SIDEBAR ── -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-text">LUMIN<span>A</span></div>
    <div class="sidebar-logo-sub">Admin panelis</div>
  </div>
  <nav class="sidebar-nav">
    <a href="/4pt/blazkova/lumina/Lumina/admin/index.php"        class="sidebar-item <?= $adminPage==='index'       ?'active':'' ?>"><span class="sidebar-icon">📊</span> Pārskats</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php" class="sidebar-item <?= $adminPage==='rezervacijas'?'active':'' ?>"><span class="sidebar-icon">📅</span> Rezervācijas</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/klienti.php"      class="sidebar-item <?= $adminPage==='klienti'     ?'active':'' ?>"><span class="sidebar-icon">👤</span> Klienti</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/preces.php"       class="sidebar-item <?= $adminPage==='preces'      ?'active':'' ?>"><span class="sidebar-icon">🛍</span> Veikals</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/pasutijumi.php"   class="sidebar-item <?= $adminPage==='pasutijumi'  ?'active':'' ?>"><span class="sidebar-icon">🖼</span> Foto pasūtījumi</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/portfolio.php"    class="sidebar-item <?= $adminPage==='portfolio'   ?'active':'' ?>"><span class="sidebar-icon">🖼</span> Portfolio</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php"    class="sidebar-item <?= $adminPage==='galerijas'   ?'active':'' ?>"><span class="sidebar-icon">🗂</span> Galerijas</a>
  </nav>
  <div class="sidebar-footer">
    <a href="/4pt/blazkova/lumina/Lumina/index.php" target="_blank">← Uz mājaslapu</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/logout.php" style="margin-top:10px;display:block;">Iziet</a>
  </div>
</aside>

<!-- ── MAIN ── -->
<main class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-left">
      <button class="admin-menu-btn" id="adminMenuBtn" onclick="toggleSidebar()" aria-label="Izvēlne">
        <span></span><span></span><span></span>
      </button>
      <div class="topbar-title"><?= isset($pageTitle) ? $pageTitle : 'Admin' ?></div>
    </div>
    <div class="topbar-right">
      <span class="topbar-user">Administrators ✦</span>
      <a href="/4pt/blazkova/lumina/Lumina/index.php" target="_blank" style="font-size:10px;color:var(--grey);text-decoration:none;letter-spacing:1px;white-space:nowrap;">← Mājaslapa</a>
    </div>
  </div>
  <div class="admin-content">
  <div class="toast-container" id="toastContainer"></div>

<script>
function toggleSidebar() {
  const sb = document.getElementById('adminSidebar');
  const ov = document.getElementById('sidebarOverlay');
  const isOpen = sb.classList.contains('mobile-open');
  sb.classList.toggle('mobile-open', !isOpen);
  ov.classList.toggle('show', !isOpen);
  document.body.style.overflow = isOpen ? '' : 'hidden';
}
function closeSidebar() {
  document.getElementById('adminSidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('show');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidebar(); });
</script>
