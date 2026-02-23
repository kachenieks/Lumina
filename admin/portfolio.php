<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Portfolio';

$uploadDir = __DIR__ . '/../uploads/portfolio/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM portfolio WHERE id=$id"));
  if ($row && $row['attels_url'] && !filter_var($row['attels_url'], FILTER_VALIDATE_URL)) {
    @unlink($uploadDir . $row['attels_url']);
  }
  mysqli_query($savienojums, "DELETE FROM portfolio WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/portfolio.php?msg=deleted'); exit;
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nosaukums  = escape($savienojums, $_POST['nosaukums'] ?? '');
  $kategorija = escape($savienojums, $_POST['kategorija'] ?? '');
  $apraksts   = escape($savienojums, $_POST['apraksts'] ?? '');
  $aktivs     = isset($_POST['aktivs']) ? 1 : 0;
  $editId     = (int)($_POST['edit_id'] ?? 0);

  // Image handling
  $attels_url = '';
  if (!empty($_FILES['attels']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
      $filename = uniqid('port_') . '.' . $ext;
      if (move_uploaded_file($_FILES['attels']['tmp_name'], $uploadDir . $filename)) {
        $attels_url = $filename;
        // Delete old file if editing
        if ($editId) {
          $old = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM portfolio WHERE id=$editId"));
          if ($old && $old['attels_url'] && !filter_var($old['attels_url'], FILTER_VALIDATE_URL)) {
            @unlink($uploadDir . $old['attels_url']);
          }
        }
      } else {
        $error = 'Kļūda augšupielādējot. Pārbaud mapes tiesības (chmod 755 uploads/portfolio/).';
      }
    } else { $error = 'Atļauts: JPG, PNG, WEBP, GIF.'; }
  } elseif (!empty($_POST['attels_url_text'])) {
    $attels_url = escape($savienojums, trim($_POST['attels_url_text']));
  }

  if (!$error) {
    if ($editId) {
      $imgSql = $attels_url ? ", attels_url='$attels_url'" : '';
      $sql = "UPDATE portfolio SET nosaukums='$nosaukums', kategorija='$kategorija', apraksts='$apraksts', aktivs=$aktivs $imgSql WHERE id=$editId";
    } else {
      $sql = "INSERT INTO portfolio (nosaukums, kategorija, apraksts, attels_url, aktivs) VALUES ('$nosaukums','$kategorija','$apraksts','$attels_url',$aktivs)";
    }
    if (mysqli_query($savienojums, $sql)) {
      $success = $editId ? 'Attēls atjaunināts!' : 'Attēls pievienots!';
      if ($editId) { header('Location: /4pt/blazkova/lumina/Lumina/admin/portfolio.php?msg=updated'); exit; }
    } else { $error = 'DB kļūda: ' . mysqli_error($savienojums); }
  }
}

