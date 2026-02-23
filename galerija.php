<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['klients_id'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/login.php?redirect=galerija');
  exit;
}

$klientsId = (int)$_SESSION['klients_id'];
$galId = (int)($_GET['id'] ?? 0);

if (!$galId) {
  header('Location: /4pt/blazkova/lumina/Lumina/profils.php?tab=galerijas');
  exit;
}

// Fetch gallery — make sure it belongs to this client
$gal = mysqli_fetch_assoc(mysqli_query($savienojums,
  "SELECT g.*, k.vards, k.uzvards FROM galerijas g
   LEFT JOIN klienti k ON g.klienta_id = k.id
   WHERE g.id = $galId AND g.klienta_id = $klientsId"
));

if (!$gal) {
  // Gallery not found or doesn't belong to this client
  header('Location: /4pt/blazkova/lumina/Lumina/profils.php?tab=galerijas');
  exit;
}

// Check expiry
$isExpired = $gal['deriga_lidz'] && $gal['deriga_lidz'] < date('Y-m-d');

// Fetch photos
$photos = [];
$pRes = mysqli_query($savienojums,
  "SELECT * FROM galeriju_foto WHERE galerijas_id = $galId ORDER BY seciba ASC, id ASC"
);
if ($pRes) while ($p = mysqli_fetch_assoc($pRes)) $photos[] = $p;

$pageTitle = htmlspecialchars($gal['nosaukums']);
$extraCss = 'galerijas.css';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
.galerija-hero {
  padding: 100px 64px 48px;
  background: var(--cream);
  border-bottom: 1px solid var(--grey3);
}
.galerija-hero-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}
.gal-photo-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 4px;
  padding: 4px;
}
.gal-photo-item {
  aspect-ratio: 1;
  overflow: hidden;
  background: var(--cream2);
  cursor: pointer;
  position: relative;
}
.gal-photo-item img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform .4s ease;
  display: block;
}
.gal-photo-item:hover img { transform: scale(1.04); }
.gal-photo-overlay {
  position: absolute;
  inset: 0;
  background: rgba(28,28,28,0);
  transition: background .3s;
  display: flex;
  align-items: center;
  justify-content: center;
}
.gal-photo-item:hover .gal-photo-overlay { background: rgba(28,28,28,.25); }
.gal-photo-overlay-icon {
  color: #fff;
  font-size: 28px;
  opacity: 0;
  transition: opacity .3s;
}
.gal-photo-item:hover .gal-photo-overlay-icon { opacity: 1; }

/* Fullscreen lightbox */
.gal-lightbox {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.96);
  z-index: 2000;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  pointer-events: none;
  transition: opacity .25s;
}
.gal-lightbox.open {
  opacity: 1;
  pointer-events: all;
}
.gal-lb-img {
  max-width: 90vw;
  max-height: 88vh;
  object-fit: contain;
  border-radius: 2px;
  user-select: none;
}
.gal-lb-close {
  position: absolute;
  top: 20px;
  right: 24px;
  background: none;
  border: none;
  color: rgba(255,255,255,.7);
  font-size: 32px;
  cursor: pointer;
  line-height: 1;
  transition: color .2s;
}
.gal-lb-close:hover { color: #fff; }
.gal-lb-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.2);
  color: #fff;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background .2s;
}
.gal-lb-nav:hover { background: rgba(255,255,255,.25); }
.gal-lb-prev { left: 20px; }
.gal-lb-next { right: 20px; }
.gal-lb-counter {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  color: rgba(255,255,255,.5);
  font-size: 12px;
  letter-spacing: 2px;
}
.gal-lb-download {
  position: absolute;
  top: 20px;
  right: 72px;
  color: rgba(255,255,255,.6);
  font-size: 13px;
  text-decoration: none;
  border: 1px solid rgba(255,255,255,.25);
  padding: 6px 14px;
  border-radius: 4px;
  transition: .2s;
}
.gal-lb-download:hover { background: rgba(255,255,255,.1); color: #fff; }

@media (max-width: 768px) {
  .galerija-hero { padding: 90px 22px 36px; }
  .gal-photo-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<?php if ($isExpired): ?>
<div style="background:#c0392b;color:#fff;text-align:center;padding:12px;font-size:13px;">
  ⚠ Šī galerija ir zaudējusi derīgumu (<?= htmlspecialchars($gal['deriga_lidz']) ?>). Sazinieties ar mums, lai pagarinātu piekļuvi.
</div>
<?php endif; ?>

<!-- Hero -->
<div class="galerija-hero">
  <div class="galerija-hero-inner">
    <div>
      <a href="/4pt/blazkova/lumina/Lumina/profils.php?tab=galerijas" style="font-size:12px;color:var(--gold);text-decoration:none;letter-spacing:1px;">← Atpakaļ uz galerijām</a>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,5vw,54px);font-weight:300;color:var(--ink);margin:12px 0 8px;">
        <?= htmlspecialchars($gal['nosaukums']) ?>
      </h1>
      <?php if ($gal['apraksts']): ?>
      <p style="font-size:14px;color:var(--grey);max-width:560px;line-height:1.7;"><?= htmlspecialchars($gal['apraksts']) ?></p>
      <?php endif; ?>
    </div>
    <div style="text-align:right;flex-shrink:0;">
      <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);margin-bottom:6px;">Fotosesija</div>
      <div style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--gold);"><?= count($photos) ?> foto</div>
      <?php if ($gal['deriga_lidz']): ?>
      <div style="font-size:11px;color:var(--grey);margin-top:4px;">Pieejama līdz <?= date('d.m.Y', strtotime($gal['deriga_lidz'])) ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Photos -->
