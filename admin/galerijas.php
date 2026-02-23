<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Galerijas';

// Handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  mysqli_query($savienojums, "DELETE FROM galerijas WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/galerijas.php?deleted=1');
  exit;
}

$success = '';
$error = '';

// Handle add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $klienta_id = (int)($_POST['klienta_id'] ?? 0);
  $nosaukums = escape($savienojums, $_POST['nosaukums'] ?? '');
  $apraksts = escape($savienojums, $_POST['apraksts'] ?? '');
  $foto_skaits = (int)($_POST['foto_skaits'] ?? 0);
  $deriga_lidz = escape($savienojums, $_POST['deriga_lidz'] ?? '');
  $editId = (int)($_POST['edit_id'] ?? 0);
  
  if ($editId) {
    $sql = "UPDATE galerijas SET klienta_id=$klienta_id, nosaukums='$nosaukums', apraksts='$apraksts', foto_skaits=$foto_skaits, deriga_lidz='$deriga_lidz' WHERE id=$editId";
  } else {
    $sql = "INSERT INTO galerijas (klienta_id, nosaukums, apraksts, foto_skaits, deriga_lidz) VALUES ($klienta_id, '$nosaukums', '$apraksts', $foto_skaits, '$deriga_lidz')";
  }
  
  if (mysqli_query($savienojums, $sql)) {
    $success = $editId ? 'Galerija atjaunināta!' : 'Galerija izveidota!';
  } else {
    $error = 'DB kļūda: ' . mysqli_error($savienojums);
  }
}

// Filter by client
$klientsFilter = isset($_GET['klients']) ? (int)$_GET['klients'] : 0;
$where = $klientsFilter ? "WHERE g.klienta_id=$klientsFilter" : '';

// Fetch
$items = [];
$result = mysqli_query($savienojums, "
  SELECT g.*, k.vards, k.uzvards 
  FROM galerijas g 
  LEFT JOIN klienti k ON g.klienta_id = k.id 
  $where
  ORDER BY g.izveidota DESC
");
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

// Get clients for dropdown
$klienti = [];
$kResult = mysqli_query($savienojums, "SELECT id, vards, uzvards FROM klienti ORDER BY vards");
while ($row = mysqli_fetch_assoc($kResult)) $klienti[] = $row;

$editItem = null;
if (isset($_GET['edit'])) {
  $editId = (int)$_GET['edit'];
  $editItem = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM galerijas WHERE id=$editId"));
}

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Galerija dzēsta.</div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error"><?= $error ?></div><?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:28px;align-items:start;">
  
  <!-- List -->
  <div>
    <div class="section-heading" style="margin-bottom:18px;">Klientu Galerijas (<?= count($items) ?>)</div>
    <div class="admin-card" style="padding:0;overflow:hidden;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Galerija</th>
            <th>Klients</th>
            <th>Foto</th>
            <th>Derīga līdz</th>
            <th>Darbības</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $g): ?>
          <tr>
            <td style="color:var(--grey2);"><?= $g['id'] ?></td>
            <td>
              <strong><?= htmlspecialchars($g['nosaukums']) ?></strong>
              <div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars($g['apraksts']) ?></div>
            </td>
            <td><?= $g['vards'] ? htmlspecialchars($g['vards'] . ' ' . $g['uzvards']) : '—' ?></td>
            <td><?= $g['foto_skaits'] ?></td>
            <td style="font-size:12px;"><?= date('d.m.Y', strtotime($g['deriga_lidz'])) ?></td>
            <td>
              <a href="?edit=<?= $g['id'] ?>" class="action-btn">Rediģēt</a>
              <a href="?delete=<?= $g['id'] ?>" class="action-btn danger" onclick="return confirm('Dzēst galeriju?')">Dzēst</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($items)): ?>
          <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--grey2);">Nav galeriju</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:20px;"><?= $editItem ? 'Rediģēt galeriju' : '+ Jauna galerija' ?></div>
    
    <form method="POST">
      <?php if ($editItem): ?>
      <input type="hidden" name="edit_id" value="<?= $editItem['id'] ?>">
      <?php endif; ?>
      
      <div class="form-group">
        <label class="form-label">Klients</label>
        <select name="klienta_id" class="form-select">
          <option value="">Nav piešķirts</option>
          <?php foreach ($klienti as $k): ?>
          <option value="<?= $k['id'] ?>" <?= ($editItem['klienta_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['vards'] . ' ' . $k['uzvārds']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label class="form-label">Nosaukums *</label>
        <input type="text" name="nosaukums" class="form-input" required placeholder="Kāzu fotosesija — Jūrmala" value="<?= htmlspecialchars($editItem['nosaukums'] ?? '') ?>">
      </div>
      
      <div class="form-group">
        <label class="form-label">Apraksts</label>
        <input type="text" name="apraksts" class="form-input" placeholder="Skaistākās kāzas 2026. gadā" value="<?= htmlspecialchars($editItem['apraksts'] ?? '') ?>">
      </div>
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Foto skaits</label>
          <input type="number" name="foto_skaits" class="form-input" min="0" value="<?= $editItem['foto_skaits'] ?? 0 ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Derīga līdz</label>
          <input type="date" name="deriga_lidz" class="form-input" required value="<?= $editItem['deriga_lidz'] ?? date('Y-m-d', strtotime('+6 months')) ?>">
        </div>
      </div>
      
      <button type="submit" class="btn-primary" style="width:100%;"><?= $editItem ? 'Saglabāt' : 'Izveidot' ?> →</button>
      <?php if ($editItem): ?>
      <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php" style="display:block;text-align:center;margin-top:10px;font-size:11px;color:var(--grey);text-decoration:none;">Atcelt</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
