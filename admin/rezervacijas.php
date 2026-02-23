<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Rezervācijas';

// Handle status update
if (isset($_GET['status'])) {
  $id = (int)$_GET['id'];
  $status = escape($savienojums, $_GET['status']);
  mysqli_query($savienojums, "UPDATE rezervacijas SET statuss='$status' WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?updated=1');
  exit;
}
// Handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  mysqli_query($savienojums, "DELETE FROM rezervacijas WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?deleted=1');
  exit;
}

// Fetch
$filter = isset($_GET['filter']) ? escape($savienojums, $_GET['filter']) : '';
$where = $filter ? "WHERE r.statuss='$filter'" : '';
$result = mysqli_query($savienojums, "
  SELECT r.*, k.vards, k.uzvards, k.epasts, k.talrunis
  FROM rezervacijas r 
  LEFT JOIN klienti k ON r.klienta_id = k.id
  $where
  ORDER BY r.datums DESC
");
$items = [];
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Statuss atjaunināts.</div><?php endif; ?>
<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Rezervācija dzēsta.</div><?php endif; ?>

<div class="section-header">
  <div class="section-heading">Rezervācijas (<?= count($items) ?>)</div>
  <div style="display:flex;gap:6px;">
    <a href="?" class="action-btn <?= !$filter ? 'success' : '' ?>">Visas</a>
    <a href="?filter=apstiprinats" class="action-btn">Apstiprinātas</a>
    <a href="?filter=pabeigts" class="action-btn">Pabeigtas</a>
    <a href="?filter=atcelts" class="action-btn">Atceltas</a>
  </div>
</div>

<div class="admin-card" style="padding:0;overflow:hidden;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Klients</th>
        <th>Pakalpojums</th>
        <th>Datums / Laiks</th>
        <th>Vieta</th>
        <th>Cena</th>
        <th>Statuss</th>
        <th>Darbības</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $r): ?>
      <tr>
        <td style="color:var(--grey2);"><?= $r['id'] ?></td>
        <td>
          <strong><?= $r['vards'] ? htmlspecialchars($r['vards'] . ' ' . $r['uzvards']) : '<em style="color:var(--grey2)">Nav konts</em>' ?></strong>
          <?php if ($r['epasts']): ?><div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars($r['epasts']) ?></div><?php endif; ?>
        </td>
        <td style="max-width:200px;"><?= htmlspecialchars($r['pakalpojums']) ?></td>
        <td><?= date('d.m.Y', strtotime($r['datums'])) ?><br><small style="color:var(--grey2);"><?= substr($r['laiks'], 0, 5) ?></small></td>
        <td><?= $r['vieta'] ? htmlspecialchars($r['vieta']) : '—' ?></td>
        <td style="color:var(--gold);font-weight:500;"><?= $r['cena'] ? '€' . number_format($r['cena'], 0) : '—' ?></td>
        <td>
          <span class="status-badge <?= $r['statuss'] === 'apstiprinats' ? 'pending' : ($r['statuss'] === 'pabeigts' ? 'delivered' : 'cancelled') ?>">
            <?= $r['statuss'] ?>
          </span>
        </td>
        <td>
          <?php if ($r['statuss'] !== 'pabeigts'): ?>
          <a href="?status=pabeigts&id=<?= $r['id'] ?>" class="action-btn success">Pabeigt</a>
          <?php endif; ?>
          <?php if ($r['statuss'] !== 'atcelts'): ?>
          <a href="?status=atcelts&id=<?= $r['id'] ?>" class="action-btn danger">Atcelt</a>
          <?php endif; ?>
          <a href="?delete=<?= $r['id'] ?>" class="action-btn danger" onclick="return confirm('Dzēst rezervāciju #<?= $r['id'] ?>?')">✕</a>
        </td>
      </tr>
      <?php if ($r['papildu_info']): ?>
      <tr>
        <td colspan="8" style="background:var(--cream2);font-size:12px;color:var(--grey);font-style:italic;padding:8px 16px;">
          💬 <?= htmlspecialchars($r['papildu_info']) ?>
        </td>
      </tr>
      <?php endif; ?>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
      <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--grey2);">Nav rezervāciju</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
