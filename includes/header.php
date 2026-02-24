<?php
// Get current page for active nav
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? $pageTitle . ' — LUMINA' : 'LUMINA — Profesionālā Fotogrāfija' ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/4pt/blazkova/lumina/Lumina/css/global.css">
<?php if (isset($extraCss)): ?>
<link rel="stylesheet" href="/4pt/blazkova/lumina/Lumina/css/<?= $extraCss ?>">
<?php endif; ?>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<nav id="nav">
  <a href="/4pt/blazkova/lumina/Lumina/index.php" class="logo">LUMIN<span>A</span></a>
  <ul class="nav-links">
    <li><a href="/4pt/blazkova/lumina/Lumina/portfolio.php" <?= $currentPage === 'portfolio' ? 'class="active"' : '' ?>>Portfelis</a></li>
    <li><a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php" <?= $currentPage === 'pakalpojumi' ? 'class="active"' : '' ?>>Pakalpojumi</a></li>
    <li><a href="/4pt/blazkova/lumina/Lumina/veikals.php" <?= $currentPage === 'veikals' ? 'class="active"' : '' ?>>Veikals</a></li>
    <li><a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" <?= $currentPage === 'rezervacija' ? 'class="active"' : '' ?>>Rezervēt</a></li>
    <li><a href="/4pt/blazkova/lumina/Lumina/galerijas.php" <?= $currentPage === 'galerijas' ? 'class="active"' : '' ?>>Galerijas</a></li>
    <li><a href="/4pt/blazkova/lumina/Lumina/index.php#contact">Kontakti</a></li>
  </ul>
  <div class="nav-right">
    <a href="/4pt/blazkova/lumina/Lumina/veikals.php#cart" class="cart-icon" title="Grozs">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      <?php
      $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
      ?>
      <div class="cart-dot" id="cartCount"><?= $cartCount ?></div>
    </a>
    <?php if (isset($_SESSION['klients_id'])): ?>
      <a href="/4pt/blazkova/lumina/Lumina/profils.php" class="nav-btn">Profils</a>
      <a href="/4pt/blazkova/lumina/Lumina/logout.php" class="nav-btn" style="border-color:rgba(184,151,90,0.2);">Iziet</a>
    <?php else: ?>
      <a href="/4pt/blazkova/lumina/Lumina/login.php" class="nav-btn">Pieslēgties</a>
    <?php endif; ?>
    <a href="/4pt/blazkova/lumina/Lumina/admin/index.php" class="nav-btn admin-btn">Admin ✦</a>
  </div>
</nav>

<!-- Dark page transition overlay -->
<div id="pageTransition" style="position:fixed;inset:0;background:#1C1C1C;z-index:9999;pointer-events:none;opacity:0;transition:opacity .45s cubic-bezier(.4,0,.2,1);"></div>

<div class="toast-container" id="toastContainer"></div>

<script>
// Page transition — dark overlay on nav link clicks, fade in on every page load
(function(){
  const overlay = document.getElementById('pageTransition');
  const navigated = sessionStorage.getItem('lumina_nav');

  // Fade IN on load — if we navigated here via a link, start dark then fade clear
  if (navigated) {
    sessionStorage.removeItem('lumina_nav');
    overlay.style.opacity = '1';
    overlay.style.transition = 'none';
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        overlay.style.transition = 'opacity .5s cubic-bezier(.4,0,.2,1)';
        overlay.style.opacity = '0';
      });
    });
  }

  // Fade OUT before leaving — on ALL internal nav links
  document.addEventListener('click', function(e) {
    const a = e.target.closest('a');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href) return;
    // Skip: external, anchors, admin, javascript, logout, same-page tab switches
    if (href.startsWith('#') || href.startsWith('javascript') || href.startsWith('http') || href.startsWith('//')) return;
    if (href.includes('/admin/') || href.includes('logout')) return;
    if (href.includes('?tab=') || href.includes('&tab=')) return;

    e.preventDefault();
    sessionStorage.setItem('lumina_nav', '1');
    overlay.style.opacity = '1';
    setTimeout(() => { window.location.href = href; }, 440);
  });
})();
</script>
