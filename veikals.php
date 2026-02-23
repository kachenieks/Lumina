<?php
session_start();
require_once __DIR__ . '/includes/db.php';
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

// Handle upload form
$uploadSuccess = '';
$uploadError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_order'])) {
  // Process upload
  $produkts = escape($savienojums, $_POST['produkts'] ?? '');
  $notes = escape($savienojums, $_POST['notes'] ?? '');
  
  if (!empty($_FILES['fotos']['name'][0])) {
    $uploadDir = '/home/claude/lumina/Lumina/uploads/pasutijumi/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $uploaded = [];
    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp) {
      if (!empty($tmp)) {
        $ext = strtolower(pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png'])) {
          $filename = uniqid() . '.' . $ext;
          move_uploaded_file($tmp, $uploadDir . $filename);
          $uploaded[] = $filename;
        }
      }
    }
    if (!empty($uploaded)) {
      $uploadSuccess = 'Paldies! ' . count($uploaded) . ' foto augšupielādēts. Sazināsimies ar jums.';
    }
  } else {
    $uploadError = 'Lūdzu izvēlieties vismaz vienu fotogrāfiju.';
  }
}

// Fetch products
$result = mysqli_query($savienojums, "SELECT * FROM preces WHERE aktivs = 1 ORDER BY bestseller DESC, id ASC");
$products = [];
while ($row = mysqli_fetch_assoc($result)) $products[] = $row;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div class="section-label">Veikals</div>
  <h1>Mākslas Darbi <em>Jūsu Telpai</em></h1>
  <p>Iegādājieties gatavus mākslas darbus vai pasūtiet druku no savām fotogrāfijām.</p>
</div>

<section class="veikals-section">
  <div class="shop-grid">
    <?php foreach ($products as $i => $p): ?>
    <?php
    $imgSrc = !empty($p['attels_url']) ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $p['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=500&q=80';
    ?>
    <div class="product-card reveal reveal-delay-<?= ($i % 3) + 1 ?>" onclick="openProductModal(<?= $i ?>)">
      <div class="product-img">
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['nosaukums']) ?>">
        <?php if ($p['bestseller']): ?><div class="product-tag">Bestseller</div><?php endif; ?>
        <div class="product-overlay">
          <button class="add-cart-btn" onclick="event.stopPropagation();addToCartAjax(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nosaukums'])) ?>')">Pievienot →</button>
        </div>
      </div>
      <div class="product-name"><?= htmlspecialchars($p['nosaukums']) ?></div>
      <div class="product-price">€<?= number_format($p['cena'], 0) ?></div>
      <div class="product-sub"><?= htmlspecialchars($p['kategorija']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Upload CTA -->
<section class="upload-section">
  <div class="upload-cta-content reveal">
    <div class="section-label" style="display:flex;justify-content:center;">Personalizēts pasūtījums</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,5vw,52px);font-weight:300;color:var(--ink);margin:14px 0;">Ievilciet savus foto<br><em style="font-style:italic;color:var(--gold)">un pasūtiet druku</em></h2>
    <p style="font-size:14px;color:var(--grey);max-width:460px;margin:0 auto 32px;line-height:1.8;">Augšupielādējiet savu fotogrāfiju un mēs izgatavosim unikālu drukas darbu, canvas vai fotogrāmatu.</p>
    <button class="btn-primary" onclick="openModal('uploadModal')">Augšupielādēt Foto →</button>
  </div>
</section>

<!-- Cart Sidebar -->
<div id="cartSidebar" class="cart-sidebar">
  <div class="cart-header">
    <h3>Grozs</h3>
    <button onclick="document.getElementById('cartSidebar').classList.remove('open')">×</button>
  </div>
  <div class="cart-items" id="cartItems">
    <!-- Loaded dynamically -->
  </div>
  <div class="cart-footer">
    <div class="cart-total" id="cartTotal"></div>
    <button class="btn-primary" style="width:100%;margin-top:14px;" onclick="checkout()">Noformēt Pasūtījumu →</button>
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
      <button id="prodModalBtn" class="btn-primary">Pievienot Grozam →</button>
    </div>
  </div>
