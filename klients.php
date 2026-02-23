<?php 
require_once 'header.php';

// Redirect if not logged in
if(!isset($_SESSION['klients_id'])){
    $_SESSION['toast'] = ['msg' => 'Lūdzu pieslēdzieties, lai skatītu profilu', 'type' => 'error'];
    header('Location: index.php');
    exit;
}

$klienta_id = $_SESSION['klients_id'];

// Get client data
$klient_res = mysqli_query($savienojums, "SELECT * FROM klienti WHERE id=$klienta_id");
$klients = mysqli_fetch_assoc($klient_res);

// Get orders
$pasutijumi = mysqli_query($savienojums, "SELECT * FROM pasutijumi WHERE klienta_id=$klienta_id ORDER BY izveidots DESC");

// Get reservations
$rezervacijas = mysqli_query($savienojums, "SELECT * FROM rezervacijas WHERE klienta_id=$klienta_id ORDER BY datums DESC");

// Get galleries
$galerijas = mysqli_query($savienojums, "SELECT * FROM galerijas WHERE klienta_id=$klienta_id ORDER BY izveidota DESC");

// Handle profile update
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])){
    $v = mysqli_real_escape_string($savienojums, trim($_POST['vards']));
    $u = mysqli_real_escape_string($savienojums, trim($_POST['uzvards']));
    $t = mysqli_real_escape_string($savienojums, trim($_POST['talrunis']));
    mysqli_query($savienojums, "UPDATE klienti SET vards='$v', uzvards='$u', talrunis='$t' WHERE id=$klienta_id");
    $_SESSION['klients_vards'] = $v;
    $_SESSION['toast'] = ['msg' => 'Profils atjaunināts!', 'type' => 'success'];
    header('Location: klients.php?tab=settings');
    exit;
}

$active_tab = $_GET['tab'] ?? 'orders';

$statuss_labels = [
    'gaida_maksajumu' => 'Gaida maksājumu',
    'apstrade' => 'Apstrādē',
    'razosana' => 'Ražošanā',
    'piegade' => 'Piegādē',
    'piegadats' => 'Piegādāts',
    'gaidoss' => 'Gaida apstiprinājumu',
    'apstiprinats' => 'Apstiprināts',
    'pabeigts' => 'Pabeigts',
    'atsaukts' => 'Atcelts',
];
?>

<style>
.klients-layout{padding-top:80px;min-height:100vh;background:var(--cream);}
.klients-header{background:var(--white);padding:40px 64px;border-bottom:1px solid var(--grey3);display:flex;align-items:center;justify-content:space-between;}
.klients-avatar{width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,var(--gold) 0%,var(--gold-light) 100%);display:flex;align-items:center;justify-content:center;font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--white);border:2px solid var(--gold);flex-shrink:0;}
.klients-name{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:400;color:var(--ink);}
.klients-since{font-size:11px;color:var(--grey2);margin-top:4px;}
.klients-total-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);text-align:right;}
.klients-total{font-family:'Cormorant Garamond',serif;font-size:34px;color:var(--gold);}
.klients-tabs{background:var(--white);border-bottom:1px solid var(--grey3);display:flex;padding:0 64px;}
.klients-tab{padding:16px 24px;font-size:10px;letter-spacing:2.5px;text-transform:uppercase;color:var(--grey);cursor:pointer;border-bottom:2px solid transparent;transition:all .3s;text-decoration:none;display:block;}
.klients-tab:hover,.klients-tab.active{color:var(--gold);border-bottom-color:var(--gold);}
.klients-content{padding:52px 64px;max-width:1100px;}
.order-card{display:grid;grid-template-columns:80px 1fr auto auto;gap:20px;align-items:center;padding:20px;background:var(--white);margin-bottom:10px;border:1px solid var(--grey3);transition:box-shadow .2s;}
.order-card:hover{box-shadow:0 4px 20px var(--shadow);}
.order-img{width:80px;height:60px;object-fit:cover;}
.order-name{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:4px;}
.order-meta{font-size:11px;color:var(--grey2);}
.order-price{font-size:17px;color:var(--gold);font-weight:500;}
.rez-card{padding:20px;background:var(--white);margin-bottom:10px;border:1px solid var(--grey3);border-left:3px solid var(--gold);}
.rez-card.past{border-left-color:var(--grey3);}
.rez-service{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--ink);margin-bottom:6px;}
.rez-meta{font-size:12px;color:var(--grey);line-height:1.8;}
.gal-card{padding:20px;background:var(--white);margin-bottom:10px;border:1px solid var(--grey3);}
.gal-title{font-family:'Cormorant Garamond',serif;font-size:21px;color:var(--ink);margin-bottom:6px;}
.gal-meta{font-size:12px;color:var(--grey);margin-bottom:14px;}
.gal-bar{background:var(--grey3);height:3px;border-radius:2px;margin:10px 0;}
.gal-bar-fill{background:var(--gold);height:100%;border-radius:2px;}
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:32px;}
.stat-box{background:var(--white);padding:22px;border:1px solid var(--grey3);text-align:center;}
.stat-box-num{font-family:'Cormorant Garamond',serif;font-size:36px;color:var(--gold);}
.stat-box-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);margin-top:4px;}
@media(max-width:900px){.klients-header{padding:20px 22px;flex-wrap:wrap;gap:16px;}.klients-tabs{padding:0 10px;overflow-x:auto;}.klients-content{padding:30px 22px;}.order-card{grid-template-columns:60px 1fr;}.stats-row{grid-template-columns:repeat(2,1fr);}}
</style>

