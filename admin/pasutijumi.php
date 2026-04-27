<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Foto pasūtījumi';

function escape2($db, $s) { return mysqli_real_escape_string($db, $s); }

// Status update
if (isset($_GET['status'], $_GET['id'])) {
  $id     = (int)$_GET['id'];
  $status = in_array($_GET['status'], ['jauns','apstiprinats','pabeigts','atcelts']) ? $_GET['status'] : 'jauns';
  mysqli_query($savienojums, "UPDATE pasutijumi SET statuss='$status' WHERE id=$id");
  $f = isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '';
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=updated'.$f); exit;
}

// Delete
if (isset($_GET['delete'])) {
  $id  = (int)$_GET['delete'];
  $row = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT foto_fails, foto_urls FROM pasutijumi WHERE id=$id"));
  if ($row) {
    $base = __DIR__ . '/../uploads/pasutijumi/';
    if ($row['foto_fails'] && !filter_var($row['foto_fails'], FILTER_VALIDATE_URL))
      @unlink($base . $row['foto_fails']);
    if ($row['foto_urls']) {
      $arr = json_decode($row['foto_urls'], true) ?: [];
      foreach ($arr as $u)
        if (!filter_var($u, FILTER_VALIDATE_URL)) @unlink($base . basename($u));
    }
  }
  mysqli_query($savienojums, "DELETE FROM pasutijumi WHERE id=$id");
  $f = isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '';
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=deleted'.$f); exit;
}

// Fetch orders
$filter = $_GET['filter'] ?? '';
$where  = $filter ? "WHERE p.statuss='".escape2($savienojums,$filter)."'" : '';
$orders = [];
$res = mysqli_query($savienojums,
  "SELECT p.*, k.vards, k.uzvards, k.epasts
   FROM pasutijumi p
   LEFT JOIN klienti k ON p.klienta_id = k.id AND p.klienta_id > 0
   $where
   ORDER BY p.izveidots DESC"
);
while ($r = mysqli_fetch_assoc($res)) $orders[] = $r;

// Build photo URL list for one order
function orderPhotos(array $o): array {
  $base = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/';
  $out  = [];
  if (!empty($o['foto_urls'])) {
    $arr = json_decode($o['foto_urls'], true);
    if (is_array($arr)) {
      foreach ($arr as $u) {
        $u = trim($u); if (!$u) continue;
        $out[] = filter_var($u, FILTER_VALIDATE_URL) ? $u : $base . basename($u);
      }
    } else {
      $u = trim($o['foto_urls']);
      if ($u) $out[] = filter_var($u, FILTER_VALIDATE_URL) ? $u : $base . basename($u);
    }
  }
  if (!empty($o['foto_fails'])) {
    $u  = trim($o['foto_fails']);
    $r2 = filter_var($u, FILTER_VALIDATE_URL) ? $u : $base . $u;
    if (!in_array($r2, $out)) $out[] = $r2;
  }
  return array_values(array_unique(array_filter($out)));
}

