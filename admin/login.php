<?php
session_start();

// Simple admin password - in production use proper auth
$adminPassword = 'lumina2026';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($_POST['parole'] === $adminPassword) {
    $_SESSION['admin_auth'] = true;
    header('Location: /4pt/blazkova/lumina/Lumina/admin/index.php');
    exit;
  } else {
    $error = 'Nepareiza parole.';
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
body { background: var(--ink); display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
.login-box { background: var(--white); padding: 52px; width: 100%; max-width: 400px; }
</style>
</head>
<body>
<div class="login-box">
  <div style="text-align:center;margin-bottom:36px;">
    <div style="font-family:'Cormorant Garamond',serif;font-size:30px;letter-spacing:8px;color:var(--ink);">LUMIN<span style="color:var(--gold)">A</span></div>
    <div style="font-size:10px;letter-spacing:4px;text-transform:uppercase;color:var(--grey);margin-top:8px;">Admin panelis</div>
  </div>
  
  <?php if (isset($error)): ?>
  <div class="alert alert-error"><?= $error ?></div>
  <?php endif; ?>
  
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Parole</label>
      <input type="password" name="parole" class="form-input" placeholder="Ievadiet admin paroli" autofocus>
    </div>
    <button type="submit" class="btn-primary" style="width:100%;">Pieslēgties →</button>
  </form>
  <div style="margin-top:20px;text-align:center;">
    <a href="/4pt/blazkova/lumina/Lumina/index.php" style="font-size:11px;color:var(--grey2);text-decoration:none;">← Uz mājaslapu</a>
  </div>
</div>
</body>
</html>
