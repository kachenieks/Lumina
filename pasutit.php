<?php
ob_start();
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
mysqli_report(MYSQLI_REPORT_OFF);

$pageTitle = 'Pasūtīt';
$extraCss  = 'pasutit.css';

// ── Handle AJAX: add order to cart ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_order_to_cart'])) {
  header('Content-Type: application/json');
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

  $preceId  = (int)($_POST['prece_id'] ?? 0);
  $notes    = strip_tags($_POST['notes'] ?? '');
  $prece    = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM preces WHERE id=$preceId AND aktivs=1"));
  if (!$prece) { echo json_encode(['error' => 'Produkts nav atrasts']); exit; }

  $uploadDir = __DIR__ . '/uploads/pasutijumi/';
  if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

  $fotoUrls = [];
  $required = (int)$prece['foto_skaits'];

  // Handle uploaded files
  if (!empty($_FILES['fotos']['name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $k => $tmp) {
      if (!$tmp) continue;
      $ext = strtolower(pathinfo($_FILES['fotos']['name'][$k], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
      $fname = 'ord_' . uniqid() . '.' . $ext;
      if (move_uploaded_file($tmp, $uploadDir . $fname)) {
        $fotoUrls[] = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $fname;
      }
    }
  }
  // Gallery URLs from profile
  if (!empty($_POST['gallery_urls'])) {
    $gUrls = json_decode($_POST['gallery_urls'], true) ?: [];
    foreach ($gUrls as $u) if (filter_var($u, FILTER_VALIDATE_URL)) $fotoUrls[] = $u;
  }

  if (count($fotoUrls) < $required) {
    echo json_encode(['error' => "Nepieciešamas $required bildes, pievienoti " . count($fotoUrls)]);
    exit;
  }

  $cartKey = 'order_' . uniqid();
  $_SESSION['cart'][$cartKey] = [
    'qty'       => 1,
    'name'      => $prece['nosaukums'],
    'cena'      => (float)$prece['cena'],
    'is_foto'   => true,
    'foto_url'  => $fotoUrls[0] ?? '',
    'foto_urls' => json_encode($fotoUrls),
    'notes'     => $notes,
    'prece_id'  => $preceId,
  ];

  echo json_encode(['ok' => true, 'count' => array_sum(array_column($_SESSION['cart'], 'qty'))]);
  exit;
}

// ── Load all active products ──────────────────────────────
// Add missing columns safely
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS tips varchar(50) DEFAULT 'druka'");
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS foto_skaits int(3) DEFAULT 1");
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS izmers varchar(100) DEFAULT ''");
@mysqli_query($savienojums, "ALTER TABLE preces ADD COLUMN IF NOT EXISTS orientacija varchar(20) DEFAULT 'portrait'");

$result = mysqli_query($savienojums, "SELECT * FROM preces WHERE aktivs=1 ORDER BY kategorija, cena ASC");
$preces = [];
while ($r = mysqli_fetch_assoc($result)) $preces[] = $r;

// Client galleries
$clientPhotos = [];
if (isset($_SESSION['klients_id'])) {
  $kId = (int)$_SESSION['klients_id'];
  $gRes = mysqli_query($savienojums,
    "SELECT gf.attels_url, g.nosaukums as galNosaukums
     FROM galeriju_foto gf
     JOIN galerijas g ON gf.galerijas_id = g.id
     WHERE g.klienta_id = $kId
     ORDER BY g.id DESC, gf.seciba ASC");
  while ($r = mysqli_fetch_assoc($gRes)) $clientPhotos[] = $r;
}

// Group products by category
$grouped = [];
foreach ($preces as $p) {
  $cat = $p['kategorija'] ?: 'Citi';
  $grouped[$cat][] = $p;
}

include __DIR__ . '/includes/header.php';
?>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="section-label">Veikals</div>
  <h1>Pasūtīt <em>produktu</em></h1>
  <p>Izvēlies produktu, pievieno fotogrāfijas un pasūti. Piegāde 2–3 nedēļu laikā.</p>
</div>

<div class="pasutit-wrap">

  <!-- ── STEP 1: Choose product ── -->
  <div class="ps-step" id="step1">
    <div class="ps-step-header">
      <div class="ps-step-num">01</div>
      <h2 class="ps-step-title">Izvēlies produktu</h2>
    </div>

    <?php foreach ($grouped as $cat => $items): ?>
    <div class="ps-category">
      <div class="ps-cat-label"><?= htmlspecialchars($cat) ?></div>
      <div class="ps-products-grid">
        <?php foreach ($items as $p):
          $imgSrc = !empty($p['attels_url'])
            ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $p['attels_url'])
            : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=500&q=80';
          $fotoN  = (int)($p['foto_skaits'] ?? 1);
          $tips   = $p['tips'] ?? 'druka';
          $izmers = $p['izmers'] ?? '';
        ?>
        <div class="ps-product <?= $p['bestseller'] ? 'ps-product--best' : '' ?>"
             onclick="selectProduct(<?= $p['id'] ?>)"
             data-id="<?= $p['id'] ?>"
             data-name="<?= htmlspecialchars(addslashes($p['nosaukums'])) ?>"
             data-price="<?= $p['cena'] ?>"
             data-foto="<?= $fotoN ?>"
             data-tips="<?= htmlspecialchars($tips) ?>"
             data-izmers="<?= htmlspecialchars($izmers) ?>"
             data-orient="<?= htmlspecialchars($p['orientacija'] ?? 'portrait') ?>">
          <?php if ($p['bestseller']): ?>
          <div class="ps-best-badge">Populārākais</div>
          <?php endif; ?>
          <div class="ps-product-img">
            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['nosaukums']) ?>">
          </div>
          <div class="ps-product-info">
            <div class="ps-product-name"><?= htmlspecialchars($p['nosaukums']) ?></div>
            <?php if ($izmers): ?>
            <div class="ps-product-size"><?= htmlspecialchars($izmers) ?></div>
            <?php endif; ?>
            <div class="ps-product-meta">
              <?php if ($fotoN > 1): ?>
              <span class="ps-foto-count"><?= $fotoN ?> foto</span>
              <?php endif; ?>
              <span class="ps-product-price">€<?= number_format($p['cena'], 0) ?></span>
            </div>
            <?php if (!empty($p['apraksts'])): ?>
            <div class="ps-product-desc"><?= htmlspecialchars($p['apraksts']) ?></div>
            <?php endif; ?>
          </div>
          <div class="ps-select-indicator">✓ Izvēlēts</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- ── STEP 2: Upload photos ── -->
  <div class="ps-step ps-step--locked" id="step2">
    <div class="ps-step-header">
      <div class="ps-step-num">02</div>
      <h2 class="ps-step-title">Pievieno <span id="step2Title">fotogrāfijas</span></h2>
    </div>
    <div class="ps-step-body">

      <!-- Photo counter -->
      <div class="ps-photo-counter" id="photoCounter">
        <div class="ps-counter-bar">
          <div class="ps-counter-fill" id="counterFill"></div>
        </div>
        <div class="ps-counter-text" id="counterText">0 / 0 foto pievienoti</div>
      </div>

      <!-- Upload tabs -->
      <div class="ps-upload-tabs">
        <button class="ps-tab active" onclick="switchTab('upload', this)">
          Augšupielādēt
        </button>
        <?php if (!empty($clientPhotos)): ?>
        <button class="ps-tab" onclick="switchTab('gallery', this)">
          Manas galerijas (<?= count($clientPhotos) ?>)
        </button>
        <?php endif; ?>
      </div>

      <!-- Upload zone -->
      <div id="tabUpload" class="ps-tab-content">
        <div class="ps-dropzone" id="dropzone">
          <div class="ps-drop-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
          </div>
          <div class="ps-drop-title">Velciet bildes šeit vai klikšķiniet</div>
          <div class="ps-drop-sub">JPG, PNG, WEBP · Var pievienot vairākas uzreiz</div>
          <input type="file" id="fileInput" multiple accept="image/*" style="display:none">
        </div>
      </div>

      <!-- Gallery picker -->
      <?php if (!empty($clientPhotos)): ?>
      <div id="tabGallery" class="ps-tab-content" style="display:none;">
        <div class="ps-gallery-grid">
          <?php foreach ($clientPhotos as $cp):
            $pSrc = filter_var($cp['attels_url'] ?? '', FILTER_VALIDATE_URL)
              ? $cp['attels_url']
              : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . ($cp['attels_url'] ?? '');
          ?>
          <div class="ps-gallery-thumb" onclick="toggleGalleryPhoto(this, '<?= htmlspecialchars($pSrc) ?>')"
               data-url="<?= htmlspecialchars($pSrc) ?>">
            <img src="<?= htmlspecialchars($pSrc) ?>" alt="">
            <div class="ps-gallery-check">✓</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Photo preview grid -->
      <div class="ps-previews" id="photoPreviews"></div>

    </div>
  </div>

  <!-- ── STEP 3: Preview ── -->
  <div class="ps-step ps-step--locked" id="step3">
    <div class="ps-step-header">
      <div class="ps-step-num">03</div>
      <h2 class="ps-step-title">Priekšskatījums</h2>
    </div>
    <div class="ps-step-body">
      <div class="ps-preview-area" id="previewArea">
        <!-- Rendered by JS based on product type -->
      </div>
      <div class="ps-notes-wrap">
        <label class="ps-notes-label">Papildu vēlmes (neobligāts)</label>
        <textarea id="orderNotes" class="ps-notes" placeholder="Piemēram: melnbalta versija, apgriezums pa kreisi, īpaša piezīme..."></textarea>
      </div>
    </div>
  </div>

  <!-- ── STEP 4: Order summary ── -->
  <div class="ps-step ps-step--locked" id="step4">
    <div class="ps-step-header">
      <div class="ps-step-num">04</div>
      <h2 class="ps-step-title">Apstiprināt pasūtījumu</h2>
    </div>
    <div class="ps-step-body">
      <div class="ps-summary" id="orderSummary"></div>
      <button class="ps-order-btn" id="orderBtn" onclick="submitOrder()" disabled>
        Pievienot grozam →
      </button>
    </div>
  </div>

</div><!-- /pasutit-wrap -->

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const CLIENT_PHOTOS = <?= json_encode(array_map(function($cp) {
  return filter_var($cp['attels_url'] ?? '', FILTER_VALIDATE_URL)
    ? $cp['attels_url']
    : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . ($cp['attels_url'] ?? '');
}, $clientPhotos)) ?>;

let selectedProduct = null;
let uploadedFiles   = [];   // File objects
let galleryUrls     = [];   // selected gallery URLs
let allPhotoUrls    = [];   // combined preview URLs

// ── STEP 1: Select product ────────────────────────────────
function selectProduct(id) {
  document.querySelectorAll('.ps-product').forEach(el => el.classList.remove('ps-product--selected'));
  const el = document.querySelector(`.ps-product[data-id="${id}"]`);
  if (!el) return;
  el.classList.add('ps-product--selected');

  selectedProduct = {
    id:      id,
    name:    el.dataset.name,
    price:   parseFloat(el.dataset.price),
    fotos:   parseInt(el.dataset.foto),
    tips:    el.dataset.tips,
    izmers:  el.dataset.izmers,
    orient:  el.dataset.orient,
  };

  // Unlock step 2
  const s2 = document.getElementById('step2');
  s2.classList.remove('ps-step--locked');
  document.getElementById('step2Title').textContent =
    selectedProduct.fotos === 1 ? 'fotogrāfiju' : selectedProduct.fotos + ' fotogrāfijas';

  // Update counter
  updateCounter();
  s2.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── TABS ─────────────────────────────────────────────────
function switchTab(tab, btn) {
  document.querySelectorAll('.ps-tab-content').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.ps-tab').forEach(el => el.classList.remove('active'));
  document.getElementById('tab' + tab.charAt(0).toUpperCase() + tab.slice(1)).style.display = 'block';
  btn.classList.add('active');
}

// ── FILE UPLOAD ───────────────────────────────────────────
document.getElementById('dropzone').addEventListener('click', () => {
  document.getElementById('fileInput').click();
});

document.getElementById('dropzone').addEventListener('dragover', e => {
  e.preventDefault();
  document.getElementById('dropzone').classList.add('ps-dropzone--over');
});
document.getElementById('dropzone').addEventListener('dragleave', () => {
  document.getElementById('dropzone').classList.remove('ps-dropzone--over');
});
document.getElementById('dropzone').addEventListener('drop', e => {
  e.preventDefault();
  document.getElementById('dropzone').classList.remove('ps-dropzone--over');
  handleFiles(Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/')));
});
document.getElementById('fileInput').addEventListener('change', function() {
  handleFiles(Array.from(this.files));
  this.value = '';
});

function handleFiles(files) {
  const required = selectedProduct ? selectedProduct.fotos : 99;
  const canAdd   = required - uploadedFiles.length - galleryUrls.length;
  if (canAdd <= 0) { showToast('Nepieciešamais foto skaits jau sasniegts.', 'error'); return; }
  const toAdd = files.slice(0, canAdd);
  uploadedFiles.push(...toAdd);
  toAdd.forEach(f => {
    const reader = new FileReader();
    reader.onload = ev => {
      allPhotoUrls.push({ url: ev.target.result, type: 'file', file: f });
      renderPreviews();
      updateCounter();
      checkStep3();
    };
    reader.readAsDataURL(f);
  });
}

// ── GALLERY PICKER ────────────────────────────────────────
function toggleGalleryPhoto(el, url) {
  const required = selectedProduct ? selectedProduct.fotos : 99;
  if (el.classList.contains('ps-gallery-thumb--sel')) {
    el.classList.remove('ps-gallery-thumb--sel');
    galleryUrls = galleryUrls.filter(u => u !== url);
    allPhotoUrls = allPhotoUrls.filter(p => p.url !== url);
    renderPreviews();
    updateCounter();
    checkStep3();
  } else {
    if (galleryUrls.length + uploadedFiles.length >= required) {
      showToast('Nepieciešamais foto skaits jau sasniegts.', 'error'); return;
    }
    el.classList.add('ps-gallery-thumb--sel');
    galleryUrls.push(url);
    allPhotoUrls.push({ url, type: 'gallery' });
    renderPreviews();
    updateCounter();
    checkStep3();
  }
}

// ── RENDER PHOTO PREVIEWS ─────────────────────────────────
function renderPreviews() {
  const container = document.getElementById('photoPreviews');
  if (!allPhotoUrls.length) { container.innerHTML = ''; return; }
  container.innerHTML = allPhotoUrls.map((p, i) => `
    <div class="ps-preview-thumb">
      <img src="${p.url}" alt="">
      <div class="ps-preview-num">${i + 1}</div>
      <button class="ps-preview-del" onclick="removePhoto(${i})">×</button>
    </div>`).join('');
}

function removePhoto(i) {
  const p = allPhotoUrls[i];
  if (p.type === 'file') uploadedFiles = uploadedFiles.filter(f => f !== p.file);
  if (p.type === 'gallery') {
    galleryUrls = galleryUrls.filter(u => u !== p.url);
    document.querySelectorAll('.ps-gallery-thumb').forEach(el => {
      if (el.dataset.url === p.url) el.classList.remove('ps-gallery-thumb--sel');
    });
  }
  allPhotoUrls.splice(i, 1);
  renderPreviews();
  updateCounter();
  checkStep3();
}

// ── COUNTER ───────────────────────────────────────────────
function updateCounter() {
  if (!selectedProduct) return;
  const have = allPhotoUrls.length;
  const need = selectedProduct.fotos;
  const pct  = Math.min(100, Math.round(have / need * 100));
  document.getElementById('counterFill').style.width = pct + '%';
  document.getElementById('counterFill').style.background = have >= need ? '#27ae60' : '#B8975A';
  document.getElementById('counterText').textContent = `${have} / ${need} foto pievienoti`;
}

// ── CHECK IF STEP 3 READY ─────────────────────────────────
function checkStep3() {
  if (!selectedProduct) return;
  const have = allPhotoUrls.length;
  const need = selectedProduct.fotos;
  if (have < need) return;

  // Unlock step 3
  const s3 = document.getElementById('step3');
  s3.classList.remove('ps-step--locked');
  renderPreview();
  renderSummary();
  document.getElementById('step4').classList.remove('ps-step--locked');
  document.getElementById('orderBtn').disabled = false;
  s3.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── PRODUCT PREVIEW ───────────────────────────────────────
function renderPreview() {
  const area  = document.getElementById('previewArea');
  const tips  = selectedProduct.tips;
  const first = allPhotoUrls[0]?.url || '';

  if (tips === 'albums') {
    // Album: show first 6 photos as spread
    const photos = allPhotoUrls.slice(0, 6);
    area.innerHTML = `
      <div class="ps-album-preview">
        <div class="ps-album-cover" style="background-image:url('${first}')">
          <div class="ps-album-title">${htmlEsc(selectedProduct.name)}</div>
        </div>
        <div class="ps-album-pages">
          ${photos.slice(1).map(p => `<div class="ps-album-page" style="background-image:url('${p.url}')"></div>`).join('')}
          ${photos.length < 4 ? Array(4 - photos.length).fill('<div class="ps-album-page ps-album-page--empty">+</div>').join('') : ''}
        </div>
        <div class="ps-preview-label">${selectedProduct.izmers} · ${selectedProduct.fotos} foto</div>
      </div>`;
  } else if (tips === 'canvas' || tips === 'panelis') {
    area.innerHTML = `
      <div class="ps-canvas-preview">
        <div class="ps-wall-scene">
          <div class="ps-wall-bg"></div>
          <div class="ps-canvas-frame" data-orient="${selectedProduct.orient}">
            <div class="ps-canvas-img" style="background-image:url('${first}')"></div>
            ${tips === 'canvas' ? '<div class="ps-canvas-side ps-canvas-side--top"></div><div class="ps-canvas-side ps-canvas-side--right"></div><div class="ps-canvas-shadow"></div>' : ''}
          </div>
        </div>
        <div class="ps-preview-label">${selectedProduct.izmers}${tips === 'canvas' ? ' · Audekls ar rāmi' : ' · Alumīnija panelis'}</div>
      </div>`;
  } else {
    // Print / druka
    area.innerHTML = `
      <div class="ps-print-preview">
        <div class="ps-print-mat">
          <div class="ps-print-img" style="background-image:url('${first}');aspect-ratio:${selectedProduct.orient === 'square' ? '1' : '3/4'}"></div>
        </div>
        <div class="ps-preview-label">${selectedProduct.izmers} · Foto druka</div>
      </div>`;
  }
}

// ── SUMMARY ───────────────────────────────────────────────
function renderSummary() {
  document.getElementById('orderSummary').innerHTML = `
    <div class="ps-sum-row">
      <span class="ps-sum-label">Produkts</span>
      <span class="ps-sum-val">${htmlEsc(selectedProduct.name)}</span>
    </div>
    <div class="ps-sum-row">
      <span class="ps-sum-label">Izmērs</span>
      <span class="ps-sum-val">${htmlEsc(selectedProduct.izmers)}</span>
    </div>
    <div class="ps-sum-row">
      <span class="ps-sum-label">Foto</span>
      <span class="ps-sum-val">${allPhotoUrls.length} pievienoti</span>
    </div>
    <div class="ps-sum-row ps-sum-row--total">
      <span class="ps-sum-label">Cena</span>
      <span class="ps-sum-price">€${selectedProduct.price.toFixed(2)}</span>
    </div>`;
}

// ── SUBMIT ────────────────────────────────────────────────
function submitOrder() {
  if (!selectedProduct || allPhotoUrls.length < selectedProduct.fotos) return;
  const btn = document.getElementById('orderBtn');
  btn.textContent = 'Pievieno...'; btn.disabled = true;

  const fd = new FormData();
  fd.append('add_order_to_cart', '1');
  fd.append('prece_id', selectedProduct.id);
  fd.append('notes', document.getElementById('orderNotes').value);

  // Attach uploaded files
  uploadedFiles.forEach(f => fd.append('fotos[]', f));
  // Attach gallery URLs
  const gals = allPhotoUrls.filter(p => p.type === 'gallery').map(p => p.url);
  if (gals.length) fd.append('gallery_urls', JSON.stringify(gals));

  fetch('/4pt/blazkova/lumina/Lumina/pasutit.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        document.getElementById('cartCount').textContent = data.count;
        showToast(selectedProduct.name + ' pievienots grozam ✓', 'success');
        // Reset
        selectedProduct = null; uploadedFiles = []; galleryUrls = []; allPhotoUrls = [];
        document.querySelectorAll('.ps-product--selected').forEach(e => e.classList.remove('ps-product--selected'));
        document.getElementById('photoPreviews').innerHTML = '';
        ['step2','step3','step4'].forEach(id => document.getElementById(id).classList.add('ps-step--locked'));
        document.getElementById('orderBtn').disabled = true;
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        showToast(data.error || 'Kļūda', 'error');
        btn.textContent = 'Pievienot grozam →'; btn.disabled = false;
      }
    }).catch(() => {
      showToast('Savienojuma kļūda', 'error');
      btn.textContent = 'Pievienot grozam →'; btn.disabled = false;
    });
}

function htmlEsc(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
