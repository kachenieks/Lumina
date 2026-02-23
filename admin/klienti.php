<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Klienti';

// Handle delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  mysqli_query($savienojums, "DELETE FROM klienti WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/klienti.php?deleted=1');
  exit;
}

// Fetch with stats
$result = mysqli_query($savienojums, "
  SELECT k.*, 
    COUNT(DISTINCT r.id) as rez_skaits,
    SUM(r.cena) as rez_summa
  FROM klienti k
  LEFT JOIN rezervacijas r ON k.id = r.klienta_id
  GROUP BY k.id
  ORDER BY k.registrets DESC
");
$items = [];
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Klients dzēsts.</div><?php endif; ?>

<div class="section-header">
  <div class="section-heading">Klienti (<?= count($items) ?>)</div>
</div>

<div class="admin-card" style="padding:0;overflow:hidden;">
  <table class="admin-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Klients</th>
        <th>Kontakti</th>
        <th>Reģistrēts</th>
        <th>Rezervācijas</th>
        <th>Kopā</th>
        <th>Darbības</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $k): ?>
      <tr>
        <td style="color:var(--grey2);"><?= $k['id'] ?></td>
        <td>
          <strong><?= htmlspecialchars($k['vards'] . ' ' . $k['uzvards']) ?></strong>
        </td>
        <td>
          <div style="font-size:12px;"><?= htmlspecialchars($k['epasts']) ?></div>
          <div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars($k['talrunis']) ?></div>
        </td>
        <td style="font-size:12px;color:var(--grey2);"><?= date('d.m.Y', strtotime($k['registrets'])) ?></td>
        <td><?= $k['rez_skaits'] ?></td>
        <td style="color:var(--gold);font-weight:500;">€<?= number_format($k['kopeja_summa'] ?? 0, 2) ?></td>
        <td>
          <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php?klients=<?= $k['id'] ?>" class="action-btn">Galerijas</a>
          <a href="?delete=<?= $k['id'] ?>" class="action-btn danger" onclick="return confirm('Dzēst klientu <?= htmlspecialchars(addslashes($k['vards'])) ?>?')">Dzēst</a>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
      <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--grey2);">Nav klientu</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
