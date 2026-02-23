<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$pageTitle = 'Pārskats';
include __DIR__ . '/includes/header.php';

// Stats
$totalRez = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) as n, SUM(cena) as s FROM rezervacijas"));;
$monthRez = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) as n FROM rezervacijas WHERE MONTH(izveidots)=MONTH(NOW()) AND YEAR(izveidots)=YEAR(NOW())"));
$klienti = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) as n FROM klienti"));
$preces = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) as n FROM preces WHERE aktivs=1"));
$portfolio = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) as n FROM portfolio WHERE aktivs=1"));

// Recent reservations
$rezResult = mysqli_query($savienojums, "
  SELECT r.*, k.vards, k.uzvards 
  FROM rezervacijas r 
  LEFT JOIN klienti k ON r.klienta_id = k.id 
  ORDER BY r.izveidots DESC LIMIT 5
");
$rezervacijas = [];
while ($row = mysqli_fetch_assoc($rezResult)) $rezervacijas[] = $row;
?>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-num">€<?= number_format($totalRez['s'] ?? 0, 0) ?></div>
    <div class="stat-label">Kopējie ieņēmumi</div>
    <div class="stat-change">No rezervācijām</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $totalRez['n'] ?></div>
    <div class="stat-label">Kopā rezervācijas</div>
    <div class="stat-change">↑ <?= $monthRez['n'] ?> šomēnes</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $klienti['n'] ?></div>
    <div class="stat-label">Reģistrētie klienti</div>
    <div class="stat-change stat-num" style="font-size:11px;color:#228B22;">Aktīvi</div>
  </div>
  <div class="stat-card">
    <div class="stat-num"><?= $portfolio['n'] ?></div>
    <div class="stat-label">Portfolio attēli</div>
    <div class="stat-change"><?= $preces['n'] ?> aktīvas preces</div>
  </div>
</div>

<!-- Recent Reservations -->
<div class="admin-card">
  <div class="section-header">
    <div class="section-heading">Jaunākās rezervācijas</div>
    <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php" class="action-btn">Skatīt visas →</a>
  </div>
  
  <table class="admin-table">
    <thead>
      <tr>
        <th>Klients</th>
        <th>Pakalpojums</th>
        <th>Datums</th>
        <th>Summa</th>
        <th>Statuss</th>
        <th>Darbības</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rezervacijas as $r): ?>
      <tr>
        <td>
          <strong><?= $r['vards'] ? htmlspecialchars($r['vards'] . ' ' . $r['uzvards']) : 'Nezināms klients' ?></strong>
        </td>
        <td><?= htmlspecialchars($r['pakalpojums']) ?></td>
        <td><?= date('d.m.Y', strtotime($r['datums'])) ?> · <?= substr($r['laiks'], 0, 5) ?></td>
        <td style="color:var(--gold);font-weight:500;"><?= $r['cena'] ? '€' . number_format($r['cena'], 0) : '—' ?></td>
        <td>
          <span class="status-badge <?= $r['statuss'] === 'apstiprinats' ? 'pending' : ($r['statuss'] === 'pabeigts' ? 'delivered' : 'cancelled') ?>">
            <?= htmlspecialchars($r['statuss']) ?>
          </span>
        </td>
        <td>
          <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?edit=<?= $r['id'] ?>" class="action-btn">Rediģēt</a>
          <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?delete=<?= $r['id'] ?>" class="action-btn danger" onclick="return confirm('Dzēst rezervāciju?')">Dzēst</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Quick links -->
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:20px;">
  <a href="/4pt/blazkova/lumina/Lumina/admin/portfolio.php" class="admin-card" style="text-decoration:none;display:block;transition:all .3s;">
    <div style="font-size:28px;margin-bottom:10px;">🖼</div>
    <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:4px;">Portfolio</div>
    <div style="font-size:12px;color:var(--grey);">Pievienot, rediģēt attēlus</div>
  </a>
  <a href="/4pt/blazkova/lumina/Lumina/admin/preces.php" class="admin-card" style="text-decoration:none;display:block;transition:all .3s;">
    <div style="font-size:28px;margin-bottom:10px;">🛍</div>
    <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:4px;">Veikals</div>
    <div style="font-size:12px;color:var(--grey);">Pārvaldīt produktus</div>
  </a>
  <a href="/4pt/blazkova/lumina/Lumina/admin/galerijas.php" class="admin-card" style="text-decoration:none;display:block;transition:all .3s;">
    <div style="font-size:28px;margin-bottom:10px;">🗂</div>
    <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:4px;">Galerijas</div>
    <div style="font-size:12px;color:var(--grey);">Klientu galerijas</div>
  </a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