$items = [];
$result = mysqli_query($savienojums, "SELECT * FROM portfolio ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

$editItem = null;
if (isset($_GET['edit'])) {
  $editItem = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM portfolio WHERE id=" . (int)$_GET['edit']));
}

include __DIR__ . '/includes/header.php';
?>
<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?= $_GET['msg']==='deleted'?'Attēls dzēsts.':'Attēls atjaunināts.' ?></div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start;">

  <!-- List -->
  <div>
    <div class="section-header">
      <div class="section-heading">Portfolio Attēli (<?= count($items) ?>)</div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;">
      <?php foreach ($items as $item):
        $src = !empty($item['attels_url'])
          ? (filter_var($item['attels_url'], FILTER_VALIDATE_URL) ? $item['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $item['attels_url'])
          : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=300&q=80';
      ?>
      <div style="position:relative;aspect-ratio:1;background:#eee;border-radius:6px;overflow:hidden;border:<?= isset($_GET['edit']) && (int)$_GET['edit']===$item['id'] ? '2px solid var(--gold)' : '1px solid var(--border)' ?>;">
        <img src="<?= htmlspecialchars($src) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='https://via.placeholder.com/200x200?text=No+img'">
        <?php if (!$item['aktivs']): ?><div style="position:absolute;top:4px;left:4px;background:rgba(192,57,43,.85);color:#fff;font-size:9px;padding:2px 6px;border-radius:3px;text-transform:uppercase;">Slēpts</div><?php endif; ?>
        <div class="img-actions">
          <a href="?edit=<?= $item['id'] ?>" class="action-btn">✏ Edit</a>
          <a href="?delete=<?= $item['id'] ?>" onclick="return confirm('Dzēst?')" class="action-btn danger">✕</a>
        </div>
        <div style="position:absolute;bottom:0;left:0;right:0;padding:6px 8px;background:linear-gradient(transparent,rgba(0,0,0,.7));color:#fff;font-size:10px;">
          <?= htmlspecialchars($item['nosaukums']) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:20px;"><?= $editItem ? '✏ Rediģēt' : '+ Pievienot attēlu' ?></div>

    <form method="POST" enctype="multipart/form-data">
      <?php if ($editItem): ?>
      <input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>">
      <?php if (!empty($editItem['attels_url'])): ?>
      <?php $curSrc = filter_var($editItem['attels_url'], FILTER_VALIDATE_URL) ? $editItem['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $editItem['attels_url']; ?>
      <div style="margin-bottom:14px;border-radius:8px;overflow:hidden;">
        <img src="<?= htmlspecialchars($curSrc) ?>" style="width:100%;height:180px;object-fit:cover;" onerror="this.style.display='none'">
        <div style="font-size:11px;color:var(--grey2);padding:4px;word-break:break-all;"><?= htmlspecialchars($editItem['attels_url']) ?></div>
      </div>
      <?php endif; ?>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">📁 Augšupielādēt jaunu attēlu</label>
        <div class="upload-zone" onclick="document.getElementById('portImg').click()" style="padding:16px;cursor:pointer;">
          <input type="file" id="portImg" name="attels" style="display:none;" accept="image/*" onchange="previewImg(this)">
          <div id="uploadPlaceholder" style="text-align:center;">
            <div style="font-size:26px;">📸</div>
            <div style="font-size:11px;color:var(--grey2);margin-top:4px;">Klikšķiniet vai velciet JPG/PNG</div>
          </div>
          <img id="imgPreviewEl" style="display:none;width:100%;height:120px;object-fit:cover;border-radius:4px;" src="" alt="">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">🔗 Vai ielīmēt URL (ja nav faila)</label>
        <input type="url" name="attels_url_text" class="form-input" placeholder="https://images.unsplash.com/..." value="<?= ($editItem && filter_var($editItem['attels_url'] ?? '', FILTER_VALIDATE_URL)) ? htmlspecialchars($editItem['attels_url']) : '' ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Nosaukums *</label>
        <input type="text" name="nosaukums" class="form-input" required value="<?= htmlspecialchars($editItem['nosaukums'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label class="form-label">Kategorija *</label>
        <select name="kategorija" class="form-select" required>
          <option value="">Izvēlieties...</option>
          <?php foreach (['kazas','portreti','pasakumi','gimene','komercials'] as $c): ?>
          <option value="<?= $c ?>" <?= ($editItem['kategorija'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
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

      <button type="submit" class="btn-primary" style="width:100%;"><?= $editItem ? 'Saglabāt izmaiņas' : 'Pievienot' ?> →</button>
      <?php if ($editItem): ?>
      <a href="/4pt/blazkova/lumina/Lumina/admin/portfolio.php" style="display:block;text-align:center;margin-top:10px;font-size:12px;color:var(--grey2);text-decoration:none;">✕ Atcelt rediģēšanu</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<style>
.img-actions{position:absolute;top:4px;right:4px;display:flex;gap:3px;opacity:0;transition:.2s;}
div:hover>.img-actions{opacity:1;}
</style>
<script>
function previewImg(input) {
  if (input.files && input.files[0]) {
    const r = new FileReader();
    r.onload = e => {
      document.getElementById('imgPreviewEl').src = e.target.result;
      document.getElementById('imgPreviewEl').style.display = 'block';
      document.getElementById('uploadPlaceholder').style.display = 'none';
    };
    r.readAsDataURL(input.files[0]);
  }
}
</script>
