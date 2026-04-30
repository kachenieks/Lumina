<?php
$adminPage = basename($_SERVER['PHP_SELF'], '.php');

// SVG icons — no emoji
function adminIcon(string $name): string {
  $icons = [
    'dashboard' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'calendar'  => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    'clients'   => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'shop'      => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
    'photo'     => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
    'portfolio' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
    'galleries' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><polyline points="12 11 12 17"/><polyline points="9 14 12 11 15 14"/></svg>',
  ];
  return $icons[$name] ?? '';
}
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

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-logo">
    <div class="sidebar-logo-text">LUMIN<span>A</span></div>
    <div class="sidebar-logo-sub">Admin panelis</div>
  </div>
  <nav class="sidebar-nav">
    <a href="/4pt/blazkova/lumina/Lumina/admin/index.php"        class="sidebar-item <?= $adminPage==='index'       ?'active':'' ?>"><?= adminIcon('dashboard') ?> Pārskats</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php" class="sidebar-item <?= $adminPage==='rezervacijas'?'active':'' ?>"><?= adminIcon('calendar')  ?> Rezervācijas</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/klienti.php"      class="sidebar-item <?= $adminPage==='klienti'     ?'active':'' ?>"><?= adminIcon('clients')   ?> Klienti</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/preces.php"       class="sidebar-item <?= $adminPage==='preces'      ?'active':'' ?>"><?= adminIcon('shop')      ?> Veikals</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/pasutijumi.php"   class="sidebar-item <?= $adminPage==='pasutijumi'  ?'active':'' ?>"><?= adminIcon('photo')     ?> Foto pasūtījumi</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/portfolio.php"    class="sidebar-item <?= $adminPage==='portfolio'   ?'active':'' ?>"><?= adminIcon('portfolio') ?> Portfolio</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php"    class="sidebar-item <?= $adminPage==='galerijas'   ?'active':'' ?>"><?= adminIcon('galleries') ?> Galerijas</a>
  </nav>
  <div class="sidebar-footer">
    <a href="/4pt/blazkova/lumina/Lumina/index.php" target="_blank">Uz mājaslapu</a>
    <a href="/4pt/blazkova/lumina/Lumina/admin/logout.php" style="margin-top:10px;display:block;">Iziet</a>
  </div>
</aside>

<main class="admin-main">
  <div class="admin-topbar">
    <div class="topbar-left">
      <button class="admin-menu-btn" id="adminMenuBtn" onclick="toggleSidebar()" aria-label="Izvēlne">
        <span></span><span></span><span></span>
      </button>
      <div class="topbar-title"><?= isset($pageTitle) ? $pageTitle : 'Admin' ?></div>
    </div>
    <div class="topbar-right">
      <span class="topbar-user">Administrators</span>
      <a href="/4pt/blazkova/lumina/Lumina/index.php" target="_blank" style="font-size:10px;color:var(--grey);text-decoration:none;letter-spacing:1px;white-space:nowrap;">Uz mājaslapu</a>
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
