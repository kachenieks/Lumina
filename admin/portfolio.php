<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Portfolio';

// Handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  // Get image path first
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM portfolio WHERE id=$id"));
  if ($row && $row['attels_url'] && !filter_var($row['attels_url'], FILTER_VALIDATE_URL)) {
    @unlink('/home/claude/lumina/Lumina/uploads/portfolio/' . $row['attels_url']);
  }
  mysqli_query($savienojums, "DELETE FROM portfolio WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/portfolio.php?deleted=1');
  exit;
}

// Handle add/edit
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nosaukums = escape($savienojums, $_POST['nosaukums'] ?? '');
  $kategorija = escape($savienojums, $_POST['kategorija'] ?? '');
  $apraksts = escape($savienojums, $_POST['apraksts'] ?? '');
  $aktivs = isset($_POST['aktivs']) ? 1 : 0;
  $editId = (int)($_POST['edit_id'] ?? 0);
  
  $attels_url = '';
  
  // Handle file upload
  if (!empty($_FILES['attels']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
      $uploadDir = '/home/claude/lumina/Lumina/uploads/portfolio/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
      $filename = uniqid('port_') . '.' . $ext;
      if (move_uploaded_file($_FILES['attels']['tmp_name'], $uploadDir . $filename)) {
        $attels_url = $filename;
      } else {
        $error = 'Kļūda augšupielādējot failu.';
      }
    } else {
      $error = 'Atļauts tikai JPG, PNG, WEBP formāts.';
    }
  } elseif (!empty($_POST['attels_url_text'])) {
    $attels_url = escape($savienojums, $_POST['attels_url_text']);
  }
  
  if (!$error) {
    if ($editId) {
      $imgSql = $attels_url ? ", attels_url='$attels_url'" : '';
      $sql = "UPDATE portfolio SET nosaukums='$nosaukums', kategorija='$kategorija', apraksts='$apraksts', aktivs=$aktivs $imgSql WHERE id=$editId";
    } else {
      $sql = "INSERT INTO portfolio (nosaukums, kategorija, apraksts, attels_url, aktivs) VALUES ('$nosaukums', '$kategorija', '$apraksts', '$attels_url', $aktivs)";
    }
    if (mysqli_query($savienojums, $sql)) {
      $success = $editId ? 'Attēls atjaunināts!' : 'Attēls pievienots!';
    } else {
      $error = 'DB kļūda: ' . mysqli_error($savienojums);
    }
  }
}

// Fetch items
$items = [];
$result = mysqli_query($savienojums, "SELECT * FROM portfolio ORDER BY pievienots DESC");
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

