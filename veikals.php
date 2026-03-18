<?php
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

// Handle upload/order
$uploadSuccess = '';
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_order'])) {
  $produkts = escape($savienojums, $_POST['produkts'] ?? '');
  $notes    = escape($savienojums, $_POST['notes'] ?? '');
  $cropData = escape($savienojums, $_POST['crop_data'] ?? '');
  $klienta_id = isset($_SESSION['klients_id']) ? (int)$_SESSION['klients_id'] : 0;
  $galUrl   = escape($savienojums, $_POST['gallery_url'] ?? '');

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
    $klientaEmail = $_SESSION['klients_epasts'] ?? '';
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

// Products
$result = mysqli_query($savienojums, "SELECT * FROM preces WHERE aktivs = 1 ORDER BY bestseller DESC, id ASC");
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
.editor-topbar{display:flex;justify-content:space-between;align-items:center;padding:16px 28px;border-bottom:1px solid var(--border);background:var(--white);position:sticky;top:0;z-index:10;}
.editor-layout{display:grid;grid-template-columns:320px 1fr;min-height:calc(100vh - 60px);}
.editor-sidebar{padding:28px 24px;border-right:1px solid var(--border);overflow-y:auto;background:#faf9f7;}
.editor-step{margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid var(--border);}
.editor-step:last-child{border-bottom:none;}
.editor-step-num{font-size:10px;color:var(--gold);letter-spacing:3px;text-transform:uppercase;margin-bottom:6px;}
.editor-step-title{font-family:'Cormorant Garamond',serif;font-size:18px;color:var(--ink);margin-bottom:14px;}
.editor-dropzone{border:2px dashed var(--border);border-radius:10px;padding:28px 16px;text-align:center;cursor:pointer;transition:.2s;background:var(--white);}
.editor-dropzone:hover,.editor-dropzone.drag-over{border-color:var(--gold);background:rgba(184,151,90,.04);}
.dropzone-icon{font-size:32px;margin-bottom:8px;}
.dropzone-text{font-size:14px;color:var(--ink);margin-bottom:4px;}
.dropzone-sub{font-size:11px;color:var(--grey);}
.editor-tabs{display:flex;gap:0;margin-bottom:14px;border:1px solid var(--border);border-radius:6px;overflow:hidden;}
.editor-tab{flex:1;padding:8px;font-size:12px;border:none;background:transparent;cursor:pointer;color:var(--grey);transition:.2s;}
.editor-tab.active{background:var(--gold);color:var(--white);}
.product-options{display:flex;flex-direction:column;gap:8px;}
.product-option{display:flex;border:1.5px solid var(--border);border-radius:8px;padding:12px 14px;cursor:pointer;transition:.2s;}
.product-option:hover{border-color:var(--gold);}
.product-option.selected{border-color:var(--gold);background:rgba(184,151,90,.06);}
.product-option input{display:none;}
.product-option-name{font-size:13px;font-weight:500;color:var(--ink);}
.product-option-price{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--gold);line-height:1;}
.product-option-desc{font-size:11px;color:var(--grey);margin-top:2px;}
.editor-controls{display:flex;flex-direction:column;gap:10px;padding:18px 22px;background:#faf9f7;border-top:1px solid var(--border);}
.control-group{display:flex;align-items:center;gap:10px;}
.control-label{font-size:11px;color:var(--grey);width:100px;flex-shrink:0;}
.control-val{font-size:11px;color:var(--gold);width:36px;text-align:right;flex-shrink:0;}
.editor-slider{flex:1;-webkit-appearance:none;height:3px;border-radius:2px;background:var(--border);outline:none;}
.editor-slider::-webkit-slider-thumb{-webkit-appearance:none;width:16px;height:16px;border-radius:50%;background:var(--gold);cursor:pointer;}
.editor-btn{padding:7px 14px;font-size:11px;border:1px solid var(--border);background:var(--white);border-radius:5px;cursor:pointer;color:var(--grey);transition:.2s;}
.editor-btn:hover{border-color:var(--gold);color:var(--gold);}
/* Dark sliders for editor panel */
.dark-slider{-webkit-appearance:none;appearance:none;height:4px;border-radius:2px;background:rgba(255,255,255,.12);outline:none;cursor:pointer;transition:background .2s;}
.dark-slider:hover{background:rgba(255,255,255,.2);}
.dark-slider::-webkit-slider-thumb{-webkit-appearance:none;width:18px;height:18px;border-radius:50%;background:var(--gold,#B8975A);cursor:pointer;border:2px solid #1a1a1a;box-shadow:0 0 0 1px rgba(184,151,90,.4);}
.dark-slider::-moz-range-thumb{width:18px;height:18px;border-radius:50%;background:var(--gold,#B8975A);cursor:pointer;border:2px solid #1a1a1a;}
/* Wall preview */
.editor-preview-area{display:flex;flex-direction:column;background:#ece9e4;}
.editor-preview-label{text-align:center;font-size:10px;letter-spacing:3px;color:rgba(0,0,0,.3);text-transform:uppercase;padding:14px;flex-shrink:0;}
.wall-scene{flex:1;display:flex;align-items:center;justify-content:center;min-height:420px;padding:40px;position:relative;}
.wall-bg{position:absolute;inset:0;background:linear-gradient(160deg,#e8e3dc 0%,#d5cfc7 100%);}
.wall-frame-wrapper{position:relative;z-index:2;}
.wall-frame{background:var(--white);box-shadow:0 20px 60px rgba(0,0,0,.22),0 4px 12px rgba(0,0,0,.12);border:14px solid #f5f3ef;position:relative;width:280px;}
.frame-canvas-area{width:100%;aspect-ratio:3/4;overflow:hidden;position:relative;background:#111;cursor:grab;}
.frame-canvas-area:active{cursor:grabbing;}
.photo-container{position:absolute;inset:0;overflow:hidden;}
#photoCanvas{position:absolute;width:100%;height:100%;object-fit:cover;top:0;left:0;transform-origin:center center;}
.photo-placeholder{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:#222;}
.frame-shadow{position:absolute;bottom:-18px;left:10%;right:10%;height:16px;background:rgba(0,0,0,.15);filter:blur(10px);border-radius:50%;}
/* Gallery thumbs */
.gallery-thumb{position:relative;aspect-ratio:1;overflow:hidden;border-radius:6px;cursor:pointer;border:2px solid transparent;transition:.2s;}
.gallery-thumb:hover{border-color:var(--gold);}
.gallery-thumb img{width:100%;height:100%;object-fit:cover;}
.gallery-thumb-overlay{position:absolute;inset:0;background:rgba(184,151,90,.7);color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;}
.gallery-thumb:hover .gallery-thumb-overlay{opacity:1;}
.gallery-picker-thumb{position:relative;aspect-ratio:1;overflow:hidden;border-radius:8px;cursor:pointer;border:2px solid transparent;transition:.2s;}
.gallery-picker-thumb:hover{border-color:var(--gold);}
.gallery-picker-thumb img{width:100%;height:100%;object-fit:cover;}
.gallery-picker-overlay{position:absolute;inset:0;background:rgba(184,151,90,.75);color:#fff;font-size:13px;display:flex;align-items:center;justify-content:center;opacity:0;transition:.2s;}
.gallery-picker-thumb:hover .gallery-picker-overlay{opacity:1;}
@media(max-width:800px){
  .editor-layout{grid-template-columns:1fr;}
  .wall-frame{width:200px;}
  .editor-sidebar{border-right:none;border-bottom:1px solid var(--border);}
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
    <div class="product-card reveal reveal-delay-<?= ($i%3)+1 ?>" onclick="openProductModal(<?= $i ?>)">
      <div class="product-img">
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['nosaukums']) ?>">
        <?php if ($p['bestseller']): ?><div class="product-tag">Bestseller</div><?php endif; ?>
        <div class="product-overlay">
          <button class="add-cart-btn" onclick="event.stopPropagation();addToCartAjax(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['nosaukums'])) ?>')">Pievienot →</button>
        </div>
      </div>
      <div class="product-name"><?= htmlspecialchars($p['nosaukums']) ?></div>
      <div class="product-price">€<?= number_format($p['cena'],0) ?></div>
      <div class="product-sub"><?= htmlspecialchars($p['kategorija']) ?></div>
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
      Augšupielādējiet fotogrāfiju, izvēlieties produktu un skatiet priekšskatījumu reāllaikā. Velciet, tālummaino un koriģējiet.
    </p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
      <button class="btn-primary" onclick="openPhotoEditor()">✦ Atvērt Foto Editoru</button>
      <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
      <button class="btn-outline" onclick="openModal('galleryPickerModal')">Izvēlēties no manām galerijām</button>
      <?php elseif (!isset($_SESSION['klients_id'])): ?>
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
        <button id="prodModalBtn" class="btn-primary">Pievienot Grozam →</button>
        <button class="btn-outline" onclick="closeModal('productModal');openPhotoEditor()">Pielāgot ar manu foto →</button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════ -->
<!-- FOTO EDITORS — fullscreen modal -->
<!-- ══════════════════════════════════════════════════ -->
<div class="modal-overlay" id="photoEditorModal" style="align-items:flex-start;padding:0;overflow:hidden;">
  <div style="background:var(--white);width:100%;height:100vh;display:flex;flex-direction:column;">

    <!-- Topbar -->
    <div class="editor-topbar">
      <div style="font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--ink);">✦ Foto Editors</div>
      <div style="display:flex;gap:12px;align-items:center;">
        <button class="btn-outline" style="padding:8px 18px;font-size:12px;" onclick="closeModal('photoEditorModal')">← Atpakaļ</button>
        <button class="btn-primary" style="padding:8px 22px;font-size:12px;" id="orderBtn" onclick="submitOrder()" disabled>Pasūtīt →</button>
      </div>
    </div>

    <!-- Main editor area -->
    <div class="editor-layout" style="flex:1;overflow:hidden;">

      <!-- LEFT: Controls -->
      <div class="editor-sidebar" style="overflow-y:auto;height:100%;">

        <!-- Step 1 -->
        <div class="editor-step">
          <div class="editor-step-num">01</div>
          <div class="editor-step-title">Pievienojiet foto</div>

          <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
          <div class="editor-tabs">
            <button class="editor-tab active" onclick="switchTab('upload')">Augšupielādēt</button>
            <button class="editor-tab" onclick="switchTab('gallery')">Manas galerijas</button>
          </div>
          <?php endif; ?>

          <div id="tabUpload">
            <div class="editor-dropzone" id="editorDropzone" onclick="document.getElementById('editorFileInput').click()">
              <input type="file" id="editorFileInput" accept="image/*" style="display:none;" onchange="loadPhoto(this.files[0])">
              <div class="dropzone-icon">📷</div>
              <div class="dropzone-text">Ievilciet foto šeit</div>
              <div class="dropzone-sub">vai noklikšķiniet · JPG, PNG · max 15MB</div>
            </div>
            <div id="photoLoaded" style="display:none;padding:10px;background:rgba(184,151,90,.08);border-radius:8px;border:1px solid rgba(184,151,90,.3);font-size:12px;color:var(--gold);text-align:center;">
              ✓ Foto ielādēts — koriģējiet labajā pusē
            </div>
          </div>

          <?php if (isset($_SESSION['klients_id']) && !empty($clientPhotos)): ?>
          <div id="tabGallery" style="display:none;">
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;max-height:260px;overflow-y:auto;">
              <?php foreach ($clientPhotos as $cp):
                $ps = filter_var($cp['attels_url']??'', FILTER_VALIDATE_URL) ? $cp['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/'.($cp['attels_url']??'');
              ?>
              <div class="gallery-thumb" onclick="loadPhotoFromUrl('<?= htmlspecialchars($ps) ?>')">
                <img src="<?= htmlspecialchars($ps) ?>" alt="">
                <div class="gallery-thumb-overlay">Izvēlēties</div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Step 2 -->
        <div class="editor-step">
          <div class="editor-step-num">02</div>
          <div class="editor-step-title">Izvēlieties produktu</div>
          <div class="product-options">
            <label class="product-option" onclick="selectProduct(this,'Fotodruka 30×40 cm','29','print_30x40')">
              <input type="radio" name="pt">
              <div class="product-option-inner">
                <div class="product-option-name">Fotodruka 30×40</div>
                <div class="product-option-price">€29</div>
                <div class="product-option-desc">Augstas kvalitātes drukas papīrs</div>
              </div>
            </label>
            <label class="product-option" onclick="selectProduct(this,'Canvas 50×70 cm','79','canvas_50x70')">
              <input type="radio" name="pt">
              <div class="product-option-inner">
                <div class="product-option-name">Canvas 50×70</div>
                <div class="product-option-price">€79</div>
                <div class="product-option-desc">Audekls uz rāmja · gatavs karšanai</div>
              </div>
            </label>
            <label class="product-option" onclick="selectProduct(this,'Canvas 80×120 cm','139','canvas_80x120')">
              <input type="radio" name="pt">
              <div class="product-option-inner">
                <div class="product-option-name">Canvas 80×120</div>
                <div class="product-option-price">€139</div>
                <div class="product-option-desc">Liela formāta audekls</div>
              </div>
            </label>
            <label class="product-option" onclick="selectProduct(this,'Sienas panelis 60×90 cm','149','panel_60x90')">
              <input type="radio" name="pt">
              <div class="product-option-inner">
                <div class="product-option-name">Sienas panelis 60×90</div>
                <div class="product-option-price">€149</div>
                <div class="product-option-desc">Alumīnija panelis · spīdīgs</div>
              </div>
            </label>
            <label class="product-option" onclick="selectProduct(this,'Fotogrāmata 30×30 cm','129','book_30x30')">
              <input type="radio" name="pt">
              <div class="product-option-inner">
                <div class="product-option-name">Fotogrāmata 30×30</div>
                <div class="product-option-price">€129</div>
                <div class="product-option-desc">Cietie vāki · 30 lapas iekļautas</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="editor-step">
          <div class="editor-step-num">03</div>
          <div class="editor-step-title">Papildu vēlmes</div>
          <textarea id="orderNotes" class="form-textarea" style="height:80px;width:100%;box-sizing:border-box;font-size:13px;" placeholder="Melnbalta versija, īpašas piezīmes..."></textarea>
          <div style="margin-top:12px;padding:14px;background:var(--cream2,#f5f3ef);border-radius:6px;">
            <div style="font-size:10px;color:var(--grey);text-transform:uppercase;letter-spacing:2px;margin-bottom:6px;">Kopsavilkums</div>
            <div id="summaryProduct" style="font-size:13px;color:var(--ink);margin-bottom:4px;">— izvēlieties produktu —</div>
            <div id="summaryPrice" style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--gold);"></div>
          </div>
        </div>

      </div><!-- /sidebar -->

      <!-- RIGHT: Wall preview -->
      <div class="editor-preview-area" style="overflow:hidden;display:flex;flex-direction:column;height:100%;">
        <div class="editor-preview-label">Priekšskatījums reāllaikā — velciet foto ar peli</div>
        
        <div class="wall-scene" id="wallScene" style="flex:1;">
          <div class="wall-bg"></div>
          <div class="wall-frame-wrapper" id="wallFrameWrapper">
            <div class="wall-frame" id="wallFrame">
              <div class="frame-canvas-area" id="frameCanvasArea">
                <div class="photo-container" id="photoContainer" style="cursor:grab;">
                  <canvas id="photoCanvas" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;transform-origin:center center;"></canvas>
                  <div class="photo-placeholder" id="photoPlaceholder">
                    <div style="text-align:center;color:rgba(255,255,255,.35);font-size:13px;">
                      <div style="font-size:38px;margin-bottom:10px;">🖼️</div>
                      Augšupielādējiet<br>fotogrāfiju
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="frame-shadow"></div>
          </div>
        </div>

        <!-- Sliders -->
        <div class="editor-controls" id="editorControls" style="display:none;flex-direction:column;gap:0;border-top:2px solid var(--gold,#B8975A);">
          <div style="padding:10px 20px 6px;background:#1C1C1C;">
            <span style="font-size:9px;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.4);">Pielāgot fotogrāfiju</span>
          </div>
          <div style="background:#1a1a1a;padding:14px 20px;display:flex;flex-direction:column;gap:12px;">
            <!-- Zoom -->
            <div class="control-group" style="display:grid;grid-template-columns:28px 1fr 44px;gap:10px;align-items:center;">
              <div style="color:rgba(255,255,255,.5);font-size:15px;text-align:center;" title="Tālummaiņa">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35M11 8v6M8 11h6"/></svg>
              </div>
              <input type="range" id="zoomSlider" min="50" max="300" value="100" class="editor-slider dark-slider" oninput="applyTransform()">
              <span id="zoomVal" style="font-size:11px;color:var(--gold,#B8975A);font-family:monospace;text-align:right;white-space:nowrap;">100%</span>
            </div>
            <!-- Horizontal -->
            <div class="control-group" style="display:grid;grid-template-columns:28px 1fr 44px;gap:10px;align-items:center;">
              <div style="color:rgba(255,255,255,.5);font-size:15px;text-align:center;" title="Horizontāli">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M15 8l4 4-4 4M9 8l-4 4 4 4"/></svg>
              </div>
              <input type="range" id="posX" min="-200" max="200" value="0" class="editor-slider dark-slider" oninput="applyTransform()">
              <span id="posXVal" style="font-size:11px;color:rgba(255,255,255,.4);font-family:monospace;text-align:right;white-space:nowrap;">0</span>
            </div>
            <!-- Vertical -->
            <div class="control-group" style="display:grid;grid-template-columns:28px 1fr 44px;gap:10px;align-items:center;">
              <div style="color:rgba(255,255,255,.5);font-size:15px;text-align:center;" title="Vertikāli">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M8 15l4 4 4-4M8 9l4-4 4 4"/></svg>
              </div>
              <input type="range" id="posY" min="-200" max="200" value="0" class="editor-slider dark-slider" oninput="applyTransform()">
              <span id="posYVal" style="font-size:11px;color:rgba(255,255,255,.4);font-family:monospace;text-align:right;white-space:nowrap;">0</span>
            </div>
            <!-- Rotation -->
            <div class="control-group" style="display:grid;grid-template-columns:28px 1fr 44px;gap:10px;align-items:center;">
              <div style="color:rgba(255,255,255,.5);font-size:15px;text-align:center;" title="Rotācija">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38"/></svg>
              </div>
              <input type="range" id="rotSlider" min="-180" max="180" value="0" class="editor-slider dark-slider" oninput="applyTransform()">
              <span id="rotVal" style="font-size:11px;color:var(--gold,#B8975A);font-family:monospace;text-align:right;white-space:nowrap;">0°</span>
            </div>
          </div>
          <div style="display:flex;gap:1px;background:#111;">
            <button class="editor-btn dark-btn" onclick="resetTransform()" style="flex:1;padding:10px;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;border:none;background:#252525;color:rgba(255,255,255,.45);cursor:pointer;transition:.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.45)'">
              Atiestatīt
            </button>
            <button class="editor-btn dark-btn" onclick="fitPhoto()" style="flex:1;padding:10px;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;border:none;background:#2a2520;color:var(--gold,#B8975A);cursor:pointer;transition:.2s;" onmouseover="this.style.background='#B8975A';this.style.color='#fff'" onmouseout="this.style.background='#2a2520';this.style.color='var(--gold,#B8975A)'">
              Pielāgot rāmim
            </button>
          </div>
        </div>

      </div><!-- /preview -->
    </div><!-- /editor-layout -->

    <!-- Hidden form -->
    <form id="orderForm" method="POST" enctype="multipart/form-data" style="display:none;">
      <input type="hidden" name="upload_order" value="1">
      <input type="hidden" name="produkts" id="formProdukts">
      <input type="hidden" name="notes" id="formNotes">
      <input type="hidden" name="crop_data" id="formCropData">
      <input type="hidden" name="gallery_url" id="formGalleryUrl">
      <input type="file" name="foto" id="formFotoInput" accept="image/*">
    </form>

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
  return['id'=>$p['id'],'img'=>$src,'cat'=>$p['kategorija'],'title'=>$p['nosaukums'],'desc'=>$p['apraksts'],'price'=>$p['cena']];
},$products)) ?>;

// ── Product modal ────────────────────────
function openProductModal(i){
  const p=products[i];
  document.getElementById('prodModalImg').src=p.img;
  document.getElementById('prodModalCat').textContent=p.cat;
  document.getElementById('prodModalTitle').textContent=p.title;
  document.getElementById('prodModalDesc').textContent=p.desc;
  document.getElementById('prodModalPrice').textContent='€'+parseFloat(p.price).toFixed(0);
  document.getElementById('prodModalBtn').onclick=()=>{addToCartAjax(p.id,p.title);closeModal('productModal');};
  openModal('productModal');
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
let loadedImage=null,loadedFile=null,selectedProduct=null,isDragging=false,dragStart={x:0,y:0};

const productAspects={'print_30x40':'3/4','canvas_50x70':'5/7','canvas_80x120':'2/3','panel_60x90':'2/3','book_30x30':'1/1'};

function openPhotoEditor(){openModal('photoEditorModal');}

function switchTab(tab){
  document.getElementById('tabUpload').style.display=tab==='upload'?'block':'none';
  const tg=document.getElementById('tabGallery');
  if(tg)tg.style.display=tab==='gallery'?'block':'none';
  document.querySelectorAll('.editor-tab').forEach((t,i)=>t.classList.toggle('active',(i===0&&tab==='upload')||(i===1&&tab==='gallery')));
}

function loadPhoto(file){
  if(!file)return;
  loadedFile=file;
  const dt=new DataTransfer();dt.items.add(file);
  document.getElementById('formFotoInput').files=dt.files;
  const r=new FileReader();
  r.onload=e=>applyPhotoToEditor(e.target.result);
  r.readAsDataURL(file);
}

function loadPhotoFromUrl(url){
  loadedImage=url;
  loadedFile=null;
  document.getElementById('formGalleryUrl').value=url;
  applyPhotoToEditor(url);
}

function selectFromGallery(url){
  closeModal('galleryPickerModal');
  loadPhotoFromUrl(url);
  openPhotoEditor();
}

function applyPhotoToEditor(src){
  loadedImage=src;
  const img=new Image();
  img.crossOrigin='anonymous';
  img.onload=()=>{
    const canvas=document.getElementById('photoCanvas');
    canvas.width=img.naturalWidth;canvas.height=img.naturalHeight;
    canvas.getContext('2d').drawImage(img,0,0);
    canvas.style.display='block';
    document.getElementById('photoPlaceholder').style.display='none';
    document.getElementById('editorDropzone').style.display='none';
    document.getElementById('photoLoaded').style.display='block';
    document.getElementById('editorControls').style.display='flex';
    resetTransform();checkOrderReady();
    showToast('Foto ielādēts! Velciet ar peli vai lietojiet slīdņus.','success');
  };
  img.onerror=()=>{
    // If CORS fails, still show as background
    const container=document.getElementById('photoContainer');
    container.style.backgroundImage=`url('${src}')`;
    container.style.backgroundSize='cover';
    container.style.backgroundPosition='center';
    document.getElementById('photoPlaceholder').style.display='none';
    document.getElementById('editorDropzone').style.display='none';
    document.getElementById('photoLoaded').style.display='block';
    document.getElementById('editorControls').style.display='flex';
    checkOrderReady();
    showToast('Foto ielādēts!','success');
  };
  img.src=src;
}

function applyTransform(){
  const canvas=document.getElementById('photoCanvas');
  if(!canvas)return;
  const zoom=document.getElementById('zoomSlider').value;
  const px=document.getElementById('posX').value;
  const py=document.getElementById('posY').value;
  const rot=document.getElementById('rotSlider').value;
  document.getElementById('zoomVal').textContent=zoom+'%';
  document.getElementById('rotVal').textContent=rot+'°';
  const pxv=document.getElementById('posXVal');
  const pyv=document.getElementById('posYVal');
  if(pxv) pxv.textContent=px;
  if(pyv) pyv.textContent=py;
  canvas.style.transform=`translate(${px}px,${py}px) scale(${zoom/100}) rotate(${rot}deg)`;
  const container=document.getElementById('photoContainer');
  if(container && container.style.backgroundImage){
    container.style.backgroundPosition=`calc(50% + ${px}px) calc(50% + ${py}px)`;
    container.style.backgroundSize=`${zoom}%`;
  }
}

function resetTransform(){
  ['zoomSlider','posX','posY','rotSlider'].forEach(id=>{
    document.getElementById(id).value=id==='zoomSlider'?100:0;
  });
  applyTransform();
}

function fitPhoto(){resetTransform();}

function selectProduct(label,name,price,type){
  document.querySelectorAll('.product-option').forEach(l=>l.classList.remove('selected'));
  label.classList.add('selected');
  selectedProduct={name,price,type};
  const aspect=productAspects[type]||'3/4';
  document.getElementById('frameCanvasArea').style.aspectRatio=aspect;
  document.getElementById('summaryProduct').textContent=name;
  document.getElementById('summaryPrice').textContent='€'+price;
  checkOrderReady();
}

function checkOrderReady(){
  const ready=loadedImage&&selectedProduct;
  const btn=document.getElementById('orderBtn');
  btn.disabled=!ready;btn.style.opacity=ready?'1':'0.4';
}

function submitOrder(){
  if(!loadedImage||!selectedProduct)return;
  const cropData=JSON.stringify({
    zoom:document.getElementById('zoomSlider').value,
    posX:document.getElementById('posX').value,
    posY:document.getElementById('posY').value,
    rot:document.getElementById('rotSlider').value,
    product:selectedProduct.type,
    src:loadedFile?null:loadedImage
  });
  document.getElementById('formProdukts').value=selectedProduct.name+' — €'+selectedProduct.price;
  document.getElementById('formNotes').value=document.getElementById('orderNotes').value;
  document.getElementById('formCropData').value=cropData;
  document.getElementById('orderForm').submit();
}

// ── Drag to move photo ───────────────────
const pc=document.getElementById('photoContainer');
pc.addEventListener('mousedown',e=>{
  if(!loadedImage)return;
  isDragging=true;
  dragStart.x=e.clientX-parseInt(document.getElementById('posX').value||0);
  dragStart.y=e.clientY-parseInt(document.getElementById('posY').value||0);
  pc.style.cursor='grabbing';e.preventDefault();
});
document.addEventListener('mousemove',e=>{
  if(!isDragging)return;
  document.getElementById('posX').value=Math.max(-200,Math.min(200,e.clientX-dragStart.x));
  document.getElementById('posY').value=Math.max(-200,Math.min(200,e.clientY-dragStart.y));
  applyTransform();
});
document.addEventListener('mouseup',()=>{isDragging=false;pc.style.cursor='grab';});

// Touch
pc.addEventListener('touchstart',e=>{
  if(!loadedImage)return;isDragging=true;
  const t=e.touches[0];
  dragStart.x=t.clientX-parseInt(document.getElementById('posX').value||0);
  dragStart.y=t.clientY-parseInt(document.getElementById('posY').value||0);
},{passive:true});
document.addEventListener('touchmove',e=>{
  if(!isDragging)return;
  const t=e.touches[0];
  document.getElementById('posX').value=Math.max(-200,Math.min(200,t.clientX-dragStart.x));
  document.getElementById('posY').value=Math.max(-200,Math.min(200,t.clientY-dragStart.y));
  applyTransform();
},{passive:true});
document.addEventListener('touchend',()=>isDragging=false);

// Scroll to zoom
pc.addEventListener('wheel',e=>{
  if(!loadedImage)return;e.preventDefault();
  const s=document.getElementById('zoomSlider');
  s.value=Math.max(50,Math.min(300,parseInt(s.value)-e.deltaY*0.3));
  applyTransform();
},{passive:false});

// Drag & drop file
const dz=document.getElementById('editorDropzone');
if(dz){
  dz.addEventListener('dragover',e=>{e.preventDefault();dz.classList.add('drag-over');});
  dz.addEventListener('dragleave',()=>dz.classList.remove('drag-over'));
  dz.addEventListener('drop',e=>{
    e.preventDefault();dz.classList.remove('drag-over');
    const f=e.dataTransfer.files[0];
    if(f&&f.type.startsWith('image/'))loadPhoto(f);
  });
}

<?php if($uploadSuccess): ?>window.addEventListener('DOMContentLoaded',()=>openModal('successModal'));<?php endif; ?>
<?php if($uploadError): ?>window.addEventListener('DOMContentLoaded',()=>showToast('<?= addslashes($uploadError) ?>','error'));<?php endif; ?>
<?php if(isset($_GET['paid'])): ?>window.addEventListener('DOMContentLoaded',()=>{document.getElementById('cartCount').textContent='0';openModal('successModal');});<?php endif; ?>
<?php if(isset($_GET['cancelled'])): ?>window.addEventListener('DOMContentLoaded',()=>showToast('Maksājums atcelts. Grozs saglabāts.','error'));<?php endif; ?>
</script>
