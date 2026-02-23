<?php
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Klientu Galerijas';
$extraCss = 'galerijas.css';

// If logged in, show their galleries
$klientsId = isset($_SESSION['klients_id']) ? (int)$_SESSION['klients_id'] : null;

$galerijas = [];
if ($klientsId) {
  $sql = "SELECT * FROM galerijas WHERE klienta_id = $klientsId ORDER BY izveidota DESC";
  $result = mysqli_query($savienojums, $sql);
  while ($row = mysqli_fetch_assoc($result)) $galerijas[] = $row;
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div class="section-label">Privātās galerijas</div>
  <h1>Jūsu <em>Galerijas</em></h1>
  <p>Šeit atradīsiet savas fotosesiju fotogrāfijas. Pieejams tikai reģistrētiem klientiem.</p>
</div>

<section class="galerijas-section">
  <?php if (!$klientsId): ?>
    <!-- Not logged in -->
    <div class="login-prompt reveal">
      <div style="text-align:center;padding:80px 40px;max-width:500px;margin:0 auto;">
        <div style="font-size:48px;margin-bottom:24px;opacity:.3;">🔒</div>
        <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;color:var(--ink);margin-bottom:16px;">Pieslēdzieties, lai skatītu</h2>
        <p style="font-size:14px;color:var(--grey);line-height:1.8;margin-bottom:32px;">Jūsu fotogrāfijas ir drošībā. Pieslēdzieties ar savu kontu, lai piekļūtu savām privātajām galerijām.</p>
        <div style="display:flex;gap:14px;justify-content:center;">
          <a href="/4pt/blazkova/lumina/Lumina/login.php" class="btn-primary">Pieslēgties →</a>
          <a href="/4pt/blazkova/lumina/Lumina/registracija.php" class="btn-outline">Reģistrēties</a>
        </div>
      </div>
    </div>
  <?php elseif (empty($galerijas)): ?>
    <!-- Logged in but no galleries -->
    <div class="reveal" style="text-align:center;padding:80px 40px;">
      <div style="font-size:48px;margin-bottom:24px;opacity:.3;">📷</div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;color:var(--ink);margin-bottom:16px;">Vēl nav galeriju</h2>
      <p style="font-size:14px;color:var(--grey);line-height:1.8;margin-bottom:32px;">Pēc jūsu fotosesijas mēs augšupielādēsim fotogrāfijas šeit.</p>
      <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt Sesiju →</a>
    </div>
  <?php else: ?>
    <!-- Show galleries -->
    <div class="galerijas-grid">
      <?php foreach ($galerijas as $g): ?>
      <div class="galerija-card reveal" onclick="window.location='/4pt/blazkova/lumina/Lumina/galerija.php?id=<?= $g['id'] ?>'">
        <div class="galerija-thumb">
          <div class="galerija-count"><?= $g['foto_skaits'] ?> foto</div>
          <div class="galerija-placeholder">
            <span>📷</span>
          </div>
        </div>
        <div class="galerija-info">
          <div class="galerija-title"><?= htmlspecialchars($g['nosaukums']) ?></div>
          <div class="galerija-meta"><?= htmlspecialchars($g['apraksts']) ?></div>
          <div class="galerija-dates">
            <span>Derīgs līdz: <?= date('d.m.Y', strtotime($g['deriga_lidz'])) ?></span>
            <span class="galerija-link">Skatīt →</span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
