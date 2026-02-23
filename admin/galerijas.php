<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Galerijas';

$uploadDir = __DIR__ . '/../uploads/galerijas/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$success = $error = '';

// Delete gallery
if (isset($_GET['delete_gal'])) {
  $id = (int)$_GET['delete_gal'];
  mysqli_query($savienojums, "DELETE FROM galeriju_foto WHERE galerijas_id=$id");
  mysqli_query($savienojums, "DELETE FROM galerijas WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/galerijas.php?msg=deleted'); exit;
}

// Delete single photo
if (isset($_GET['delete_foto'])) {
  $id = (int)$_GET['delete_foto'];
  $gid = (int)($_GET['gid'] ?? 0);
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT attels_url FROM galeriju_foto WHERE id=$id"));
  if ($row && $row['attels_url'] && !filter_var($row['attels_url'], FILTER_VALIDATE_URL)) @unlink($uploadDir . $row['attels_url']);
  mysqli_query($savienojums, "DELETE FROM galeriju_foto WHERE id=$id");
  mysqli_query($savienojums, "UPDATE galerijas SET foto_skaits=foto_skaits-1 WHERE id=$gid AND foto_skaits>0");
  header("Location: /4pt/blazkova/lumina/Lumina/admin/galerijas.php?view=$gid&msg=foto_deleted"); exit;
}

// Save gallery (create/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gallery'])) {
  $klienta_id = (int)($_POST['klienta_id'] ?? 0);
  $nosaukums  = escape($savienojums, $_POST['nosaukums'] ?? '');
  $apraksts   = escape($savienojums, $_POST['apraksts'] ?? '');
  $deriga_lidz = escape($savienojums, $_POST['deriga_lidz'] ?? '');
  $editId     = (int)($_POST['edit_id'] ?? 0);

  if ($editId) {
    mysqli_query($savienojums, "UPDATE galerijas SET klienta_id=$klienta_id, nosaukums='$nosaukums', apraksts='$apraksts', deriga_lidz='$deriga_lidz' WHERE id=$editId");
    $success = 'Galerija atjaunināta!';
  } else {
    mysqli_query($savienojums, "INSERT INTO galerijas (klienta_id, nosaukums, apraksts, foto_skaits, deriga_lidz, izveidota) VALUES ($klienta_id,'$nosaukums','$apraksts',0,'$deriga_lidz',NOW())");
    $success = 'Galerija izveidota!';
  }
  header('Location: /4pt/blazkova/lumina/Lumina/admin/galerijas.php?msg=saved'); exit;
}

