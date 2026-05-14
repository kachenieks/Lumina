<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit;
}
$pageTitle = 'Foto pasūtījumi';

// ── POST: status update (POST is reliable, GET can be cached/blocked) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_status'])) {
  $id     = (int)$_POST['id'];
  $status = in_array($_POST['status'], ['jauns','apstiprinats','pabeigts','atcelts']) ? $_POST['status'] : 'jauns';
  mysqli_query($savienojums, "UPDATE pasutijumi SET statuss='$status' WHERE id=$id");

  // On pabeigts: append tracking code to notes + send email
  if ($status === 'pabeigts') {
    $tracking = trim($_POST['tracking'] ?? '');
    if ($tracking) {
      $ord = mysqli_fetch_assoc(mysqli_query($savienojums,
        "SELECT papildu_info FROM pasutijumi WHERE id=$id"));
      $newNote = trim(($ord['papildu_info'] ?? '')) . ($tracking ? "\nIzsekošanas kods: $tracking" : '');
      $noteEsc = escape($savienojums, $newNote);
      mysqli_query($savienojums, "UPDATE pasutijumi SET papildu_info='$noteEsc' WHERE id=$id");
    }
    // Send email
    $ord = mysqli_fetch_assoc(mysqli_query($savienojums,
      "SELECT p.*, k.vards, k.uzvards, k.epasts FROM pasutijumi p
       LEFT JOIN klienti k ON p.klienta_id=k.id AND p.klienta_id>0 WHERE p.id=$id"));
    $to    = $ord['epasts'] ?: ($ord['viesis_epasts'] ?? '');
    $vards = $ord['vards'] ? $ord['vards'].' '.($ord['uzvards']??'') : 'Klients';
    if ($to) {
      try {
        require_once __DIR__ . '/../includes/mailer.php';
        if (function_exists('mailFotoPasutijumsPabeigts'))
          mailFotoPasutijumsPabeigts($to, $vards, $ord['produkts'], $tracking);
        elseif (function_exists('mailFotoPasutijumsKlients'))
          mailFotoPasutijumsKlients($to, $vards, $ord['produkts'],
            'Pasūtījums ir nosūtīts!' . ($tracking ? ' Kods: '.$tracking : ''));
      } catch (\Throwable $e) { error_log('Mail: '.$e->getMessage()); }
    }
  }

  $f = isset($_POST['filter']) ? '&filter='.urlencode($_POST['filter']) : '';
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=updated'.$f); exit;
}

// ── Delete ──
if (isset($_GET['delete'])) {
  $id  = (int)$_GET['delete'];
  $row = mysqli_fetch_assoc(mysqli_query($savienojums,
    "SELECT foto_fails, foto_urls FROM pasutijumi WHERE id=$id"));
  if ($row) {
    $base = __DIR__ . '/../uploads/pasutijumi/';
    if ($row['foto_fails'] && !filter_var($row['foto_fails'], FILTER_VALIDATE_URL)
        && !str_starts_with($row['foto_fails'], '/'))
      @unlink($base . $row['foto_fails']);
    foreach (json_decode($row['foto_urls'] ?: '[]', true) ?: [] as $u)
      if (!filter_var($u, FILTER_VALIDATE_URL) && !str_starts_with($u, '/'))
        @unlink($base . basename($u));
  }
  mysqli_query($savienojums, "DELETE FROM pasutijumi WHERE id=$id");
  $f = isset($_GET['filter']) ? '&filter='.urlencode($_GET['filter']) : '';
  header('Location: /4pt/blazkova/lumina/Lumina/admin/pasutijumi.php?msg=deleted'.$f); exit;
}

// ── Fetch orders ──
$filter = $_GET['filter'] ?? '';
$where  = $filter ? "WHERE p.statuss='".escape($savienojums,$filter)."'" : '';
$orders = [];
$res = mysqli_query($savienojums,
  "SELECT p.*, k.vards, k.uzvards, k.epasts
   FROM pasutijumi p
   LEFT JOIN klienti k ON p.klienta_id=k.id AND p.klienta_id>0
   $where ORDER BY p.izveidots DESC");
while ($r = mysqli_fetch_assoc($res)) $orders[] = $r;

// ── Build photo URL list ──
function orderPhotos(array $o): array {
  $base = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/';
  $out  = [];
  if (!empty($o['foto_urls'])) {
    $arr = json_decode($o['foto_urls'], true);
    if (is_array($arr)) {
      foreach ($arr as $u) {
        $u = trim($u); if (!$u) continue;
        $out[] = (filter_var($u,FILTER_VALIDATE_URL)||str_starts_with($u,'/')) ? $u : $base.$u;
      }
    } elseif (trim($o['foto_urls'])) {
      $u = trim($o['foto_urls']);
      $out[] = (filter_var($u,FILTER_VALIDATE_URL)||str_starts_with($u,'/')) ? $u : $base.$u;
    }
  }
  if (!empty($o['foto_fails'])) {
    $u  = trim($o['foto_fails']);
    $r2 = (filter_var($u,FILTER_VALIDATE_URL)||str_starts_with($u,'/')) ? $u : $base.$u;
    if (!in_array($r2,$out)) $out[] = $r2;
  }
  return array_values(array_unique(array_filter($out)));
}

include __DIR__ . '/includes/header.php';
?>
<style>
.og{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;}
.oc{background:var(--white);border:1px solid var(--grey3);overflow:hidden;transition:box-shadow .2s;}
.oc:hover{box-shadow:0 4px 20px rgba(0,0,0,.09);}
.ob{padding:16px 18px;}
.opr{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--ink);margin-bottom:4px;}
.om{font-size:11px;color:var(--grey2);margin-bottom:10px;line-height:1.6;}
.oa{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;padding-top:10px;border-top:1px solid var(--grey3);}
.sbadge{display:inline-flex;align-items:center;padding:3px 9px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.s-jauns{background:#fff3e0;color:#e65100}.s-apstiprinats{background:#e8f5e9;color:#2e7d32}
.s-pabeigts{background:#e3f2fd;color:#1565c0}.s-atcelts{background:#fce4ec;color:#c62828}.s-apmaksats{background:#f3e5f5;color:#6a1b9a}
/* Photo strip */
.pstrip{display:flex;gap:3px;overflow-x:auto;padding:3px;background:var(--cream2);scroll-behavior:smooth;}
.pstrip::-webkit-scrollbar{height:4px;}.pstrip::-webkit-scrollbar-thumb{background:var(--grey3);}
.pstrip img{width:68px;height:68px;object-fit:cover;flex-shrink:0;cursor:pointer;border:2px solid transparent;transition:.15s;border-radius:1px;}
.pstrip img:hover{border-color:var(--gold);transform:scale(1.03);}
.pcb{font-size:10px;color:var(--grey);padding:4px 10px;background:var(--cream2);border-bottom:1px solid var(--grey3);display:flex;justify-content:space-between;align-items:center;}
/* Delivery modal */
#dmWrap{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;}
#dmWrap.open{display:flex;}
#dmBox{background:var(--white);padding:28px 32px;max-width:460px;width:94%;border-radius:6px;box-shadow:0 20px 60px rgba(0,0,0,.2);}
/* Lightbox */
#lxWrap{display:none;position:fixed;inset:0;background:rgba(0,0,0,.96);z-index:9999;flex-direction:column;align-items:center;justify-content:center;}
#lxWrap.open{display:flex;}
#lxImg{max-width:90vw;max-height:78vh;object-fit:contain;border:1px solid rgba(255,255,255,.06);}
#lxStrip{display:flex;gap:5px;margin-top:12px;overflow-x:auto;max-width:90vw;padding-bottom:4px;}
#lxStrip img{width:56px;height:56px;object-fit:cover;cursor:pointer;opacity:.4;border:2px solid transparent;transition:.15s;flex-shrink:0;}
#lxStrip img.on{opacity:1;border-color:#B8975A;}
#lxInfo{color:rgba(255,255,255,.4);font-size:11px;margin-top:8px;letter-spacing:1px;}
#lxClose{position:absolute;top:18px;right:22px;background:none;border:none;color:#fff;font-size:28px;cursor:pointer;padding:4px 8px;}
#lxBtns{display:flex;gap:10px;margin-top:14px;align-items:center;}
.lxNav{background:none;border:1px solid rgba(255,255,255,.25);color:#fff;padding:7px 22px;cursor:pointer;font-size:18px;transition:.15s;}
.lxNav:hover{background:#B8975A;border-color:#B8975A;}
.lxDl{padding:9px 22px;background:#B8975A;color:#fff;font-size:10px;letter-spacing:2px;text-transform:uppercase;text-decoration:none;cursor:pointer;}
</style>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success" style="margin-bottom:18px;">
  <?= $_GET['msg']==='deleted'?'Pasūtījums dzēsts.':'Statuss atjaunināts.' ?>
</div>
<?php endif; ?>

<div class="section-header">
  <div class="section-heading">Foto pasūtījumi</div>
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php foreach([''=> 'Visi','jauns'=>'Jauni','apstiprinats'=>'Apstiprināti','pabeigts'=>'Pabeigti','atcelts'=>'Atcelti','apmaksats'=>'Apmaksāti'] as $f=>$lbl): ?>
    <a href="?filter=<?= urlencode($f) ?>" class="action-btn <?= $filter===$f?'success':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>
</div>

<?php if (empty($orders)): ?>
<div style="text-align:center;padding:60px;color:var(--grey2);">
  <p>Nav pasūtījumu.</p>
</div>
<?php else: ?>
<div class="og">
<?php foreach ($orders as $o):
  $sl    = $o['statuss'] ?? 'jauns';
  $photos= orderPhotos($o);
  $pc    = count($photos);
  $oid   = (int)$o['id'];
  $flt   = urlencode($filter);
  // Safe data attribute — json_encode then htmlspecialchars
  $pda   = htmlspecialchars(json_encode($photos, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES);
  $tda   = htmlspecialchars($o['produkts'] ?? '', ENT_QUOTES);
  $de    = $o['epasts'] ?: ($o['viesis_epasts'] ?? '');
  // Extract client delivery address from notes
  $notes = $o['papildu_info'] ?? '';
  preg_match('/Pieg[aā]des adrese:\s*([^\n|]+)/u', $notes, $m);
  $clientAddr = trim($m[1] ?? '');
  $notesClean = trim(preg_replace('/Pieg[aā]des adrese:[^\n]*/u', '', $notes));
  $notesClean = trim(preg_replace('/Izseko[sš]anas kods:[^\n]*/u', '', $notesClean));
  preg_match('/Izseko[sš]anas kods:\s*([^\n]+)/u', $notes, $m2);
  $trackingStored = trim($m2[1] ?? '');
?>
<div class="oc">
  <?php if ($pc > 0): ?>
  <!-- Photo count bar -->
  <div class="pcb">
    <span><?= $pc ?> foto</span>
    <span data-p="<?= $pda ?>" data-t="<?= $tda ?>"
      style="color:var(--gold);cursor:pointer;font-size:10px;letter-spacing:1px;text-transform:uppercase;"
      onclick="lxOpen(this)">Aplūkot visas</span>
  </div>
  <!-- Main photo -->
  <div data-p="<?= $pda ?>" data-t="<?= $tda ?>" data-i="0"
    style="position:relative;cursor:pointer;overflow:hidden;"
    onclick="lxOpen(this)">
    <img src="<?= htmlspecialchars($photos[0]) ?>" alt=""
      style="width:100%;height:200px;object-fit:cover;display:block;transition:transform .3s;"
      onmouseover="this.style.transform='scale(1.03)'"
      onmouseout="this.style.transform='scale(1)'"
      onerror="this.style.display='none'">
    <?php if ($pc > 1): ?>
    <div style="position:absolute;bottom:6px;right:6px;background:rgba(0,0,0,.68);color:#fff;font-size:10px;padding:2px 8px;border-radius:10px;pointer-events:none;">+<?= $pc-1 ?> vēl</div>
    <?php endif; ?>
  </div>
  <?php if ($pc > 1): ?>
  <!-- Thumbnail strip — each thumb is individually clickable -->
  <div class="pstrip">
    <?php foreach ($photos as $i => $ph): ?>
    <img src="<?= htmlspecialchars($ph) ?>" alt=""
      data-p="<?= $pda ?>" data-t="<?= $tda ?>" data-i="<?= $i ?>"
      onclick="lxOpen(this)"
      onerror="this.style.display='none'"
      title="Foto <?= $i+1 ?>">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php else: ?>
  <div style="height:140px;display:flex;align-items:center;justify-content:center;background:var(--cream2);font-size:11px;color:var(--grey2);letter-spacing:1px;">NAV FOTO</div>
  <?php endif; ?>

  <div class="ob">
    <!-- Product + status -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:6px;">
      <div class="opr"><?= htmlspecialchars($o['produkts'] ?? '') ?></div>
      <span class="sbadge s-<?= $sl ?>"><?= ucfirst($sl) ?></span>
    </div>
    <!-- Client info -->
    <div class="om">
      <?php if ($o['vards']): ?>
        <strong><?= htmlspecialchars($o['vards'].' '.($o['uzvards']??'')) ?></strong>
        <?php if ($de): ?>&nbsp;&middot;&nbsp;<a href="mailto:<?= htmlspecialchars($de) ?>" style="color:var(--gold);"><?= htmlspecialchars($de) ?></a><?php endif; ?>
      <?php else: ?>
        <em>Viesis</em><?php if ($de): ?>&nbsp;&middot;&nbsp;<a href="mailto:<?= htmlspecialchars($de) ?>" style="color:var(--gold);font-style:normal;"><?= htmlspecialchars($de) ?></a><?php endif; ?>
      <?php endif; ?>
      <div style="margin-top:2px;"><?= date('d.m.Y H:i', strtotime($o['izveidots'])) ?></div>
    </div>
    <!-- Delivery address (from client) -->
    <?php if ($clientAddr): ?>
    <div style="font-size:12px;background:var(--cream2);border-left:3px solid var(--gold);padding:7px 10px;margin-bottom:8px;">
      <span style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--grey);display:block;margin-bottom:2px;">Klienta adrese</span>
      <strong><?= htmlspecialchars($clientAddr) ?></strong>
    </div>
    <?php endif; ?>
    <!-- Tracking code (if already set) -->
    <?php if ($trackingStored): ?>
    <div style="font-size:12px;background:#e8f5e9;border-left:3px solid #2e7d32;padding:7px 10px;margin-bottom:8px;">
      <span style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#2e7d32;display:block;margin-bottom:2px;">Izsekošanas kods</span>
      <strong style="font-family:monospace;letter-spacing:2px;"><?= htmlspecialchars($trackingStored) ?></strong>
    </div>
    <?php endif; ?>
    <!-- Notes (clean, without address/tracking) -->
    <?php if ($notesClean): ?>
    <div style="font-size:12px;color:var(--grey);background:var(--cream);padding:7px 10px;border-left:2px solid var(--grey3);margin-bottom:8px;"><?= htmlspecialchars($notesClean) ?></div>
    <?php endif; ?>

    <!-- Action buttons -->
    <div class="oa">
      <?php if ($pc > 0): ?>
      <button data-p="<?= $pda ?>" data-t="<?= $tda ?>"
        onclick="dlAll(this)" class="action-btn" style="cursor:pointer;">Lejupielādēt (<?= $pc ?>)</button>
      <?php endif; ?>
      <?php if (in_array($sl, ['jauns','apmaksats'])): ?>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="do_status" value="1">
        <input type="hidden" name="id" value="<?= $oid ?>">
        <input type="hidden" name="status" value="apstiprinats">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <button type="submit" class="action-btn success" onclick="return confirm('Apstiprināt?')">Apstiprināt</button>
      </form>
      <?php endif; ?>
      <?php if (!in_array($sl, ['pabeigts','atcelts'])): ?>
      <button class="action-btn"
        onclick="dmOpen(<?= $oid ?>, '<?= htmlspecialchars($filter) ?>', '<?= htmlspecialchars(addslashes($clientAddr)) ?>')">Pabeigts</button>
      <?php endif; ?>
      <?php if ($sl !== 'atcelts'): ?>
      <form method="POST" style="display:inline;">
        <input type="hidden" name="do_status" value="1">
        <input type="hidden" name="id" value="<?= $oid ?>">
        <input type="hidden" name="status" value="atcelts">
        <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
        <button type="submit" class="action-btn danger" onclick="return confirm('Atcelt?')">Atcelt</button>
      </form>
      <?php endif; ?>
      <a href="?delete=<?= $oid ?>&filter=<?= $flt ?>"
        onclick="return confirm('Dzēst pasūtījumu #<?= $oid ?>?')" class="action-btn danger">Dzēst</a>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── DELIVERY MODAL ── -->
<div id="dmWrap" onclick="if(event.target===this)dmClose()">
  <div id="dmBox">
    <div style="font-family:'Cormorant Garamond',serif;font-size:22px;margin-bottom:4px;">Atzīmēt kā pabeigtu</div>
    <p style="font-size:13px;color:var(--grey);margin-bottom:18px;">Pārbaudiet klienta norādīto adresi un ievadiet izsekošanas kodu. Klientam tiks nosūtīts e-pasta paziņojums.</p>
    <form method="POST" id="dmForm">
      <input type="hidden" name="do_status" value="1">
      <input type="hidden" name="status" value="pabeigts">
      <input type="hidden" name="id" id="dmId">
      <input type="hidden" name="filter" id="dmFilter">
      <div style="margin-bottom:14px;">
        <div style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--grey);margin-bottom:6px;">Klienta norādītā piegādes adrese</div>
        <div id="dmClientAddr" style="background:var(--cream2);padding:10px 12px;font-size:13px;border-radius:4px;min-height:36px;color:var(--ink);border:1px solid var(--grey3);"></div>
      </div>
      <div style="margin-bottom:18px;">
        <label for="dmTracking" style="font-size:10px;text-transform:uppercase;letter-spacing:1px;color:var(--grey);display:block;margin-bottom:6px;">Omniva / DPD izsekošanas kods</label>
        <input type="text" name="tracking" id="dmTracking" class="form-input"
          placeholder="LV123456789EE vai DPD kods"
          style="width:100%;box-sizing:border-box;font-family:monospace;font-size:14px;letter-spacing:2px;text-transform:uppercase;">
        <div style="font-size:11px;color:var(--grey2);margin-top:4px;">Neobligāts — atstājiet tukšu ja nesūtāt ar izsekošanu</div>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;">
        <button type="button" onclick="dmClose()" class="action-btn">Atcelt</button>
        <button type="submit" class="btn-primary" style="padding:9px 24px;">Nosūtīt e-pastu un pabeigt</button>
      </div>
    </form>
  </div>
</div>

<!-- ── LIGHTBOX ── -->
<div id="lxWrap" onclick="if(event.target===this)lxClose()">
  <button id="lxClose" onclick="lxClose()">&#10005;</button>
  <img id="lxImg" src="" alt="">
  <div id="lxStrip"></div>
  <div id="lxInfo"></div>
  <div id="lxBtns">
    <button class="lxNav" onclick="lxNav(-1)">&#8249;</button>
    <a class="lxDl" id="lxDl" href="#" download>Lejupielādēt</a>
    <button class="lxNav" onclick="lxNav(1)">&#8250;</button>
  </div>
</div>

<script>
/* ── Delivery modal ── */
function dmOpen(id, filter, clientAddr) {
  document.getElementById('dmId').value     = id;
  document.getElementById('dmFilter').value = filter;
  document.getElementById('dmTracking').value = '';
  document.getElementById('dmClientAddr').textContent = clientAddr || '(Nav norādīta)';
  document.getElementById('dmWrap').classList.add('open');
  document.body.style.overflow = 'hidden';
  setTimeout(() => document.getElementById('dmTracking').focus(), 100);
}
function dmClose() {
  document.getElementById('dmWrap').classList.remove('open');
  document.body.style.overflow = '';
}

/* ── Lightbox ── */
var _lxP = [], _lxC = 0;

function lxOpen(el) {
  // Walk up to find data-p attribute
  var src = el;
  while (src && !src.dataset.p) src = src.parentElement;
  if (!src) return;
  var photos = JSON.parse(src.dataset.p);
  var title  = src.dataset.t || '';
  var idx    = parseInt(src.dataset.i || '0', 10);
  _lxP = photos;
  document.getElementById('lxWrap').classList.add('open');
  document.body.style.overflow = 'hidden';
  lxShow(idx);
}
function lxClose() {
  document.getElementById('lxWrap').classList.remove('open');
  document.body.style.overflow = '';
}
function lxShow(i) {
  if (i < 0) i = _lxP.length - 1;
  if (i >= _lxP.length) i = 0;
  _lxC = i;
  document.getElementById('lxImg').src = _lxP[i];
  document.getElementById('lxInfo').textContent = (i+1) + ' / ' + _lxP.length;
  var dl = document.getElementById('lxDl'); dl.href = _lxP[i]; dl.download = 'lumina_foto_'+(i+1)+'.jpg';
  document.getElementById('lxStrip').innerHTML = _lxP.map(function(p, j) {
    return '<img src="'+p+'" class="'+(j===i?'on':'')+'" onclick="lxShow('+j+')" onerror="this.style.display=\'none\'">';
  }).join('');
}
function lxNav(d) { lxShow(_lxC + d); }

/* ── Download all ── */
function dlAll(btn) {
  var photos = JSON.parse(btn.dataset.p);
  var title  = btn.dataset.t || 'foto';
  if (!photos.length) return;
  if (!confirm('Lejupielādēt ' + photos.length + ' foto?')) return;
  var slug = title.replace(/[^a-z0-9]/gi,'_').toLowerCase();
  var i = 0;
  function next() {
    if (i >= photos.length) return;
    fetch(photos[i]).then(r=>r.blob()).then(blob=>{
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'lumina_' + slug + '_' + String(i+1).padStart(2,'0') + '.jpg';
      document.body.appendChild(a); a.click(); document.body.removeChild(a);
      setTimeout(()=>URL.revokeObjectURL(a.href), 1000);
      i++; setTimeout(next, 700);
    }).catch(()=>{ window.open(photos[i],'_blank'); i++; setTimeout(next,400); });
  }
  next();
}

/* ── Keyboard ── */
document.addEventListener('keydown', function(e) {
  if (document.getElementById('lxWrap').classList.contains('open')) {
    if (e.key==='ArrowLeft') lxNav(-1);
    else if (e.key==='ArrowRight') lxNav(1);
    else if (e.key==='Escape') lxClose();
  } else if (e.key==='Escape') dmClose();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
