<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Manas Galerijas';
$extraCss = 'galerijas.css';

$klientsId = isset($_SESSION['klients_id']) ? (int)$_SESSION['klients_id'] : null;

$galerijas = [];
if ($klientsId) {
  $result = mysqli_query($savienojums, "SELECT * FROM galerijas WHERE klienta_id=$klientsId ORDER BY izveidota DESC");
  while ($row = mysqli_fetch_assoc($result)) {
    // Get cover photo (first photo from galeriju_foto)
    $cover = mysqli_fetch_assoc(mysqli_query($savienojums,
      "SELECT attels_url FROM galeriju_foto WHERE galerijas_id={$row['id']} ORDER BY seciba ASC, id ASC LIMIT 1"
    ));
    $row['cover'] = $cover ? $cover['attels_url'] : null;
    $galerijas[] = $row;
  }
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
  <div class="reveal" style="text-align:center;padding:80px 40px;max-width:500px;margin:0 auto;">
    <div style="font-size:48px;margin-bottom:24px;opacity:.3;">🔒</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;color:var(--ink);margin-bottom:16px;">Pieslēdzieties, lai skatītu</h2>
    <p style="font-size:14px;color:var(--grey);line-height:1.8;margin-bottom:32px;">Jūsu fotogrāfijas ir drošībā. Pieslēdzieties ar savu kontu.</p>
    <div style="display:flex;gap:14px;justify-content:center;">
      <a href="/4pt/blazkova/lumina/Lumina/login.php" class="btn-primary">Pieslēgties →</a>
      <a href="/4pt/blazkova/lumina/Lumina/registracija.php" class="btn-outline">Reģistrēties</a>
    </div>
  </div>

  <?php elseif (empty($galerijas)): ?>
  <div class="reveal" style="text-align:center;padding:80px 40px;">
    <div style="font-size:48px;margin-bottom:24px;opacity:.3;">📷</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;color:var(--ink);margin-bottom:16px;">Vēl nav galeriju</h2>
    <p style="font-size:14px;color:var(--grey);line-height:1.8;margin-bottom:32px;">Pēc fotosesijas mēs augšupielādēsim fotogrāfijas šeit.</p>
    <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt sesiju →</a>
  </div>

  <?php else: ?>
  <div class="galerijas-grid">
    <?php foreach ($galerijas as $g):
      // Cover image
      if ($g['cover']) {
        $coverSrc = filter_var($g['cover'], FILTER_VALIDATE_URL)
          ? $g['cover']
          : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . $g['cover'];
      } else {
        $coverSrc = null;
      }
      $isExpired = $g['deriga_lidz'] && $g['deriga_lidz'] < date('Y-m-d');
    ?>
    <div class="galerija-card reveal" onclick="window.location='/4pt/blazkova/lumina/Lumina/galerija.php?id=<?= $g['id'] ?>'">
      <div class="galerija-thumb" style="position:relative;overflow:hidden;aspect-ratio:4/3;background:var(--cream2);">
        <?php if ($coverSrc): ?>
          <img src="<?= htmlspecialchars($coverSrc) ?>" alt="<?= htmlspecialchars($g['nosaukums']) ?>"
            style="width:100%;height:100%;object-fit:cover;transition:transform .5s;"
            onmouseover="this.style.transform='scale(1.05)'"
            onmouseout="this.style.transform='scale(1)'"
            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;font-size:36px;opacity:.25;position:absolute;inset:0;">📷</div>
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px;opacity:.2;">📷</div>
        <?php endif; ?>
        <div class="galerija-count" style="position:absolute;top:12px;right:12px;background:rgba(0,0,0,.55);color:#fff;font-size:11px;padding:4px 10px;border-radius:20px;backdrop-filter:blur(4px);">
          <?= $g['foto_skaits'] ?> foto
        </div>
        <?php if ($isExpired): ?>
        <div style="position:absolute;top:12px;left:12px;background:rgba(192,57,43,.85);color:#fff;font-size:9px;padding:3px 8px;border-radius:3px;text-transform:uppercase;letter-spacing:1px;">Beidzies</div>
        <?php endif; ?>
        <div style="position:absolute;inset:0;background:linear-gradient(transparent 50%,rgba(0,0,0,.4));"></div>
      </div>
      <div class="galerija-info" style="padding:20px 0 0;">
        <div class="galerija-title" style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--ink);margin-bottom:6px;"><?= htmlspecialchars($g['nosaukums']) ?></div>
        <?php if ($g['apraksts']): ?><div style="font-size:12px;color:var(--grey);margin-bottom:6px;"><?= htmlspecialchars($g['apraksts']) ?></div><?php endif; ?>
        <div style="display:flex;justify-content:space-between;align-items:center;font-size:11px;color:var(--grey);">
          <span><?= $g['foto_skaits'] ?> fotogrāfijas · Derīgs līdz <?= $g['deriga_lidz'] ? date('d.m.Y', strtotime($g['deriga_lidz'])) : '—' ?></span>
          <span style="color:var(--gold);">Skatīt →</span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
