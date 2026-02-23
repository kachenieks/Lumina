<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Preces';

$uploadDir = __DIR__ . '/../uploads/preces/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM preces WHERE id=$id"));
  if ($row && $row['attels_url'] && !filter_var($row['attels_url'], FILTER_VALIDATE_URL)) {
    @unlink($uploadDir . $row['attels_url']);
  }
  mysqli_query($savienojums, "DELETE FROM preces WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/preces.php?msg=deleted'); exit;
}

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nosaukums  = escape($savienojums, $_POST['nosaukums'] ?? '');
  $kategorija = escape($savienojums, $_POST['kategorija'] ?? '');
  $cena       = (float)($_POST['cena'] ?? 0);
  $apraksts   = escape($savienojums, $_POST['apraksts'] ?? '');
  $bestseller = isset($_POST['bestseller']) ? 1 : 0;
  $aktivs     = isset($_POST['aktivs']) ? 1 : 0;
  $editId     = (int)($_POST['edit_id'] ?? 0);

  $attels_url = '';
  if (!empty($_FILES['attels']['tmp_name'])) {
    $ext = strtolower(pathinfo($_FILES['attels']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
      $filename = uniqid('prece_') . '.' . $ext;
      if (move_uploaded_file($_FILES['attels']['tmp_name'], $uploadDir . $filename)) {
        $attels_url = $filename;
        if ($editId) {
          $old = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM preces WHERE id=$editId"));
          if ($old && $old['attels_url'] && !filter_var($old['attels_url'], FILTER_VALIDATE_URL)) @unlink($uploadDir . $old['attels_url']);
        }
      } else { $error = 'Upload kļūda. Pārbaudi chmod 755 uploads/preces/'; }
    } else { $error = 'Atļauts: JPG, PNG, WEBP.'; }
  } elseif (!empty($_POST['attels_url_text'])) {
    $attels_url = escape($savienojums, trim($_POST['attels_url_text']));
  }

  if (!$error) {
    if ($editId) {
      $imgSql = $attels_url ? ", attels_url='$attels_url'" : '';
      $sql = "UPDATE preces SET nosaukums='$nosaukums', kategorija='$kategorija', cena=$cena, apraksts='$apraksts', bestseller=$bestseller, aktivs=$aktivs $imgSql WHERE id=$editId";
    } else {
      $sql = "INSERT INTO preces (nosaukums, kategorija, cena, apraksts, attels_url, noliktava, bestseller, aktivs) VALUES ('$nosaukums','$kategorija',$cena,'$apraksts','$attels_url',-1,$bestseller,$aktivs)";
    }
    if (mysqli_query($savienojums, $sql)) {
      $success = $editId ? 'Prece atjaunināta!' : 'Prece pievienota!';
    } else { $error = 'DB kļūda: ' . mysqli_error($savienojums); }
  }
}

$items = [];
$result = mysqli_query($savienojums, "SELECT * FROM preces ORDER BY id DESC");
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

$editItem = null;
if (isset($_GET['edit'])) {
  $editItem = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM preces WHERE id=" . (int)$_GET['edit']));
}

