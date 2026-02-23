<?php
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

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
  $vards = escape($savienojums, $_POST['vards'] ?? '');
  $uzvards = escape($savienojums, $_POST['uzvards'] ?? '');
  $talrunis = escape($savienojums, $_POST['talrunis'] ?? '');
  
  $sql = "UPDATE klienti SET vards='$vards', uzvards='$uzvards', talrunis='$talrunis' WHERE id=$klientsId";
  if (mysqli_query($savienojums, $sql)) {
    $_SESSION['klients_vards'] = $vards;
    $success = 'Profils atjaunināts!';
  } else {
    $error = 'Kļūda: ' . mysqli_error($savienojums);
  }
}

// Fetch client data
$klients = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT * FROM klienti WHERE id=$klientsId"));

// Fetch reservations
$rezervacijas = [];
$rezResult = mysqli_query($savienojums, "SELECT * FROM rezervacijas WHERE klienta_id=$klientsId ORDER BY datums DESC");
while ($row = mysqli_fetch_assoc($rezResult)) $rezervacijas[] = $row;

// Fetch galleries
$galerijas = [];
$galResult = mysqli_query($savienojums, "SELECT * FROM galerijas WHERE klienta_id=$klientsId ORDER BY izveidota DESC");
while ($row = mysqli_fetch_assoc($galResult)) $galerijas[] = $row;

$activeTab = $_GET['tab'] ?? 'rezervacijas';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div style="padding-top:80px;min-height:100vh;background:var(--cream);">
  
  <!-- Profile Header -->
  <div class="profils-header">
    <div class="profils-header-inner">
      <div class="profils-avatar">
        <?= strtoupper(substr($klients['vards'], 0, 1)) ?>
      </div>
      <div>
        <h1 style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:300;color:var(--ink);"><?= htmlspecialchars($klients['vards'] . ' ' . $klients['uzvards']) ?></h1>
        <div style="font-size:12px;color:var(--grey);margin-top:4px;"><?= htmlspecialchars($klients['epasts']) ?></div>
      </div>
      <div style="margin-left:auto;text-align:right;">
        <div style="font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);">Kopā iztērēts</div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:32px;color:var(--gold);">€<?= number_format($klients['kopeja_summa'], 2) ?></div>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="profils-tabs-wrap">
    <div class="profils-tabs">
      <a href="?tab=rezervacijas" class="profils-tab <?= $activeTab === 'rezervacijas' ? 'active' : '' ?>">Rezervācijas</a>
      <a href="?tab=galerijas" class="profils-tab <?= $activeTab === 'galerijas' ? 'active' : '' ?>">Manas Galerijas</a>
      <a href="?tab=iestatijumi" class="profils-tab <?= $activeTab === 'iestatijumi' ? 'active' : '' ?>">Iestatījumi</a>
    </div>
  </div>

  <div class="profils-content">
    
    <?php if ($success): ?>
    <div class="alert alert-success" style="margin-bottom:24px;"><?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:24px;"><?= $error ?></div>
    <?php endif; ?>

    <!-- RESERVATIONS TAB -->
    <?php if ($activeTab === 'rezervacijas'): ?>
    <div>
      <div style="font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:var(--grey2);margin-bottom:24px;"><?= count($rezervacijas) ?> rezervācijas</div>
      
      <?php if (empty($rezervacijas)): ?>
      <div style="text-align:center;padding:60px;color:var(--grey);">
        <div style="font-size:40px;margin-bottom:16px;opacity:.3;">📅</div>
        <p>Vēl nav rezervāciju.</p>
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary" style="margin-top:20px;display:inline-block;">Rezervēt sesiju →</a>
      </div>
      <?php else: ?>
      <?php foreach ($rezervacijas as $r): ?>
      <div class="rezervacija-row">
        <div class="rez-date">
          <div style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:300;color:var(--gold);"><?= date('d', strtotime($r['datums'])) ?></div>
          <div style="font-size:10px;letter-spacing:1px;color:var(--grey);"><?= date('M Y', strtotime($r['datums'])) ?></div>
        </div>
        <div class="rez-info">
          <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:4px;"><?= htmlspecialchars($r['pakalpojums']) ?></div>
          <div style="font-size:12px;color:var(--grey);">
            📅 <?= date('d.m.Y', strtotime($r['datums'])) ?> plkst. <?= substr($r['laiks'], 0, 5) ?>
            <?php if ($r['vieta']): ?> · 📍 <?= htmlspecialchars($r['vieta']) ?><?php endif; ?>
          </div>
        </div>
        <div>
          <?php if ($r['cena']): ?>
          <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--gold);margin-bottom:6px;">€<?= number_format($r['cena'], 0) ?></div>
          <?php endif; ?>
        </div>
        <div>
          <span class="status-badge <?= $r['statuss'] === 'apstiprinats' ? 'pending' : ($r['statuss'] === 'pabeigts' ? 'delivered' : 'cancelled') ?>">
            <?= htmlspecialchars($r['statuss']) ?>
          </span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
      
      <div style="margin-top:32px;">
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-outline">+ Jauna rezervācija</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- GALLERIES TAB -->
    <?php if ($activeTab === 'galerijas'): ?>
    <div>
      <?php if (empty($galerijas)): ?>
      <div style="text-align:center;padding:60px;color:var(--grey);">
        <div style="font-size:40px;margin-bottom:16px;opacity:.3;">🖼️</div>
        <p>Jūsu galerijas vēl nav augšupielādētas.</p>
        <p style="margin-top:8px;font-size:12px;">Pēc fotosesijas mēs ievietosim šeit jūsu fotogrāfijas.</p>
      </div>
      <?php else: ?>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
        <?php foreach ($galerijas as $g): ?>
        <a href="/4pt/blazkova/lumina/Lumina/galerija.php?id=<?= $g['id'] ?>" style="text-decoration:none;" class="galerija-preview-card">
          <div style="height:160px;background:var(--cream2);display:flex;align-items:center;justify-content:center;font-size:36px;opacity:.3;">📷</div>
          <div style="padding:20px 0 0;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--ink);margin-bottom:6px;"><?= htmlspecialchars($g['nosaukums']) ?></div>
            <div style="font-size:12px;color:var(--grey);"><?= $g['foto_skaits'] ?> fotogrāfijas · Derīgs līdz <?= date('d.m.Y', strtotime($g['deriga_lidz'])) ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- SETTINGS TAB -->
    <?php if ($activeTab === 'iestatijumi'): ?>
    <div style="max-width:540px;">
      <form method="POST">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-row">
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
          <label class="form-label">E-pasts (nevar mainīt)</label>
          <input type="email" class="form-input" value="<?= htmlspecialchars($klients['epasts']) ?>" readonly style="background:var(--cream2);color:var(--grey);">
        </div>
        <div class="form-group">
          <label class="form-label">Tālrunis</label>
          <input type="tel" name="talrunis" class="form-input" value="<?= htmlspecialchars($klients['talrunis']) ?>">
        </div>
        <button type="submit" class="btn-primary">Saglabāt izmaiņas</button>
      </form>
      <div style="margin-top:40px;padding-top:32px;border-top:1px solid var(--grey3);">
        <a href="/4pt/blazkova/lumina/Lumina/logout.php" class="btn-outline" style="color:#C0392B;border-color:#C0392B;">Iziet no konta</a>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
