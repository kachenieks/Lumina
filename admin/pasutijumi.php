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
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT foto_fails, foto_urls FROM pasutijumi WHERE id=$id"));
  if ($row) {
    if ($row['foto_fails'] && !filter_var($row['foto_fails'], FILTER_VALIDATE_URL)) {
      @unlink(__DIR__ . '/../uploads/pasutijumi/' . $row['foto_fails']);
    }
    if ($row['foto_urls']) {
      $urls = json_decode($row['foto_urls'], true) ?: [];
      foreach ($urls as $u) {
        if (!filter_var($u, FILTER_VALIDATE_URL)) @unlink(__DIR__ . '/../uploads/pasutijumi/' . basename($u));
      }
    }
  }
  mysqli_query($savienojums, "DELETE FROM pasutijumi WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=deleted'); exit;
}

// Fetch all orders with client info
$filter = $_GET['filter'] ?? '';
$where = $filter ? "WHERE p.statuss='".escape($savienojums,$filter)."'" : '';
$orders = [];
$res = mysqli_query($savienojums,
  "SELECT p.*, k.vards, k.uzvards, k.epasts, p.viesis_epasts
   FROM pasutijumi p
   LEFT JOIN klienti k ON p.klienta_id = k.id
   $where
   ORDER BY p.izveidots DESC"
);
while ($r = mysqli_fetch_assoc($res)) $orders[] = $r;

include __DIR__ . '/includes/header.php';

// Helper: build all photo URLs for an order
function getOrderPhotos(array $o): array {
  $base = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/';
  $photos = [];

  // foto_urls — can be JSON array OR a single URL string
  if (!empty($o['foto_urls'])) {
    $raw = $o['foto_urls'];
    // Try JSON array first
    $arr = json_decode($raw, true);
    if (is_array($arr)) {
      foreach ($arr as $u) {
        $u = trim($u);
        if ($u === '') continue;
        $photos[] = filter_var($u, FILTER_VALIDATE_URL) ? $u : $base . basename($u);
      }
    } else {
      // Plain URL string
      $u = trim($raw);
      if ($u !== '') $photos[] = filter_var($u, FILTER_VALIDATE_URL) ? $u : $base . basename($u);
    }
  }

  // Single foto_fails (non-album or fallback)
  if (!empty($o['foto_fails'])) {
    $u = trim($o['foto_fails']);
    $resolved = filter_var($u, FILTER_VALIDATE_URL) ? $u : $base . $u;
    if (!in_array($resolved, $photos)) $photos[] = $resolved;
  }

  return array_values(array_unique(array_filter($photos)));
}
?>
<style>
.order-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:22px; }
.order-card { background:var(--white); border:1px solid var(--grey3); overflow:hidden; transition:.2s; }
.order-card:hover { box-shadow:0 4px 24px rgba(0,0,0,.1); }
.order-body { padding:18px 20px; }
.order-product { font-family:'Cormorant Garamond',serif; font-size:20px; color:var(--ink); margin-bottom:4px; }
.order-meta { font-size:11px; color:var(--grey2); margin-bottom:10px; }
.order-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:12px; padding-top:12px; border-top:1px solid var(--grey3); }
.sp { display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase; }
.sp-jauns{background:#fff3e0;color:#e65100;}.sp-apstiprinats{background:#e8f5e9;color:#2e7d32;}
.sp-pabeigts{background:#e3f2fd;color:#1565c0;}.sp-atcelts{background:#fce4ec;color:#c62828;}.sp-apmaksats{background:#f3e5f5;color:#6a1b9a;}

/* Photo strip */
.photo-strip { display:flex; gap:3px; overflow-x:auto; padding:3px; background:var(--cream2); }
.photo-strip::-webkit-scrollbar { height:4px; }
.photo-strip::-webkit-scrollbar-thumb { background:var(--grey3); }
.photo-strip-thumb { width:72px; height:72px; object-fit:cover; flex-shrink:0; cursor:pointer;
  border:2px solid transparent; transition:.15s; border-radius:2px; }
.photo-strip-thumb:hover { border-color:var(--gold); transform:scale(1.04); }
.photo-strip-thumb.active { border-color:var(--gold); }
.photo-count-badge { font-size:10px; color:var(--grey); padding:4px 8px; background:var(--cream2);
  border-bottom:1px solid var(--grey3); display:flex; justify-content:space-between; align-items:center; }

/* Lightbox */
#lx { display:none; position:fixed; inset:0; background:rgba(0,0,0,.93); z-index:9999;
  align-items:center; justify-content:center; flex-direction:column; }
#lx.open { display:flex; }
#lx-img { max-width:88vw; max-height:80vh; object-fit:contain; border:1px solid rgba(255,255,255,.1); }
#lx-strip { display:flex; gap:6px; margin-top:14px; overflow-x:auto; max-width:88vw; padding-bottom:4px; }
#lx-strip img { width:60px; height:60px; object-fit:cover; cursor:pointer; opacity:.5;
  border:2px solid transparent; transition:.15s; flex-shrink:0; }
