<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Pieslēgties';

if (isset($_SESSION['klients_id'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/profils.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $epasts = escape($savienojums, trim($_POST['epasts'] ?? ''));
  $parole = $_POST['parole'] ?? '';

  $result = mysqli_query($savienojums, "SELECT * FROM klienti WHERE epasts='$epasts' LIMIT 1");
  $klients = mysqli_fetch_assoc($result);

  if ($klients && password_verify($parole, $klients['parole'])) {
    session_regenerate_id(true); // prevent session fixation
    $_SESSION['klients_id']    = $klients['id'];
    $_SESSION['klients_vards'] = $klients['vards'];
    $_SESSION['klients_epasts']= $klients['epasts'];
    header('Location: /4pt/blazkova/lumina/Lumina/profils.php');
    exit;
  } else {
    $error = 'Nepareizs e-pasts vai parole.';
  }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:120px 22px 60px;background:var(--cream2);">
  <div style="width:100%;max-width:440px;">
    <div style="text-align:center;margin-bottom:40px;">
      <div class="section-label" style="display:block;text-align:center;">Laipni lūgti atpakaļ</div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:46px;font-weight:300;color:var(--ink);margin-top:10px;">Pieslēgties</h1>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:20px;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="background:var(--white);padding:48px;border:1px solid var(--grey3);">
      <form method="POST" autocomplete="off">
        <div class="form-group">
          <label class="form-label">E-pasta adrese</label>
          <input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required
            value="<?= htmlspecialchars($_POST['epasts'] ?? '') ?>" autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Parole</label>
          <div style="position:relative;">
            <input type="password" name="parole" id="paroleInput" class="form-input" placeholder="Ievadiet paroli" required style="padding-right:44px;">
            <button type="button" onclick="togglePw('paroleInput','eyeBtn')" id="eyeBtn"
              style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--grey);font-size:16px;">👁</button>
          </div>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;margin-top:10px;">Pieslēgties →</button>
      </form>
      <div style="margin-top:24px;text-align:center;font-size:13px;color:var(--grey);">
        Nav konta? <a href="/4pt/blazkova/lumina/Lumina/registracija.php" style="color:var(--gold);text-decoration:none;">Reģistrēties →</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
function togglePw(inputId, btnId) {
  const inp = document.getElementById(inputId);
  const btn = document.getElementById(btnId);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁' : '🙈';
}
</script>