// Edit item?
$editItem = null;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  $editItem = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM portfolio WHERE id=$editId"));
}

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['deleted'])): ?>
<div class="alert alert-success">Attēls dzēsts.</div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start;">
  
  <!-- Portfolio List -->
  <div>
    <div class="section-header">
      <div class="section-heading">Portfolio Attēli (<?= count($items) ?>)</div>
    </div>
    
    <div class="preview-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:20px;">
      <?php foreach ($items as $item): ?>
      <?php
      $src = !empty($item['attels_url']) ? (filter_var($item['attels_url'], FILTER_VALIDATE_URL) ? $item['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $item['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=300&q=80';
      ?>
      <div class="preview-item" style="aspect-ratio:1;position:relative;">
        <img src="<?= htmlspecialchars($src) ?>" alt="" onerror="this.src='https://images.unsplash.com/photo-1519741497674-611481863552?w=300&q=80'">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,0);transition:background .2s;display:flex;flex-direction:column;justify-content:flex-end;padding:8px;" onmouseenter="this.style.background='rgba(0,0,0,.6)'" onmouseleave="this.style.background='rgba(0,0,0,0)'">
          <div style="color:#fff;font-size:10px;opacity:0;transition:opacity .2s;" class="preview-labels">
            <div style="font-weight:600;margin-bottom:2px;"><?= htmlspecialchars($item['nosaukums']) ?></div>
            <div style="color:var(--gold-light);"><?= htmlspecialchars($item['kategorija']) ?></div>
          </div>
          <div style="display:flex;gap:4px;margin-top:6px;opacity:0;transition:opacity .2s;" class="preview-actions">
            <a href="?edit=<?= $item['id'] ?>" style="flex:1;text-align:center;background:rgba(255,255,255,.9);color:var(--ink);padding:4px;font-size:9px;text-decoration:none;letter-spacing:1px;text-transform:uppercase;">Edit</a>
            <a href="?delete=<?= $item['id'] ?>" onclick="return confirm('Dzēst?')" style="background:rgba(192,57,43,.9);color:#fff;padding:4px 8px;font-size:9px;text-decoration:none;">✕</a>
          </div>
        </div>
        <?php if (!$item['aktivs']): ?>
        <div style="position:absolute;top:4px;left:4px;background:rgba(192,57,43,.8);color:#fff;font-size:8px;padding:2px 6px;text-transform:uppercase;letter-spacing:1px;">Slēpts</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Add/Edit Form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:20px;"><?= $editItem ? 'Rediģēt attēlu' : '+ Pievienot attēlu' ?></div>
    
    <form method="POST" enctype="multipart/form-data">
      <?php if ($editItem): ?>
      <input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>">
      <?php if (!empty($editItem['attels_url'])): ?>
      <?php $src = filter_var($editItem['attels_url'], FILTER_VALIDATE_URL) ? $editItem['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $editItem['attels_url']; ?>
      <div style="margin-bottom:14px;"><img src="<?= htmlspecialchars($src) ?>" style="width:100%;height:160px;object-fit:cover;" onerror="this.style.display='none'"></div>
      <?php endif; ?>
      <?php endif; ?>
      
      <div class="form-group">
        <label class="form-label">Augšupielādēt attēlu (fails)</label>
        <div class="upload-zone" onclick="document.getElementById('portImg').click()" style="padding:20px;">
          <input type="file" id="portImg" name="attels" class="upload-input" accept="image/*" onchange="previewImg(this)">
          <div id="imgPreview" style="display:none;width:100%;height:120px;object-fit:cover;"></div>
          <div id="uploadPlaceholder">
            <span style="font-size:24px;">📸</span>
            <div style="font-size:12px;color:var(--grey);margin-top:6px;">Klikšķiniet vai velciet</div>
          </div>
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Vai URL saite (ja nav faila)</label>
        <input type="url" name="attels_url_text" class="form-input" placeholder="https://..." value="<?= ($editItem && filter_var($editItem['attels_url'] ?? '', FILTER_VALIDATE_URL)) ? htmlspecialchars($editItem['attels_url']) : '' ?>">
      </div>
      
      <div class="form-group">
        <label class="form-label">Nosaukums *</label>
        <input type="text" name="nosaukums" class="form-input" required value="<?= htmlspecialchars($editItem['nosaukums'] ?? '') ?>">
      </div>
      
      <div class="form-group">
        <label class="form-label">Kategorija *</label>
        <select name="kategorija" class="form-select" required>
          <option value="">Izvēlieties...</option>
          <?php foreach (['kazas','portreti','pasakumi','gimene','komercials'] as $cat): ?>
          <option value="<?= $cat ?>" <?= ($editItem['kategorija'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">Apraksts</label>
        <textarea name="apraksts" class="form-textarea" style="height:60px;"><?= htmlspecialchars($editItem['apraksts'] ?? '') ?></textarea>
      </div>
      
      <div class="form-group" style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" id="aktivs" name="aktivs" <?= (!isset($editItem) || $editItem['aktivs']) ? 'checked' : '' ?> style="width:auto;">
        <label for="aktivs" class="form-label" style="margin:0;">Redzams mājas lapā</label>
      </div>
      
      <button type="submit" class="btn-primary" style="width:100%;"><?= $editItem ? 'Saglabāt' : 'Pievienot' ?> →</button>
      <?php if ($editItem): ?>
      <a href="/4pt/blazkova/lumina/Lumina/admin/portfolio.php" style="display:block;text-align:center;margin-top:10px;font-size:11px;color:var(--grey);text-decoration:none;">Atcelt rediģēšanu</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function previewImg(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const preview = document.getElementById('imgPreview');
      preview.style.cssText = 'display:block;width:100%;height:120px;object-fit:cover;';
      preview.outerHTML = '<img id="imgPreview" src="' + e.target.result + '" style="display:block;width:100%;height:120px;object-fit:cover;">';
      document.getElementById('uploadPlaceholder').style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Hover effects for preview items
document.querySelectorAll('.preview-item').forEach(item => {
  item.addEventListener('mouseenter', () => {
    item.querySelector('.preview-labels') && (item.querySelector('.preview-labels').style.opacity = '1');
    item.querySelector('.preview-actions') && (item.querySelector('.preview-actions').style.opacity = '1');
  });
  item.addEventListener('mouseleave', () => {
    item.querySelector('.preview-labels') && (item.querySelector('.preview-labels').style.opacity = '0');
    item.querySelector('.preview-actions') && (item.querySelector('.preview-actions').style.opacity = '0');
  });
});
</script>
