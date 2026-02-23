<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Veikals — Preces';

// Handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM preces WHERE id=$id"));
  if ($row && $row['attels_url'] && !filter_var($row['attels_url'], FILTER_VALIDATE_URL)) {
    @unlink('/home/claude/lumina/Lumina/uploads/preces/' . $row['attels_url']);
  }
  mysqli_query($savienojums, "DELETE FROM preces WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/preces.php?deleted=1');
  exit;
}

$success = '';
$error = '';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nosaukums = escape($savienojums, $_POST['nosaukums'] ?? '');
  $kategorija = escape($savienojums, $_POST['kategorija'] ?? '');
  $cena = (float)($_POST['cena'] ?? 0);
  $apraksts = escape($savienojums, $_POST['apraksts'] ?? '');
  $bestseller = isset($_POST['bestseller']) ? 1 : 0;
  $aktivs = isset($_POST['aktivs']) ? 1 : 0;
  $editId = (int)($_POST['edit_id'] ?? 0);
  
  $attels_url = '';
  if (!empty($_FILES['attels']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
      $uploadDir = '/home/claude/lumina/Lumina/uploads/preces/';
      if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
      $filename = uniqid('prece_') . '.' . $ext;
      if (move_uploaded_file($_FILES['attels']['tmp_name'], $uploadDir . $filename)) {
        $attels_url = $filename;
      }
    }
  } elseif (!empty($_POST['attels_url_text'])) {
    $attels_url = escape($savienojums, $_POST['attels_url_text']);
  }
  
  if (!$error) {
    if ($editId) {
      $imgSql = $attels_url ? ", attels_url='$attels_url'" : '';
      $sql = "UPDATE preces SET nosaukums='$nosaukums', kategorija='$kategorija', cena=$cena, apraksts='$apraksts', bestseller=$bestseller, aktivs=$aktivs $imgSql WHERE id=$editId";
    } else {
      $sql = "INSERT INTO preces (nosaukums, kategorija, cena, apraksts, attels_url, noliktava, bestseller, aktivs) VALUES ('$nosaukums', '$kategorija', $cena, '$apraksts', '$attels_url', -1, $bestseller, $aktivs)";
    }
    if (mysqli_query($savienojums, $sql)) {
      $success = $editId ? 'Prece atjaunināta!' : 'Prece pievienota!';
    } else {
      $error = 'DB kļūda: ' . mysqli_error($savienojums);
    }
  }
}

// Fetch
$items = [];
$result = mysqli_query($savienojums, "SELECT * FROM preces ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

$editItem = null;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  $editItem = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM preces WHERE id=$editId"));
}

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Prece dzēsta.</div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start;">
  
  <!-- Products Table -->
  <div>
    <div class="section-heading" style="margin-bottom:18px;">Preču Katalogs (<?= count($items) ?>)</div>
    <div class="admin-card" style="padding:0;overflow:hidden;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Attēls</th>
            <th>Nosaukums</th>
            <th>Kategorija</th>
            <th>Cena</th>
            <th>Statuss</th>
            <th>Darbības</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $p): ?>
          <?php $src = !empty($p['attels_url']) ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $p['attels_url']) : ''; ?>
          <tr>
            <td><?php if ($src): ?><img src="<?= htmlspecialchars($src) ?>" onerror="this.style.display='none'"><?php else: ?><div style="width:60px;height:46px;background:var(--cream2);"></div><?php endif; ?></td>
            <td>
              <strong><?= htmlspecialchars($p['nosaukums']) ?></strong>
              <?php if ($p['bestseller']): ?> <span class="status-badge delivered" style="font-size:8px;">Bestseller</span><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['kategorija']) ?></td>
            <td style="color:var(--gold);font-weight:500;">€<?= number_format($p['cena'], 2) ?></td>
            <td><span class="status-badge <?= $p['aktivs'] ? 'delivered' : 'cancelled' ?>"><?= $p['aktivs'] ? 'Aktīvs' : 'Slēpts' ?></span></td>
            <td>
              <a href="?edit=<?= $p['id'] ?>" class="action-btn">Rediģēt</a>
              <a href="?delete=<?= $p['id'] ?>" class="action-btn danger" onclick="return confirm('Dzēst preci?')">Dzēst</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:20px;"><?= $editItem ? 'Rediģēt preci' : '+ Jauna prece' ?></div>
    
    <form method="POST" enctype="multipart/form-data">
      <?php if ($editItem): ?>
      <input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>">
      <?php if (!empty($editItem['attels_url'])): ?>
      <?php $src = filter_var($editItem['attels_url'], FILTER_VALIDATE_URL) ? $editItem['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $editItem['attels_url']; ?>
      <img src="<?= htmlspecialchars($src) ?>" style="width:100%;height:140px;object-fit:cover;margin-bottom:14px;" onerror="this.style.display='none'">
      <?php endif; ?>
      <?php endif; ?>
      
      <div class="form-group">
        <label class="form-label">Attēls (fails)</label>
        <div class="upload-zone" onclick="document.getElementById('preceImg').click()" style="padding:16px;">
          <input type="file" id="preceImg" name="attels" class="upload-input" accept="image/*">
          <div style="font-size:20px;">📷</div>
          <div style="font-size:11px;color:var(--grey);margin-top:4px;">Augšupielādēt</div>
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Vai URL</label>
        <input type="url" name="attels_url_text" class="form-input" placeholder="https://..." value="<?= ($editItem && filter_var($editItem['attels_url'] ?? '', FILTER_VALIDATE_URL)) ? htmlspecialchars($editItem['attels_url']) : '' ?>">
      </div>
      
      <div class="form-group">
        <label class="form-label">Nosaukums *</label>
        <input type="text" name="nosaukums" class="form-input" required value="<?= htmlspecialchars($editItem['nosaukums'] ?? '') ?>">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Kategorija</label>
          <select name="kategorija" class="form-select">
            <?php foreach (['Drukas darbi','Canvas','Personalizēti','Grāmatas'] as $cat): ?>
            <option <?= ($editItem['kategorija'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Cena (€) *</label>
          <input type="number" name="cena" class="form-input" step="0.01" min="0" required value="<?= $editItem['cena'] ?? '' ?>">
        </div>
      </div>
      
      <div class="form-group">
        <label class="form-label">Apraksts</label>
        <textarea name="apraksts" class="form-textarea" style="height:70px;"><?= htmlspecialchars($editItem['apraksts'] ?? '') ?></textarea>
      </div>
      
      <div style="display:flex;gap:20px;margin-bottom:17px;">
        <div style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" id="bestseller" name="bestseller" <?= ($editItem['bestseller'] ?? 0) ? 'checked' : '' ?> style="width:auto;">
          <label for="bestseller" class="form-label" style="margin:0;">Bestseller</label>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <input type="checkbox" id="aktivs" name="aktivs" <?= (!isset($editItem) || $editItem['aktivs']) ? 'checked' : '' ?> style="width:auto;">
          <label for="aktivs" class="form-label" style="margin:0;">Aktīvs</label>
        </div>
      </div>
      
      <button type="submit" class="btn-primary" style="width:100%;"><?= $editItem ? 'Saglabāt' : 'Pievienot' ?> →</button>
      <?php if ($editItem): ?>
      <a href="/4pt/blazkova/lumina/Lumina/admin/preces.php" style="display:block;text-align:center;margin-top:10px;font-size:11px;color:var(--grey);text-decoration:none;">Atcelt</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