// Upload photos to gallery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photos'])) {
  $galId = (int)($_POST['galerijas_id'] ?? 0);
  $uploaded = 0;
  if ($galId && !empty($_FILES['fotos']['name'][0])) {
    foreach ($_FILES['fotos']['tmp_name'] as $i => $tmp) {
      if (empty($tmp)) continue;
      $ext = strtolower(pathinfo($_FILES['fotos']['name'][$i], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png','webp'])) continue;
      $filename = uniqid('gal_') . '.' . $ext;
      if (move_uploaded_file($tmp, $uploadDir . $filename)) {
        mysqli_query($savienojums, "INSERT INTO galeriju_foto (galerijas_id, attels_url, pievienots) VALUES ($galId,'$filename',NOW())");
        $uploaded++;
      }
    }
    if ($uploaded) {
      mysqli_query($savienojums, "UPDATE galerijas SET foto_skaits=foto_skaits+$uploaded WHERE id=$galId");
      $success = "$uploaded foto augšupielādēti!";
    } else {
      $error = 'Neizdevās augšupielādēt. Pārbaudi chmod 755 uploads/galerijas/';
    }
  }
}

// Clients
$klienti = [];
$kRes = mysqli_query($savienojums, "SELECT id, vards, uzvards, epasts FROM klienti ORDER BY vards");
while ($k = mysqli_fetch_assoc($kRes)) $klienti[] = $k;

// Galleries
$galFilter = isset($_GET['klients']) ? (int)$_GET['klients'] : 0;
$where = $galFilter ? "WHERE g.klienta_id=$galFilter" : '';
$gals = [];
$gRes = mysqli_query($savienojums, "SELECT g.*, k.vards, k.uzvards FROM galerijas g LEFT JOIN klienti k ON g.klienta_id=k.id $where ORDER BY g.izveidota DESC");
while ($g = mysqli_fetch_assoc($gRes)) $gals[] = $g;

// View specific gallery
$viewGal = null;
$galPhotos = [];
if (isset($_GET['view'])) {
  $vid = (int)$_GET['view'];
  $viewGal = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT g.*,k.vards,k.uzvards,k.epasts FROM galerijas g LEFT JOIN klienti k ON g.klienta_id=k.id WHERE g.id=$vid"));
  $pRes = mysqli_query($savienojums, "SELECT * FROM galeriju_foto WHERE galerijas_id=$vid ORDER BY id DESC");
  while ($p = mysqli_fetch_assoc($pRes)) $galPhotos[] = $p;
}

$editGal = null;
if (isset($_GET['edit'])) {
  $editGal = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM galerijas WHERE id=" . (int)$_GET['edit']));
}

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?= ['deleted'=>'Dzēsts.','saved'=>'Saglabāts!','foto_deleted'=>'Foto dzēsts.'][$_GET['msg']] ?? 'OK' ?></div>
<?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($viewGal): ?>
<!-- ══════════ GALLERY VIEW ══════════ -->
<div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
  <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php" style="color:var(--gold);text-decoration:none;font-size:13px;">← Atpakaļ</a>
  <div>
    <div class="section-heading"><?= htmlspecialchars($viewGal['nosaukums']) ?></div>
    <div style="font-size:12px;color:var(--grey2);">Klients: <?= htmlspecialchars($viewGal['vards'].' '.$viewGal['uzvards']) ?> · <?= $viewGal['foto_skaits'] ?> foto · Derīga līdz: <?= $viewGal['deriga_lidz'] ?: '—' ?></div>
  </div>
</div>

<!-- Upload form -->
<div class="admin-card" style="margin-bottom:24px;">
  <div class="section-heading" style="margin-bottom:14px;">📸 Augšupielādēt foto klientam</div>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="upload_photos" value="1">
    <input type="hidden" name="galerijas_id" value="<?= $viewGal['id'] ?>">
    <div class="upload-zone" id="galDropzone" onclick="document.getElementById('galFileInput').click()" style="cursor:pointer;padding:24px;text-align:center;">
      <input type="file" id="galFileInput" name="fotos[]" multiple accept="image/*" style="display:none;" onchange="showGalPreviews(this)">
      <div style="font-size:32px;">📁</div>
      <div style="font-size:14px;color:var(--ink);margin:8px 0 4px;">Ievilciet vai klikšķiniet lai pievienotu foto</div>
      <div style="font-size:11px;color:var(--grey2);">JPG, PNG, WEBP · Var izvēlēties vairākus uzreiz</div>
    </div>
    <div id="galPreviews" style="display:grid;grid-template-columns:repeat(6,1fr);gap:6px;margin-top:10px;"></div>
    <button type="submit" class="btn-primary" style="margin-top:14px;">⬆ Augšupielādēt →</button>
  </form>
</div>

<!-- Photo grid -->
<div class="section-heading" style="margin-bottom:12px;">Galerijas foto (<?= count($galPhotos) ?>)</div>
<?php if (empty($galPhotos)): ?>
<div style="text-align:center;padding:40px;color:var(--grey2);border:1px dashed var(--border);border-radius:8px;">Galerija ir tukša. Augšupielādējiet foto.</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">
  <?php foreach ($galPhotos as $p):
    $src = filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . $p['attels_url'];
  ?>
  <div style="position:relative;aspect-ratio:1;border-radius:6px;overflow:hidden;background:#eee;">
    <img src="<?= htmlspecialchars($src) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='https://via.placeholder.com/140'">
    <div style="position:absolute;top:4px;right:4px;">
      <a href="?delete_foto=<?= $p['id'] ?>&gid=<?= $viewGal['id'] ?>" onclick="return confirm('Dzēst šo foto?')" style="background:rgba(192,57,43,.85);color:#fff;border:none;border-radius:4px;padding:3px 7px;font-size:11px;cursor:pointer;text-decoration:none;">✕</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ══════════ GALLERY LIST ══════════ -->
<div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start;">

  <div>
    <div class="section-header">
      <div class="section-heading">Galerijas (<?= count($gals) ?>)</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        <a href="?" class="action-btn <?= !$galFilter?'success':'' ?>">Visas</a>
        <?php foreach ($klienti as $k): ?>
        <a href="?klients=<?= $k['id'] ?>" class="action-btn <?= $galFilter===$k['id']?'success':'' ?>"><?= htmlspecialchars($k['vards']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-card" style="padding:0;overflow:hidden;">
      <table class="admin-table">
        <thead><tr><th>Galerija</th><th>Klients</th><th>Foto</th><th>Derīga līdz</th><th>Darbības</th></tr></thead>
        <tbody>
          <?php foreach ($gals as $g): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($g['nosaukums']) ?></strong>
              <?php if ($g['apraksts']): ?><div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars(substr($g['apraksts'],0,60)) ?></div><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($g['vards'].' '.$g['uzvards']) ?></td>
            <td>
              <a href="?view=<?= $g['id'] ?>" style="color:var(--gold);text-decoration:none;font-weight:600;"><?= $g['foto_skaits'] ?> foto ↗</a>
            </td>
            <td style="font-size:12px;color:<?= $g['deriga_lidz'] && $g['deriga_lidz'] < date('Y-m-d') ? '#c0392b' : 'var(--grey2)' ?>;">
              <?= $g['deriga_lidz'] ?: '—' ?>
            </td>
            <td>
              <a href="?view=<?= $g['id'] ?>" class="action-btn success">📸 Foto</a>
              <a href="?edit=<?= $g['id'] ?>" class="action-btn">✏</a>
              <a href="?delete_gal=<?= $g['id'] ?>" onclick="return confirm('Dzēst galeriju un visus tās foto?')" class="action-btn danger">🗑</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($gals)): ?><tr><td colspan="5" style="text-align:center;padding:28px;color:var(--grey2);">Nav galeriju</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:16px;"><?= $editGal ? '✏ Rediģēt galeriju' : '+ Jauna galerija' ?></div>
    <form method="POST">
      <input type="hidden" name="save_gallery" value="1">
      <?php if ($editGal): ?><input type="hidden" name="edit_id" value="<?= $editGal['id'] ?>"><?php endif; ?>
      <div class="form-group">
        <label class="form-label">Klients *</label>
        <select name="klienta_id" class="form-select" required>
          <option value="">Izvēlieties klientu...</option>
          <?php foreach ($klienti as $k): ?>
          <option value="<?= $k['id'] ?>" <?= ($editGal['klienta_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['vards'].' '.$k['uzvards']) ?> (<?= htmlspecialchars($k['epasts']) ?>)
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Galerijas nosaukums *</label>
        <input type="text" name="nosaukums" class="form-input" required placeholder="Kāzas 2025 · Jana & Māris" value="<?= htmlspecialchars($editGal['nosaukums'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Apraksts</label>
        <textarea name="apraksts" class="form-textarea" style="height:60px;"><?= htmlspecialchars($editGal['apraksts'] ?? '') ?></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">Derīga līdz</label>
        <input type="date" name="deriga_lidz" class="form-input" value="<?= $editGal['deriga_lidz'] ?? date('Y-m-d', strtotime('+90 days')) ?>">
      </div>
      <button type="submit" class="btn-primary" style="width:100%;"><?= $editGal ? 'Saglabāt' : 'Izveidot galeriju' ?> →</button>
      <?php if ($editGal): ?>
      <a href="?view=<?= $editGal['id'] ?>" class="action-btn success" style="display:block;text-align:center;margin-top:8px;">📸 Pievienot foto →</a>
      <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php" style="display:block;text-align:center;margin-top:6px;font-size:11px;color:var(--grey2);text-decoration:none;">✕ Atcelt</a>
      <?php endif; ?>
    </form>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function showGalPreviews(input) {
  const container = document.getElementById('galPreviews');
  container.innerHTML = '';
  Array.from(input.files).forEach(f => {
    const div = document.createElement('div');
    div.style.cssText = 'aspect-ratio:1;border-radius:4px;overflow:hidden;background:#eee;';
    const r = new FileReader();
    r.onload = e => div.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
    r.readAsDataURL(f);
    container.appendChild(div);
  });
}
// Drag & drop
const dz = document.getElementById('galDropzone');
if (dz) {
  dz.addEventListener('dragover', e => { e.preventDefault(); dz.style.borderColor='var(--gold)'; });
  dz.addEventListener('dragleave', () => dz.style.borderColor='');
  dz.addEventListener('drop', e => {
    e.preventDefault(); dz.style.borderColor='';
    const inp = document.getElementById('galFileInput');
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).forEach(f => { if(f.type.startsWith('image/')) dt.items.add(f); });
    inp.files = dt.files;
    showGalPreviews(inp);
  });
}
</script>