#lx-strip img.active { opacity:1; border-color:var(--gold,#B8975A); }
#lx-info { color:rgba(255,255,255,.6); font-size:12px; margin-top:10px; letter-spacing:1px; }
#lx-nav { display:flex; gap:16px; margin-top:16px; }
#lx-nav button { background:none; border:1px solid rgba(255,255,255,.3); color:#fff; padding:8px 22px;
  cursor:pointer; font-size:18px; transition:.15s; }
#lx-nav button:hover { background:var(--gold,#B8975A); border-color:var(--gold,#B8975A); }
#lx-close { position:absolute; top:20px; right:24px; background:none; border:none; color:#fff;
  font-size:28px; cursor:pointer; opacity:.7; transition:.15s; }
#lx-close:hover { opacity:1; }
#lx-download { display:inline-block; margin-top:10px; padding:8px 22px; background:var(--gold,#B8975A);
  color:#fff; font-size:11px; letter-spacing:2px; text-transform:uppercase; text-decoration:none;
  transition:.15s; border:none; cursor:pointer; }
#lx-download:hover { background:#9a7d48; }
#lx-dl-all { display:inline-block; margin-top:10px; margin-left:8px; padding:8px 22px;
  background:transparent; border:1px solid rgba(255,255,255,.4); color:#fff; font-size:11px;
  letter-spacing:2px; text-transform:uppercase; cursor:pointer; transition:.15s; }
#lx-dl-all:hover { background:rgba(255,255,255,.1); }
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
    $photos = getOrderPhotos($o);
    $photoCount = count($photos);
    $orderId = $o['id'];
    $photosJson = json_encode($photos);
  ?>
  <div class="order-card">

    <?php if ($photoCount > 0): ?>
    <!-- Photo count badge -->
    <div class="photo-count-badge">
      <span><?= $photoCount ?> foto · <span id="idx-<?= $orderId ?>">1 / <?= $photoCount ?></span></span>
      <button onclick="openLightbox(<?= $photosJson ?>, 0, '<?= htmlspecialchars(addslashes($o['produkts'])) ?>')"
        style="background:none;border:none;cursor:pointer;color:var(--gold);font-size:11px;letter-spacing:1px;text-transform:uppercase;padding:0;">
        Aplūkot visas →
      </button>
    </div>

    <!-- Main preview (first photo, clickable) -->
    <div style="position:relative;cursor:pointer;" onclick="openLightbox(<?= $photosJson ?>, 0, '<?= htmlspecialchars(addslashes($o['produkts'])) ?>')">
      <img src="<?= htmlspecialchars($photos[0]) ?>" alt=""
        style="width:100%;height:220px;object-fit:cover;display:block;"
        onerror="this.parentNode.innerHTML='<div style=\'height:220px;display:flex;align-items:center;justify-content:center;background:var(--cream2);font-size:48px;opacity:.2;\'>🖼️</div>'">
      <?php if ($photoCount > 1): ?>
      <div style="position:absolute;bottom:8px;right:8px;background:rgba(0,0,0,.65);color:#fff;font-size:10px;padding:3px 8px;border-radius:12px;letter-spacing:.5px;">
        +<?= $photoCount-1 ?> vēl
      </div>
      <?php endif; ?>
      <div style="position:absolute;inset:0;background:rgba(0,0,0,0);transition:.2s;"
        onmouseover="this.style.background='rgba(0,0,0,.15)'"
        onmouseout="this.style.background='rgba(0,0,0,0)'">
      </div>
    </div>

    <?php if ($photoCount > 1): ?>
    <!-- Thumbnail strip -->
    <div class="photo-strip" id="strip-<?= $orderId ?>">
      <?php foreach ($photos as $i => $ph): ?>
      <img src="<?= htmlspecialchars($ph) ?>" class="photo-strip-thumb <?= $i===0?'active':'' ?>"
        onclick="openLightbox(<?= $photosJson ?>, <?= $i ?>, '<?= htmlspecialchars(addslashes($o['produkts'])) ?>')"
        onerror="this.style.display='none'">
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div style="height:180px;display:flex;align-items:center;justify-content:center;background:var(--cream2);font-size:48px;opacity:.18;">🖼️</div>
    <?php endif; ?>

    <div class="order-body">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px;">
        <div class="order-product"><?= htmlspecialchars($o['produkts']) ?></div>
        <span class="sp sp-<?= $sl ?>"><?= ucfirst($sl) ?></span>
      </div>

      <div class="order-meta">
        <?php
        $dispEmail = $o['epasts'] ?: ($o['viesis_epasts'] ?? '');
        ?>
        <?php if ($o['vards']): ?>
        <strong><?= htmlspecialchars($o['vards'].' '.($o['uzvards']??'')) ?></strong>
        <?php if ($dispEmail): ?> · <a href="mailto:<?= htmlspecialchars($dispEmail) ?>" style="color:var(--gold);"><?= htmlspecialchars($dispEmail) ?></a><?php endif; ?>
        <?php else: ?>
        <em style="color:var(--grey2);">Viesis</em><?php if ($dispEmail): ?> · <a href="mailto:<?= htmlspecialchars($dispEmail) ?>" style="color:var(--gold);font-style:normal;"><?= htmlspecialchars($dispEmail) ?></a><?php endif; ?>
        <?php endif; ?>
        <div style="margin-top:3px;">📅 <?= date('d.m.Y H:i', strtotime($o['izveidots'])) ?></div>
      </div>

      <?php if (!empty($o['papildu_info'])): ?>
      <div style="font-size:12px;color:var(--grey);background:var(--cream);padding:8px 12px;border-left:2px solid var(--gold);margin-bottom:8px;">
        💬 <?= htmlspecialchars($o['papildu_info']) ?>
      </div>
      <?php endif; ?>

      <div class="order-actions">
        <?php if ($photoCount > 0): ?>
        <button onclick="downloadAll(<?= $photosJson ?>, '<?= addslashes($o['produkts']) ?>')"
          class="action-btn" style="cursor:pointer;">
          ⬇ Lejupielādēt (<?= $photoCount ?>)
        </button>
        <?php endif; ?>
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

