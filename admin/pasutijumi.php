<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Foto pasūtījumi';

// Status update
if (isset($_GET['status'], $_GET['id'])) {
  $id = (int)$_GET['id'];
  $status = in_array($_GET['status'], ['jauns','apstiprinats','pabeigts','atcelts']) ? $_GET['status'] : 'jauns';
  mysqli_query($savienojums, "UPDATE pasutijumi SET statuss='$status' WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=updated'); exit;
}

// Delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT foto_fails FROM pasutijumi WHERE id=$id"));
  if ($row && $row['foto_fails'] && !filter_var($row['foto_fails'], FILTER_VALIDATE_URL)) {
    @unlink(__DIR__ . '/../uploads/pasutijumi/' . $row['foto_fails']);
  }
  mysqli_query($savienojums, "DELETE FROM pasutijumi WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=deleted'); exit;
}

// Fetch all orders with client info
$filter = $_GET['filter'] ?? '';
$where = $filter ? "WHERE p.statuss='".escape($savienojums,$filter)."'" : '';
$orders = [];
$res = mysqli_query($savienojums,
  "SELECT p.*, k.vards, k.uzvards, k.epasts
   FROM pasutijumi p
   LEFT JOIN klienti k ON p.klienta_id = k.id
   $where
   ORDER BY p.izveidots DESC"
);
while ($r = mysqli_fetch_assoc($res)) $orders[] = $r;

include __DIR__ . '/includes/header.php';
?>
<style>
.order-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:20px; }
.order-card { background:var(--white); border:1px solid var(--grey3); overflow:hidden; transition:.2s; }
.order-card:hover { box-shadow:0 4px 20px var(--shadow); }
.order-photo { width:100%; height:220px; object-fit:cover; display:block; background:var(--cream2); }
.order-photo-placeholder { width:100%; height:220px; background:var(--cream2); display:flex; align-items:center; justify-content:center; font-size:52px; opacity:.2; }
.order-body { padding:18px 20px; }
.order-product { font-family:'Cormorant Garamond',serif; font-size:20px; font-weight:400; color:var(--ink); margin-bottom:6px; }
.order-meta { font-size:11px; color:var(--grey2); margin-bottom:12px; }
.order-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:14px; padding-top:14px; border-top:1px solid var(--grey3); }
.sp { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase; }
.sp-jauns { background:#fff3e0;color:#e65100; }
.sp-apstiprinats { background:#e8f5e9;color:#2e7d32; }
.sp-pabeigts { background:#e3f2fd;color:#1565c0; }
.sp-atcelts { background:#fce4ec;color:#c62828; }
.sp-apmaksats { background:#f3e5f5;color:#6a1b9a; }
</style>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">
  <?= $_GET['msg']==='updated' ? 'Statuss atjaunināts.' : 'Pasūtījums dzēsts.' ?>
</div>
<?php endif; ?>

<div class="section-header">
  <div class="section-heading">Foto pasūtījumi</div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php
    $filters = [''=> 'Visi', 'jauns'=>'Jauni', 'apstiprinats'=>'Apstiprināti', 'pabeigts'=>'Pabeigti', 'atcelts'=>'Atcelti', 'apmaksats'=>'Apmaksāti'];
    foreach ($filters as $f => $lbl):
    ?>
    <a href="?filter=<?= $f ?>" class="action-btn <?= $filter===$f?'success':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($orders)): ?>
<div style="text-align:center;padding:60px;color:var(--grey2);">
  <div style="font-size:48px;opacity:.2;margin-bottom:16px;">🖼️</div>
  <p>Nav pasūtījumu.</p>
</div>
<?php else: ?>
<div class="order-grid">
  <?php foreach ($orders as $o):
    $sl = $o['statuss'] ?? 'jauns';
    $fotoSrc = null;
    if (!empty($o['foto_fails'])) {
      if (filter_var($o['foto_fails'], FILTER_VALIDATE_URL)) {
        $fotoSrc = $o['foto_fails'];
      } else {
        $fotoSrc = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $o['foto_fails'];
      }
    }
  ?>
  <div class="order-card">
    <?php if ($fotoSrc): ?>
    <a href="<?= htmlspecialchars($fotoSrc) ?>" target="_blank">
      <img src="<?= htmlspecialchars($fotoSrc) ?>" class="order-photo" alt="" onerror="this.parentNode.innerHTML='<div class=\'order-photo-placeholder\'>🖼️</div>'">
    </a>
    <?php else: ?>
    <div class="order-photo-placeholder">🖼️</div>
    <?php endif; ?>

    <div class="order-body">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
        <div class="order-product"><?= htmlspecialchars($o['produkts']) ?></div>
        <span class="sp sp-<?= $sl ?>"><?= ucfirst($sl) ?></span>
      </div>

      <div class="order-meta">
        <?php if ($o['vards']): ?>
        <strong><?= htmlspecialchars($o['vards'].' '.($o['uzvards']??'')) ?></strong>
        <?php if ($o['epasts']): ?> · <a href="mailto:<?= htmlspecialchars($o['epasts']) ?>" style="color:var(--gold);"><?= htmlspecialchars($o['epasts']) ?></a><?php endif; ?>
        <?php else: ?>
        <em style="color:var(--grey2);">Viesis</em>
        <?php endif; ?>
        <div style="margin-top:4px;">📅 <?= date('d.m.Y H:i', strtotime($o['izveidots'])) ?></div>
      </div>

      <?php if (!empty($o['papildu_info'])): ?>
      <div style="font-size:12px;color:var(--grey);background:var(--cream);padding:8px 12px;border-left:2px solid var(--gold);margin-bottom:8px;">
        💬 <?= htmlspecialchars($o['papildu_info']) ?>
      </div>
      <?php endif; ?>

      <div class="order-actions">
        <?php if ($sl === 'jauns' || $sl === 'apmaksats'): ?>
        <a href="?status=apstiprinats&id=<?= $o['id'] ?>" class="action-btn success"
           onclick="return confirm('Apstiprināt pasūtījumu?')">✓ Apstiprināt</a>
        <?php endif; ?>
        <?php if ($sl !== 'pabeigts' && $sl !== 'atcelts'): ?>
        <a href="?status=pabeigts&id=<?= $o['id'] ?>" class="action-btn"
           onclick="return confirm('Atzīmēt kā pabeigtu?')">✔ Pabeigts</a>
        <?php endif; ?>
        <?php if ($sl !== 'atcelts'): ?>
        <a href="?status=atcelts&id=<?= $o['id'] ?>" class="action-btn danger"
           onclick="return confirm('Atcelt pasūtījumu?')">✕ Atcelt</a>
        <?php endif; ?>
        <a href="?delete=<?= $o['id'] ?>" class="action-btn danger"
           onclick="return confirm('Dzēst pasūtījumu #<?= $o['id'] ?>?')">🗑</a>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
