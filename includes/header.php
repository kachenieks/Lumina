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
    <button onclick="openCart()" class="cart-icon" title="Grozs" style="background:none;border:none;cursor:pointer;padding:0;display:flex;align-items:center;position:relative;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      <?php
      $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
      ?>
      <div class="cart-dot" id="cartCount"><?= $cartCount ?></div>
    </button>
    <?php if (isset($_SESSION['klients_id'])): ?>
      <a href="/4pt/blazkova/lumina/Lumina/profils.php" class="nav-btn">Profils</a>
      <a href="/4pt/blazkova/lumina/Lumina/logout.php" class="nav-btn" style="border-color:rgba(184,151,90,0.2);">Iziet</a>
    <?php else: ?>
      <a href="/4pt/blazkova/lumina/Lumina/login.php" class="nav-btn">Pieslēgties</a>
    <?php endif; ?>
    <a href="/4pt/blazkova/lumina/Lumina/admin/index.php" class="nav-btn admin-btn">Admin ✦</a>
  </div>
</nav>

<!-- Global cart sidebar (works on every page) -->
<div id="globalCartOverlay" onclick="closeGlobalCart()" style="position:fixed;inset:0;background:rgba(0,0,0,0);z-index:1099;display:none;transition:background .3s;"></div>
<div id="globalCartSidebar" style="
  position:fixed;top:0;right:0;width:380px;max-width:95vw;height:100vh;
  background:#fff;z-index:1100;
  transform:translateX(100%);transition:transform .35s cubic-bezier(.4,0,.2,1);
  display:flex;flex-direction:column;box-shadow:-8px 0 40px rgba(0,0,0,.12);
">
  <div style="display:flex;justify-content:space-between;align-items:center;padding:24px 28px;border-bottom:1px solid #f0ece4;">
    <div style="font-family:'Cormorant Garamond',serif;font-size:22px;color:#1C1C1C;letter-spacing:1px;">Grozs</div>
    <button onclick="closeGlobalCart()" style="background:none;border:none;cursor:pointer;font-size:24px;color:#888;line-height:1;padding:4px;">×</button>
  </div>
  <div id="globalCartItems" style="flex:1;overflow-y:auto;padding:0 28px;"></div>
  <div style="padding:20px 28px;border-top:1px solid #f0ece4;">
    <div id="globalCartTotal" style="margin-bottom:14px;"></div>
    <button id="globalCheckoutBtn" onclick="globalCheckout()" style="
      width:100%;padding:14px;background:#1C1C1C;color:#B8975A;
      border:none;cursor:pointer;font-size:11px;letter-spacing:3px;
      text-transform:uppercase;font-family:'Montserrat',sans-serif;font-weight:600;
      transition:.2s;
    " onmouseover="this.style.background='#B8975A';this.style.color='#fff';"
       onmouseout="this.style.background='#1C1C1C';this.style.color='#B8975A';">
      Apmaksāt ar karti →
    </button>
    <div style="text-align:center;margin-top:10px;font-size:10px;color:#aaa;display:flex;align-items:center;justify-content:center;gap:6px;">
      <span style="color:#6772e5;font-weight:700;font-family:Arial;">stripe</span>
      <span>· Drošs maksājums</span>
    </div>
  </div>
</div>
<div class="toast-container" id="toastContainer"></div>