<?php if (empty($photos)): ?>
<div style="text-align:center;padding:100px 40px;color:var(--grey);">
  <div style="font-size:52px;margin-bottom:20px;opacity:.25;">📷</div>
  <div style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--ink);margin-bottom:10px;">Fotogrāfijas tiek gatavotas</div>
  <p style="font-size:14px;line-height:1.8;">Jūsu sesijas foto vēl tiek apstrādāti. Paziņosim, kad būs gatavi!</p>
</div>
<?php else: ?>
<div class="gal-photo-grid">
  <?php foreach ($photos as $i => $p):
    $src = filter_var($p['attels_url'], FILTER_VALIDATE_URL)
      ? $p['attels_url']
      : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . $p['attels_url'];
  ?>
  <div class="gal-photo-item" onclick="openLb(<?= $i ?>)">
    <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($p['nosaukums'] ?: 'Foto ' . ($i+1)) ?>" loading="lazy">
    <div class="gal-photo-overlay">
      <div class="gal-photo-overlay-icon">⊕</div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Lightbox -->
<div class="gal-lightbox" id="galLightbox">
  <button class="gal-lb-close" onclick="closeLb()">×</button>
  <a id="lbDownload" href="#" download class="gal-lb-download">⬇ Lejupielādēt</a>
  <button class="gal-lb-nav gal-lb-prev" onclick="lbNav(-1)">‹</button>
  <img class="gal-lb-img" id="lbImg" src="" alt="">
  <button class="gal-lb-nav gal-lb-next" onclick="lbNav(1)">›</button>
  <div class="gal-lb-counter" id="lbCounter"></div>
</div>

<script>
const photos = <?= json_encode(array_map(function($p) {
  return filter_var($p['attels_url'], FILTER_VALIDATE_URL)
    ? $p['attels_url']
    : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . $p['attels_url'];
}, $photos)) ?>;

let current = 0;

function openLb(i) {
  current = i;
  showLb();
  document.getElementById('galLightbox').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLb() {
  document.getElementById('galLightbox').classList.remove('open');
  document.body.style.overflow = '';
}

function showLb() {
  document.getElementById('lbImg').src = photos[current];
  document.getElementById('lbDownload').href = photos[current];
  document.getElementById('lbCounter').textContent = (current + 1) + ' / ' + photos.length;
}

function lbNav(dir) {
  current = (current + dir + photos.length) % photos.length;
  showLb();
}

// Keyboard navigation
document.addEventListener('keydown', e => {
  if (!document.getElementById('galLightbox').classList.contains('open')) return;
  if (e.key === 'Escape') closeLb();
  if (e.key === 'ArrowLeft') lbNav(-1);
  if (e.key === 'ArrowRight') lbNav(1);
});

// Click outside to close
document.getElementById('galLightbox').addEventListener('click', e => {
  if (e.target === document.getElementById('galLightbox')) closeLb();
});
</script>
<?php endif; ?>

<!-- Bottom CTA -->
<div style="text-align:center;padding:60px 22px;background:var(--cream2);border-top:1px solid var(--grey3);">
  <div style="font-size:13px;color:var(--grey);margin-bottom:16px;">Vai vēlaties pasūtīt druku no savām fotogrāfijām?</div>
  <a href="/4pt/blazkova/lumina/Lumina/veikals.php" class="btn-primary">Veikals — Pasūtīt druku →</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
