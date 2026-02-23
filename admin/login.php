<?php
session_name('lumina_admin');
session_start();

if (isset($_SESSION['admin_auth'])) {
  header('Location: /4pt/blazkova/lumina/Lumina/admin/index.php');
  exit;
}

// Admin password - change this!
$adminPassword = 'Lumina2026!';

$error = '';
$attempts = $_SESSION['login_attempts'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Rate limiting - max 5 attempts
  if ($attempts >= 5) {
    $error = 'Pārāk daudz mēģinājumu. Lūdzu uzgaidiet.';
  } elseif ($_POST['parole'] === $adminPassword) {
    session_regenerate_id(true);
    $_SESSION['admin_auth'] = true;
    $_SESSION['login_attempts'] = 0;
    header('Location: /4pt/blazkova/lumina/Lumina/admin/index.php');
    exit;
  } else {
    $_SESSION['login_attempts'] = $attempts + 1;
    $error = 'Nepareiza parole. (' . ($_SESSION['login_attempts']) . '/5)';
    // Small delay to slow brute force
    sleep(1);
  }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<title>Admin — LUMINA</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/4pt/blazkova/lumina/Lumina/admin/admin.css">
<style>
body { background: #1a1a1a; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.login-box { background: var(--white); padding: 52px; width: 100%; max-width: 400px; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
.pw-wrap { position: relative; }
.pw-toggle { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--grey2); font-size: 16px; }
</style>
</head>
<body>
<div class="login-box">
  <div style="text-align:center;margin-bottom:36px;">
    <div style="font-family:'Cormorant Garamond',serif;font-size:30px;letter-spacing:8px;color:var(--ink);">LUMIN<span style="color:var(--gold)">A</span></div>
    <div style="font-size:10px;letter-spacing:4px;text-transform:uppercase;color:var(--grey);margin-top:8px;">Admin panelis</div>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($attempts >= 5): ?>
  <div style="text-align:center;padding:20px;color:var(--grey2);font-size:13px;">
    Pārāk daudz neveiksmīgu mēģinājumu.<br>
    <a href="?" style="color:var(--gold);" onclick="<?php $_SESSION['login_attempts']=0; ?>">Mēģināt vēlreiz</a>
  </div>
  <?php else: ?>
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Admin parole</label>
      <div class="pw-wrap">
        <input type="password" name="parole" id="adminPw" class="form-input" placeholder="••••••••" autofocus style="padding-right:44px;">
        <button type="button" class="pw-toggle" onclick="const i=document.getElementById('adminPw');i.type=i.type==='password'?'text':'password';">👁</button>
      </div>
    </div>
    <button type="submit" class="btn-primary" style="width:100%;">Pieslēgties →</button>
  </form>
  <?php endif; ?>

  <div style="margin-top:20px;text-align:center;">
    <a href="/4pt/blazkova/lumina/Lumina/index.php" style="font-size:11px;color:var(--grey2);text-decoration:none;">← Uz mājaslapu</a>
  </div>
</div>
</body>
</html>