<div class="klients-layout">
  <div class="klients-header">
    <div style="display:flex;align-items:center;gap:18px;">
      <div class="klients-avatar"><?= strtoupper(substr($klients['vards'],0,1).substr($klients['uzvards'],0,1)) ?></div>
      <div>
        <div class="klients-name"><?= htmlspecialchars($klients['vards'].' '.$klients['uzvards']) ?></div>
        <div class="klients-since"><?= htmlspecialchars($klients['epasts']) ?> · Klients kopš <?= date('Y', strtotime($klients['registrets'])) ?>. gada</div>
      </div>
    </div>
    <div style="text-align:right;">
      <div class="klients-total-label">Kopā iztērēts</div>
      <div class="klients-total">€<?= number_format($klients['kopeja_summa'],2) ?></div>
    </div>
  </div>

  <div class="klients-tabs">
    <a href="klients.php?tab=orders" class="klients-tab <?= $active_tab=='orders'?'active':'' ?>">📦 Pasūtījumi</a>
    <a href="klients.php?tab=gallery" class="klients-tab <?= $active_tab=='gallery'?'active':'' ?>">🖼 Galerijas</a>
    <a href="klients.php?tab=bookings" class="klients-tab <?= $active_tab=='bookings'?'active':'' ?>">📅 Rezervācijas</a>
    <a href="klients.php?tab=settings" class="klients-tab <?= $active_tab=='settings'?'active':'' ?>">⚙ Iestatījumi</a>
  </div>

  <div class="klients-content">
    <?php if($active_tab === 'orders'): ?>
      <div class="stats-row">
        <?php
        $cnt = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM pasutijumi WHERE klienta_id=$klienta_id"));
        $sum = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COALESCE(SUM(cena),0) as s FROM pasutijumi WHERE klienta_id=$klienta_id"));
        $del = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM pasutijumi WHERE klienta_id=$klienta_id AND statuss='piegadats'"));
        $pend = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM pasutijumi WHERE klienta_id=$klienta_id AND statuss NOT IN ('piegadats','atsaukts')"));
        ?>
        <div class="stat-box"><div class="stat-box-num"><?= $cnt['c'] ?></div><div class="stat-box-label">Pasūtījumi</div></div>
        <div class="stat-box"><div class="stat-box-num">€<?= number_format($sum['s'],0) ?></div><div class="stat-box-label">Kopā iztērēts</div></div>
        <div class="stat-box"><div class="stat-box-num"><?= $del['c'] ?></div><div class="stat-box-label">Saņemts</div></div>
        <div class="stat-box"><div class="stat-box-num"><?= $pend['c'] ?></div><div class="stat-box-label">Apstrādē</div></div>
      </div>

      <div style="font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--grey2);margin-bottom:16px;"><?= $cnt['c'] ?> pasūtījumi</div>
      <?php 
      mysqli_data_seek($pasutijumi, 0);
      while($p = mysqli_fetch_assoc($pasutijumi)): 
        $imgs = ['https://images.unsplash.com/photo-1519741497674-611481863552?w=200&q=80','https://images.unsplash.com/photo-1509042239860-f550ce710b93?w=200&q=80','https://images.unsplash.com/photo-1484399172022-72a90b12e3c1?w=200&q=80','https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=200&q=80'];
        $img = $imgs[$p['id'] % count($imgs)];
      ?>
      <div class="order-card">
        <img class="order-img" src="<?= $img ?>" alt="">
        <div>
          <div class="order-name"><?= htmlspecialchars($p['preces_nosaukums']) ?></div>
          <div class="order-meta"><?= date('d. M Y', strtotime($p['izveidots'])) ?> · #ORD-<?= $p['id'] ?> · <?= htmlspecialchars($p['piegades_metode'] ?? '') ?></div>
        </div>
        <div style="text-align:right;">
          <div class="order-price">€<?= number_format($p['cena'],2) ?></div>
          <div style="font-size:11px;color:var(--grey2);margin-top:3px;"><?= htmlspecialchars($p['izmers'] ?? '') ?></div>
        </div>
        <div><span class="status-badge <?= htmlspecialchars($p['statuss']) ?>"><?= htmlspecialchars($statuss_labels[$p['statuss']] ?? $p['statuss']) ?></span></div>
      </div>
      <?php endwhile; ?>

    <?php elseif($active_tab === 'gallery'): ?>
      <?php 
      mysqli_data_seek($galerijas, 0);
      $gal_count = 0;
      while($g = mysqli_fetch_assoc($galerijas)): 
        $gal_count++;
        $days_left = max(0, ceil((strtotime($g['deriga_lidz']) - time()) / 86400));
        $pct = min(100, round($days_left / 180 * 100));
      ?>
      <div class="gal-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
          <div>
            <div class="gal-title"><?= htmlspecialchars($g['nosaukums']) ?></div>
            <div class="gal-meta">📸 <?= $g['foto_skaits'] ?> fotogrāfijas · 📅 Izveidota: <?= date('d. M Y', strtotime($g['izveidota'])) ?></div>
          </div>
          <span class="status-badge <?= $days_left > 30 ? 'apstiprinats' : 'gaidoss' ?>">
            Derīga <?= $days_left ?> dienas
          </span>
        </div>
        <div class="gal-bar"><div class="gal-bar-fill" style="width:<?= $pct ?>%"></div></div>
        <div style="display:flex;gap:10px;margin-top:14px;">
          <button class="action-btn" onclick="showToast('Galerija tiek atvērta...','success')">Skatīt galeriju →</button>
          <button class="action-btn" onclick="showToast('ZIP lejupielāde sākta!','success')">Lejupielādēt ZIP</button>
        </div>
      </div>
      <?php endwhile; ?>
      <?php if($gal_count === 0): ?>
      <div style="text-align:center;padding:60px 0;color:var(--grey);">
        <div style="font-size:48px;margin-bottom:16px;">📷</div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--ink);margin-bottom:8px;">Vēl nav galeriju</div>
        <div>Pēc fotosesijas jūsu fotogrāfijas parādīsies šeit</div>
        <a href="rezervet.php" class="btn-primary" style="margin-top:24px;display:inline-block;">Rezervēt sesiju →</a>
      </div>
      <?php endif; ?>

    <?php elseif($active_tab === 'bookings'): ?>
      <?php 
      mysqli_data_seek($rezervacijas, 0);
      $rez_count = 0;
      while($r = mysqli_fetch_assoc($rezervacijas)): 
        $rez_count++;
        $is_past = strtotime($r['datums']) < time();
        $is_upcoming = !$is_past && $r['statuss'] !== 'atsaukts';
      ?>
      <div class="rez-card <?= $is_past ? 'past' : '' ?>">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:10px;">
          <div>
            <div style="font-size:9px;letter-spacing:3px;text-transform:uppercase;color:<?= $is_past ? 'var(--grey2)' : 'var(--gold)' ?>;margin-bottom:5px;">
              <?= $is_past ? 'Pabeigta rezervācija' : 'Gaidāma rezervācija' ?>
            </div>
            <div class="rez-service"><?= htmlspecialchars($r['pakalpojums']) ?></div>
            <div class="rez-meta">
              📅 <?= date('d. M Y', strtotime($r['datums'])) ?>, plkst. <?= substr($r['laiks'],0,5) ?>
              <?php if($r['vieta']): ?> · 📍 <?= htmlspecialchars($r['vieta']) ?><?php endif; ?>
            </div>
          </div>
          <span class="status-badge <?= htmlspecialchars($r['statuss']) ?>"><?= htmlspecialchars($statuss_labels[$r['statuss']] ?? $r['statuss']) ?></span>
        </div>
        <?php if($r['papildu_info']): ?>
        <div style="margin-top:10px;font-size:12px;color:var(--grey);padding:10px;background:var(--cream);border-left:2px solid var(--grey3);"><?= htmlspecialchars($r['papildu_info']) ?></div>
        <?php endif; ?>
      </div>
      <?php endwhile; ?>
      <?php if($rez_count === 0): ?>
      <div style="text-align:center;padding:60px 0;color:var(--grey);">
        <div style="font-size:48px;margin-bottom:16px;">📅</div>
        <div style="font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--ink);margin-bottom:8px;">Nav rezervāciju</div>
        <a href="rezervet.php" class="btn-primary" style="margin-top:24px;display:inline-block;">Rezervēt sesiju →</a>
      </div>
      <?php endif; ?>

    <?php elseif($active_tab === 'settings'): ?>
      <h3 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:300;color:var(--ink);margin-bottom:28px;">Profila iestatījumi</h3>
      <form method="POST" action="klients.php?tab=settings" style="max-width:580px;">
        <input type="hidden" name="update_profile" value="1">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Vārds</label><input class="form-input" name="vards" value="<?= htmlspecialchars($klients['vards']) ?>"></div>
          <div class="form-group"><label class="form-label">Uzvārds</label><input class="form-input" name="uzvards" value="<?= htmlspecialchars($klients['uzvards']) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">E-pasts</label><input class="form-input" value="<?= htmlspecialchars($klients['epasts']) ?>" type="email" disabled style="opacity:.6;"></div>
        <div class="form-group"><label class="form-label">Tālrunis</label><input class="form-input" name="talrunis" value="<?= htmlspecialchars($klients['talrunis'] ?? '') ?>" type="tel"></div>
        <div class="form-group"><label class="form-label">Jauna parole</label><input class="form-input" name="parole" placeholder="Atstājiet tukšu, ja nemainīsiet" type="password"></div>
        <button type="submit" class="btn-primary">Saglabāt izmaiņas</button>
      </form>
      
      <div style="margin-top:40px;padding-top:40px;border-top:1px solid var(--grey3);">
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--ink);margin-bottom:16px;">Konta kopsavilkums</h3>
        <div class="stats-row">
          <?php
          $orders_cnt = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM pasutijumi WHERE klienta_id=$klienta_id"));
          $rez_cnt = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM rezervacijas WHERE klienta_id=$klienta_id"));
          $gal_cnt = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM galerijas WHERE klienta_id=$klienta_id"));
          ?>
          <div class="stat-box"><div class="stat-box-num"><?= $orders_cnt['c'] ?></div><div class="stat-box-label">Pasūtījumi</div></div>
          <div class="stat-box"><div class="stat-box-num"><?= $rez_cnt['c'] ?></div><div class="stat-box-label">Rezervācijas</div></div>
          <div class="stat-box"><div class="stat-box-num"><?= $gal_cnt['c'] ?></div><div class="stat-box-label">Galerijas</div></div>
          <div class="stat-box"><div class="stat-box-num">€<?= number_format($klients['kopeja_summa'],0) ?></div><div class="stat-box-label">Kopā iztērēts</div></div>
        </div>
        <a href="logout.php" class="btn-outline" style="margin-top:20px;display:inline-block;border-color:#C0392B;color:#C0392B;">Iziet no konta</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'footer.php'; ?>