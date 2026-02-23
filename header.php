<?php
session_start();
require_once 'db.php';

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUMINA — Profesionālā Fotogrāfija</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">

<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--gold:#B8975A;--gold-light:#D4AF70;--gold-dim:rgba(184,151,90,0.10);--gold-border:rgba(184,151,90,0.28);--cream:#FAF8F4;--cream2:#F3EEE6;--cream3:#EAE3D4;--white:#FFFFFF;--ink:#1C1C1C;--ink2:#3D3730;--grey:#7A7267;--grey2:#AAA49C;--grey3:#D8D3CB;--shadow:rgba(90,70,30,0.07);--shadow2:rgba(90,70,30,0.13);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Montserrat',sans-serif;background:var(--cream);color:var(--ink);overflow-x:hidden;cursor:none;}
.cursor{position:fixed;width:8px;height:8px;background:var(--gold);border-radius:50%;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);transition:transform .1s;}
.cursor-ring{position:fixed;width:34px;height:34px;border:1.5px solid rgba(184,151,90,.45);border-radius:50%;pointer-events:none;z-index:9998;transform:translate(-50%,-50%);transition:all .15s ease;}
.cursor-ring.hovering{width:52px;height:52px;border-color:var(--gold);background:var(--gold-dim);}
nav{position:fixed;top:0;left:0;right:0;z-index:100;padding:28px 64px;display:flex;align-items:center;justify-content:space-between;transition:all .4s ease;}
nav.scrolled{padding:17px 64px;background:rgba(250,248,244,.96);backdrop-filter:blur(16px);box-shadow:0 1px 0 var(--grey3);}
.logo{font-family:'Cormorant Garamond',serif;font-size:25px;font-weight:400;letter-spacing:10px;color:var(--ink);text-decoration:none;}
.logo span{color:var(--gold);}
.nav-links{display:flex;gap:38px;list-style:none;}
.nav-links a{font-size:10px;font-weight:500;letter-spacing:2.5px;text-transform:uppercase;text-decoration:none;color:var(--grey);transition:color .3s;position:relative;}
.nav-links a::after{content:'';position:absolute;bottom:-4px;left:0;width:0;height:1px;background:var(--gold);transition:width .3s;}
.nav-links a:hover,.nav-links a.active{color:var(--ink);}
.nav-links a:hover::after,.nav-links a.active::after{width:100%;}
.nav-right{display:flex;align-items:center;gap:18px;}
.nav-btn{padding:10px 22px;border:1px solid var(--gold-border);font-family:'Montserrat',sans-serif;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--gold);background:transparent;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-block;}
.nav-btn:hover{background:var(--gold);color:var(--white);border-color:var(--gold);}
.nav-btn.admin-btn{background:var(--gold-dim);}
.cart-icon{position:relative;cursor:pointer;color:var(--ink2);}
.cart-dot{position:absolute;top:-7px;right:-7px;width:17px;height:17px;background:var(--gold);border-radius:50%;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;color:var(--white);}
.btn-primary{padding:15px 40px;background:var(--ink);color:var(--white);font-family:'Montserrat',sans-serif;font-size:10px;letter-spacing:3px;text-transform:uppercase;font-weight:500;border:none;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-block;}
.btn-primary:hover{background:var(--gold);transform:translateY(-2px);box-shadow:0 8px 24px var(--shadow2);}
.btn-outline{padding:15px 40px;background:transparent;color:var(--ink);font-family:'Montserrat',sans-serif;font-size:10px;letter-spacing:3px;text-transform:uppercase;font-weight:500;border:1px solid var(--grey3);cursor:pointer;transition:all .3s;text-decoration:none;display:inline-block;}
.btn-outline:hover{border-color:var(--gold);color:var(--gold);}
.section-label{font-size:10px;letter-spacing:5px;text-transform:uppercase;color:var(--gold);margin-bottom:16px;}
.section-title{font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,60px);font-weight:300;line-height:1.1;color:var(--ink);}
.reveal{opacity:0;transform:translateY(34px);transition:opacity .8s ease,transform .8s ease;}
.reveal.visible{opacity:1;transform:translateY(0);}
.reveal-delay-1{transition-delay:.1s;}.reveal-delay-2{transition-delay:.2s;}.reveal-delay-3{transition-delay:.3s;}
.form-group{margin-bottom:17px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-label{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--grey);display:block;margin-bottom:9px;}
.form-input,.form-select,.form-textarea{width:100%;padding:13px 15px;background:var(--white);border:1px solid var(--grey3);color:var(--ink);font-family:'Montserrat',sans-serif;font-size:13px;transition:border-color .3s;outline:none;appearance:none;}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--gold);}
.form-textarea{resize:none;height:108px;}
.form-select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M0 0l6 8 6-8z' fill='%23B8975A'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 15px center;}
.toast-container{position:fixed;bottom:34px;right:34px;z-index:9999;}
.toast{padding:14px 21px;background:var(--white);border-left:3px solid var(--gold);margin-top:9px;font-size:13px;color:var(--ink);box-shadow:0 8px 32px var(--shadow2);transform:translateX(110px);opacity:0;transition:all .4s cubic-bezier(.175,.885,.32,1.275);max-width:290px;}
.toast.show{transform:translateX(0);opacity:1;}
.toast.success{border-color:#228B22;}
.toast.error{border-color:#C0392B;}
.modal-overlay{position:fixed;inset:0;background:rgba(28,28,28,.6);z-index:1000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .35s;backdrop-filter:blur(6px);}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{background:var(--white);max-width:820px;width:92%;position:relative;transform:translateY(30px) scale(.98);transition:transform .4s cubic-bezier(.175,.885,.32,1.275);max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.16);}
.modal-overlay.open .modal{transform:translateY(0) scale(1);}
.modal-close{position:absolute;top:17px;right:17px;width:36px;height:36px;background:var(--cream2);border:none;color:var(--grey);cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;transition:all .2s;z-index:2;}
.modal-close:hover{background:var(--gold);color:var(--white);}
.status-badge{padding:4px 11px;font-size:9px;letter-spacing:2px;text-transform:uppercase;font-weight:600;}
.status-badge.piegadats,.status-badge.pabeigts{background:rgba(34,139,34,.08);color:#228B22;}
.status-badge.gaida_maksajumu,.status-badge.gaidoss{background:var(--gold-dim);color:var(--gold);}
.status-badge.apstrade,.status-badge.razosana,.status-badge.apstiprinats,.status-badge.processing{background:rgba(70,130,180,.08);color:steelblue;}
.status-badge.atsaukts{background:rgba(192,57,43,.08);color:#C0392B;}
.page-header{padding:180px 64px 80px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);border-bottom:1px solid var(--grey3);position:relative;overflow:hidden;}
.page-header::before{content:'';position:absolute;top:-100px;right:-100px;width:500px;height:500px;background:radial-gradient(circle,var(--gold-dim) 0%,transparent 70%);pointer-events:none;}
.page-breadcrumb{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--grey2);margin-bottom:16px;}
.page-breadcrumb a{color:var(--grey2);text-decoration:none;transition:color .2s;}
.page-breadcrumb a:hover{color:var(--gold);}
.action-btn{padding:5px 13px;background:transparent;border:1px solid var(--grey3);color:var(--grey);font-family:'Montserrat',sans-serif;font-size:9px;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:all .2s;margin-right:4px;text-decoration:none;display:inline-block;}
.action-btn:hover{border-color:var(--gold);color:var(--gold);}
.action-btn.danger:hover{border-color:#C0392B;color:#C0392B;}
footer{background:var(--ink);padding:76px 64px 38px;}
.footer-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:52px;margin-bottom:52px;}
.footer-logo{font-family:'Cormorant Garamond',serif;font-size:27px;font-weight:300;letter-spacing:8px;margin-bottom:16px;color:var(--white);}
.footer-logo span{color:var(--gold);}
.footer-desc{font-size:13px;line-height:1.8;color:rgba(255,255,255,.38);max-width:255px;}
.footer-social{display:flex;gap:11px;margin-top:20px;}
.social-btn{width:36px;height:36px;border:1px solid rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center;font-size:11px;cursor:pointer;transition:all .3s;text-decoration:none;color:rgba(255,255,255,.38);}
.social-btn:hover{border-color:var(--gold);color:var(--gold);}
.footer-heading{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:var(--gold);margin-bottom:20px;}
.footer-links{list-style:none;}
.footer-links li{margin-bottom:10px;}
.footer-links a{font-size:13px;color:rgba(255,255,255,.38);text-decoration:none;transition:color .2s;}
.footer-links a:hover{color:var(--white);}
.footer-contact{font-size:13px;color:rgba(255,255,255,.38);line-height:2;}
.footer-bottom{padding-top:34px;border-top:1px solid rgba(255,255,255,.06);display:flex;justify-content:space-between;align-items:center;}
.footer-copy{font-size:11px;color:rgba(255,255,255,.18);letter-spacing:1px;}
@media(max-width:900px){nav{padding:17px 22px;}nav.scrolled{padding:13px 22px;}.nav-links{display:none;}.footer-grid{grid-template-columns:1fr 1fr;gap:32px;}.page-header{padding:120px 22px 50px;}}
</style>
</head>
<body>

<div class="cursor" id="cursor"></div>
<div class="cursor-ring" id="cursorRing"></div>

<nav id="nav">
  <a href="index.php" class="logo">LUMIN<span>A</span></a>
  <ul class="nav-links">
    <li><a href="index.php#portfolio" class="<?= $current_page == 'index' ? '' : '' ?>">Portfelis</a></li>
    <li><a href="pakalpojumi.php" class="<?= $current_page == 'pakalpojumi' ? 'active' : '' ?>">Pakalpojumi</a></li>
    <li><a href="veikals.php" class="<?= $current_page == 'veikals' ? 'active' : '' ?>">Veikals</a></li>
    <li><a href="rezervet.php" class="<?= $current_page == 'rezervet' ? 'active' : '' ?>">Rezervēt</a></li>
    <li><a href="par_mums.php" class="<?= $current_page == 'par_mums' ? 'active' : '' ?>">Par mums</a></li>
    <li><a href="kontakti.php" class="<?= $current_page == 'kontakti' ? 'active' : '' ?>">Kontakti</a></li>
  </ul>
  <div class="nav-right">
    <div class="cart-icon" onclick="openModal('cartModal')" title="Grozs">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      <div class="cart-dot" id="cartCount"><?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?></div>
    </div>
    <?php if(isset($_SESSION['klients_id'])): ?>
      <a href="klients.php" class="nav-btn">Profils</a>
      <a href="logout.php" class="nav-btn" style="opacity:.7;">Iziet</a>
    <?php else: ?>
      <button class="nav-btn" onclick="openModal('loginModal')">Pieslēgties</button>
    <?php endif; ?>
    <a href="admin.php" class="nav-btn admin-btn">Admin ✦</a>
  </div>
</nav>

<div class="toast-container" id="toastContainer"></div>

<!-- LOGIN MODAL -->
<div class="modal-overlay" id="loginModal">
  <div class="modal" style="max-width:440px;padding:42px;">
    <button class="modal-close" onclick="closeModal('loginModal')">×</button>
    <div class="section-label">Klienta zona</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:300;color:var(--ink);margin-bottom:26px;">Pieslēgties</h2>
    <form method="POST" action="login.php">
      <div class="form-group"><label class="form-label">E-pasts</label><input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required></div>
      <div class="form-group"><label class="form-label">Parole</label><input type="password" name="parole" class="form-input" placeholder="••••••••" required></div>
      <button type="submit" class="btn-primary" style="width:100%">Pieslēgties →</button>
    </form>
    <div style="text-align:center;margin-top:18px;font-size:12px;color:var(--grey)">Nav konta? <button onclick="closeModal('loginModal');openModal('registerModal')" style="background:none;border:none;color:var(--gold);cursor:pointer;font-family:'Montserrat',sans-serif;font-size:12px;">Reģistrēties</button></div>
    <div style="margin-top:14px;padding:12px;background:var(--cream2);font-size:11px;color:var(--grey);">
      <strong>Demo:</strong> anna@epasts.lv / parole123
    </div>
  </div>
</div>

<!-- REGISTER MODAL -->
<div class="modal-overlay" id="registerModal">
  <div class="modal" style="max-width:440px;padding:42px;">
    <button class="modal-close" onclick="closeModal('registerModal')">×</button>
    <div class="section-label">Jauns konts</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:300;color:var(--ink);margin-bottom:26px;">Reģistrēties</h2>
    <form method="POST" action="register.php">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Vārds</label><input type="text" name="vards" class="form-input" placeholder="Vārds" required></div>
        <div class="form-group"><label class="form-label">Uzvārds</label><input type="text" name="uzvards" class="form-input" placeholder="Uzvārds" required></div>
      </div>
      <div class="form-group"><label class="form-label">E-pasts</label><input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required></div>
      <div class="form-group"><label class="form-label">Tālrunis</label><input type="tel" name="talrunis" class="form-input" placeholder="+371 XX XXX XXX"></div>
      <div class="form-group"><label class="form-label">Parole</label><input type="password" name="parole" class="form-input" placeholder="Min. 8 rakstzīmes" required></div>
      <button type="submit" class="btn-primary" style="width:100%">Izveidot kontu →</button>
    </form>
  </div>
</div>

<!-- CART MODAL -->
<div class="modal-overlay" id="cartModal">
  <div class="modal" style="max-width:490px;padding:42px;">
    <button class="modal-close" onclick="closeModal('cartModal')">×</button>
    <div class="section-label">Iepirkumu</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:300;color:var(--ink);margin-bottom:26px;">Grozs</h2>
    <div id="cartItems">
      <?php if(isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
        <?php $total = 0; foreach($_SESSION['cart'] as $i => $item): $total += $item['cena']; ?>
        <div class="order-row" style="display:grid;grid-template-columns:62px 1fr auto auto;gap:16px;align-items:center;padding:13px 0;border-bottom:1px solid var(--grey3);">
          <img style="width:62px;height:48px;object-fit:cover;" src="<?= htmlspecialchars($item['attels']) ?>" alt="">
          <div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:16px;color:var(--ink);margin-bottom:3px;"><?= htmlspecialchars($item['nosaukums']) ?></div>
            <div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars($item['izmers'] ?? '') ?></div>
          </div>
          <div style="font-size:15px;color:var(--gold);font-weight:500;">€<?= number_format($item['cena'],2) ?></div>
          <a href="remove_cart.php?i=<?= $i ?>" style="background:none;border:none;color:var(--grey2);cursor:pointer;font-size:15px;text-decoration:none;">✕</a>
        </div>
        <?php endforeach; ?>
        <div style="border-top:1px solid var(--grey3);margin:20px 0;padding-top:20px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:9px;font-size:13px;color:var(--grey)"><span>Starpsumma</span><span>€<?= number_format($total,2) ?></span></div>
          <div style="display:flex;justify-content:space-between;margin-bottom:9px;font-size:13px;color:var(--grey)"><span>Piegāde</span><span>€8.00</span></div>
          <div style="display:flex;justify-content:space-between;font-family:'Cormorant Garamond',serif;font-size:27px;font-weight:300;color:var(--gold)"><span>Kopā</span><span>€<?= number_format($total+8,2) ?></span></div>
        </div>
        <a href="checkout.php" class="btn-primary" style="width:100%;display:block;text-align:center;">Noformēt Pasūtījumu →</a>
      <?php else: ?>
        <div style="text-align:center;padding:40px 0;color:var(--grey);">
          <div style="font-size:36px;margin-bottom:13px;">🛒</div>
          <div style="font-family:'Cormorant Garamond',serif;font-size:21px;color:var(--ink);margin-bottom:8px;">Grozs ir tukšs</div>
          <div style="font-size:12px;">Apskatiet mūsu <a href="veikals.php" style="color:var(--gold);">veikalu</a></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const cursor=document.getElementById('cursor'),cursorRing=document.getElementById('cursorRing');
let mx=0,my=0,rx=0,ry=0;
document.addEventListener('mousemove',e=>{mx=e.clientX;my=e.clientY;cursor.style.left=mx+'px';cursor.style.top=my+'px';});
function animRing(){rx+=(mx-rx)*.12;ry+=(my-ry)*.12;cursorRing.style.left=rx+'px';cursorRing.style.top=ry+'px';requestAnimationFrame(animRing);}animRing();
document.querySelectorAll('a,button,.portfolio-item,.product-card,.service-card,.cart-icon').forEach(el=>{el.addEventListener('mouseenter',()=>cursorRing.classList.add('hovering'));el.addEventListener('mouseleave',()=>cursorRing.classList.remove('hovering'));});
window.addEventListener('scroll',()=>document.getElementById('nav').classList.toggle('scrolled',window.scrollY>60));
const ro=new IntersectionObserver(e=>e.forEach(x=>{if(x.isIntersecting)x.target.classList.add('visible');}),{threshold:.1});
document.querySelectorAll('.reveal').forEach(el=>ro.observe(el));
function openModal(id){document.getElementById(id).classList.add('open');document.body.style.overflow='hidden';}
function closeModal(id){document.getElementById(id).classList.remove('open');document.body.style.overflow='';}
function closeAllModals(){document.querySelectorAll('.modal-overlay').forEach(m=>m.classList.remove('open'));document.body.style.overflow='';}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)closeAllModals();}));
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeAllModals();});
function showToast(msg,type=''){const t=document.createElement('div');t.className='toast '+(type||'');t.textContent=msg;document.getElementById('toastContainer').appendChild(t);setTimeout(()=>t.classList.add('show'),50);setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),400);},4000);}
<?php if(isset($_SESSION['toast'])): ?>
showToast('<?= addslashes($_SESSION['toast']['msg']) ?>','<?= $_SESSION['toast']['type'] ?>');
<?php unset($_SESSION['toast']); endif; ?>
</script>