include __DIR__ . '/includes/header.php';
?>
<?php if (isset($_GET['msg'])): ?><div class="alert alert-success"><?= $_GET['msg']==='deleted'?'Prece dzēsta.':'Saglabāts.' ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 380px;gap:28px;align-items:start;">

  <!-- Products list -->
  <div>
    <div class="section-header"><div class="section-heading">Preces (<?= count($items) ?>)</div></div>
    <div class="admin-card" style="padding:0;overflow:hidden;">
      <table class="admin-table">
        <thead><tr><th>Foto</th><th>Nosaukums</th><th>Kategorija</th><th>Cena</th><th>Status</th><th>Darbības</th></tr></thead>
        <tbody>
          <?php foreach ($items as $p):
            $src = !empty($p['attels_url'])
              ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $p['attels_url'])
              : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=100&q=60';
          ?>
          <tr style="<?= isset($_GET['edit']) && (int)$_GET['edit']===$p['id'] ? 'background:rgba(184,151,90,.08);' : '' ?>">
            <td style="width:56px;padding:6px;">
              <img src="<?= htmlspecialchars($src) ?>" style="width:48px;height:48px;object-fit:cover;border-radius:4px;" onerror="this.src='https://via.placeholder.com/48'">
            </td>
            <td>
              <strong><?= htmlspecialchars($p['nosaukums']) ?></strong>
              <?php if ($p['bestseller']): ?> <span style="font-size:9px;background:var(--gold);color:#fff;padding:1px 5px;border-radius:10px;">★ Best</span><?php endif; ?>
            </td>
            <td style="color:var(--grey2);font-size:12px;"><?= htmlspecialchars($p['kategorija']) ?></td>
            <td style="color:var(--gold);font-weight:600;">€<?= number_format($p['cena'], 2) ?></td>
            <td><span style="font-size:10px;padding:2px 8px;border-radius:10px;background:<?= $p['aktivs'] ? 'rgba(39,174,96,.15)' : 'rgba(192,57,43,.15)' ?>;color:<?= $p['aktivs'] ? '#27ae60' : '#c0392b' ?>;"><?= $p['aktivs'] ? 'Aktīvs' : 'Slēpts' ?></span></td>
            <td>
              <a href="?edit=<?= $p['id'] ?>" class="action-btn">✏</a>
              <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Dzēst preci?')" class="action-btn danger">✕</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?><tr><td colspan="6" style="text-align:center;padding:28px;color:var(--grey2);">Nav preču</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:18px;"><?= $editItem ? '✏ Rediģēt preci' : '+ Pievienot preci' ?></div>
    <form method="POST" enctype="multipart/form-data">
      <?php if ($editItem): ?>
      <input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>">
      <?php if (!empty($editItem['attels_url'])): ?>
      <?php $cs = filter_var($editItem['attels_url'], FILTER_VALIDATE_URL) ? $editItem['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $editItem['attels_url']; ?>
      <div style="margin-bottom:12px;border-radius:8px;overflow:hidden;">
        <img src="<?= htmlspecialchars($cs) ?>" style="width:100%;height:160px;object-fit:cover;" onerror="this.style.display='none'">
        <div style="font-size:10px;color:var(--grey2);padding:3px;word-break:break-all;"><?= htmlspecialchars($editItem['attels_url']) ?></div>
      </div>
      <?php endif; ?>
      <?php endif; ?>

      <div class="form-group">
        <label class="form-label">📁 Jauns attēls (fails)</label>
        <div class="upload-zone" onclick="document.getElementById('precImg').click()" style="padding:14px;cursor:pointer;">
          <input type="file" id="precImg" name="attels" style="display:none;" accept="image/*" onchange="prevImg(this)">
          <div id="pp" style="text-align:center;"><div style="font-size:24px;">🛍️</div><div style="font-size:11px;color:var(--grey2);">JPG / PNG / WEBP</div></div>
          <img id="pp2" style="display:none;width:100%;height:100px;object-fit:cover;border-radius:4px;" src="">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">🔗 URL saite</label>
        <input type="url" name="attels_url_text" class="form-input" placeholder="https://..." value="<?= ($editItem && filter_var($editItem['attels_url'] ?? '', FILTER_VALIDATE_URL)) ? htmlspecialchars($editItem['attels_url']) : '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Nosaukums *</label>
        <input type="text" name="nosaukums" class="form-input" required value="<?= htmlspecialchars($editItem['nosaukums'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Kategorija</label>
        <select name="kategorija" class="form-select">
          <?php foreach (['Drukas darbi','Canvas','Personalizēti','Grāmatas'] as $c): ?>
          <option <?= ($editItem['kategorija'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Cena (€) *</label>
        <input type="number" name="cena" class="form-input" step="0.01" required value="<?= $editItem['cena'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Apraksts</label>
        <textarea name="apraksts" class="form-textarea" style="height:70px;"><?= htmlspecialchars($editItem['apraksts'] ?? '') ?></textarea>
      </div>
      <div class="form-group" style="display:flex;gap:16px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
          <input type="checkbox" name="bestseller" <?= ($editItem['bestseller'] ?? 0) ? 'checked' : '' ?>> Bestseller
        </label>
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
          <input type="checkbox" name="aktivs" <?= (!isset($editItem) || $editItem['aktivs']) ? 'checked' : '' ?>> Aktīvs
        </label>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;"><?= $editItem ? 'Saglabāt' : 'Pievienot' ?> →</button>
      <?php if ($editItem): ?><a href="/4pt/blazkova/lumina/Lumina/admin/preces.php" style="display:block;text-align:center;margin-top:8px;font-size:11px;color:var(--grey2);text-decoration:none;">✕ Atcelt</a><?php endif; ?>
    </form>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function prevImg(i){if(i.files&&i.files[0]){const r=new FileReader();r.onload=e=>{document.getElementById('pp2').src=e.target.result;document.getElementById('pp2').style.display='block';document.getElementById('pp').style.display='none';};r.readAsDataURL(i.files[0]);}}
</script>
