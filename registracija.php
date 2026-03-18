<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Reģistrācija';

if (isset($_SESSION['klients_id'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/profils.php');
  exit;
}

$error = '';
$success = '';

function validatePassword($p) {
  if (strlen($p) < 8) return 'Parolei jābūt vismaz 8 simbolus garai.';
  if (!preg_match('/[A-ZĀČĒĢĪĶĻŅŠŪŽ]/u', $p)) return 'Parolei jābūt vismaz vienam lielajam burtam (A-Z).';
  if (!preg_match('/[0-9]/', $p)) return 'Parolei jābūt vismaz vienam ciparam.';
  if (!preg_match('/[^A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž0-9]/', $p)) return 'Parolei jābūt vismaz vienam speciālam simbolam (!, @, #, $ utt.).';
  return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vards    = escape($savienojums, trim($_POST['vards'] ?? ''));
  $uzvards  = escape($savienojums, trim($_POST['uzvards'] ?? ''));
  $epasts   = escape($savienojums, trim($_POST['epasts'] ?? ''));
  $talrunis = escape($savienojums, trim($_POST['talrunis'] ?? ''));
  $parole   = $_POST['parole'] ?? '';
  $parole2  = $_POST['parole2'] ?? '';

  if (empty($vards) || empty($epasts) || empty($parole)) {
    $error = 'Lūdzu aizpildiet visus obligātos laukus.';
  } elseif ($parole !== $parole2) {
    $error = 'Paroles nesakrīt.';
  } elseif ($pwErr = validatePassword($parole)) {
    $error = $pwErr;
  } elseif (!filter_var(str_replace('\\', '', $epasts), FILTER_VALIDATE_EMAIL)) {
    $error = 'Lūdzu ievadiet derīgu e-pasta adresi.';
  } else {
    $check = mysqli_query($savienojums, "SELECT id FROM klienti WHERE epasts='$epasts'");
    if (mysqli_num_rows($check) > 0) {
      $error = 'Šis e-pasts jau ir reģistrēts. <a href="/4pt/blazkova/lumina/Lumina/login.php" style="color:var(--gold)">Pieslēgties →</a>';
    } else {
      $hash = password_hash($parole, PASSWORD_DEFAULT);
      $sql = "INSERT INTO klienti (vards, uzvards, epasts, talrunis, parole, kopeja_summa) VALUES ('$vards','$uzvards','$epasts','$talrunis','$hash',0)";
      if (mysqli_query($savienojums, $sql)) {
        $id = mysqli_insert_id($savienojums);
        session_regenerate_id(true);
        $_SESSION['klients_id']    = $id;
        $_SESSION['klients_vards'] = $vards;
        $_SESSION['klients_epasts']= $epasts;
        // E-pasta apstiprinājums
        try { require_once __DIR__ . '/includes/mailer.php'; mailRegistracija($epasts, $vards); } catch(\Throwable $e) { error_log('Mail err: '.$e->getMessage()); }
        header('Location: /4pt/blazkova/lumina/Lumina/profils.php');
        exit;
      } else {
        $error = 'Reģistrācijas kļūda: ' . mysqli_error($savienojums);
      }
    }
  }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:120px 22px 60px;background:var(--cream2);">
  <div style="width:100%;max-width:500px;">
    <div style="text-align:center;margin-bottom:40px;">
      <div class="section-label" style="display:block;text-align:center;">Pievienojieties</div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:46px;font-weight:300;color:var(--ink);margin-top:10px;">Reģistrēties</h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:20px;"><?= $error ?></div>
    <?php endif; ?>

    <div style="background:var(--white);padding:48px;border:1px solid var(--grey3);">
      <form method="POST" id="regForm">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          <div class="form-group">
            <label class="form-label">Vārds *</label>
            <input type="text" name="vards" class="form-input" required value="<?= htmlspecialchars($_POST['vards'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Uzvārds</label>
            <input type="text" name="uzvards" class="form-input" value="<?= htmlspecialchars($_POST['uzvards'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">E-pasta adrese *</label>
          <input type="email" name="epasts" class="form-input" required placeholder="jusu@epasts.lv" value="<?= htmlspecialchars($_POST['epasts'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Tālrunis</label>
          <input type="tel" name="talrunis" class="form-input" placeholder="+371 2000 0000" value="<?= htmlspecialchars($_POST['talrunis'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Parole *</label>
          <div style="position:relative;">
            <input type="password" name="parole" id="pw1" class="form-input" required placeholder="Ievadiet paroli" style="padding-right:44px;" oninput="checkPw(this.value)">
            <button type="button" onclick="togglePw('pw1')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--grey);font-size:16px;">👁</button>
          </div>
          <!-- Password strength indicator -->
          <div style="margin-top:8px;">
            <div style="display:flex;gap:4px;margin-bottom:6px;">
              <div id="s1" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
              <div id="s2" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
              <div id="s3" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
              <div id="s4" style="height:3px;flex:1;border-radius:2px;background:#eee;transition:.3s;"></div>
            </div>
            <div style="font-size:10px;color:var(--grey);line-height:1.6;" id="pwHints">
              <span id="h1" style="opacity:.5;">✓ Vismaz 8 simboli</span> ·
              <span id="h2" style="opacity:.5;">✓ Lielais burts (A-Z)</span> ·
              <span id="h3" style="opacity:.5;">✓ Cipars (0-9)</span> ·
              <span id="h4" style="opacity:.5;">✓ Speciāls simbols (!@#$...)</span>
            </div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Apstiprināt paroli *</label>
          <div style="position:relative;">
            <input type="password" name="parole2" id="pw2" class="form-input" required placeholder="Atkārtojiet paroli" style="padding-right:44px;">
            <button type="button" onclick="togglePw('pw2')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--grey);font-size:16px;">👁</button>
          </div>
          <div id="pwMatch" style="font-size:11px;margin-top:4px;display:none;"></div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;margin-top:6px;">Reģistrēties →</button>
      </form>
      <div style="margin-top:24px;text-align:center;font-size:13px;color:var(--grey);">
        Jau ir konts? <a href="/4pt/blazkova/lumina/Lumina/login.php" style="color:var(--gold);text-decoration:none;">Pieslēgties →</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function togglePw(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}

function checkPw(v) {
  const checks = [
    v.length >= 8,
    /[A-ZĀČĒĢĪĶĻŅŠŪŽ]/.test(v),
    /[0-9]/.test(v),
    /[^A-Za-zĀāČčĒēĢģĪīĶķĻļŅņŠšŪūŽž0-9]/.test(v)
  ];
  const colors = ['#eee','#e74c3c','#e67e22','#f1c40f','#27ae60'];
  const score = checks.filter(Boolean).length;
  [1,2,3,4].forEach(i => {
    document.getElementById('s'+i).style.background = i <= score ? colors[score] : '#eee';
    document.getElementById('h'+i).style.opacity = checks[i-1] ? '1' : '0.4';
    document.getElementById('h'+i).style.color = checks[i-1] ? '#27ae60' : 'var(--grey)';
  });
}

// Check passwords match
document.getElementById('pw2').addEventListener('input', function() {
  const match = this.value === document.getElementById('pw1').value;
  const el = document.getElementById('pwMatch');
  el.style.display = this.value ? 'block' : 'none';
  el.textContent = match ? '✓ Paroles sakrīt' : '✕ Paroles nesakrīt';
  el.style.color = match ? '#27ae60' : '#e74c3c';
});

// Prevent submit if passwords don't match
document.getElementById('regForm').addEventListener('submit', function(e) {
  if (document.getElementById('pw1').value !== document.getElementById('pw2').value) {
    e.preventDefault();
    document.getElementById('pwMatch').style.display = 'block';
    document.getElementById('pwMatch').textContent = '✕ Paroles nesakrīt';
    document.getElementById('pwMatch').style.color = '#e74c3c';
  }
});
</script>
