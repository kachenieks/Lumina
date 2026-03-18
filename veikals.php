<?php
ob_start(); // Buffer output — prevents PHP errors/warnings from breaking HTML
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
// Disable strict mysqli exceptions so @ suppressor works properly
mysqli_report(MYSQLI_REPORT_OFF);
$pageTitle = 'Veikals';
$extraCss = 'veikals.css';

// Handle cart operations
if (isset($_GET['action'])) {
  header('Content-Type: application/json');
  $action = $_GET['action'];
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  if ($action === 'add') {
    $id = (int)($_GET['id'] ?? 0);
    $prece = mysqli_query($savienojums, "SELECT * FROM preces WHERE id = $id AND aktivs = 1");
    if ($row = mysqli_fetch_assoc($prece)) {
      if (!isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id] = ['qty' => 0, 'name' => $row['nosaukums'], 'cena' => $row['cena']];
      }
      $_SESSION['cart'][$id]['qty']++;
    }
    echo json_encode(['count' => array_sum(array_column($_SESSION['cart'], 'qty'))]);
    exit;
  }
  if ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    unset($_SESSION['cart'][$id]);
    echo json_encode(['count' => array_sum(array_column($_SESSION['cart'], 'qty'))]);
    exit;
  }
  if ($action === 'count') {
    echo json_encode(['count' => array_sum(array_column($_SESSION['cart'], 'qty'))]);
    exit;
  }
}

// ── Pievienot foto pasūtījumu grozam (AJAX POST) ──────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_photo_to_cart'])) {
  header('Content-Type: application/json');
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

  $produktsName = escape($savienojums, $_POST['produkts'] ?? '');
  $cena         = (float)($_POST['cena'] ?? 0);
  $notes        = strip_tags($_POST['notes'] ?? '');
  $guestEmail   = filter_var($_POST['guest_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';

  $uploadDir = __DIR__ . '/uploads/pasutijumi/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

  $allFotoUrls = [];

  // Handle multiple uploaded files (fotos[])
  if (!empty($_FILES['fotos']['name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
      if (!$tmp || !is_uploaded_file($tmp)) continue;
      $origName = $_FILES['fotos']['name'][$k] ?? 'foto.jpg';
      $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
      $fname = 'f_' . uniqid() . '.' . $ext;
      if (move_uploaded_file($tmp, $uploadDir . $fname)) {
        $allFotoUrls[] = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $fname;
      }
    }
  }

  // Handle gallery URLs
  if (!empty($_POST['gallery_urls'])) {
    $gals = json_decode($_POST['gallery_urls'], true) ?: [];
    foreach ($gals as $u) {
      if (filter_var($u, FILTER_VALIDATE_URL)) $allFotoUrls[] = $u;
    }
  }

  // Single legacy foto field
  if (empty($allFotoUrls) && !empty($_FILES['foto']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
      $fname = 'f_' . uniqid() . '.' . $ext;
      if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fname)) {
        $allFotoUrls[] = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $fname;
      }
    }
  }

  if (empty($allFotoUrls)) {
    echo json_encode(['error' => 'Nav pievienotu fotogrāfiju']); exit;
  }

  $cartKey = 'foto_' . uniqid();
  $_SESSION['cart'][$cartKey] = [
    'qty'        => 1,
    'name'       => strip_tags($produktsName),
    'cena'       => $cena,
    'is_foto'    => true,
    'foto_url'   => $allFotoUrls[0],
    'foto_urls'  => json_encode($allFotoUrls),
    'notes'      => $notes,
    'guest_email'=> $guestEmail,
  ];

  echo json_encode([
    'ok'    => true,
    'count' => array_sum(array_column($_SESSION['cart'], 'qty')),
  ]);
  exit;
}

// Handle upload/order
$uploadSuccess = '';
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_order'])) {
  $produkts = escape($savienojums, $_POST['produkts'] ?? '');
  $notes    = escape($savienojums, $_POST['notes'] ?? '');
  $cropData = escape($savienojums, $_POST['crop_data'] ?? '');
  $klienta_id = isset($_SESSION['klients_id']) ? (int)$_SESSION['klients_id'] : 0;
  $galUrl   = escape($savienojums, $_POST['gallery_url'] ?? '');
  // Guest email — use session or submitted
  $guestEmail = '';
  if (!isset($_SESSION['klients_id'])) {
    $guestEmail = filter_var($_POST['guest_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '';
  }

  $fotoInfo = '';

  if (!empty($_FILES['foto']['name'])) {
    $uploadDir = __DIR__ . '/uploads/pasutijumi/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
      $filename = uniqid() . '.' . $ext;
      if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename)) {
        $fotoInfo = $filename;
        @mysqli_query($savienojums, "INSERT INTO pasutijumi (klienta_id,produkts,foto_fails,crop_data,papildu_info,statuss,izveidots) VALUES ($klienta_id,'$produkts','$filename','$cropData','$notes','jauns',NOW())");
        $uploadSuccess = 'Pasūtījums saņemts!';
      } else {
        $uploadError = 'Augšupielādes kļūda. Pārbaudiet mapes tiesības (chmod 755 uploads/pasutijumi/).';
      }
    } else { $uploadError = 'Atļautie formāti: JPG, PNG, WEBP.'; }
  } elseif (!empty($galUrl)) {
    $fotoInfo = $galUrl;
    @mysqli_query($savienojums, "INSERT INTO pasutijumi (klienta_id,produkts,foto_fails,crop_data,papildu_info,statuss,izveidots) VALUES ($klienta_id,'$produkts','$galUrl','$cropData','$notes','jauns',NOW())");
    $uploadSuccess = 'Pasūtījums saņemts!';
  } else { $uploadError = 'Lūdzu pievienojiet fotogrāfiju.'; }

  // E-pasta paziņojumi — tikai ja PHPMailer ir augšupielādēts
  if ($uploadSuccess) {
    $klientaVards = $_SESSION['klients_vards'] ?? 'Viesis';
    $klientaEmail = $_SESSION['klients_epasts'] ?? $guestEmail;
    $mailerPath = __DIR__ . '/includes/mailer.php';
    $phpmailerOk = file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php');
    if ($phpmailerOk && file_exists($mailerPath)) {
      try {
        require_once $mailerPath;
        if (function_exists('mailFotoPasutijumsAdmin'))
          mailFotoPasutijumsAdmin($klientaVards, $klientaEmail, $produkts, $notes, $fotoInfo);
        if ($klientaEmail && function_exists('mailFotoPasutijumsKlients'))
          mailFotoPasutijumsKlients($klientaEmail, $klientaVards, $produkts, $notes);
      } catch (\Throwable $e) { error_log('Mail error: ' . $e->getMessage()); }
    }
  }
}

