<?php
/**
 * LUMINA — Viesa galerijas piekļuve ar kodu
 * Viesis ievada 8 simbolu kodu → redz savas bildes
 * Ja viesis vēlāk reģistrējas ar to pašu e-pastu → galerija sasaistās ar kontu
 */
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Galerijas piekļuve';
$extraCss  = '';

$error  = '';
$galerija  = null;
$galPhotos = [];

// If already logged in — redirect to profils.php
if (isset($_SESSION['klients_id'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/profils.php?tab=galerijas');
  exit;
}

// Code submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['kods'])) {
  $kods = strtoupper(trim($_POST['kods'] ?? $_GET['kods'] ?? ''));
  $kodsEsc = escape($savienojums, $kods);

  if (strlen($kods) < 4) {
    $error = 'Ievadiet derīgu piekļuves kodu.';
  } else {
    $galerija = mysqli_fetch_assoc(mysqli_query($savienojums,
      "SELECT g.*, k.vards, k.uzvards FROM galerijas g
       LEFT JOIN klienti k ON g.klienta_id = k.id
       WHERE g.piekluves_kods = '$kodsEsc'
         AND (g.deriga_lidz IS NULL OR g.deriga_lidz >= CURDATE())
       LIMIT 1"
    ));
    if (!$galerija) {
      $error = 'Kods nav atrasts vai galerija ir beigusies.';
    } else {
      $pRes = mysqli_query($savienojums,
        "SELECT * FROM galeriju_foto WHERE galerijas_id={$galerija['id']} ORDER BY seciba, id"
      );
      while ($p = mysqli_fetch_assoc($pRes)) $galPhotos[] = $p;
      $_SESSION['viesis_galerija_kods'] = $kods; // remember in session
    }
  }
} elseif (isset($_SESSION['viesis_galerija_kods'])) {
  // Re-use from session
  $kods = $_SESSION['viesis_galerija_kods'];
  $kodsEsc = escape($savienojums, $kods);
  $galerija = mysqli_fetch_assoc(mysqli_query($savienojums,
    "SELECT g.*, k.vards, k.uzvards FROM galerijas g
     LEFT JOIN klienti k ON g.klienta_id = k.id
     WHERE g.piekluves_kods = '$kodsEsc'
       AND (g.deriga_lidz IS NULL OR g.deriga_lidz >= CURDATE())
     LIMIT 1"
  ));
  if ($galerija) {
    $pRes = mysqli_query($savienojums,
      "SELECT * FROM galeriju_foto WHERE galerijas_id={$galerija['id']} ORDER BY seciba, id"
    );
    while ($p = mysqli_fetch_assoc($pRes)) $galPhotos[] = $p;
  }
}

include __DIR__ . '/includes/header.php';
$BASE = '/4pt/blazkova/lumina/Lumina/uploads/galerijas/';
?>
<style>
.vg-wrap { max-width:860px; margin:60px auto; padding:0 20px; }
.vg-hero  { text-align:center; margin-bottom:48px; }
.vg-code-form { max-width:340px; margin:0 auto 48px; text-align:center; }
.vg-code-input { width:100%;padding:14px 20px;font-size:22px;letter-spacing:6px;text-align:center;font-family:monospace;text-transform:uppercase;border:2px solid var(--grey3);background:var(--white);color:var(--ink);margin-bottom:12px;outline:none;transition:.2s; }
.vg-code-input:focus { border-color:var(--gold); }
.vg-photo-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px; }
.vg-photo { aspect-ratio:1;overflow:hidden;cursor:pointer;position:relative; }
.vg-photo img { width:100%;height:100%;object-fit:cover;transition:transform .3s; }
.vg-photo:hover img { transform:scale(1.04); }
/* Lightbox */
#vglx { display:none;position:fixed;inset:0;background:rgba(0,0,0,.96);z-index:9999;align-items:center;justify-content:center;flex-direction:column; }
#vglx.open { display:flex; }
#vglx img.main { max-width:90vw;max-height:80vh;object-fit:contain; }
#vglx-strip { display:flex;gap:5px;margin-top:12px;overflow-x:auto;max-width:90vw;padding-bottom:4px; }
#vglx-strip img { width:54px;height:54px;object-fit:cover;cursor:pointer;opacity:.4;border:2px solid transparent;transition:.15s;flex-shrink:0; }
#vglx-strip img.active { opacity:1;border-color:#B8975A; }
#vglx-close { position:absolute;top:18px;right:22px;background:none;border:none;color:#fff;font-size:28px;cursor:pointer; }
#vglx-nav { display:flex;gap:12px;margin-top:14px;align-items:center; }
#vglx-nav button { background:none;border:1px solid rgba(255,255,255,.3);color:#fff;padding:7px 22px;cursor:pointer;font-size:18px; }
#vglx-nav button:hover { background:#B8975A;border-color:#B8975A; }
.vglx-dl { padding:8px 22px;background:#B8975A;color:#fff;font-size:10px;letter-spacing:2px;text-transform:uppercase;text-decoration:none; }
#vglx-info { color:rgba(255,255,255,.4);font-size:11px;margin-top:8px;letter-spacing:1px; }
.note-box { background:var(--cream2);border-left:3px solid var(--gold);padding:14px 18px;font-size:13px;color:var(--grey);margin-bottom:24px;border-radius:0 4px 4px 0; }
</style>

<div class="vg-wrap">
  <?php if (!$galerija): ?>
  <!-- ── CODE ENTRY ── -->
  <div class="vg-hero">
    <div style="font-size:48px;margin-bottom:16px;">📸</div>
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:400;margin-bottom:8px;">Galerijas piekļuve</h1>
    <p style="color:var(--grey);font-size:15px;">Ievadiet piekļuves kodu, ko saņēmāt e-pastā pēc fotosesijas.</p>
  </div>

  <div class="vg-code-form">
    <?php if ($error): ?>
    <div style="background:#fef0f0;border:1px solid #f5c6c6;color:#b71c1c;padding:10px 14px;border-radius:4px;font-size:13px;margin-bottom:14px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="text" name="kods" class="vg-code-input" placeholder="XXXXXXXX" maxlength="12" autocomplete="off" autofocus>
      <button type="submit" class="btn-primary" style="width:100%;padding:13px;">Atvērt galeriju →</button>
    </form>
    <p style="margin-top:20px;font-size:12px;color:var(--grey2);">
      Koda nesaņēmāt? Sazinieties ar fotogrāfi:<br>
      <a href="mailto:katrinablazkova06@gmail.com" style="color:var(--gold);">katrinablazkova06@gmail.com</a>
    </p>
    <p style="margin-top:16px;font-size:12px;color:var(--grey2);">
      Ir konts? <a href="/4pt/blazkova/lumina/Lumina/login.php" style="color:var(--gold);">Pieslēgties →</a>
    </p>
  </div>

  <?php else: ?>
  <!-- ── GALLERY VIEW ── -->
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
    <div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:400;margin:0 0 4px;"><?= htmlspecialchars($galerija['nosaukums']) ?></h1>
      <div style="font-size:12px;color:var(--grey2);"><?= count($galPhotos) ?> foto · Derīga līdz: <?= $galerija['deriga_lidz'] ?: 'nenorādīts' ?></div>
    </div>
    <button onclick="location.href='?kods=<?= urlencode(strtolower($_SESSION['viesis_galerija_kods'] ?? '')) ?>'" class="action-btn" style="cursor:pointer;">🔄 Atjaunināt</button>
  </div>

  <div class="note-box">
    💡 Nākotnē, reģistrējoties ar šo e-pastu, šī galerija automātiski parādīsies jūsu profilā.
    <a href="/4pt/blazkova/lumina/Lumina/registracija.php" style="color:var(--gold);margin-left:6px;">Izveidot kontu →</a>
  </div>

  <?php if (empty($galPhotos)): ?>
  <div style="text-align:center;padding:60px;color:var(--grey2);">
    <div style="font-size:40px;opacity:.3;margin-bottom:12px;">🖼️</div>
    <p>Galerija vēl ir tukša. Fotogrāfe drīz pievienosies foto.</p>
  </div>
  <?php else: ?>
  <?php
  $allPhotos = array_map(function($p) use ($BASE) {
    return filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : $BASE . $p['attels_url'];
  }, $galPhotos);
  $allJson = json_encode($allPhotos);
  ?>
  <div class="vg-photo-grid">
    <?php foreach ($allPhotos as $i => $src): ?>
    <div class="vg-photo" onclick="vglxOpen(<?= $i ?>)">
      <img src="<?= htmlspecialchars($src) ?>" alt="" loading="lazy" onerror="this.parentNode.style.display='none'">
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Lightbox -->
  <div id="vglx" onclick="if(event.target===this)vglxClose()">
    <button id="vglx-close" onclick="vglxClose()">✕</button>
    <img class="main" id="vglx-img" src="" alt="">
    <div id="vglx-strip"></div>
    <div id="vglx-info"></div>
    <div id="vglx-nav">
      <button onclick="vglxNav(-1)">‹</button>
      <a class="vglx-dl" id="vglx-dl" href="#" download>⬇ Lejupielādēt</a>
      <button onclick="vglxNav(1)">›</button>
    </div>
  </div>
  <script>
  var _vP=<?= $allJson ?? '[]' ?>, _vC=0;
  function vglxOpen(i){_vC=i;document.getElementById('vglx').classList.add('open');document.body.style.overflow='hidden';_vS(i);}
  function vglxClose(){document.getElementById('vglx').classList.remove('open');document.body.style.overflow='';}
  function _vS(i){if(i<0)i=_vP.length-1;if(i>=_vP.length)i=0;_vC=i;
    document.getElementById('vglx-img').src=_vP[i];
    document.getElementById('vglx-info').textContent=(i+1)+' / '+_vP.length;
    var dl=document.getElementById('vglx-dl');dl.href=_vP[i];dl.download='lumina_foto_'+(i+1)+'.jpg';
    document.getElementById('vglx-strip').innerHTML=_vP.map(function(p,j){return'<img src="'+p+'" class="'+(j===i?'active':'')+'" onclick="_vS('+j+')" onerror="this.style.display=\'none\'">';}).join('');}
  function vglxNav(d){_vS(_vC+d);}
  document.addEventListener('keydown',function(e){
    if(!document.getElementById('vglx').classList.contains('open'))return;
    if(e.key==='ArrowLeft')vglxNav(-1);else if(e.key==='ArrowRight')vglxNav(1);else if(e.key==='Escape')vglxClose();
  });
  </script>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
