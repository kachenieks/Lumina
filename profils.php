<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Mans Profils';
$extraCss = 'profils.css';

if (!isset($_SESSION['klients_id'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/login.php');
  exit;
}

$klientsId = (int)$_SESSION['klients_id'];
$success = '';
$error = '';

function validatePassword($p) {
  if (strlen($p) < 8) return 'Parolei jābūt vismaz 8 simbolus garai.';
  if (!preg_match('/[A-ZĀČĒĢĪĶĻŅŠŪŽ]/u', $p)) return 'Parolei jābūt vismaz vienam lielajam burtam.';
  if (!preg_match('/[0-9]/', $p)) return 'Parolei jābūt vismaz vienam ciparam.';
  if (!preg_match('/[^A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž0-9]/', $p)) return 'Parolei jābūt vismaz vienam speciālam simbolam (!@#$...).';
  return '';
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $vards    = escape($savienojums, trim($_POST['vards'] ?? ''));
  $uzvards  = escape($savienojums, trim($_POST['uzvards'] ?? ''));
  $talrunis = escape($savienojums, trim($_POST['talrunis'] ?? ''));
  if (mysqli_query($savienojums, "UPDATE klienti SET vards='$vards', uzvards='$uzvards', talrunis='$talrunis' WHERE id=$klientsId")) {
    $_SESSION['klients_vards'] = $vards;
    $success = 'Profils atjaunināts!';
  } else {
    $error = 'Kļūda: ' . mysqli_error($savienojums);
  }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
  $currentPw = $_POST['current_password'] ?? '';
  $newPw     = $_POST['new_password'] ?? '';
  $newPw2    = $_POST['new_password2'] ?? '';

  $klRow = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT parole FROM klienti WHERE id=$klientsId"));

  if (!password_verify($currentPw, $klRow['parole'])) {
    $error = 'Pašreizējā parole ir nepareiza.';
  } elseif ($newPw !== $newPw2) {
    $error = 'Jaunās paroles nesakrīt.';
  } elseif ($pwErr = validatePassword($newPw)) {
    $error = $pwErr;
  } else {
    $hash = password_hash($newPw, PASSWORD_DEFAULT);
    mysqli_query($savienojums, "UPDATE klienti SET parole='$hash' WHERE id=$klientsId");
    $success = 'Parole veiksmīgi mainīta!';
    // Send confirmation email
    $klRow2 = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT vards, epasts FROM klienti WHERE id=$klientsId"));
    try {
      require_once __DIR__ . '/includes/mailer.php';
      $html = '<h2 style="font-family:Georgia,serif;font-weight:300;color:#1C1C1C;">Parole nomainīta</h2>
        <p style="color:#7A7267;line-height:1.8;">Sveiki, <strong>' . htmlspecialchars($klRow2['vards']) . '</strong>!</p>
        <p style="color:#7A7267;line-height:1.8;">Jūsu LUMINA konta parole tikko tika veiksmīgi nomainīta.</p>
        <p style="color:#7A7267;line-height:1.8;">Ja šīs izmaiņas neveicāt jūs, lūdzu nekavējoties sazinieties ar mums.</p>
        <a href="' . SITE_URL . '/profils.php" style="display:inline-block;padding:13px 30px;background:#1C1C1C;color:#B8975A;text-decoration:none;font-size:12px;letter-spacing:2px;text-transform:uppercase;margin-top:20px;">Mans profils →</a>';
      luminaMail($klRow2['epasts'], $klRow2['vards'], 'Parole nomainīta — LUMINA', $html);
    } catch(\Throwable $e) { error_log('Mail err: '.$e->getMessage()); }
  }
}

// Fetch client data
$klients = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM klienti WHERE id=$klientsId"));

// Fetch reservations
$rezervacijas = [];
$rezResult = mysqli_query($savienojums, "SELECT * FROM rezervacijas WHERE klienta_id=$klientsId ORDER BY datums DESC");
while ($row = mysqli_fetch_assoc($rezResult)) $rezervacijas[] = $row;

// Fetch galleries with cover photo
$galerijas = [];
$galResult = mysqli_query($savienojums, "SELECT * FROM galerijas WHERE klienta_id=$klientsId ORDER BY izveidota DESC");
while ($row = mysqli_fetch_assoc($galResult)) {
  $cover = mysqli_fetch_assoc(mysqli_query($savienojums,
    "SELECT attels_url FROM galeriju_foto WHERE galerijas_id={$row['id']} ORDER BY seciba ASC, id ASC LIMIT 1"
  ));
  $row['cover'] = $cover ? $cover['attels_url'] : null;
  $galerijas[] = $row;
}

$activeTab = $_GET['tab'] ?? 'rezervacijas';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="padding-top:80px;min-height:100vh;background:var(--cream);">

  <!-- Profile Header -->
  <div class="profils-header">
    <div class="profils-header-inner">
      <div class="profils-avatar"><?= strtoupper(mb_substr($klients['vards'], 0, 1, 'UTF-8')) ?></div>
      <div>
        <h1 style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:300;color:var(--ink);"><?= htmlspecialchars($klients['vards'] . ' ' . $klients['uzvards']) ?></h1>
        <div style="font-size:12px;color:var(--grey);margin-top:4px;"><?= htmlspecialchars($klients['epasts']) ?></div>
      </div>
      <div style="margin-left:auto;text-align:right;">
        <div style="font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);">Kopā iztērēts</div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:32px;color:var(--gold);">€<?= number_format($klients['kopeja_summa'] ?? 0, 2) ?></div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="profils-tabs-wrap">
    <div class="profils-tabs">
      <a href="?tab=rezervacijas" class="profils-tab <?= $activeTab==='rezervacijas'?'active':'' ?>">Rezervācijas</a>
      <a href="?tab=pasutijumi"   class="profils-tab <?= $activeTab==='pasutijumi'?'active':'' ?>">Pasūtījumi</a>
      <a href="?tab=galerijas"    class="profils-tab <?= $activeTab==='galerijas'?'active':'' ?>">Manas galerijas</a>
      <a href="?tab=iestatijumi"  class="profils-tab <?= $activeTab==='iestatijumi'?'active':'' ?>">Iestatījumi</a>
    </div>
  </div>

  <div class="profils-content">

    <?php if ($success): ?><div class="alert alert-success" style="margin-bottom:24px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error" style="margin-bottom:24px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- ── REZERVĀCIJAS ── -->
    <?php if ($activeTab === 'rezervacijas'): ?>
    <div>
      <?php if (empty($rezervacijas)): ?>
      <div style="text-align:center;padding:60px;color:var(--grey);">
        <div style="font-size:40px;opacity:.3;margin-bottom:16px;">📅</div>
        <p>Jums vēl nav rezervāciju.</p>
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary" style="display:inline-block;margin-top:20px;">Rezervēt sesiju →</a>
      </div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:14px;">
        <?php foreach ($rezervacijas as $r):
          $sc = ['gaida'=>'#e67e22','apstiprinats'=>'#27ae60','pabeigts'=>'#2980b9','atcelts'=>'#c0392b'];
          $sl = $r['statuss'] ?? 'gaida';
        ?>
        <div style="background:var(--white);border:1px solid var(--grey3);padding:24px 28px;display:flex;gap:20px;align-items:center;flex-wrap:wrap;">
          <div style="flex:1;min-width:180px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);"><?= htmlspecialchars($r['pakalpojums']) ?></div>
            <div style="font-size:12px;color:var(--grey);margin-top:4px;">
              <?= date('d.m.Y', strtotime($r['datums'])) ?> · <?= substr($r['laiks'],0,5) ?>
              <?php if ($r['vieta']): ?> · <?= htmlspecialchars($r['vieta']) ?><?php endif; ?>
            </div>
          </div>
          <?php if ($r['cena']): ?>
          <div style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--gold);">€<?= number_format($r['cena'],0) ?></div>
          <?php endif; ?>
          <div>
            <span style="font-size:10px;padding:4px 12px;border-radius:20px;background:<?= $sc[$sl]??'#999' ?>18;color:<?= $sc[$sl]??'#999' ?>;font-weight:600;letter-spacing:1px;text-transform:uppercase;"><?= ucfirst($sl) ?></span>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── PASŪTĪJUMI ── -->
    <?php elseif ($activeTab === 'pasutijumi'): ?>
    <?php
    $pasutijumi = [];
    $pRes = mysqli_query($savienojums, "SELECT * FROM pasutijumi WHERE klienta_id=$klientsId ORDER BY izveidots DESC");
    while ($p = mysqli_fetch_assoc($pRes)) $pasutijumi[] = $p;
    ?>
    <div>
      <?php if (empty($pasutijumi)): ?>
      <div style="text-align:center;padding:60px;color:var(--grey);">
        <div style="font-size:40px;opacity:.3;margin-bottom:16px;">🛍️</div>
        <p>Jums vēl nav pasūtījumu.</p>
        <a href="/4pt/blazkova/lumina/Lumina/veikals.php" class="btn-primary" style="display:inline-block;margin-top:20px;">Uz veikalu →</a>
      </div>
      <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:16px;">
        <?php foreach ($pasutijumi as $p):
          $sc = ['jauns'=>'#e67e22','apstiprinats'=>'#27ae60','pabeigts'=>'#2980b9','atcelts'=>'#c0392b','apmaksats'=>'#8e44ad'];
          $sl = $p['statuss'] ?? 'jauns';
          $sl_label = ['jauns'=>'Jauns','apstiprinats'=>'Apstiprināts','pabeigts'=>'Pabeigts','atcelts'=>'Atcelts','apmaksats'=>'Apmaksāts'];
          // Photo src
          $fotoSrc = null;
          if (!empty($p['foto_fails'])) {
            if (filter_var($p['foto_fails'], FILTER_VALIDATE_URL)) {
              $fotoSrc = $p['foto_fails'];
            } else {
              $fotoSrc = '/4pt/blazkova/lumina/Lumina/uploads/pasutijumi/' . $p['foto_fails'];
            }
          }
        ?>
        <div style="background:var(--white);border:1px solid var(--grey3);display:flex;gap:0;overflow:hidden;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 4px 20px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
          <?php if ($fotoSrc): ?>
          <div style="width:110px;flex-shrink:0;">
            <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="" style="width:110px;height:110px;object-fit:cover;display:block;">
          </div>
          <?php else: ?>
          <div style="width:110px;height:110px;flex-shrink:0;background:var(--cream2);display:flex;align-items:center;justify-content:center;font-size:32px;opacity:.25;">🖼️</div>
          <?php endif; ?>
          <div style="padding:16px 20px;flex:1;min-width:0;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;">
              <div style="font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--ink);"><?= htmlspecialchars($p['produkts']) ?></div>
              <span style="font-size:9px;padding:4px 10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;background:<?= ($sc[$sl]??'#999') ?>18;color:<?= $sc[$sl]??'#999' ?>;flex-shrink:0;"><?= $sl_label[$sl]??ucfirst($sl) ?></span>
            </div>
            <?php if (!empty($p['papildu_info'])): ?>
            <div style="font-size:12px;color:var(--grey);margin-top:6px;"><?= htmlspecialchars($p['papildu_info']) ?></div>
            <?php endif; ?>
            <div style="font-size:11px;color:var(--grey2);margin-top:8px;"><?= date('d.m.Y H:i', strtotime($p['izveidots'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── GALERIJAS ── -->
    <?php elseif ($activeTab === 'galerijas'): ?>
    <div>
      <?php if (empty($galerijas)): ?>
      <div style="text-align:center;padding:60px;color:var(--grey);">
        <div style="font-size:40px;margin-bottom:16px;opacity:.3;">🖼️</div>
        <p>Jūsu galerijas vēl nav augšupielādētas.</p>
        <p style="margin-top:8px;font-size:12px;">Pēc fotosesijas mēs ievietosim šeit jūsu fotogrāfijas.</p>
      </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px;">
        <?php foreach ($galerijas as $g):
          $coverSrc = null;
          if ($g['cover']) {
            $coverSrc = filter_var($g['cover'], FILTER_VALIDATE_URL)
              ? $g['cover']
              : '/4pt/blazkova/lumina/Lumina/uploads/galerijas/' . $g['cover'];
          }
          $expired = $g['deriga_lidz'] && $g['deriga_lidz'] < date('Y-m-d');
        ?>
        <a href="/4pt/blazkova/lumina/Lumina/galerija.php?id=<?= $g['id'] ?>" style="text-decoration:none;display:block;background:var(--white);border:1px solid var(--grey3);overflow:hidden;transition:box-shadow .2s;" onmouseover="this.style.boxShadow='0 8px 32px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow=''">
          <div style="height:180px;overflow:hidden;position:relative;background:var(--cream2);">
            <?php if ($coverSrc): ?>
              <img src="<?= htmlspecialchars($coverSrc) ?>" alt="" style="width:100%;height:100%;object-fit:cover;transition:transform .4s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
            <?php else: ?>
              <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:48px;opacity:.18;">📷</div>
            <?php endif; ?>
            <div style="position:absolute;inset:0;background:linear-gradient(transparent 40%,rgba(0,0,0,.35));"></div>
            <div style="position:absolute;bottom:10px;right:12px;color:#fff;font-size:11px;background:rgba(0,0,0,.4);padding:3px 9px;border-radius:12px;"><?= $g['foto_skaits'] ?> foto</div>
            <?php if ($expired): ?><div style="position:absolute;top:8px;left:8px;background:rgba(192,57,43,.85);color:#fff;font-size:9px;padding:2px 7px;border-radius:3px;text-transform:uppercase;">Beidzies</div><?php endif; ?>
          </div>
          <div style="padding:18px 20px;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:5px;"><?= htmlspecialchars($g['nosaukums']) ?></div>
            <div style="font-size:11px;color:var(--grey);display:flex;justify-content:space-between;">
              <span>Derīgs līdz <?= $g['deriga_lidz'] ? date('d.m.Y', strtotime($g['deriga_lidz'])) : '—' ?></span>
              <span style="color:var(--gold);">Atvērt →</span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ── IESTATĪJUMI ── -->
    <?php elseif ($activeTab === 'iestatijumi'): ?>
    <div style="max-width:580px;display:flex;flex-direction:column;gap:32px;">

      <!-- Profile info -->
      <div style="background:var(--white);border:1px solid var(--grey3);padding:32px;">
        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--grey3);">Personas dati</div>
        <form method="POST">
          <input type="hidden" name="update_profile" value="1">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div class="form-group">
              <label class="form-label">Vārds</label>
              <input type="text" name="vards" class="form-input" value="<?= htmlspecialchars($klients['vards']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Uzvārds</label>
              <input type="text" name="uzvards" class="form-input" value="<?= htmlspecialchars($klients['uzvards']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">E-pasts <span style="font-size:10px;color:var(--grey)">(nevar mainīt)</span></label>
            <input type="email" class="form-input" value="<?= htmlspecialchars($klients['epasts']) ?>" readonly style="background:var(--cream2);color:var(--grey);cursor:not-allowed;">
          </div>
          <div class="form-group">
            <label class="form-label">Tālrunis</label>
            <input type="tel" name="talrunis" class="form-input" value="<?= htmlspecialchars($klients['talrunis'] ?? '') ?>">
          </div>
          <button type="submit" class="btn-primary">Saglabāt izmaiņas</button>
        </form>
      </div>

      <!-- Password change -->
      <div style="background:var(--white);border:1px solid var(--grey3);padding:32px;">
        <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:22px;padding-bottom:14px;border-bottom:1px solid var(--grey3);">Mainīt paroli</div>
        <form method="POST" id="pwForm">
          <input type="hidden" name="change_password" value="1">
          <div class="form-group">
            <label class="form-label">Pašreizējā parole</label>
            <div style="position:relative;">
              <input type="password" name="current_password" id="cpw" class="form-input" placeholder="••••••••" style="padding-right:44px;">
              <button type="button" onclick="tp('cpw')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--grey);">👁</button>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Jaunā parole</label>
            <div style="position:relative;">
              <input type="password" name="new_password" id="npw" class="form-input" placeholder="Vismaz 8 simboli, lielais burts, cipars, simbols" style="padding-right:44px;" oninput="checkNewPw(this.value)">
              <button type="button" onclick="tp('npw')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--grey);">👁</button>
            </div>
            <div style="display:flex;gap:4px;margin-top:6px;">
              <div id="ps1" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
              <div id="ps2" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
              <div id="ps3" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
              <div id="ps4" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
            </div>
            <div style="font-size:10px;color:var(--grey);margin-top:5px;line-height:1.6;" id="pwHints2">
              <span id="ph1" style="opacity:.5;">✓ 8+ simboli</span> ·
              <span id="ph2" style="opacity:.5;">✓ Lielais burts</span> ·
              <span id="ph3" style="opacity:.5;">✓ Cipars</span> ·
              <span id="ph4" style="opacity:.5;">✓ Speciāls simbols</span>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Apstiprināt jauno paroli</label>
            <div style="position:relative;">
              <input type="password" name="new_password2" id="npw2" class="form-input" placeholder="Atkārtojiet jauno paroli" style="padding-right:44px;">
              <button type="button" onclick="tp('npw2')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--grey);">👁</button>
            </div>
            <div id="pwm2" style="font-size:11px;margin-top:4px;display:none;"></div>
          </div>
          <button type="submit" class="btn-outline" style="border-color:var(--gold);color:var(--gold);">Mainīt paroli →</button>
        </form>
      </div>

      <!-- Logout -->
      <div style="padding:20px 0;">
        <a href="/4pt/blazkova/lumina/Lumina/logout.php" style="color:#c0392b;border:1px solid #c0392b;padding:10px 22px;font-size:12px;letter-spacing:1px;text-transform:uppercase;text-decoration:none;transition:.2s;" onmouseover="this.style.background='#c0392b';this.style.color='#fff';" onmouseout="this.style.background='';this.style.color='#c0392b';">Iziet no konta</a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function tp(id) { const e=document.getElementById(id); e.type=e.type==='password'?'text':'password'; }
function checkNewPw(v) {
  const c=[v.length>=8,/[A-ZĀČĒĢĪĶĻŅŠŪŽ]/.test(v),/[0-9]/.test(v),/[^A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž0-9]/.test(v)];
  const col=['#eee','#e74c3c','#e67e22','#f1c40f','#27ae60'];
  const s=c.filter(Boolean).length;
  [1,2,3,4].forEach(i=>{
    document.getElementById('ps'+i).style.background=i<=s?col[s]:'#eee';
    document.getElementById('ph'+i).style.opacity=c[i-1]?'1':'0.4';
    document.getElementById('ph'+i).style.color=c[i-1]?'#27ae60':'var(--grey)';
  });
}
document.getElementById('npw2').addEventListener('input',function(){
  const m=this.value===document.getElementById('npw').value;
  const e=document.getElementById('pwm2');
  e.style.display=this.value?'block':'none';
  e.textContent=m?'✓ Paroles sakrīt':'✕ Paroles nesakrīt';
  e.style.color=m?'#27ae60':'#e74c3c';
});
document.getElementById('pwForm').addEventListener('submit',function(e){
  if(document.getElementById('npw').value!==document.getElementById('npw2').value){
    e.preventDefault();
    const el=document.getElementById('pwm2');
    el.style.display='block';el.textContent='✕ Paroles nesakrīt';el.style.color='#e74c3c';
  }
});
</script>