</div>

<!-- Upload Modal -->
<div class="modal-overlay" id="uploadModal">
  <div class="modal" style="max-width:560px;padding:42px;">
    <button class="modal-close" onclick="closeModal('uploadModal')">×</button>
    <div class="section-label">Personalizēts pasūtījums</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:300;color:var(--ink);margin:10px 0 26px;">Augšupielādēt Foto</h2>
    
    <?php if ($uploadSuccess): ?>
    <div class="alert alert-success"><?= $uploadSuccess ?></div>
    <?php endif; ?>
    <?php if ($uploadError): ?>
    <div class="alert alert-error"><?= $uploadError ?></div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="upload_order" value="1">
      <div class="upload-zone" id="uploadZone" onclick="document.getElementById('uploadFileInput').click()">
        <input type="file" id="uploadFileInput" name="fotos[]" class="upload-input" multiple accept="image/*" onchange="handlePreview(this.files)">
        <span class="upload-icon">📷</span>
        <div class="upload-title">Ievilciet foto šeit</div>
        <div class="upload-sub">vai noklikšķiniet · JPEG, PNG · maks. 10 MB katrs</div>
      </div>
      <div id="uploadPreviews" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:14px;"></div>
      
      <div class="form-group" style="margin-top:20px;">
        <label class="form-label">Produkta veids</label>
        <select name="produkts" class="form-select">
          <option>Fotodruka 30×40 cm — €29</option>
          <option>Canvas 50×70 cm — €79</option>
          <option>Fotogrāmata 30×30 cm — €129</option>
          <option>Sienas panelis 60×90 cm — €149</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Papildu vēlmes</label>
        <textarea name="notes" class="form-textarea" style="height:76px;" placeholder="Melnbalta versija, īpašas piezīmes..."></textarea>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;">Nosūtīt Pasūtījumu →</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const products = <?= json_encode(array_map(function($p) {
  $src = !empty($p['attels_url']) ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $p['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80';
  return ['id' => $p['id'], 'img' => $src, 'cat' => $p['kategorija'], 'title' => $p['nosaukums'], 'desc' => $p['apraksts'], 'price' => $p['cena']];
}, $products)) ?>;

function openProductModal(i) {
  const p = products[i];
  document.getElementById('prodModalImg').src = p.img;
  document.getElementById('prodModalCat').textContent = p.cat;
  document.getElementById('prodModalTitle').textContent = p.title;
  document.getElementById('prodModalDesc').textContent = p.desc;
  document.getElementById('prodModalPrice').textContent = '€' + parseFloat(p.price).toFixed(0);
  document.getElementById('prodModalBtn').onclick = () => { addToCartAjax(p.id, p.title); closeModal('productModal'); };
  openModal('productModal');
}

function addToCartAjax(id, name) {
  fetch('/4pt/blazkova/lumina/Lumina/veikals.php?action=add&id=' + id)
    .then(r => r.json())
    .then(data => {
      document.getElementById('cartCount').textContent = data.count;
      showToast(name + ' pievienots grozam ✓', 'success');
    });
}

function checkout() {
  showToast('Pasūtījums nosūtīts! Paldies!', 'success');
  document.getElementById('cartSidebar').classList.remove('open');
}

// Upload preview
function handlePreview(files) {
  const container = document.getElementById('uploadPreviews');
  container.innerHTML = '';
  Array.from(files).forEach(f => {
    const item = document.createElement('div');
    item.style.cssText = 'aspect-ratio:1;overflow:hidden;background:var(--cream2);';
    const r = new FileReader();
    r.onload = ev => {
      item.innerHTML = `<img src="${ev.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
    };
    r.readAsDataURL(f);
    container.appendChild(item);
  });
}

// Init drag zone
document.addEventListener('DOMContentLoaded', () => {
  initDropZone('uploadZone', 'uploadFileInput', 'uploadPreviews');
});
</script>