// Ensure new columns exist (safe on any MySQL version)
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS tips         varchar(50)  DEFAULT 'druka'");
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS foto_skaits  int(3)       DEFAULT 1");
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS izmers       varchar(100) DEFAULT ''");
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS orientacija  varchar(20)  DEFAULT 'portrait'");

// Products
$result = mysqli_query($savienojums, "SELECT * FROM preces WHERE aktivs = 1 ORDER BY kategorija ASC, cena ASC");
$products = [];
while ($row = mysqli_fetch_assoc($result)) $products[] = $row;

// Client galleries
$clientPhotos = [];
if (isset($_SESSION['klients_id'])) {
  $kid = (int)$_SESSION['klients_id'];
  $fRes = mysqli_query($savienojums, "SELECT gf.*, g.nosaukums as gal_nosaukums FROM galeriju_foto gf JOIN galerijas g ON g.id=gf.galerijas_id WHERE g.klienta_id=$kid ORDER BY gf.id DESC LIMIT 60");
  if ($fRes) while ($f = mysqli_fetch_assoc($fRes)) $clientPhotos[] = $f;
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
/* ─── EDITOR STYLES ─── */
:root{--border:#e8e3da;}
.editor-topbar{display:flex;justify-content:space-between;align-items:center;padding:14px clamp(16px,3vw,28px);border-bottom:1px solid var(--border);background:var(--white);position:sticky;top:0;z-index:10;gap:12px;flex-wrap:wrap;}
.editor-layout{display:grid;grid-template-columns:320px 1fr;min-height:calc(100vh - 60px);}
.editor-sidebar{padding:24px 20px;border-right:1px solid var(--border);overflow-y:auto;background:#faf9f7;max-height:calc(100vh - 60px);position:sticky;top:60px;}
.editor-step{margin-bottom:26px;padding-bottom:26px;border-bottom:1px solid var(--border);}
.editor-step:last-child{border-bottom:none;}
.editor-step-num{font-size:10px;color:var(--gold);letter-spacing:3px;text-transform:uppercase;margin-bottom:5px;}
.editor-step-title{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--ink);margin-bottom:14px;}
.editor-dropzone{border:2px dashed var(--border);padding:24px 14px;text-align:center;cursor:pointer;transition:.2s;background:var(--white);}
.editor-dropzone:hover,.editor-dropzone.drag-over{border-color:var(--gold);background:rgba(184,151,90,.04);}
.dropzone-icon{font-size:30px;margin-bottom:8px;}
.dropzone-text{font-size:13px;color:var(--ink);margin-bottom:4px;}
.dropzone-sub{font-size:11px;color:var(--grey);}
.editor-tabs{display:flex;gap:0;margin-bottom:14px;border:1px solid var(--border);overflow:hidden;}
.editor-tab{flex:1;padding:9px;font-size:11px;letter-spacing:.5px;border:none;background:transparent;cursor:pointer;color:var(--grey);transition:.2s;}
.editor-tab.active{background:var(--gold);color:var(--white);}
.product-options{display:flex;flex-direction:column;gap:6px;}
.product-option{display:flex;border:1.5px solid var(--border);padding:11px 13px;cursor:pointer;transition:.2s;position:relative;}
.product-option:hover{border-color:var(--gold);}
.product-option.selected{border-color:var(--gold);background:rgba(184,151,90,.06);}
.product-option.selected::before{content:'✓';position:absolute;top:10px;right:12px;font-size:11px;color:var(--gold);font-weight:700;}
.product-option input{display:none;}
.product-option-name{font-size:13px;font-weight:500;color:var(--ink);}
.product-option-price{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--gold);line-height:1;}
.product-option-desc{font-size:11px;color:var(--grey);margin-top:2px;}
/* Wall preview */
.editor-preview-area{display:flex;flex-direction:column;background:#ece9e4;}
.editor-preview-label{text-align:center;font-size:10px;letter-spacing:3px;color:rgba(0,0,0,.3);text-transform:uppercase;padding:14px;flex-shrink:0;}
.wall-scene{flex:1;display:flex;align-items:center;justify-content:center;min-height:420px;padding:40px;position:relative;}
.wall-bg{position:absolute;inset:0;background:linear-gradient(160deg,#e8e3dc 0%,#d5cfc7 100%);}
.wall-frame-wrapper{position:relative;z-index:2;}
.wall-frame{background:var(--white);box-shadow:0 24px 64px rgba(0,0,0,.25),0 4px 12px rgba(0,0,0,.12);border:14px solid #f5f3ef;position:relative;width:280px;}
.frame-canvas-area{width:100%;aspect-ratio:3/4;overflow:hidden;position:relative;background:#111;cursor:grab;}
.frame-canvas-area:active{cursor:grabbing;}
.photo-container{position:absolute;inset:0;overflow:hidden;}
#photoCanvas{position:absolute;width:100%;height:100%;object-fit:cover;top:0;left:0;transform-origin:center center;}
.photo-placeholder{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#222;}
.frame-shadow{position:absolute;bottom:-18px;left:10%;right:10%;height:16px;background:rgba(0,0,0,.15);filter:blur(10px);border-radius:50%;}
/* Dark control panel */
.editor-controls{display:flex;flex-direction:column;gap:0;border-top:2px solid var(--gold,#B8975A);}
.ctrl-header{padding:10px 20px 8px;background:#1a1a1a;}
.ctrl-header span{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.35);}
.ctrl-body{background:#161616;padding:16px 20px;display:flex;flex-direction:column;gap:14px;}
.ctrl-row{display:grid;grid-template-columns:30px 1fr 48px;gap:10px;align-items:center;}
.ctrl-icon{color:rgba(255,255,255,.45);display:flex;align-items:center;justify-content:center;}
.ctrl-val{font-size:11px;font-family:monospace;text-align:right;white-space:nowrap;color:var(--gold,#B8975A);}
.ctrl-val.muted{color:rgba(255,255,255,.3);}
/* Improved dark slider */
.dark-slider{
  -webkit-appearance:none;appearance:none;
  width:100%;height:5px;border-radius:3px;
  background:rgba(255,255,255,.1);
  outline:none;cursor:pointer;
}
.dark-slider::-webkit-slider-thumb{
  -webkit-appearance:none;
  width:20px;height:20px;border-radius:50%;
  background:var(--gold,#B8975A);
  cursor:pointer;
  border:3px solid #161616;
  box-shadow:0 0 0 1.5px var(--gold,#B8975A),0 2px 8px rgba(0,0,0,.4);
  transition:transform .15s;
}
.dark-slider::-webkit-slider-thumb:active{transform:scale(1.2);}
.dark-slider::-moz-range-thumb{
  width:20px;height:20px;border-radius:50%;
  background:var(--gold,#B8975A);
  cursor:pointer;border:3px solid #161616;
}
.dark-slider::-webkit-slider-runnable-track{border-radius:3px;}
.ctrl-actions{display:flex;gap:1px;background:#111;}
.ctrl-btn{flex:1;padding:11px;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;border:none;cursor:pointer;transition:.2s;font-family:'Montserrat',sans-serif;}
.ctrl-btn-reset{background:#222;color:rgba(255,255,255,.4);}
.ctrl-btn-reset:hover{background:#333;color:#fff;}
.ctrl-btn-fit{background:rgba(184,151,90,.12);color:var(--gold,#B8975A);}
.ctrl-btn-fit:hover{background:var(--gold,#B8975A);color:#fff;}
/* Gallery thumbs */
.gallery-thumb{position:relative;aspect-ratio:1;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:.2s;}
.gallery-thumb:hover{border-color:var(--gold);}
.gallery-thumb img{width:100%;height:100%;object-fit:cover;}
.gallery-thumb-overlay{position:absolute;inset:0;background:rgba(184,151,90,.7);color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;}
.gallery-thumb:hover .gallery-thumb-overlay{opacity:1;}
.gallery-picker-thumb{position:relative;aspect-ratio:1;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:.2s;}
.gallery-picker-thumb:hover{border-color:var(--gold);}
.gallery-picker-thumb img{width:100%;height:100%;object-fit:cover;}
.gallery-picker-overlay{position:absolute;inset:0;background:rgba(184,151,90,.75);color:#fff;font-size:13px;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;}
.gallery-picker-thumb:hover .gallery-picker-overlay{opacity:1;}
@media(max-width:900px){
  .editor-layout{grid-template-columns:1fr;grid-template-rows:auto 1fr;}
  .editor-sidebar{max-height:none;position:static;border-right:none;border-bottom:1px solid var(--border);}
  .wall-scene{min-height:300px;padding:24px;}
  .wall-frame{width:180px;}
}
@media(max-width:480px){
  .editor-topbar{flex-direction:column;align-items:flex-start;gap:10px;}
  .wall-frame{width:150px;}
}
</style>

<div class="page-header">
  <div class="section-label">Veikals</div>
  <h1>Mākslas darbi <em>jūsu telpai</em></h1>
  <p>Iegādājieties gatavus mākslas darbus vai pasūtiet druku no savām fotogrāfijām.</p>
</div>

<section class="veikals-section">
  <div class="shop-grid">
    <?php foreach ($products as $i => $p): ?>
    <?php $imgSrc = !empty($p['attels_url']) ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/'.$p['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=500&q=80'; ?>
    <?php $fotoN = (int)($p['foto_skaits'] ?? 1); ?>
    <div class="product-card reveal reveal-delay-<?= ($i%3)+1 ?>" onclick="openPhotoEditor(<?= $p['id'] ?>)">
      <div class="product-img">
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['nosaukums']) ?>">
        <?php if ($p['bestseller']): ?><div class="product-tag">Bestseller</div><?php endif; ?>
        <?php if ($fotoN > 1): ?>
        <div class="product-tag" style="top:auto;bottom:10px;left:10px;background:rgba(28,28,28,.75);"><?= $fotoN ?> foto</div>
        <?php endif; ?>
        <div class="product-overlay">
          <button class="add-cart-btn" onclick="event.stopPropagation();openPhotoEditor(<?= $p['id'] ?>)">
            <?= $fotoN > 1 ? 'Pievienot ' . $fotoN . ' foto →' : 'Pievienot foto →' ?>
          </button>
        </div>
      </div>
      <div class="product-name"><?= htmlspecialchars($p['nosaukums']) ?></div>
      <div class="product-price">€<?= number_format($p['cena'],0) ?></div>
      <div class="product-sub"><?= htmlspecialchars($p['kategorija']) ?><?= !empty($p['izmers']) ? ' · ' . htmlspecialchars($p['izmers']) : '' ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Upload CTA -->
<section class="upload-section">
  <div class="upload-cta-content reveal">
    <div class="section-label" style="display:flex;justify-content:center;">Personalizēts pasūtījums</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,5vw,52px);font-weight:300;color:var(--ink);margin:14px 0;">
      Redziet, kā izskatīsies<br><em style="font-style:italic;color:var(--gold)">jūsu foto uz sienas</em>
    </h2>
    <p style="font-size:14px;color:var(--grey);max-width:500px;margin:0 auto 32px;line-height:1.8;">
      Izvēlieties produktu, augšupielādējiet fotogrāfijas un skatiet priekšskatījumu reāllaikā.
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
      <button class="btn-primary" onclick="openPhotoEditor(<?= !empty($products) ? $products[0]['id'] : 0 ?>)">✦ Pasūtīt ar savu foto</button>
      <?php if (!isset($_SESSION['klients_id'])): ?>
      <a href="/4pt/blazkova/lumina/Lumina/login.php" class="btn-outline">Pieslēgties — skatīt savas galerijas →</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Cart Sidebar -->
<div id="cartSidebar" class="cart-sidebar">
  <div class="cart-header"><h3>Grozs</h3><button onclick="document.getElementById('cartSidebar').classList.remove('open')">×</button></div>
  <div class="cart-items" id="cartItems"></div>
  <div class="cart-footer">
    <div class="cart-total" id="cartTotal"></div>
    <button class="btn-primary" style="width:100%;margin-top:14px;" onclick="checkout()" id="checkoutBtn">
      Apmaksāt ar karti →
    </button>
    <div style="text-align:center;margin-top:10px;font-size:11px;color:var(--grey2);display:flex;align-items:center;justify-content:center;gap:6px;">
      <svg width="38" height="16" viewBox="0 0 38 16" fill="none" xmlns="http://www.w3.org/2000/svg"><text x="0" y="13" font-family="Arial" font-size="13" fill="#6772e5" font-weight="bold">stripe</text></svg>
      <span>Drošs maksājums</span>
    </div>
  </div>
</div>

<!-- Product Modal -->
<div class="modal-overlay" id="productModal">
  <div class="modal" style="max-width:660px;padding:0;">
    <button class="modal-close" onclick="closeModal('productModal')">×</button>
    <img id="prodModalImg" style="width:100%;height:370px;object-fit:cover;display:block;" src="" alt="">
    <div style="padding:34px 38px;">
      <div id="prodModalCat" style="font-size:10px;color:var(--gold);letter-spacing:3.5px;text-transform:uppercase;margin-bottom:12px;"></div>
      <div id="prodModalTitle" style="font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:400;color:var(--ink);margin-bottom:13px;"></div>
      <div id="prodModalDesc" style="font-size:14px;line-height:1.8;color:var(--grey);margin-bottom:22px;"></div>
      <div id="prodModalPrice" style="font-family:'Cormorant Garamond',serif;font-size:32px;color:var(--gold);margin-bottom:20px;"></div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        <button id="prodModalBtn" class="btn-primary">Pasūtīt ar manu foto →</button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════ -->
<!-- ══════════════════════════════════════════════════ -->
<!-- FOTO EDITORS — fullscreen modal -->
<!-- ══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="photoEditorModal" style="align-items:flex-start;padding:0;overflow:hidden;">
  <div style="background:var(--white);width:100%;height:100vh;display:flex;flex-direction:column;">

    <!-- Topbar -->
    <div class="editor-topbar">
      <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);">✦ Foto editors</div>
      <div style="display:flex;gap:8px;align-items:center;">
        <!-- Photo counter badge -->
        <div id="editorCountBadge" style="font-size:11px;padding:6px 14px;background:var(--cream2);color:var(--grey);display:none;">
          <span id="editorCountNow">0</span> / <span id="editorCountNeed">1</span> foto
        </div>
        <button class="btn-outline" style="padding:8px 16px;font-size:11px;" onclick="closeModal('photoEditorModal')">← Atpakaļ</button>
        <button class="btn-primary" style="padding:8px 20px;font-size:11px;" id="orderBtn" onclick="addPhotoToCart()" disabled>Pievienot grozam →</button>
      </div>
    </div>

    <!-- Main editor area -->
    <div class="editor-layout" style="flex:1;overflow:hidden;">

      <!-- LEFT: Controls sidebar -->
      <div class="editor-sidebar" style="overflow-y:auto;height:100%;">

        <!-- Step 1: Upload photos -->
        <div class="editor-step">
          <div class="editor-step-num">01</div>
          <div class="editor-step-title">Pievienojiet foto</div>
          <div id="editorUploadStatus" style="font-size:12px;color:var(--grey);margin-bottom:10px;">
            Augšupielādējiet nepieciešamās fotogrāfijas
          </div>

          <!-- Upload tabs -->
          <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
          <div class="editor-tabs">
            <button class="editor-tab active" onclick="switchTab('upload')">Augšupielādēt</button>
            <button class="editor-tab" onclick="switchTab('gallery')">Manas galerijas (<?= count($clientPhotos) ?>)</button>
          </div>
          <?php endif; ?>

          <div id="tabUpload">
            <div class="editor-dropzone" id="editorDropzone" onclick="document.getElementById('editorFileInput').click()">
              <input type="file" id="editorFileInput" accept="image/*" multiple style="display:none;" onchange="handleFileInput(this.files)">
              <div class="dropzone-icon" style="font-size:28px;margin-bottom:8px;">📷</div>
              <div class="dropzone-text" style="font-size:13px;">Velciet foto šeit vai klikšķiniet</div>
              <div class="dropzone-sub" style="font-size:11px;color:var(--grey);margin-top:4px;">Var izvēlēties vairākas bildes uzreiz</div>
            </div>
          </div>

          <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
          <div id="tabGallery" style="display:none;">
            <div style="font-size:11px;color:var(--grey);margin-bottom:8px;">Klikšķiniet lai pievienotu/noņemtu</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:5px;max-height:240px;overflow-y:auto;">
              <?php foreach ($clientPhotos as $cp):
                $ps = filter_var($cp['attels_url']??'', FILTER_VALIDATE_URL)
                  ? $cp['attels_url']
                  : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/'.($cp['attels_url']??'');
              ?>
              <div class="gallery-thumb gal-pick" onclick="toggleGalleryPick(this,'<?= htmlspecialchars($ps) ?>')"
                   data-url="<?= htmlspecialchars($ps) ?>"
                   style="position:relative;aspect-ratio:1;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:.2s;">
                <img src="<?= htmlspecialchars($ps) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                <div class="gal-pick-check" style="display:none;position:absolute;inset:0;background:rgba(184,151,90,.6);color:#fff;font-size:20px;align-items:center;justify-content:center;">✓</div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Uploaded photo thumbs -->
          <div id="uploadedThumbs" style="display:grid;grid-template-columns:repeat(4,1fr);gap:5px;margin-top:10px;"></div>
        </div>

        <!-- Step 2: Edit current photo (shown when photo loaded) -->
        <div class="editor-step" id="editStep" style="display:none;">
          <div class="editor-step-num">02</div>
          <div class="editor-step-title">Rediģēt kadru</div>
          <div style="font-size:11px;color:var(--grey);margin-bottom:12px;">
            Velciet foto labajā pusē · Izmantojiet slīdņus kadrēšanai
          </div>
          <!-- Sliders inline for space -->
          <div style="display:flex;flex-direction:column;gap:10px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v6M8 11h6"/></svg>
              <input type="range" id="zoomSlider" min="10" max="300" value="100" class="dark-slider" style="flex:1;" oninput="applyTransform()">
              <span id="zoomVal" style="font-size:10px;color:var(--gold);width:36px;text-align:right;font-family:monospace;">100%</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--grey)" stroke-width="2"><path d="M5 12h14M15 8l4 4-4 4M9 8l-4 4 4 4"/></svg>
              <input type="range" id="posX" min="-200" max="200" value="0" class="dark-slider" style="flex:1;" oninput="applyTransform()">
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--grey)" stroke-width="2"><path d="M12 5v14M8 15l4 4 4-4M8 9l4-4 4 4"/></svg>
              <input type="range" id="posY" min="-200" max="200" value="0" class="dark-slider" style="flex:1;" oninput="applyTransform()">
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--grey)" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/></svg>
              <input type="range" id="rotSlider" min="-180" max="180" value="0" class="dark-slider" style="flex:1;" oninput="applyTransform()">
              <span id="rotVal" style="font-size:10px;color:var(--gold);width:36px;text-align:right;font-family:monospace;">0°</span>
            </div>
          </div>
          <div style="display:flex;gap:6px;margin-top:10px;">
            <button onclick="resetTransform()" style="flex:1;padding:7px;font-size:10px;letter-spacing:1px;text-transform:uppercase;border:1px solid var(--grey3);background:var(--white);cursor:pointer;color:var(--grey);">Atiestatīt</button>
            <button onclick="fitPhoto()" style="flex:1;padding:7px;font-size:10px;letter-spacing:1px;text-transform:uppercase;border:1px solid var(--gold-border);background:var(--gold-dim);cursor:pointer;color:var(--gold);">Pielāgot</button>
          </div>
        </div>

        <!-- Step 3: Notes + summary -->
        <div class="editor-step">
          <div class="editor-step-num" id="notesStepNum">03</div>
          <div class="editor-step-title">Papildu vēlmes</div>
          <textarea id="orderNotes" class="form-textarea" style="height:70px;width:100%;box-sizing:border-box;font-size:13px;" placeholder="Melnbalta versija, īpašas piezīmes..."></textarea>

          <?php if (!isset($_SESSION['klients_id'])): ?>
          <div style="margin-top:12px;">
            <label style="font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);display:block;margin-bottom:7px;">E-pasts *</label>
            <input type="email" id="guestEmail" class="form-input" style="width:100%;font-size:13px;" placeholder="tavs@epasts.lv">
            <div style="font-size:10px;color:var(--grey);margin-top:4px;">Apstiprinājumu nosūtīsim uz šo adresi</div>
          </div>
          <?php endif; ?>

          <div style="margin-top:12px;padding:13px;background:var(--cream2);">
            <div style="font-size:10px;color:var(--grey);text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Kopsavilkums</div>
            <div id="summaryProduct" style="font-size:13px;color:var(--ink);margin-bottom:4px;">— izvēlieties produktu no grozam —</div>
            <div id="summaryPrice" style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--gold);"></div>
          </div>
        </div>

      </div><!-- /sidebar -->

      <!-- RIGHT: Wall preview -->
      <div class="editor-preview-area" style="overflow:hidden;display:flex;flex-direction:column;height:100%;">
        <div class="editor-preview-label">Priekšskatījums — velciet foto ar peli</div>

        <div class="wall-scene" id="wallScene" style="flex:1;">
          <div class="wall-bg"></div>
          <div class="wall-frame-wrapper" id="wallFrameWrapper">
            <div class="wall-frame" id="wallFrame">
              <div class="frame-canvas-area" id="frameCanvasArea">
                <div class="photo-container" id="photoContainer" style="cursor:grab;position:absolute;inset:0;overflow:hidden;background:#111;">
                  <div id="photoWrap" style="display:none;position:absolute;width:400%;height:400%;top:-150%;left:-150%;transform-origin:center center;">
                    <img id="photoBg" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:block;">
                  </div>
                  <canvas id="photoCanvas" style="display:none;position:absolute;"></canvas>
                  <div class="photo-placeholder" id="photoPlaceholder">
                    <div style="text-align:center;color:rgba(255,255,255,.35);font-size:13px;">
                      <div style="font-size:32px;margin-bottom:8px;">🖼️</div>
                      Augšupielādējiet fotogrāfiju
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="frame-shadow"></div>
          </div>
        </div>

        <!-- Controls panel (zoom range indicator) -->
        <div class="editor-controls" id="editorControls" style="display:none;">
          <div class="ctrl-header"><span>Pielāgot fotogrāfiju</span></div>
          <div class="ctrl-body" style="display:none;"></div><!-- sliders moved to sidebar -->
        </div>

      </div><!-- /preview -->
    </div><!-- /editor-layout -->

  </div>
</div>

<!-- Gallery picker modal -->
<?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
<div class="modal-overlay" id="galleryPickerModal">
  <div class="modal" style="max-width:700px;padding:38px;">
    <button class="modal-close" onclick="closeModal('galleryPickerModal')">×</button>
    <div class="section-label">Manas galerijas</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:300;margin:10px 0 24px;">Izvēlieties fotogrāfiju</h2>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;max-height:420px;overflow-y:auto;">
      <?php foreach ($clientPhotos as $cp):
        $ps = filter_var($cp['attels_url']??'', FILTER_VALIDATE_URL) ? $cp['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/'.($cp['attels_url']??'');
      ?>
      <div class="gallery-picker-thumb" onclick="selectFromGallery('<?= htmlspecialchars($ps) ?>')">
        <img src="<?= htmlspecialchars($ps) ?>" alt="">
        <div class="gallery-picker-overlay">✓ Izvēlēties</div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Success modal -->
<div class="modal-overlay" id="successModal">
  <div class="modal" style="max-width:440px;padding:52px;text-align:center;">
    <div style="font-size:52px;margin-bottom:20px;">✓</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:300;color:var(--ink);margin-bottom:14px;">Pasūtījums saņemts!</h2>
    <p style="font-size:14px;color:var(--grey);line-height:1.8;margin-bottom:28px;">Sagatavosim jūsu pasūtījumu un sazināsimies 24 stundu laikā, lai apstiprinātu detaļas un apmaksu.</p>
    <button class="btn-primary" onclick="closeModal('successModal')">Lieliski →</button>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const products = <?= json_encode(array_map(function($p){
  $src=!empty($p['attels_url'])?(filter_var($p['attels_url'],FILTER_VALIDATE_URL)?$p['attels_url']:'/4pt/blazkova/lumina/Lumina/uploads/preces/'.$p['attels_url']):'https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80';
  return[
    'id'    => (int)$p['id'],
    'img'   => $src,
    'cat'   => $p['kategorija'],
    'title' => $p['nosaukums'],
    'desc'  => $p['apraksts'],
    'price' => $p['cena'],
    'fotos' => (int)($p['foto_skaits'] ?? 1),
    'izmers'=> $p['izmers'] ?? '',
    'orient'=> $p['orientacija'] ?? 'portrait',
    'tips'  => $p['tips'] ?? 'druka',
  ];
},$products)) ?>;

// ── Product modal — now opens editor directly ────────────
function openProductModal(i){
  const p = products[i];
  if (!p) return;
  // Open editor directly with this product
  openPhotoEditor(p.id);
}

function addToCartAjax(id,name){
  fetch('/4pt/blazkova/lumina/Lumina/veikals.php?action=add&id='+id)
    .then(r=>r.json())
    .then(d=>{document.getElementById('cartCount').textContent=d.count;showToast(name+' pievienots grozam ✓','success');});
}

function checkout(){
  const btn = document.querySelector('#cartSidebar .btn-primary');
  if (btn) { btn.textContent = 'Apstrādā...'; btn.disabled = true; }
  fetch('/4pt/blazkova/lumina/Lumina/stripe_checkout.php?action=create_checkout', {method:'POST'})
    .then(r=>r.json())
    .then(data=>{
      if (data.url) {
        window.location.href = data.url; // redirect to Stripe
      } else {
        showToast(data.error || 'Kļūda. Mēģiniet vēlreiz.', 'error');
        if (btn) { btn.textContent = 'Noformēt Pasūtījumu →'; btn.disabled = false; }
      }
    }).catch(()=>{
      showToast('Savienojuma kļūda.', 'error');
      if (btn) { btn.textContent = 'Noformēt Pasūtījumu →'; btn.disabled = false; }
    });
}

// ── Photo editor ─────────────────────────
// ── STATE ────────────────────────────────────────────────
let editorPhotos = [], currentPhotoIdx = 0, editorProduct = null;
let isDragging = false, dragStart = {x:0,y:0};

function openPhotoEditor(productId) {
  const p = products.find(x => x.id == productId);
  if (!p) return;
  editorProduct = p;
  document.getElementById('summaryProduct').textContent = p.title + (p.izmers ? ' · ' + p.izmers : '');
  document.getElementById('summaryPrice').textContent = '€' + parseFloat(p.price).toFixed(0);
  const badge = document.getElementById('editorCountBadge');
  if (badge) badge.style.display = 'flex';
  updateEditorCount();
  document.getElementById('editorUploadStatus').textContent =
    p.fotos === 1 ? 'Augšupielādējiet 1 fotogrāfiju' : `Augšupielādējiet ${p.fotos} fotogrāfijas`;
  const aspects = {portrait:'2/3', landscape:'3/2', square:'1/1'};
  document.getElementById('frameCanvasArea').style.aspectRatio = aspects[p.orient] || '2/3';
  openModal('photoEditorModal');
}

function switchTab(tab) {
  document.getElementById('tabUpload').style.display = tab==='upload'?'block':'none';
  const tg = document.getElementById('tabGallery');
  if (tg) tg.style.display = tab==='gallery'?'block':'none';
  document.querySelectorAll('.editor-tab').forEach((t,i) =>
    t.classList.toggle('active', (i===0&&tab==='upload')||(i===1&&tab==='gallery')));
}

function handleFileInput(files) { addFiles(Array.from(files)); }

function addFiles(files) {
  const need = editorProduct ? editorProduct.fotos : 50;
  const canAdd = Math.max(0, need - editorPhotos.length);
  if (!canAdd) { showToast('Nepieciešamais foto skaits sasniegts.', 'error'); return; }
  files.filter(f=>f.type.startsWith('image/')).slice(0, canAdd).forEach(file => {
    const r = new FileReader();
    r.onload = ev => {
      editorPhotos.push({src: ev.target.result, file, galleryUrl: null});
      renderEditorThumbs(); updateEditorCount();
      if (editorPhotos.length === 1) showPhotoInPreview(0);
      checkEditorReady();
    };
    r.readAsDataURL(file);
  });
}

function toggleGalleryPick(el, url) {
  const idx = editorPhotos.findIndex(p => p.galleryUrl === url);
  if (idx >= 0) {
    editorPhotos.splice(idx, 1);
    el.style.borderColor='transparent';
    el.querySelector('.gal-pick-check').style.display='none';
  } else {
    const need = editorProduct ? editorProduct.fotos : 50;
    if (editorPhotos.length >= need) { showToast('Nepieciešamais foto skaits sasniegts.','error'); return; }
    el.style.borderColor='var(--gold)';
    el.querySelector('.gal-pick-check').style.display='flex';
    editorPhotos.push({src: url, file: null, galleryUrl: url});
    if (editorPhotos.length === 1) showPhotoInPreview(0);
  }
  renderEditorThumbs(); updateEditorCount(); checkEditorReady();
}

function renderEditorThumbs() {
  const c = document.getElementById('uploadedThumbs');
  c.innerHTML = editorPhotos.map((p,i) => `
    <div style="position:relative;aspect-ratio:1;overflow:hidden;cursor:pointer;border:2px solid ${i===currentPhotoIdx?'var(--gold)':'transparent'};transition:.15s;" onclick="showPhotoInPreview(${i})">
      <img src="${p.src}" style="width:100%;height:100%;object-fit:cover;">
      <div style="position:absolute;bottom:2px;left:4px;font-size:10px;font-weight:700;color:#fff;text-shadow:0 1px 3px rgba(0,0,0,.8);">${i+1}</div>
      <button onclick="event.stopPropagation();removeEditorPhoto(${i})" style="position:absolute;top:2px;right:2px;width:17px;height:17px;background:rgba(0,0,0,.7);border:none;color:#fff;cursor:pointer;font-size:11px;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0;">×</button>
    </div>`).join('');
}

function removeEditorPhoto(i) {
  const p = editorPhotos[i];
  if (p.galleryUrl) {
    document.querySelectorAll('.gal-pick').forEach(el => {
      if (el.dataset.url === p.galleryUrl) { el.style.borderColor='transparent'; el.querySelector('.gal-pick-check').style.display='none'; }
    });
  }
  editorPhotos.splice(i, 1);
  if (currentPhotoIdx >= editorPhotos.length) currentPhotoIdx = Math.max(0, editorPhotos.length-1);
  renderEditorThumbs(); updateEditorCount();
  editorPhotos.length ? showPhotoInPreview(currentPhotoIdx) : clearPreview();
  checkEditorReady();
}

function updateEditorCount() {
  const have = editorPhotos.length, need = editorProduct ? editorProduct.fotos : 1;
  const nb = document.getElementById('editorCountNow'); if (nb) nb.textContent = have;
  const st = document.getElementById('editorUploadStatus');
  if (st) { st.textContent = have>=need ? `✓ ${have} foto pievienoti — var pasūtīt!` : `${have} / ${need} foto pievienoti`; st.style.color=have>=need?'var(--gold)':'var(--grey)'; }
}

function showPhotoInPreview(i) {
  currentPhotoIdx = i;
  const src = editorPhotos[i]?.src; if (!src) return;
  document.getElementById('photoBg').src = src;
  document.getElementById('photoWrap').style.display = 'block';
  document.getElementById('photoPlaceholder').style.display = 'none';
  document.getElementById('editorControls').style.display = 'flex';
  const es = document.getElementById('editStep'); if (es) es.style.display = 'block';
  resetTransform(); renderEditorThumbs();
}

function clearPreview() {
  document.getElementById('photoWrap').style.display = 'none';
  document.getElementById('photoBg').src = '';
  document.getElementById('photoPlaceholder').style.display = 'flex';
  document.getElementById('editorControls').style.display = 'none';
  const es = document.getElementById('editStep'); if (es) es.style.display = 'none';
}

function checkEditorReady() {
  const have = editorPhotos.length, need = editorProduct ? editorProduct.fotos : 1;
  const btn = document.getElementById('orderBtn');
  btn.disabled = have < need; btn.style.opacity = have>=need?'1':'0.4';
}

function applyTransform() {
  const pw = document.getElementById('photoWrap'); if (!pw) return;
  const zoom = parseInt(document.getElementById('zoomSlider').value);
  const px   = parseInt(document.getElementById('posX').value);
  const py   = parseInt(document.getElementById('posY').value);
  const rot  = parseInt(document.getElementById('rotSlider').value);
  const zv = document.getElementById('zoomVal'); if (zv) zv.textContent = zoom+'%';
  const rv = document.getElementById('rotVal');  if (rv) rv.textContent  = rot+'°';
  pw.style.transform = `translate(${px*0.5}px,${py*0.5}px) scale(${zoom/100}) rotate(${rot}deg)`;
}

function resetTransform() {
  ['zoomSlider','posX','posY','rotSlider'].forEach(id => { const el=document.getElementById(id); if(el) el.value=id==='zoomSlider'?100:0; });
  applyTransform();
}
function fitPhoto(){resetTransform();}

function addPhotoToCart() {
  if (!editorProduct || editorPhotos.length < editorProduct.fotos) return;
  const gEl = document.getElementById('guestEmail');
  if (gEl) { const em=gEl.value.trim(); if (!em||!em.includes('@')) { showToast('Lūdzu ievadiet e-pasta adresi!','error'); gEl.focus(); gEl.style.borderColor='#C0392B'; return; } }
  const btn = document.getElementById('orderBtn');
  btn.textContent = 'Pievieno...'; btn.disabled = true;
  const fd = new FormData();
  fd.append('add_photo_to_cart','1');
  fd.append('produkts', editorProduct.title + (editorProduct.izmers?' '+editorProduct.izmers:'') + ' — €' + parseFloat(editorProduct.price).toFixed(0));
  fd.append('cena', editorProduct.price);
  fd.append('notes', document.getElementById('orderNotes').value);
  if (gEl) fd.append('guest_email', gEl.value.trim());
  const galUrls = editorPhotos.filter(p=>p.galleryUrl).map(p=>p.galleryUrl);
  if (galUrls.length) fd.append('gallery_urls', JSON.stringify(galUrls));
  let fc=0;
  editorPhotos.filter(p=>!p.galleryUrl&&p.src.startsWith('data:')).forEach(p => fd.append('fotos[]', dataURLtoBlob(p.src), 'f'+(++fc)+'.jpg'));
  fetch('/4pt/blazkova/lumina/Lumina/veikals.php',{method:'POST',body:fd})
    .then(r=>r.json()).then(data=>{
      if(data.ok){
        document.getElementById('cartCount').textContent=data.count;
        closeModal('photoEditorModal');
        showToast(editorProduct.title+' pievienots grozam ✓','success');
        editorPhotos=[]; currentPhotoIdx=0;
        document.querySelectorAll('.gal-pick').forEach(el=>{el.style.borderColor='transparent';el.querySelector('.gal-pick-check').style.display='none';});
        clearPreview();
        document.getElementById('uploadedThumbs').innerHTML='';
        document.getElementById('orderNotes').value='';
        if(gEl) gEl.value='';
        checkEditorReady(); updateEditorCount();
      } else { showToast(data.error||'Kļūda','error'); }
      btn.textContent='Pievienot grozam →'; btn.disabled=false;
    }).catch(()=>{ showToast('Savienojuma kļūda','error'); btn.textContent='Pievienot grozam →'; btn.disabled=false; });
}

function dataURLtoBlob(d){const a=d.split(','),m=a[0].match(/:(.*?);/)[1],b=atob(a[1]);let n=b.length;const u=new Uint8Array(n);while(n--)u[n]=b.charCodeAt(n);return new Blob([u],{type:m});}

const pc=document.getElementById('photoContainer');
pc.addEventListener('mousedown',e=>{if(!editorPhotos.length)return;isDragging=true;dragStart.x=e.clientX-parseInt(document.getElementById('posX').value||0);dragStart.y=e.clientY-parseInt(document.getElementById('posY').value||0);pc.style.cursor='grabbing';e.preventDefault();});
document.addEventListener('mousemove',e=>{if(!isDragging)return;document.getElementById('posX').value=Math.max(-200,Math.min(200,e.clientX-dragStart.x));document.getElementById('posY').value=Math.max(-200,Math.min(200,e.clientY-dragStart.y));applyTransform();});
document.addEventListener('mouseup',()=>{isDragging=false;pc.style.cursor='grab';});
pc.addEventListener('touchstart',e=>{if(!editorPhotos.length)return;isDragging=true;const t=e.touches[0];dragStart.x=t.clientX-parseInt(document.getElementById('posX').value||0);dragStart.y=t.clientY-parseInt(document.getElementById('posY').value||0);},{passive:true});
document.addEventListener('touchmove',e=>{if(!isDragging)return;const t=e.touches[0];document.getElementById('posX').value=Math.max(-200,Math.min(200,t.clientX-dragStart.x));document.getElementById('posY').value=Math.max(-200,Math.min(200,t.clientY-dragStart.y));applyTransform();},{passive:true});
document.addEventListener('touchend',()=>isDragging=false);
pc.addEventListener('wheel',e=>{if(!editorPhotos.length)return;e.preventDefault();const s=document.getElementById('zoomSlider');s.value=Math.max(10,Math.min(300,parseInt(s.value)-e.deltaY*0.3));applyTransform();},{passive:false});
const dz=document.getElementById('editorDropzone');
if(dz){dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('drag-over');});dz.addEventListener('dragleave',()=>dz.classList.remove('drag-over'));dz.addEventListener('drop',e=>{e.preventDefault();dz.classList.remove('drag-over');addFiles(Array.from(e.dataTransfer.files).filter(f=>f.type.startsWith('image/')));});}

</script>