include __DIR__ . '/includes/header.php';
?>
<style>
.og { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:20px; }
.oc { background:var(--white); border:1px solid var(--grey3); overflow:hidden; transition:box-shadow .2s; }
.oc:hover { box-shadow:0 4px 20px rgba(0,0,0,.1); }
.ob { padding:16px 18px; }
.op { font-family:'Cormorant Garamond',serif; font-size:19px; color:var(--ink); margin-bottom:4px; }
.om { font-size:11px; color:var(--grey2); margin-bottom:10px; }
.oa { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; padding-top:10px; border-top:1px solid var(--grey3); }
.sbadge { display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase; }
.s-jauns{background:#fff3e0;color:#e65100}.s-apstiprinats{background:#e8f5e9;color:#2e7d32}
.s-pabeigts{background:#e3f2fd;color:#1565c0}.s-atcelts{background:#fce4ec;color:#c62828}.s-apmaksats{background:#f3e5f5;color:#6a1b9a}
.strip { display:flex; gap:2px; overflow-x:auto; padding:2px; background:var(--cream2); }
.strip img { width:66px;height:66px;object-fit:cover;flex-shrink:0;cursor:pointer;border:2px solid transparent;transition:.15s;border-radius:1px; }
.strip img:hover { border-color:var(--gold); }
.pcb { font-size:10px;color:var(--grey);padding:3px 8px;background:var(--cream2);border-bottom:1px solid var(--grey3);display:flex;justify-content:space-between;align-items:center; }
/* Lightbox */
#admlx{display:none;position:fixed;inset:0;background:rgba(0,0,0,.94);z-index:9999;flex-direction:column;align-items:center;justify-content:center;}
#admlx.open{display:flex;}
#admlx-img{max-width:88vw;max-height:78vh;object-fit:contain;}
#admlx-strip{display:flex;gap:5px;margin-top:12px;overflow-x:auto;max-width:88vw;padding-bottom:4px;}
#admlx-strip img{width:58px;height:58px;object-fit:cover;cursor:pointer;opacity:.45;border:2px solid transparent;transition:.15s;flex-shrink:0;}
#admlx-strip img.on{opacity:1;border-color:#B8975A;}
#admlx-info{color:rgba(255,255,255,.45);font-size:11px;margin-top:8px;letter-spacing:1px;}
#admlx-close{position:absolute;top:18px;right:22px;background:none;border:none;color:#fff;font-size:28px;cursor:pointer;}
#admlx-btns{display:flex;gap:10px;margin-top:14px;}
.lx-nav{background:none;border:1px solid rgba(255,255,255,.25);color:#fff;padding:6px 20px;cursor:pointer;font-size:18px;transition:.15s;}
.lx-nav:hover{background:#B8975A;border-color:#B8975A;}
.lx-dl{padding:8px 20px;background:#B8975A;color:#fff;font-size:10px;letter-spacing:2px;text-transform:uppercase;text-decoration:none;}
</style>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success" style="margin-bottom:18px;">
  <?= $_GET['msg']==='deleted' ? 'Pasūtījums dzēsts.' : 'Statuss atjaunināts.' ?>
</div>
<?php endif; ?>

<div class="section-header">
  <div class="section-heading">Foto pasūtījumi</div>
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php foreach ([''=> 'Visi','jauns'=>'Jauni','apstiprinats'=>'Apstiprināti','pabeigts'=>'Pabeigti','atcelts'=>'Atcelti','apmaksats'=>'Apmaksāti'] as $f=>$lbl): ?>
    <a href="?filter=<?= urlencode($f) ?>" class="action-btn <?= $filter===$f?'success':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($orders)): ?>
<div style="text-align:center;padding:60px;color:var(--grey2);">
  <div style="font-size:48px;opacity:.2;margin-bottom:12px;">🖼️</div><p>Nav pasūtījumu.</p>
</div>
<?php else: ?>
<div class="og">
<?php foreach ($orders as $o):
  $sl     = $o['statuss'] ?? 'jauns';
  $photos = orderPhotos($o);
  $pc     = count($photos);
  $flt    = urlencode($filter);
  $oid    = (int)$o['id'];
  // Safe JSON for data attribute
  $photosData = htmlspecialchars(json_encode($photos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES);
  $titleData  = htmlspecialchars($o['produkts'] ?? '', ENT_QUOTES);
  $dispEmail  = $o['epasts'] ?: ($o['viesis_epasts'] ?? '');
?>
<div class="oc">
  <?php if ($pc > 0): ?>
  <div class="pcb">
    <span><?= $pc ?> foto</span>
    <button data-photos="<?= $photosData ?>" data-title="<?= $titleData ?>"
      onclick="admLxOpen(JSON.parse(this.dataset.photos), 0, this.dataset.title)"
      style="background:none;border:none;cursor:pointer;color:var(--gold);font-size:10px;letter-spacing:1px;text-transform:uppercase;padding:0;">
      Aplūkot visas →
    </button>
  </div>
  <!-- Main photo -->
  <div style="position:relative;cursor:pointer;"
    data-photos="<?= $photosData ?>" data-title="<?= $titleData ?>"
    onclick="admLxOpen(JSON.parse(this.dataset.photos), 0, this.dataset.title)">
    <img src="<?= htmlspecialchars($photos[0]) ?>" alt=""
      style="width:100%;height:200px;object-fit:cover;display:block;"
      onerror="this.style.display='none'">
    <?php if ($pc > 1): ?>
    <div style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,.65);color:#fff;font-size:10px;padding:2px 8px;border-radius:10px;">+<?= $pc-1 ?> vēl</div>
    <?php endif; ?>
  </div>
  <?php if ($pc > 1): ?>
  <div class="strip">
    <?php foreach ($photos as $i => $ph): ?>
    <img src="<?= htmlspecialchars($ph) ?>" alt=""
      data-photos="<?= $photosData ?>" data-title="<?= $titleData ?>"
      onclick="admLxOpen(JSON.parse(this.dataset.photos), <?= $i ?>, this.dataset.title)"
      onerror="this.style.display='none'">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php else: ?>
  <div style="height:160px;display:flex;align-items:center;justify-content:center;background:var(--cream2);font-size:44px;opacity:.18;">🖼️</div>
  <?php endif; ?>

  <div class="ob">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
      <div class="op"><?= htmlspecialchars($o['produkts'] ?? '') ?></div>
      <span class="sbadge s-<?= $sl ?>"><?= ucfirst($sl) ?></span>
    </div>
    <div class="om">
      <?php if ($o['vards']): ?>
        <strong><?= htmlspecialchars($o['vards'].' '.($o['uzvards']??'')) ?></strong>
        <?php if ($dispEmail): ?> · <a href="mailto:<?= htmlspecialchars($dispEmail) ?>" style="color:var(--gold);"><?= htmlspecialchars($dispEmail) ?></a><?php endif; ?>
      <?php else: ?>
        <em>Viesis</em><?php if ($dispEmail): ?> · <a href="mailto:<?= htmlspecialchars($dispEmail) ?>" style="color:var(--gold);font-style:normal;"><?= htmlspecialchars($dispEmail) ?></a><?php endif; ?>
      <?php endif; ?>
      <div style="margin-top:2px;">📅 <?= date('d.m.Y H:i', strtotime($o['izveidots'])) ?></div>
    </div>
    <?php if (!empty($o['papildu_info'])): ?>
    <div style="font-size:12px;color:var(--grey);background:var(--cream);padding:7px 10px;border-left:2px solid var(--gold);margin-bottom:8px;">
      <?= htmlspecialchars($o['papildu_info']) ?>
    </div>
    <?php endif; ?>
    <div class="oa">
      <?php if ($pc > 0): ?>
      <button data-photos="<?= $photosData ?>" data-title="<?= $titleData ?>"
        onclick="admDlAll(JSON.parse(this.dataset.photos), this.dataset.title)"
        class="action-btn" style="cursor:pointer;">⬇ Lejupielādēt (<?= $pc ?>)</button>
      <?php endif; ?>
      <?php if (in_array($sl, ['jauns','apmaksats'])): ?>
      <a href="?status=apstiprinats&id=<?= $oid ?>&filter=<?= $flt ?>"
         onclick="return confirm('Apstiprināt?')" class="action-btn success">✓ Apstiprināt</a>
      <?php endif; ?>
      <?php if (!in_array($sl, ['pabeigts','atcelts'])): ?>
      <a href="?status=pabeigts&id=<?= $oid ?>&filter=<?= $flt ?>"
         onclick="return confirm('Atzīmēt kā pabeigtu?')" class="action-btn">✔ Pabeigts</a>
      <?php endif; ?>
      <?php if ($sl !== 'atcelts'): ?>
      <a href="?status=atcelts&id=<?= $oid ?>&filter=<?= $flt ?>"
         onclick="return confirm('Atcelt pasūtījumu?')" class="action-btn danger">✕ Atcelt</a>
      <?php endif; ?>
      <a href="?delete=<?= $oid ?>&filter=<?= $flt ?>"
         onclick="return confirm('Dzēst #<?= $oid ?>?')" class="action-btn danger">🗑</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Lightbox -->
<div id="admlx" onclick="if(event.target===this)admLxClose()">
  <button id="admlx-close" onclick="admLxClose()">✕</button>
  <img id="admlx-img" src="" alt="">
  <div id="admlx-strip"></div>
  <div id="admlx-info"></div>
  <div id="admlx-btns">
    <button class="lx-nav" onclick="admLxNav(-1)">‹</button>
    <a class="lx-dl" id="admlx-dl" href="#" download>⬇ Lejupielādēt</a>
    <button class="lx-nav" onclick="admLxNav(1)">›</button>
  </div>
</div>

<script>
var _AP=[], _AC=0, _AT='';
function admLxOpen(p,i,t){ _AP=p; _AT=t; document.getElementById('admlx').classList.add('open'); document.body.style.overflow='hidden'; admLxShow(i); }
function admLxClose(){ document.getElementById('admlx').classList.remove('open'); document.body.style.overflow=''; }
function admLxShow(i){
  if(i<0) i=_AP.length-1; if(i>=_AP.length) i=0; _AC=i;
  document.getElementById('admlx-img').src = _AP[i];
  document.getElementById('admlx-info').textContent = _AT + ' — ' + (i+1) + ' / ' + _AP.length;
  var dl = document.getElementById('admlx-dl'); dl.href = _AP[i]; dl.download = 'lumina_foto_'+(i+1)+'.jpg';
  document.getElementById('admlx-strip').innerHTML = _AP.map(function(p,j){
    return '<img src="'+p+'" class="'+(j===i?'on':'')+'" onclick="admLxShow('+j+')" onerror="this.style.display=\'none\'">';
  }).join('');
}
function admLxNav(d){ admLxShow(_AC+d); }

function admDlAll(photos, title) {
  if (!photos || !photos.length) return;
  if (!confirm('Lejupielādēt ' + photos.length + ' foto?')) return;
  var slug = (title||'foto').replace(/[^a-z0-9]/gi,'_').toLowerCase();
  var i = 0;
  function next() {
    if (i >= photos.length) return;
    fetch(photos[i])
      .then(function(r){ return r.blob(); })
      .then(function(blob){
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'lumina_' + slug + '_' + String(i+1).padStart(2,'0') + '.jpg';
        document.body.appendChild(a); a.click(); document.body.removeChild(a);
        setTimeout(function(){ URL.revokeObjectURL(a.href); }, 1000);
        i++; setTimeout(next, 700);
      })
      .catch(function(){ window.open(photos[i],'_blank'); i++; setTimeout(next,400); });
  }
  next();
}
document.addEventListener('keydown', function(e){
  if (!document.getElementById('admlx').classList.contains('open')) return;
  if (e.key==='ArrowLeft') admLxNav(-1);
  else if (e.key==='ArrowRight') admLxNav(1);
  else if (e.key==='Escape') admLxClose();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
