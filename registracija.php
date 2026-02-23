<?php
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Reģistrācija';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vards = escape($savienojums, trim($_POST['vards'] ?? ''));
  $uzvards = escape($savienojums, trim($_POST['uzvards'] ?? ''));
  $epasts = escape($savienojums, trim($_POST['epasts'] ?? ''));
  $talrunis = escape($savienojums, trim($_POST['talrunis'] ?? ''));
  $parole = $_POST['parole'] ?? '';
  
  if (empty($vards) || empty($epasts) || empty($parole)) {
    $error = 'Lūdzu aizpildiet visus obligātos laukus.';
  } elseif (strlen($parole) < 6) {
    $error = 'Parolei jābūt vismaz 6 simbolus garai.';
  } else {
    // Check if email exists
    $check = mysqli_query($savienojums, "SELECT id FROM klienti WHERE epasts = '$epasts'");
    if (mysqli_num_rows($check) > 0) {
      $error = 'Šis e-pasts jau ir reģistrēts.';
    } else {
      $hash = password_hash($parole, PASSWORD_DEFAULT);
      $sql = "INSERT INTO klienti (vards, uzvards, epasts, talrunis, parole, kopeja_summa) VALUES ('$vards', '$uzvards', '$epasts', '$talrunis', '$hash', 0)";
      if (mysqli_query($savienojums, $sql)) {
        $id = mysqli_insert_id($savienojums);
        $_SESSION['klients_id'] = $id;
        $_SESSION['klients_vards'] = $vards;
        $_SESSION['klients_epasts'] = $epasts;
        header('Location: /4pt/blazkova/lumina/Lumina/profils.php');
        exit;
      } else {
        $error = 'Kļūda reģistrācijā: ' . mysqli_error($savienojums);
      }
    }
  }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:120px 22px 60px;background:var(--cream2);">
  <div style="width:100%;max-width:480px;">
    <div style="text-align:center;margin-bottom:40px;">
      <div class="section-label" style="display:block;text-align:center;">Pievienojieties</div>
      <h1 style="font-family:'Cormorant Garamond',serif;font-size:46px;font-weight:300;color:var(--ink);margin-top:10px;">Reģistrēties</h1>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div style="background:var(--white);padding:48px;border:1px solid var(--grey3);">
      <form method="POST">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Vārds *</label>
            <input type="text" name="vards" class="form-input" placeholder="Vārds" required value="<?= htmlspecialchars($_POST['vards'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Uzvārds</label>
            <input type="text" name="uzvards" class="form-input" placeholder="Uzvārds" value="<?= htmlspecialchars($_POST['uzvards'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">E-pasts *</label>
          <input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required value="<?= htmlspecialchars($_POST['epasts'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Tālrunis</label>
          <input type="tel" name="talrunis" class="form-input" placeholder="+371 20 000 000" value="<?= htmlspecialchars($_POST['talrunis'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Parole * (min. 6 simboli)</label>
          <input type="password" name="parole" class="form-input" placeholder="Izveidojiet paroli" required>
        </div>
        <button type="submit" class="btn-primary" style="width:100%;margin-top:10px;">Reģistrēties →</button>
      </form>
      <div style="margin-top:24px;text-align:center;font-size:13px;color:var(--grey);">
        Jau ir konts? <a href="/4pt/blazkova/lumina/Lumina/login.php" style="color:var(--gold);text-decoration:none;">Pieslēgties →</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
