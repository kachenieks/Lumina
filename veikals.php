<?php
ob_start(); // Buffer output — prevents PHP errors/warnings from breaking HTML
// Runtime limits (ini_set only works if PHP is not CGI/FPM with open_basedir)
@ini_set('memory_limit', '256M');
@ini_set('max_execution_time', '120');
@set_time_limit(120);
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

  // Check for duplicate: same product already in cart — overwrite instead of adding
  $existingKey = null;
  foreach ($_SESSION['cart'] as $k => $item) {
    if (!empty($item['is_foto']) && ($item['name'] ?? '') === strip_tags($produktsName)) {
      $existingKey = $k;
      break;
    }
  }
  $cartKey = $existingKey ?: ('foto_' . uniqid());
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
  // Save guest email to session so stripe_checkout can use it
  if ($guestEmail && !isset($_SESSION['klients_epasts'])) {
    $_SESSION['viesis_epasts_tmp'] = $guestEmail;
  }

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

  $allFotoUrls = [];
  $uploadDir   = __DIR__ . '/uploads/pasutijumi/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

  // Multiple files (fotos[])
  if (!empty($_FILES['fotos']['name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
      if (!$tmp || !is_uploaded_file($tmp)) continue;
      $ext = strtolower(pathinfo($_FILES['fotos']['name'][$k], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
      $fname = 'f_' . uniqid() . '.' . $ext;
      if (move_uploaded_file($tmp, $uploadDir . $fname)) {
        $allFotoUrls[] = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $fname;
      }
    }
  }
  // Single file fallback (foto)
  if (empty($allFotoUrls) && !empty($_FILES['foto']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
      $fname = 'f_' . uniqid() . '.' . $ext;
      if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fname)) {
        $allFotoUrls[] = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $fname;
      }
    }
  }
  // Gallery URLs
  if (empty($allFotoUrls) && !empty($galUrl)) {
    $allFotoUrls[] = $galUrl;
  }
  // Gallery URLs JSON from editor
  if (!empty($_POST['gallery_urls'])) {
    $gals = json_decode($_POST['gallery_urls'], true) ?: [];
    foreach ($gals as $u) if (filter_var($u, FILTER_VALIDATE_URL)) $allFotoUrls[] = $u;
  }

  if (empty($allFotoUrls)) {
    $uploadError = 'Lūdzu pievienojiet fotogrāfiju.';
  } else {
    $firstFoto  = escape($savienojums, $allFotoUrls[0]);
    $fotoUrlsJs = escape($savienojums, json_encode($allFotoUrls));
    $vEmail     = escape($savienojums, $guestEmail);
    @mysqli_query($savienojums,
      "INSERT INTO pasutijumi (klienta_id, produkts, foto_fails, foto_urls, crop_data, papildu_info, viesis_epasts, statuss, izveidots)
        VALUES ($klienta_id,'$produkts','$firstFoto','$fotoUrlsJs','$cropData','$notes','$vEmail','jauns',NOW())"
    );
    $uploadSuccess = 'Pasūtījums saņemts!';
    $klientaVards  = $_SESSION['klients_vards'] ?? 'Viesis';
    $klientaEmail  = $_SESSION['klients_epasts'] ?? $guestEmail;
    try {
      require_once __DIR__ . '/includes/mailer.php';
      if (function_exists('mailFotoPasutijumsAdmin'))
        mailFotoPasutijumsAdmin($klientaVards, $klientaEmail, $produkts, $notes, $allFotoUrls[0]);
      if ($klientaEmail && function_exists('mailFotoPasutijumsKlients'))
        mailFotoPasutijumsKlients($klientaEmail, $klientaVards, $produkts, $notes);
    } catch (\Throwable $e) { error_log('Mail error: ' . $e->getMessage()); }
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
/* ─── EDITOR ─────────────────────────────────────────── */
:root{--border:#e8e3da;}
.editor-topbar{display:flex;justify-content:space-between;align-items:center;padding:12px clamp(14px,2.5vw,24px);border-bottom:1px solid var(--border);background:var(--white);position:sticky;top:0;z-index:10;gap:10px;flex-wrap:wrap;}
.editor-layout{display:grid;grid-template-columns:300px 1fr;min-height:calc(100vh - 58px);}
.editor-sidebar{padding:20px 18px;border-right:1px solid var(--border);overflow-y:auto;background:#faf9f7;max-height:calc(100vh - 58px);position:sticky;top:58px;}
.editor-step{margin-bottom:22px;padding-bottom:22px;border-bottom:1px solid var(--border);}
.editor-step:last-child{border-bottom:none;margin-bottom:0;padding-bottom:0;}
.editor-step-num{font-size:9px;color:var(--gold);letter-spacing:3px;text-transform:uppercase;margin-bottom:4px;}
.editor-step-title{font-family:'Cormorant Garamond',serif;font-size:17px;color:var(--ink);margin-bottom:12px;}
/* Dropzone */
.editor-dropzone{border:2px dashed var(--border);padding:20px 12px;text-align:center;cursor:pointer;transition:.2s;background:var(--white);border-radius:2px;}
.editor-dropzone:hover,.editor-dropzone.drag-over{border-color:var(--gold);background:rgba(184,151,90,.04);}
/* Tabs */
.editor-tabs{display:flex;gap:0;margin-bottom:12px;border:1px solid var(--border);overflow:hidden;}
.editor-tab{flex:1;padding:8px;font-size:10px;letter-spacing:.5px;border:none;background:transparent;cursor:pointer;color:var(--grey);transition:.2s;}
.editor-tab.active{background:var(--gold);color:var(--white);}
/* Gallery thumbs */
.gal-pick{position:relative;aspect-ratio:1;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:.15s;}
.gal-pick img{width:100%;height:100%;object-fit:cover;}
/* Sliders */
.dark-slider{-webkit-appearance:none;appearance:none;width:100%;height:4px;border-radius:2px;background:rgba(0,0,0,.12);outline:none;cursor:pointer;}
.dark-slider::-webkit-slider-thumb{-webkit-appearance:none;width:18px;height:18px;border-radius:50%;background:var(--gold,#B8975A);cursor:pointer;border:3px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.25);transition:transform .15s;}
.dark-slider::-webkit-slider-thumb:active{transform:scale(1.2);}
.dark-slider::-moz-range-thumb{width:18px;height:18px;border-radius:50%;background:var(--gold,#B8975A);cursor:pointer;border:3px solid #fff;}
/* Preview area */
.editor-preview-area{display:flex;flex-direction:column;background:#ece9e4;overflow:hidden;}
.editor-preview-label{text-align:center;font-size:9px;letter-spacing:3px;color:rgba(0,0,0,.28);text-transform:uppercase;padding:12px;flex-shrink:0;}
/* WALL scene — single photo */
.wall-scene{flex:1;display:flex;align-items:center;justify-content:center;padding:32px;position:relative;}
.wall-bg{position:absolute;inset:0;background:linear-gradient(160deg,#e8e3dc 0%,#d5cfc7 100%);}
.wall-frame-wrapper{position:relative;z-index:2;}
.wall-frame{background:var(--white);box-shadow:0 20px 56px rgba(0,0,0,.22),0 4px 10px rgba(0,0,0,.1);border:12px solid #f5f3ef;position:relative;width:clamp(180px,26vw,300px);}
.frame-canvas-area{width:100%;aspect-ratio:2/3;position:relative;background:#f0ece6;cursor:grab;overflow:hidden;}
.frame-canvas-area:active{cursor:grabbing;}
/* Photo inside frame — fills fully, no black */
#photoWrap{position:absolute;inset:-60%;width:220%;height:220%;transform-origin:center center;}
#photoBg{width:100%;height:100%;object-fit:cover;display:block;pointer-events:none;user-select:none;}
.photo-placeholder{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#e8e4de;}
.frame-shadow{position:absolute;bottom:-16px;left:8%;right:8%;height:14px;background:rgba(0,0,0,.12);filter:blur(9px);border-radius:50%;}
/* ALBUM preview */
.album-scene{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:24px 16px;position:relative;gap:16px;overflow:hidden;}
.album-book{position:relative;z-index:2;display:flex;box-shadow:0 16px 48px rgba(0,0,0,.28);}
.album-left,.album-right{width:clamp(130px,20vw,220px);height:clamp(130px,20vw,220px);background:#e8e4de;overflow:hidden;position:relative;}
.album-left{border-right:3px solid #b8ad9e;}
.album-spine{width:14px;background:linear-gradient(to right,#8a8278,#c4bdb5,#8a8278);box-shadow:inset 0 0 6px rgba(0,0,0,.2);}
.album-page-img{width:100%;height:100%;object-fit:cover;display:block;}
.album-page-empty{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:28px;color:rgba(0,0,0,.15);}
.album-nav{display:flex;align-items:center;gap:16px;z-index:3;}
.album-nav-btn{width:36px;height:36px;border-radius:50%;border:1.5px solid var(--gold);background:rgba(255,255,255,.9);color:var(--gold);font-size:16px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:.2s;backdrop-filter:blur(4px);}
.album-nav-btn:hover{background:var(--gold);color:#fff;}
.album-nav-btn:disabled{opacity:.3;cursor:default;}
.album-page-indicator{font-size:10px;letter-spacing:2px;color:rgba(0,0,0,.35);text-transform:uppercase;min-width:80px;text-align:center;}
/* Thumbnail strip */
.album-strip{display:flex;gap:6px;overflow-x:auto;padding:4px 8px;z-index:3;max-width:100%;}
.album-strip-thumb{width:44px;height:44px;flex-shrink:0;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:.15s;opacity:.6;user-select:none;}
.album-strip-thumb.active{border-color:var(--gold);opacity:1;}
.album-strip-thumb img{width:100%;height:100%;object-fit:cover;pointer-events:none;}
.album-strip-thumb.sortable-ghost{opacity:.25;border-color:var(--gold);}
.album-strip-thumb.sortable-chosen{border-color:var(--gold);opacity:1;box-shadow:0 4px 12px rgba(0,0,0,.2);}
.drag-hint{font-size:10px;color:rgba(255,255,255,.35);letter-spacing:1px;text-align:center;margin-top:2px;display:none;}
.drag-hint.show{display:block;}
/* Responsive */
@media(max-width:900px){
  .editor-layout{grid-template-columns:1fr;grid-template-rows:auto 1fr;}
  .editor-sidebar{max-height:none;position:static;border-right:none;border-bottom:1px solid var(--border);}
  .wall-frame{width:clamp(140px,40vw,220px);}
}
@media(max-width:480px){
  .editor-topbar{flex-direction:column;align-items:flex-start;}
  .album-left,.album-right{width:clamp(110px,36vw,160px);height:clamp(110px,36vw,160px);}
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


<!-- FOTO EDITORS — fullscreen modal -->
<div class="modal-overlay" id="photoEditorModal" style="align-items:flex-start;padding:0;overflow:hidden;">
  <div style="background:var(--white);width:100%;height:100vh;display:flex;flex-direction:column;">

    <!-- Topbar -->
    <div class="editor-topbar">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--ink);">✦ Foto editors</div>
        <div id="editorProductName" style="font-size:11px;color:var(--grey);letter-spacing:1px;"></div>
      </div>
      <div style="display:flex;gap:8px;align-items:center;">
        <div id="editorCountBadge" style="font-size:11px;padding:5px 12px;background:var(--cream2);color:var(--grey);display:none;white-space:nowrap;">
          <span id="editorCountNow">0</span>/<span id="editorCountNeed">1</span> foto
        </div>
        <button class="btn-outline" style="padding:7px 14px;font-size:11px;" onclick="closeModal('photoEditorModal')">← Atpakaļ</button>
        <button class="btn-primary" style="padding:7px 18px;font-size:11px;" id="orderBtn" onclick="addPhotoToCart()" disabled>Pievienot grozam →</button>
      </div>
    </div>

    <!-- Main editor -->
    <div class="editor-layout" style="flex:1;overflow:hidden;">

      <!-- LEFT sidebar -->
      <div class="editor-sidebar" style="overflow-y:auto;height:100%;">

        <!-- 01 Upload -->
        <div class="editor-step">
          <div class="editor-step-num">01</div>
          <div class="editor-step-title">Pievienojiet foto</div>
          <div id="editorUploadStatus" style="font-size:11px;color:var(--grey);margin-bottom:10px;">Augšupielādējiet nepieciešamās fotogrāfijas</div>

          <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
          <div class="editor-tabs">
            <button class="editor-tab active" onclick="switchTab('upload')">Augšupielādēt</button>
            <button class="editor-tab" onclick="switchTab('gallery')">Galerijas (<?= count($clientPhotos) ?>)</button>
          </div>
          <?php endif; ?>

          <div id="tabUpload">
            <div class="editor-dropzone" id="editorDropzone" onclick="document.getElementById('editorFileInput').click()">
              <input type="file" id="editorFileInput" accept="image/*" multiple style="display:none;" onchange="handleFileInput(this.files)">
              <div style="font-size:24px;margin-bottom:6px;">📷</div>
              <div style="font-size:12px;color:var(--ink);margin-bottom:3px;">Velciet foto šeit vai klikšķiniet</div>
              <div style="font-size:10px;color:var(--grey);">Var izvēlēties vairākas bildes uzreiz</div>
            </div>
          </div>

          <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
          <div id="tabGallery" style="display:none;margin-top:4px;">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:4px;max-height:220px;overflow-y:auto;">
              <?php foreach ($clientPhotos as $cp):
                $ps = filter_var($cp['attels_url']??'', FILTER_VALIDATE_URL)
                  ? $cp['attels_url']
                  : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/'.($cp['attels_url']??'');
              ?>
              <div class="gal-pick" onclick="toggleGalleryPick(this,'<?= htmlspecialchars($ps) ?>')" data-url="<?= htmlspecialchars($ps) ?>">
                <img src="<?= htmlspecialchars($ps) ?>" alt="">
                <div class="gal-pick-check" style="display:none;position:absolute;inset:0;background:rgba(184,151,90,.65);color:#fff;font-size:18px;align-items:center;justify-content:center;font-weight:bold;">✓</div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <div id="uploadedThumbs" style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;margin-top:10px;"></div>
        </div>

        <!-- 02 Edit (single photo mode only) -->
        <div class="editor-step" id="editStep" style="display:none;">
          <div class="editor-step-num">02</div>
          <div class="editor-step-title">Kadrēt foto</div>
          <div style="font-size:10px;color:var(--grey);margin-bottom:14px;">Velciet labajā pusē vai lietojiet slīdņus</div>

          <!-- Crop frame mini preview -->
          <div style="position:relative;background:var(--cream2);border:1px solid var(--border);margin-bottom:14px;overflow:hidden;" id="miniCropFrame">
            <div id="miniCropPhoto" style="width:100%;aspect-ratio:2/3;background-size:cover;background-position:center;transition:.1s;"></div>
            <div style="position:absolute;inset:0;border:2px solid var(--gold);pointer-events:none;"></div>
            <div style="position:absolute;top:0;left:33%;bottom:0;width:1px;background:rgba(184,151,90,.3);pointer-events:none;"></div>
            <div style="position:absolute;top:0;left:66%;bottom:0;width:1px;background:rgba(184,151,90,.3);pointer-events:none;"></div>
            <div style="position:absolute;left:0;right:0;top:33%;height:1px;background:rgba(184,151,90,.3);pointer-events:none;"></div>
            <div style="position:absolute;left:0;right:0;top:66%;height:1px;background:rgba(184,151,90,.3);pointer-events:none;"></div>
          </div>

          <div style="display:flex;flex-direction:column;gap:12px;">
            <!-- Zoom -->
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:9px;color:var(--grey);letter-spacing:1px;text-transform:uppercase;">Tālummaiņa</span>
                <span id="zoomVal" style="font-size:10px;color:var(--gold);font-family:monospace;">100%</span>
              </div>
              <input type="range" id="zoomSlider" min="50" max="300" value="100" class="dark-slider" oninput="applyTransform()">
            </div>
            <!-- Horizontal -->
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:9px;color:var(--grey);letter-spacing:1px;text-transform:uppercase;">Horizontāli</span>
              </div>
              <input type="range" id="posX" min="-150" max="150" value="0" class="dark-slider" oninput="applyTransform()">
            </div>
            <!-- Vertical -->
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:9px;color:var(--grey);letter-spacing:1px;text-transform:uppercase;">Vertikāli</span>
              </div>
              <input type="range" id="posY" min="-150" max="150" value="0" class="dark-slider" oninput="applyTransform()">
            </div>
            <!-- Rotation -->
            <div>
              <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:9px;color:var(--grey);letter-spacing:1px;text-transform:uppercase;">Rotācija</span>
                <span id="rotVal" style="font-size:10px;color:var(--gold);font-family:monospace;">0°</span>
              </div>
              <input type="range" id="rotSlider" min="-45" max="45" value="0" class="dark-slider" oninput="applyTransform()">
            </div>
          </div>
          <div style="display:flex;gap:6px;margin-top:12px;">
            <button onclick="resetTransform()" style="flex:1;padding:8px;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;border:1px solid var(--grey3);background:var(--white);cursor:pointer;color:var(--grey);">↺ Atiestatīt</button>
            <button onclick="fitPhoto()" style="flex:1;padding:8px;font-size:9px;letter-spacing:1.5px;text-transform:uppercase;border:1px solid var(--gold-border);background:var(--gold-dim);cursor:pointer;color:var(--gold);">⊡ Pielāgot</button>
          </div>
        </div>

        <!-- 03 Notes -->
        <div class="editor-step">
          <div class="editor-step-num" id="notesStepNum">03</div>
          <div class="editor-step-title">Papildu vēlmes</div>
          <textarea id="orderNotes" class="form-textarea" style="height:55px;width:100%;box-sizing:border-box;font-size:12px;resize:none;" placeholder="Melnbalta versija, īpašas piezīmes..."></textarea>
          <div style="margin-top:10px;">
            <label style="font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--grey);display:block;margin-bottom:4px;">Piegādes adrese <span style="color:var(--gold);">*</span></label>
            <input type="text" id="deliveryAddrField" class="form-input" style="width:100%;box-sizing:border-box;font-size:13px;padding:8px 12px;" placeholder="Omniva Rīga 1, vai ielas adrese" required>
            <div style="font-size:10px;color:var(--grey2);margin-top:3px;">Omniva / DPD pakomāts vai mājas adrese</div>
          </div>
          <?php if (!isset($_SESSION['klients_id'])): ?>
          <div style="margin-top:8px;">
            <label style="font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--grey);display:block;margin-bottom:4px;">Jūsu e-pasts (apstiprinājumam) <span style="color:var(--gold);">*</span></label>
            <input type="email" id="guestEmailField" class="form-input" style="width:100%;box-sizing:border-box;font-size:13px;padding:8px 12px;" placeholder="jusu@epasts.lv">
          </div>
          <?php endif; ?>
          <div style="margin-top:10px;padding:11px;background:var(--cream2);">
            <div style="font-size:9px;color:var(--grey);text-transform:uppercase;letter-spacing:2px;margin-bottom:5px;">Kopsavilkums</div>
            <div id="summaryProduct" style="font-size:12px;color:var(--ink);margin-bottom:3px;">—</div>
            <div id="summaryPrice" style="font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--gold);"></div>
          </div>
        </div>

      </div><!-- /sidebar -->

      <!-- RIGHT: Preview -->
      <div class="editor-preview-area" style="overflow:hidden;display:flex;flex-direction:column;height:100%;">
        <div class="editor-preview-label" id="previewLabel">Priekšskatījums reāllaikā</div>

        <!-- SINGLE PHOTO preview (druka / canvas / panelis) -->
        <div id="singlePreview" class="wall-scene" style="flex:1;">
          <div class="wall-bg"></div>
          <div class="wall-frame-wrapper" id="wallFrameWrapper">
            <div class="wall-frame" id="wallFrame">
              <div class="frame-canvas-area" id="frameCanvasArea">
                <div id="photoContainer" style="position:absolute;inset:0;overflow:hidden;cursor:grab;background:#e8e4de;">
                  <!-- Photo: oversized wrapper so panning never shows background -->
                  <div id="photoWrap" style="display:none;position:absolute;inset:-60%;width:220%;height:220%;transform-origin:center center;">
                    <img id="photoBg" src="" alt="" style="width:100%;height:100%;object-fit:cover;display:block;pointer-events:none;">
                  </div>
                  <div class="photo-placeholder" id="photoPlaceholder">
                    <div style="text-align:center;color:rgba(0,0,0,.2);font-size:12px;">
                      <div style="font-size:28px;margin-bottom:6px;opacity:.4;">🖼️</div>
                      Pievienojiet fotogrāfiju
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="frame-shadow"></div>
          </div>
        </div>

        <!-- ALBUM preview (albums) -->
        <div id="albumPreview" style="display:none;flex:1;flex-direction:column;overflow:hidden;" class="album-scene">
          <div class="wall-bg" style="position:absolute;inset:0;"></div>
          <!-- Book spread -->
          <div class="album-book" id="albumBook">
            <div class="album-left" id="albumPageLeft">
              <div class="album-page-empty">—</div>
            </div>
            <div class="album-spine"></div>
            <div class="album-right" id="albumPageRight">
              <div class="album-page-empty">—</div>
            </div>
          </div>
          <!-- Navigation -->
          <div class="album-nav">
            <button class="album-nav-btn" id="albumPrev" onclick="albumNav(-1)" disabled>‹</button>
            <div class="album-page-indicator" id="albumPageIndicator">Vāks</div>
            <button class="album-nav-btn" id="albumNext" onclick="albumNav(1)">›</button>
          </div>
          <!-- Thumbnail strip (drag to reorder) -->
          <div class="album-strip" id="albumStrip"></div>
          <div class="drag-hint" id="albumDragHint">&#8596; Velciet lai mainītu secību</div>
        </div>

      </div><!-- /preview -->
    </div><!-- /editor-layout -->
  </div>
</div>

<!-- Success modal -->
<div class="modal-overlay" id="successModal">
  <div class="modal" style="max-width:440px;padding:52px;text-align:center;">
    <div style="font-size:52px;margin-bottom:20px;">✓</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:300;color:var(--ink);margin-bottom:14px;">Pasūtījums saņemts!</h2>
    <p style="font-size:14px;color:var(--grey);line-height:1.8;margin-bottom:28px;">Sagatavosim jūsu pasūtījumu un sazināsimies 24 stundu laikā, lai apstiprinātu detaļas un apmaksu.</p>
    <button class="btn-primary" onclick="closeModal('successModal')">Lieliski →</button>
  </div>
</div>

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
// ── STATE ─────────────────────────────────────────────
let editorPhotos    = [];   // [{src, file|null, galleryUrl|null}]
let currentPhotoIdx = 0;
let editorProduct   = null; // {id, title, price, fotos, izmers, orient, tips}
let isDragging      = false;
let dragStart       = {x:0, y:0};
// Album state
let albumSpread     = 0;    // 0=cover, 1=spread1, 2=spread2 ...

// ── OPEN EDITOR ───────────────────────────────────────
function openPhotoEditor(productId) {
  let p = products.find(x => x.id == productId);
  if (!p) return;
  // Ensure all fields exist with safe defaults
  p = Object.assign({fotos:1, izmers:'', orient:'portrait', tips:'druka'}, p);
  editorProduct = p;

  // Clear delivery address field for new product
  const _da = document.getElementById('deliveryAddrField');
  if (_da) { _da.value = ''; _da.style.borderColor = ''; }

  // Update topbar
  document.getElementById('editorProductName').textContent = p.title + (p.izmers ? ' · ' + p.izmers : '');
  document.getElementById('summaryProduct').textContent = p.title + (p.izmers ? ' · ' + p.izmers : '');
  document.getElementById('summaryPrice').textContent = '€' + parseFloat(p.price).toFixed(0);

  // Counter
  const badge = document.getElementById('editorCountBadge');
  if (badge) { badge.style.display = p.fotos > 1 ? 'flex' : 'none'; }
  updateEditorCount();

  // Status text
  document.getElementById('editorUploadStatus').textContent =
    p.fotos === 1 ? 'Augšupielādējiet 1 fotogrāfiju' : `Augšupielādējiet ${p.fotos} fotogrāfijas`;

  // Show correct preview mode
  const isAlbum = p.tips === 'albums';
  document.getElementById('singlePreview').style.display  = isAlbum ? 'none' : 'flex';
  document.getElementById('albumPreview').style.display   = isAlbum ? 'flex' : 'none';
  document.getElementById('editStep').style.display       = 'none';

  // Frame aspect ratio for single preview
  if (!isAlbum) {
    const aspects = {portrait:'2/3', landscape:'3/2', square:'1/1'};
    document.getElementById('frameCanvasArea').style.aspectRatio = aspects[p.orient] || '2/3';
    document.getElementById('previewLabel').textContent = 'Velciet foto ar peli — pielāgojiet kadru';
  } else {
    document.getElementById('previewLabel').textContent = 'Albuma priekšskatījums — pāršķiriet lapas';
    albumSpread = 0;
    renderAlbum();
  }

  openModal('photoEditorModal');
}

// ── TABS ──────────────────────────────────────────────
function switchTab(tab) {
  document.getElementById('tabUpload').style.display = tab==='upload' ? 'block' : 'none';
  const tg = document.getElementById('tabGallery');
  if (tg) tg.style.display = tab==='gallery' ? 'block' : 'none';
  document.querySelectorAll('.editor-tab').forEach((t,i) =>
    t.classList.toggle('active', (i===0&&tab==='upload')||(i===1&&tab==='gallery')));
}

// ── FILE UPLOAD ───────────────────────────────────────
function handleFileInput(files) { addFiles(Array.from(files)); }

function addFiles(files) {
  const need   = editorProduct ? editorProduct.fotos : 50;
  const canAdd = Math.max(0, need - editorPhotos.length);
  if (!canAdd) { showToast('Nepieciešamais foto skaits sasniegts.', 'error'); return; }
  files.filter(f => f.type.startsWith('image/')).slice(0, canAdd).forEach(file => {
    const r = new FileReader();
    r.onload = ev => {
      editorPhotos.push({src: ev.target.result, file, galleryUrl: null});
      renderEditorThumbs();
      updateEditorCount();
      if (editorPhotos.length === 1) activatePhoto(0);
      else if (editorProduct?.tips === 'albums') renderAlbum();
      checkEditorReady();
    };
    r.readAsDataURL(file);
  });
}

// ── GALLERY PICK ──────────────────────────────────────
function toggleGalleryPick(el, url) {
  const idx = editorPhotos.findIndex(p => p.galleryUrl === url);
  if (idx >= 0) {
    editorPhotos.splice(idx, 1);
    el.style.borderColor = 'transparent';
    el.querySelector('.gal-pick-check').style.display = 'none';
    if (currentPhotoIdx >= editorPhotos.length) currentPhotoIdx = Math.max(0, editorPhotos.length-1);
    renderEditorThumbs(); updateEditorCount();
    editorPhotos.length ? activatePhoto(currentPhotoIdx) : clearPreview();
    if (editorProduct?.tips === 'albums') renderAlbum();
    checkEditorReady(); return;
  }
  const need = editorProduct ? editorProduct.fotos : 50;
  if (editorPhotos.length >= need) { showToast('Nepieciešamais foto skaits sasniegts.', 'error'); return; }
  el.style.borderColor = 'var(--gold)';
  el.querySelector('.gal-pick-check').style.display = 'flex';
  editorPhotos.push({src: url, file: null, galleryUrl: url});
  renderEditorThumbs(); updateEditorCount();
  if (editorPhotos.length === 1) activatePhoto(0);
  if (editorProduct?.tips === 'albums') renderAlbum();
  checkEditorReady();
}

// ── THUMBNAILS ────────────────────────────────────────
function renderEditorThumbs() {
  const c = document.getElementById('uploadedThumbs');
  c.innerHTML = editorPhotos.map((p, i) => `
    <div style="position:relative;aspect-ratio:1;overflow:hidden;cursor:pointer;
      border:2px solid ${i===currentPhotoIdx?'var(--gold)':'var(--grey3)'};transition:.15s;"
      onclick="activatePhoto(${i})">
      <img src="${p.src}" style="width:100%;height:100%;object-fit:cover;">
      <span style="position:absolute;bottom:2px;left:3px;font-size:9px;font-weight:700;color:#fff;
        text-shadow:0 1px 3px rgba(0,0,0,.8);">${i+1}</span>
      <button onclick="event.stopPropagation();removeEditorPhoto(${i})"
        style="position:absolute;top:2px;right:2px;width:16px;height:16px;background:rgba(0,0,0,.7);
        border:none;color:#fff;cursor:pointer;font-size:10px;border-radius:50%;
        display:flex;align-items:center;justify-content:center;padding:0;line-height:1;">×</button>
    </div>`).join('');
}

function removeEditorPhoto(i) {
  const p = editorPhotos[i];
  if (p.galleryUrl) {
    document.querySelectorAll('.gal-pick').forEach(el => {
      if (el.dataset.url === p.galleryUrl) {
        el.style.borderColor = 'transparent';
        el.querySelector('.gal-pick-check').style.display = 'none';
      }
    });
  }
  editorPhotos.splice(i, 1);
  if (currentPhotoIdx >= editorPhotos.length) currentPhotoIdx = Math.max(0, editorPhotos.length-1);
  renderEditorThumbs(); updateEditorCount();
  editorPhotos.length ? activatePhoto(currentPhotoIdx) : clearPreview();
  if (editorProduct?.tips === 'albums') renderAlbum();
  checkEditorReady();
}

// ── COUNTER ───────────────────────────────────────────
function updateEditorCount() {
  const have = editorPhotos.length, need = editorProduct ? editorProduct.fotos : 1;
  const n = document.getElementById('editorCountNow'); if (n) n.textContent = have;
  const st = document.getElementById('editorUploadStatus');
  if (st) {
    st.textContent = have >= need
      ? `✓ ${have} foto pievienoti — gatavs pasūtīt!`
      : `${have} / ${need} foto pievienoti`;
    st.style.color = have >= need ? 'var(--gold)' : 'var(--grey)';
  }
}

// ── ACTIVATE PHOTO (single mode) ──────────────────────
function activatePhoto(i) {
  currentPhotoIdx = i;
  if (editorProduct?.tips === 'albums') { renderAlbum(); return; }
  const src = editorPhotos[i]?.src;
  if (!src) return;
  const pw = document.getElementById('photoWrap');
  const pb = document.getElementById('photoBg');
  pb.src = src;
  pw.style.display = 'block';
  document.getElementById('photoPlaceholder').style.display = 'none';
  document.getElementById('editStep').style.display = 'block';
  // Update mini crop frame
  const mcp = document.getElementById('miniCropPhoto');
  if (mcp) { mcp.style.backgroundImage = `url('${src}')`; }
  resetTransform();
  renderEditorThumbs();
}

function clearPreview() {
  const pw = document.getElementById('photoWrap'); if (pw) pw.style.display = 'none';
  const pb = document.getElementById('photoBg'); if (pb) pb.src = '';
  const pp = document.getElementById('photoPlaceholder'); if (pp) pp.style.display = 'flex';
  const es = document.getElementById('editStep'); if (es) es.style.display = 'none';
}

// ── ALBUM PREVIEW ─────────────────────────────────────
function renderAlbum() {
  const left  = document.getElementById('albumPageLeft');
  const right = document.getElementById('albumPageRight');
  const ind   = document.getElementById('albumPageIndicator');
  const prev  = document.getElementById('albumPrev');
  const next  = document.getElementById('albumNext');
  const total = editorPhotos.length;
  const need  = editorProduct?.fotos || 20;

  // spread 0 = cover | spread 1+ = pages (2 photos per spread)
  if (albumSpread === 0) {
    // Cover: first photo
    left.innerHTML  = total > 0 ? `<img class="album-page-img" src="${editorPhotos[0].src}">` : `<div class="album-page-empty">Vāks</div>`;
    right.innerHTML = `<div class="album-page-empty" style="font-size:11px;color:rgba(0,0,0,.2);padding:16px;text-align:center;">
      <div style="font-size:32px;margin-bottom:8px;">📖</div>${editorProduct?.title || 'Albums'}</div>`;
    ind.textContent = 'Vāks';
    prev.disabled   = true;
  } else {
    const li = (albumSpread-1)*2 + 1;  // left photo index
    const ri = li + 1;
    left.innerHTML  = li < total ? `<img class="album-page-img" src="${editorPhotos[li].src}">` : `<div class="album-page-empty" style="font-size:20px;color:rgba(0,0,0,.1);">${li+1}</div>`;
    right.innerHTML = ri < total ? `<img class="album-page-img" src="${editorPhotos[ri].src}">` : `<div class="album-page-empty" style="font-size:20px;color:rgba(0,0,0,.1);">${ri+1}</div>`;
    ind.textContent = `Lpp. ${li+1}–${ri+1}`;
    prev.disabled   = false;
  }

  const maxSpreads = Math.ceil((need-1)/2) + 1;
  next.disabled = albumSpread >= maxSpreads - 1;

  // Strip
  renderAlbumStrip();
}

function renderAlbumStrip() {
  const strip = document.getElementById('albumStrip');
  if (!strip) return;
  strip.innerHTML = editorPhotos.map((p, i) => `
    <div class="album-strip-thumb ${currentAlbumThumb(i) ? 'active' : ''}"
      data-idx="${i}" onclick="jumpToAlbumPhoto(${i})">
      <img src="${p.src}" alt="">
    </div>`).join('');

  // Destroy old Sortable instance if exists
  if (strip._sortable) strip._sortable.destroy();

  // Init drag & drop reorder
  if (typeof Sortable !== 'undefined' && editorPhotos.length > 1) {
    strip._sortable = Sortable.create(strip, {
      animation: 150,
      ghostClass: 'sortable-ghost',
      chosenClass: 'sortable-chosen',
      direction: 'horizontal',
      onEnd: function(evt) {
        // Reorder editorPhotos array
        const moved = editorPhotos.splice(evt.oldIndex, 1)[0];
        editorPhotos.splice(evt.newIndex, 0, moved);
        renderAlbumStrip();
        renderAlbum();
        updateFotoCounter();
      }
    });
    // Show drag hint
    const hint = document.getElementById('albumDragHint');
    if (hint) hint.classList.add('show');
  }
}

function currentAlbumThumb(i) {
  if (albumSpread === 0) return i === 0;
  const li = (albumSpread-1)*2+1, ri = li+1;
  return i === li || i === ri;
}

function jumpToAlbumPhoto(i) {
  if (i === 0) { albumSpread = 0; }
  else { albumSpread = Math.floor((i-1)/2) + 1; }
  renderAlbum();
}

function albumNav(dir) {
  const need = editorProduct?.fotos || 20;
  const maxSpreads = Math.ceil((need-1)/2) + 1;
  albumSpread = Math.max(0, Math.min(maxSpreads-1, albumSpread + dir));
  // Animate
  const book = document.getElementById('albumBook');
  book.style.opacity = '0';
  book.style.transform = `translateX(${dir > 0 ? '-10px' : '10px'})`;
  setTimeout(() => {
    renderAlbum();
    book.style.transition = 'opacity .25s, transform .25s';
    book.style.opacity = '1';
    book.style.transform = 'translateX(0)';
    setTimeout(() => { book.style.transition = ''; }, 300);
  }, 100);
}

// ── TRANSFORMS ────────────────────────────────────────
function applyTransform() {
  const pw = document.getElementById('photoWrap'); if (!pw) return;
  const zoom = parseInt(document.getElementById('zoomSlider').value);
  const px   = parseInt(document.getElementById('posX').value);
  const py   = parseInt(document.getElementById('posY').value);
  const rot  = parseInt(document.getElementById('rotSlider').value);
  const zv = document.getElementById('zoomVal'); if (zv) zv.textContent = zoom+'%';
  const rv = document.getElementById('rotVal');  if (rv) rv.textContent  = rot+'°';
  // Clamp so photo always covers frame (wrapper is 220% size, so ±60% buffer)
  const maxShift = 50 * (zoom / 100);
  const cx = Math.max(-maxShift, Math.min(maxShift, px));
  const cy = Math.max(-maxShift, Math.min(maxShift, py));
  pw.style.transform = `translate(${cx*0.5}px,${cy*0.5}px) scale(${zoom/100}) rotate(${rot}deg)`;
  // Sync mini crop preview
  const mcp = document.getElementById('miniCropPhoto');
  if (mcp && editorPhotos[currentPhotoIdx]) {
    mcp.style.backgroundSize = `${zoom}%`;
    mcp.style.backgroundPosition = `calc(50% + ${cx*0.3}px) calc(50% + ${cy*0.3}px)`;
  }
}

function resetTransform() {
  const ids = ['zoomSlider','posX','posY','rotSlider'];
  ids.forEach(id => { const el = document.getElementById(id); if (el) el.value = id==='zoomSlider'?100:0; });
  applyTransform();
}

function fitPhoto() { resetTransform(); }

function checkEditorReady() {
  const have = editorPhotos.length, need = editorProduct ? editorProduct.fotos : 1;
  const btn  = document.getElementById('orderBtn');
  btn.disabled = have < need;
  btn.style.opacity = have >= need ? '1' : '0.4';
}

// ── SUBMIT ────────────────────────────────────────────
function addPhotoToCart() {
  if (!editorProduct || editorPhotos.length < editorProduct.fotos) return;
  const _da = document.getElementById('deliveryAddrField');
  if (_da && !_da.value.trim()) { _da.focus(); _da.style.borderColor='var(--gold)'; showToast('Lūdzu ievadiet piegādes adresi', 'error'); return; }
  const btn = document.getElementById('orderBtn');
  const totalPhotos = editorPhotos.length;
  btn.textContent = 'Augšupielādē ' + totalPhotos + ' foto...';
  btn.disabled = true;

  // Compress all local photos client-side before upload
  // (avoids PHP upload_max_filesize issues on shared/school servers)
  const localPhotos = editorPhotos.filter(p => !p.galleryUrl && p.src.startsWith('data:'));
  const galUrls2    = editorPhotos.filter(p => p.galleryUrl).map(p => p.galleryUrl);

  btn.textContent = localPhotos.length > 0
    ? 'Saspiež ' + localPhotos.length + ' foto...'
    : 'Pievieno...';

  // Compress all photos in parallel then upload
  Promise.all(localPhotos.map(function(p) {
    return compressImage(p.src, 1600, 1600, 0.82);
  })).then(function(compressed) {
    const fd2 = new FormData();
    fd2.append('add_photo_to_cart', '1');
    fd2.append('produkts', editorProduct.title + (editorProduct.izmers ? ' ' + editorProduct.izmers : '') + ' — €' + parseFloat(editorProduct.price).toFixed(0));
    fd2.append('cena', editorProduct.price);
    const deliveryAddr = (document.getElementById('deliveryAddrField')?.value || '').trim();
    const notesVal = document.getElementById('orderNotes').value.trim();
    const fullNotes = [notesVal, deliveryAddr ? 'Piegādes adrese: ' + deliveryAddr : ''].filter(Boolean).join(' | ');
    fd2.append('notes', fullNotes);
    // Send guest email if not logged in
    const guestEmailEl = document.getElementById('guestEmailField');
    if (guestEmailEl && guestEmailEl.value.trim()) {
      fd2.append('guest_email', guestEmailEl.value.trim());
    }
    if (galUrls2.length) fd2.append('gallery_urls', JSON.stringify(galUrls2));

    compressed.forEach(function(dataUrl, i) {
      fd2.append('fotos[]', dataURLtoBlob(dataUrl), 'foto_' + (i+1) + '.jpg');
    });

    btn.textContent = 'Augšupielādē...';

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/4pt/blazkova/lumina/Lumina/veikals.php');

    xhr.upload.addEventListener('progress', function(e) {
      if (e.lengthComputable) {
        const pct = Math.round(e.loaded / e.total * 100);
        btn.textContent = 'Augšupielādē... ' + pct + '%';
      }
    });

    xhr.onload = function() {
      let data;
      try { data = JSON.parse(xhr.responseText); }
      catch(e) { data = {ok: false, error: 'Servera kļūda'}; }
      handleCartResponse(data);
    };

    xhr.onerror = function() {
      btn.textContent = 'Pievienot grozam →';
      btn.disabled = false;
      showToast('Savienojuma kļūda. Mēģiniet vēlreiz.', 'error');
    };

    xhr.send(fd2);
  });
}

function handleCartResponse(data) {
  const btn = document.getElementById('orderBtn');
  if (data.ok) {
    document.getElementById('cartCount').textContent = data.count;
    closeModal('photoEditorModal');
    showToast((editorProduct ? editorProduct.title : 'Prece') + ' pievienots grozam ✓', 'success');
    // Reset editor state
    editorPhotos = []; currentPhotoIdx = 0; albumSpread = 0; editorProduct = null;
    document.querySelectorAll('.gal-pick').forEach(el => {
      el.style.borderColor = 'transparent';
      const chk = el.querySelector('.gal-pick-check');
      if (chk) chk.style.display = 'none';
    });
    clearPreview();
    const thumbs = document.getElementById('uploadedThumbs');
    if (thumbs) thumbs.innerHTML = '';
    const notes = document.getElementById('orderNotes');
    if (notes) notes.value = '';
    const guestField = document.getElementById('guestEmailField');
    if (guestField) guestField.value = '';
    if (typeof checkEditorReady === 'function') checkEditorReady();
    if (typeof updateEditorCount === 'function') updateEditorCount();
  } else {
    showToast(data.error || 'Kļūda, mēģiniet vēlreiz', 'error');
  }
  btn.textContent = 'Pievienot grozam →';
  btn.disabled = false;
}

function dataURLtoBlob(d) {
  const a=d.split(','), m=a[0].match(/:(.*?);/)[1], b=atob(a[1]);
  let n=b.length; const u=new Uint8Array(n);
  while(n--) u[n]=b.charCodeAt(n);
  return new Blob([u],{type:m});
}

// Compress image to max 1600px wide and JPEG 0.82 quality before upload
// Reduces 5MB photo → ~300-500KB, making 30 photos fit in ~15MB (within default limits)
function compressImage(dataUrl, maxW, maxH, quality) {
  maxW = maxW || 1600; maxH = maxH || 1600; quality = quality || 0.82;
  return new Promise(function(resolve) {
    const img = new Image();
    img.onload = function() {
      let w = img.width, h = img.height;
      if (w > maxW || h > maxH) {
        const ratio = Math.min(maxW/w, maxH/h);
        w = Math.round(w * ratio);
        h = Math.round(h * ratio);
      }
      const canvas = document.createElement('canvas');
      canvas.width = w; canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(img, 0, 0, w, h);
      resolve(canvas.toDataURL('image/jpeg', quality));
    };
    img.onerror = function() { resolve(dataUrl); }; // fallback: original
    img.src = dataUrl;
  });
}

// ── DRAG & TOUCH ──────────────────────────────────────
const pc = document.getElementById('photoContainer');
if (pc) {
  pc.addEventListener('mousedown', e => {
    if (!editorPhotos.length || editorProduct?.tips==='albums') return;
    isDragging=true;
    dragStart.x=e.clientX-parseInt(document.getElementById('posX').value||0);
    dragStart.y=e.clientY-parseInt(document.getElementById('posY').value||0);
    pc.style.cursor='grabbing'; e.preventDefault();
  });
  pc.addEventListener('touchstart', e => {
    if (!editorPhotos.length || editorProduct?.tips==='albums') return;
    isDragging=true;
    const t=e.touches[0];
    dragStart.x=t.clientX-parseInt(document.getElementById('posX').value||0);
    dragStart.y=t.clientY-parseInt(document.getElementById('posY').value||0);
  },{passive:true});
  pc.addEventListener('wheel', e => {
    if (!editorPhotos.length || editorProduct?.tips==='albums') return;
    e.preventDefault();
    const s=document.getElementById('zoomSlider');
    s.value=Math.max(50,Math.min(300,parseInt(s.value)-e.deltaY*0.3));
    applyTransform();
  },{passive:false});
}
document.addEventListener('mousemove', e => {
  if (!isDragging) return;
  document.getElementById('posX').value=e.clientX-dragStart.x;
  document.getElementById('posY').value=e.clientY-dragStart.y;
  applyTransform();
});
document.addEventListener('mouseup', () => { isDragging=false; if(pc) pc.style.cursor='grab'; });
document.addEventListener('touchmove', e => {
  if (!isDragging) return;
  const t=e.touches[0];
  document.getElementById('posX').value=t.clientX-dragStart.x;
  document.getElementById('posY').value=t.clientY-dragStart.y;
  applyTransform();
},{passive:true});
document.addEventListener('touchend', () => isDragging=false);

// Drag & drop into dropzone
const dz = document.getElementById('editorDropzone');
if (dz) {
  dz.addEventListener('dragover', e=>{e.preventDefault();dz.classList.add('drag-over');});
  dz.addEventListener('dragleave',()=>dz.classList.remove('drag-over'));
  dz.addEventListener('drop', e=>{
    e.preventDefault();dz.classList.remove('drag-over');
    addFiles(Array.from(e.dataTransfer.files).filter(f=>f.type.startsWith('image/')));
  });
}

<?php if(isset($_GET['paid'])): ?>window.addEventListener('DOMContentLoaded',()=>{document.getElementById('cartCount').textContent='0';openModal('successModal');});<?php endif; ?>
<?php if(isset($_GET['cancelled'])): ?>window.addEventListener('DOMContentLoaded',()=>showToast('Maksājums atcelts.','error'));<?php endif; ?>
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