<!-- Lightbox -->
<div id="lx">
  <button id="lx-close" onclick="closeLightbox()">✕</button>
  <img id="lx-img" src="" alt="">
  <div id="lx-strip"></div>
  <div id="lx-info"></div>
  <div id="lx-nav">
    <button onclick="lxNav(-1)">‹</button>
    <a id="lx-download" href="#" download>⬇ Lejupielādēt</a>
    <button onclick="lxNav(1)">›</button>
  </div>
</div>

<script>
let lxPhotos = [], lxCur = 0, lxTitle = '';

function openLightbox(photos, idx, title) {
  lxPhotos = photos; lxCur = idx; lxTitle = title;
  document.getElementById('lx').classList.add('open');
  document.body.style.overflow = 'hidden';
  lxShow(idx);
}

function closeLightbox() {
  document.getElementById('lx').classList.remove('open');
  document.body.style.overflow = '';
}

function lxShow(i) {
  if (i < 0) i = lxPhotos.length - 1;
  if (i >= lxPhotos.length) i = 0;
  lxCur = i;
  const img = document.getElementById('lx-img');
  img.src = lxPhotos[i];
  document.getElementById('lx-info').textContent = lxTitle + ' — ' + (i+1) + ' / ' + lxPhotos.length;
  const dl = document.getElementById('lx-download');
  dl.href = lxPhotos[i];
  dl.download = 'lumina_foto_' + (i+1) + '.jpg';
  // Thumbnails
  const strip = document.getElementById('lx-strip');
  strip.innerHTML = lxPhotos.map((p,j) =>
    `<img src="${p}" class="${j===i?'active':''}" onclick="lxShow(${j})"
      onerror="this.style.display='none'">`
  ).join('');
}

function lxNav(d) { lxShow(lxCur + d); }

// Download all photos for an order
function downloadAll(photos, title) {
  if (photos.length === 0) return;
  if (!confirm('Lejupielādēt visas ' + photos.length + ' fotogrāfijas?')) return;
  // Create hidden iframe links for each photo to avoid popup blocker
  const slug = (title||'foto').replace(/[^a-z0-9]/gi,'_').toLowerCase();
  let i = 0;
  function dlNext() {
    if (i >= photos.length) return;
    const url = photos[i];
    const a = document.createElement('a');
    // Force download via fetch blob to bypass same-origin issues
    fetch(url)
      .then(r => r.blob())
      .then(blob => {
        const blobUrl = URL.createObjectURL(blob);
        a.href = blobUrl;
        a.download = 'lumina_' + slug + '_' + String(i+1).padStart(2,'0') + '.' + (url.split('.').pop().split('?')[0] || 'jpg');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(blobUrl), 1000);
        i++;
        setTimeout(dlNext, 600);
      })
      .catch(() => {
        // Fallback: open in new tab
        window.open(url, '_blank');
        i++;
        setTimeout(dlNext, 300);
      });
  }
  dlNext();
}

// Keyboard navigation
document.addEventListener('keydown', e => {
  if (!document.getElementById('lx').classList.contains('open')) return;
  if (e.key === 'ArrowLeft') lxNav(-1);
  if (e.key === 'ArrowRight') lxNav(1);
  if (e.key === 'Escape') closeLightbox();
});

// Click outside closes
document.getElementById('lx').addEventListener('click', function(e) {
  if (e.target === this) closeLightbox();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
