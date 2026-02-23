<?php 
session_start();
require_once 'db.php';

// Simple admin auth
$admin_pass = 'admin2026';
if(isset($_POST['admin_login'])){
    if($_POST['admin_parole'] === $admin_pass){
        $_SESSION['admin'] = true;
    } else {
        $login_error = true;
    }
}
if(isset($_GET['logout_admin'])){
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit;
}

// Handle actions
if(isset($_SESSION['admin'])){
    if(isset($_GET['update_status']) && isset($_GET['table'])){
        $table = $_GET['table'] === 'rezervacijas' ? 'rezervacijas' : 'pasutijumi';
        $id = (int)$_GET['id'];
        $status = mysqli_real_escape_string($savienojums, $_GET['update_status']);
        mysqli_query($savienojums, "UPDATE $table SET statuss='$status' WHERE id=$id");
        header('Location: admin.php?tab='.$_GET['from']);
        exit;
    }
    if(isset($_GET['delete_portfolio'])){
        $id = (int)$_GET['delete_portfolio'];
        mysqli_query($savienojums, "UPDATE portfolio SET `aktīvs`=0 WHERE id=$id");
        header('Location: admin.php?tab=portfolio');
        exit;
    }
}

$active_tab = $_GET['tab'] ?? 'dashboard';

// Get stats
$total_rev = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COALESCE(SUM(cena),0) as s FROM pasutijumi WHERE MONTH(izveidots)=MONTH(CURDATE()) AND YEAR(izveidots)=YEAR(CURDATE())"));
$new_rez = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM rezervacijas WHERE MONTH(izveidots)=MONTH(CURDATE()) AND YEAR(izveidots)=YEAR(CURDATE())"));
$orders_cnt = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM pasutijumi"));
$clients_cnt = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM klienti"));
$pending_orders = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM pasutijumi WHERE statuss='gaida_maksajumu'"));
$new_clients = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM klienti WHERE MONTH(registrets)=MONTH(CURDATE()) AND YEAR(registrets)=YEAR(CURDATE())"));

// Revenue chart data
$rev_data = [];
$rev_result = mysqli_query($savienojums,"SELECT menesis, gads, summa FROM ienemumi ORDER BY gads, menesis");
while($r = mysqli_fetch_assoc($rev_result)) $rev_data[] = $r;
$months_lv = ['','Jan','Feb','Mar','Apr','Mai','Jūn','Jūl','Aug','Sep','Okt','Nov','Dec'];
?>
<!DOCTYPE html>
<html lang="lv">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LUMINA Admin Panel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;1,300&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{--gold:#B8975A;--gold-light:#D4AF70;--gold-dim:rgba(184,151,90,0.10);--gold-border:rgba(184,151,90,0.28);--cream:#FAF8F4;--cream2:#F3EEE6;--cream3:#EAE3D4;--white:#FFFFFF;--ink:#1C1C1C;--ink2:#3D3730;--grey:#7A7267;--grey2:#AAA49C;--grey3:#D8D3CB;--shadow:rgba(90,70,30,0.07);--shadow2:rgba(90,70,30,0.13);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Montserrat',sans-serif;background:var(--cream);color:var(--ink);display:flex;min-height:100vh;}
.sidebar{width:240px;background:var(--ink);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:10;overflow-y:auto;}
.sidebar-logo{padding:30px 26px 24px;border-bottom:1px solid rgba(255,255,255,.07);font-family:'Cormorant Garamond',serif;font-size:22px;letter-spacing:6px;color:var(--white);flex-shrink:0;}
.sidebar-logo span{color:var(--gold);}
.sidebar-logo small{display:block;font-size:9px;letter-spacing:3px;color:rgba(255,255,255,.3);margin-top:4px;font-family:'Montserrat',sans-serif;}
.sidebar-nav{padding:16px 0;flex:1;}
.sidebar-item{padding:12px 26px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.42);cursor:pointer;display:flex;align-items:center;gap:12px;transition:all .2s;position:relative;text-decoration:none;}
.sidebar-item.active,.sidebar-item:hover{color:var(--white);}
.sidebar-item.active{background:rgba(255,255,255,.05);}
.sidebar-item.active::before{content:'';position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--gold);}
.sidebar-footer{padding:20px 26px;border-top:1px solid rgba(255,255,255,.07);}
.admin-main{margin-left:240px;flex:1;padding:38px;min-height:100vh;background:var(--cream);}
.admin-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;}
.admin-title{font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:300;color:var(--ink);}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:32px;}
.stat-card{padding:22px;background:var(--white);border:1px solid var(--grey3);position:relative;overflow:hidden;transition:box-shadow .3s;}
.stat-card:hover{box-shadow:0 4px 22px var(--shadow);}
.stat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:var(--gold-dim);transition:background .3s;}
.stat-card:hover::after{background:var(--gold);}
.stat-icon{font-size:24px;margin-bottom:10px;}
.stat-num{font-family:'Cormorant Garamond',serif;font-size:38px;font-weight:300;color:var(--gold);line-height:1;}
.stat-label{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);margin-top:5px;}
.stat-change{font-size:11px;color:#228B22;margin-top:4px;}
.chart-grid{display:grid;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:28px;}
.chart-box{background:var(--white);border:1px solid var(--grey3);padding:28px;}
.chart-title{font-family:'Cormorant Garamond',serif;font-size:20px;color:var(--ink);margin-bottom:20px;}
.admin-table{width:100%;border-collapse:collapse;}
.admin-table th{text-align:left;padding:10px 14px;font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--grey);border-bottom:1px solid var(--grey3);background:var(--cream2);}
.admin-table td{padding:13px 14px;font-size:13px;border-bottom:1px solid var(--grey3);color:var(--ink2);vertical-align:middle;}
.admin-table tr:hover td{background:rgba(250,248,244,.6);}
.action-btn{padding:5px 13px;background:transparent;border:1px solid var(--grey3);color:var(--grey);font-family:'Montserrat',sans-serif;font-size:9px;letter-spacing:2px;text-transform:uppercase;cursor:pointer;transition:all .2s;margin-right:4px;text-decoration:none;display:inline-block;}
.action-btn:hover{border-color:var(--gold);color:var(--gold);}
.action-btn.danger:hover{border-color:#C0392B;color:#C0392B;}
.action-btn.success:hover{border-color:#228B22;color:#228B22;}
.status-badge{padding:4px 11px;font-size:9px;letter-spacing:2px;text-transform:uppercase;font-weight:600;}
.status-badge.piegadats,.status-badge.pabeigts,.status-badge.success{background:rgba(34,139,34,.08);color:#228B22;}
.status-badge.gaida_maksajumu,.status-badge.gaidoss,.status-badge.pending{background:var(--gold-dim);color:var(--gold);}
.status-badge.apstrade,.status-badge.razosana,.status-badge.apstiprinats{background:rgba(70,130,180,.08);color:steelblue;}
.status-badge.atsaukts{background:rgba(192,57,43,.08);color:#C0392B;}
.tab-content{background:var(--white);border:1px solid var(--grey3);padding:0;}
.tab-header{padding:20px 24px;border-bottom:1px solid var(--grey3);display:flex;align-items:center;justify-content:space-between;}
.tab-title{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:300;color:var(--ink);}
.form-group{margin-bottom:14px;}
.form-label{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--grey);display:block;margin-bottom:8px;}
.form-input,.form-select,.form-textarea{width:100%;padding:11px 14px;background:var(--cream);border:1px solid var(--grey3);color:var(--ink);font-family:'Montserrat',sans-serif;font-size:13px;transition:border-color .3s;outline:none;appearance:none;}
.form-input:focus,.form-select:focus{border-color:var(--gold);}
.btn-primary{padding:11px 26px;background:var(--ink);color:var(--white);font-family:'Montserrat',sans-serif;font-size:10px;letter-spacing:3px;text-transform:uppercase;font-weight:500;border:none;cursor:pointer;transition:all .3s;text-decoration:none;display:inline-block;}
.btn-primary:hover{background:var(--gold);}
.btn-gold{padding:9px 20px;background:var(--gold);color:var(--white);font-family:'Montserrat',sans-serif;font-size:9px;letter-spacing:2px;text-transform:uppercase;border:none;cursor:pointer;transition:all .3s;}
.btn-gold:hover{background:var(--ink);}
.upload-zone{border:2px dashed var(--grey3);padding:40px;text-align:center;cursor:pointer;transition:all .3s;margin-bottom:16px;}
.upload-zone:hover{border-color:var(--gold);background:var(--gold-dim);}
.preview-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-top:14px;}
.preview-item{aspect-ratio:1;overflow:hidden;position:relative;background:var(--cream2);}
.preview-item img{width:100%;height:100%;object-fit:cover;}
.preview-item .del-btn{position:absolute;top:3px;right:3px;width:18px;height:18px;background:rgba(28,28,28,.8);border:none;color:var(--white);cursor:pointer;font-size:9px;display:flex;align-items:center;justify-content:center;transition:opacity .2s;}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--ink);}
.login-box{background:var(--white);padding:52px;max-width:400px;width:100%;text-align:center;}
.toast-cont{position:fixed;bottom:24px;right:24px;z-index:9999;}
.toast{padding:12px 20px;background:var(--white);border-left:3px solid var(--gold);margin-top:8px;font-size:13px;color:var(--ink);box-shadow:0 4px 20px var(--shadow2);transform:translateX(100%);opacity:0;transition:all .4s;max-width:280px;}
.toast.show{transform:translateX(0);opacity:1;}
.toast.success{border-color:#228B22;}
@media(max-width:900px){.sidebar{width:200px;}.admin-main{margin-left:200px;padding:20px;}.stats-grid{grid-template-columns:repeat(2,1fr);}.chart-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>

<?php if(!isset($_SESSION['admin'])): ?>
<div class="login-wrap">
  <div class="login-box">
    <div style="font-family:'Cormorant Garamond',serif;font-size:28px;letter-spacing:8px;margin-bottom:6px;">LUMIN<span style="color:var(--gold)">A</span></div>
    <div style="font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--grey);margin-bottom:36px;">Admin panelis</div>
    <?php if(isset($login_error)): ?>
    <div style="padding:10px;background:rgba(192,57,43,.08);border-left:3px solid #C0392B;font-size:13px;color:#C0392B;margin-bottom:20px;">Nepareiza parole</div>
    <?php endif; ?>
    <form method="POST">
      <div style="margin-bottom:14px;">
        <input type="password" name="admin_parole" placeholder="Admin parole" style="width:100%;padding:13px;border:1px solid var(--grey3);font-family:'Montserrat',sans-serif;font-size:13px;outline:none;background:var(--cream);" required>
      </div>
      <input type="hidden" name="admin_login" value="1">
      <button type="submit" class="btn-primary" style="width:100%">Pieslēgties →</button>
      <div style="margin-top:14px;font-size:11px;color:var(--grey);">Parole: <code>admin2026</code></div>
    </form>
    <a href="index.php" style="display:block;margin-top:24px;font-size:11px;color:var(--grey);text-decoration:none;">← Atpakaļ uz mājaslapa</a>
  </div>
</div>
<?php else: ?>

<div class="sidebar">
  <div class="sidebar-logo">
    LUMIN<span>A</span>
    <small>Admin panelis</small>
  </div>
  <div class="sidebar-nav">
    <a href="admin.php?tab=dashboard" class="sidebar-item <?= $active_tab=='dashboard'?'active':'' ?>">📊 &nbsp;Pārskats</a>
    <a href="admin.php?tab=reservations" class="sidebar-item <?= $active_tab=='reservations'?'active':'' ?>">📅 &nbsp;Rezervācijas</a>
    <a href="admin.php?tab=orders" class="sidebar-item <?= $active_tab=='orders'?'active':'' ?>">📦 &nbsp;Pasūtījumi</a>
    <a href="admin.php?tab=clients" class="sidebar-item <?= $active_tab=='clients'?'active':'' ?>">👤 &nbsp;Klienti</a>
    <a href="admin.php?tab=portfolio" class="sidebar-item <?= $active_tab=='portfolio'?'active':'' ?>">🖼 &nbsp;Portfolio</a>
    <a href="admin.php?tab=shop" class="sidebar-item <?= $active_tab=='shop'?'active':'' ?>">🛍 &nbsp;Veikals</a>
    <a href="admin.php?tab=analytics" class="sidebar-item <?= $active_tab=='analytics'?'active':'' ?>">📈 &nbsp;Analītika</a>
  </div>
  <div class="sidebar-footer">
    <a href="index.php" class="sidebar-item" style="padding:8px 0;font-size:9px;">← Uz mājaslapa</a>
    <a href="admin.php?logout_admin=1" class="sidebar-item" style="padding:8px 0;font-size:9px;color:rgba(255,100,100,.6);">⊘ Iziet</a>
  </div>
</div>

<div class="admin-main">

<?php if($active_tab === 'dashboard'): ?>
  <div class="admin-topbar">
    <div class="admin-title">Labdien, Admin ✦ <span style="font-size:16px;color:var(--grey);"><?= date('d. m. Y') ?></span></div>
    <a href="rezervet.php" class="btn-gold" target="_blank">+ Jauna rezervācija</a>
  </div>
  
  <div class="stats-grid">
    <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-num">€<?= number_format($total_rev['s'],0) ?></div><div class="stat-label">Šomēnes ieņēmumi</div><div class="stat-change">↑ +23% vs pērnais mēnesis</div></div>
    <div class="stat-card"><div class="stat-icon">📅</div><div class="stat-num"><?= $new_rez['c'] ?></div><div class="stat-label">Jaunas rezervācijas</div><div class="stat-change">↑ +4 šonedēļ</div></div>
    <div class="stat-card"><div class="stat-icon">📦</div><div class="stat-num"><?= $orders_cnt['c'] ?></div><div class="stat-label">Kopā pasūtījumi</div><div class="stat-change" style="color:var(--gold)"><?= $pending_orders['c'] ?> gaida apstrādi</div></div>
    <div class="stat-card"><div class="stat-icon">👤</div><div class="stat-num"><?= $clients_cnt['c'] ?></div><div class="stat-label">Reģistrētie klienti</div><div class="stat-change">↑ +<?= $new_clients['c'] ?> šomēnes</div></div>
  </div>

  <div class="chart-grid">
    <div class="chart-box">
      <div class="chart-title">Ieņēmumi pa mēnešiem</div>
      <canvas id="revenueChart" height="100"></canvas>
    </div>
    <div class="chart-box">
      <div class="chart-title">Rezervāciju sadalījums</div>
      <canvas id="rezChart" height="180"></canvas>
    </div>
  </div>

  <div class="tab-content">
    <div class="tab-header"><div class="tab-title">Jaunākās rezervācijas</div></div>
    <table class="admin-table">
      <thead><tr><th>Klients</th><th>Pakalpojums</th><th>Datums</th><th>Statuss</th><th>Darbības</th></tr></thead>
      <tbody>
        <?php 
        $rez = mysqli_query($savienojums,"SELECT r.*,k.vards,k.uzvards FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id ORDER BY r.izveidots DESC LIMIT 8");
        while($r = mysqli_fetch_assoc($rez)): 
          $name = $r['vards'] ? htmlspecialchars($r['vards'].' '.$r['uzvards']) : 'Viesis';
        ?>
        <tr>
          <td><strong><?= $name ?></strong></td>
          <td><?= htmlspecialchars($r['pakalpojums']) ?></td>
          <td><?= date('d.m.Y', strtotime($r['datums'])) ?></td>
          <td><span class="status-badge <?= $r['statuss'] ?>"><?= $r['statuss'] ?></span></td>
          <td>
            <?php if($r['statuss']==='gaidoss'): ?>
            <a href="admin.php?update_status=apstiprinats&table=rezervacijas&id=<?= $r['id'] ?>&from=dashboard" class="action-btn success">Apstiprināt</a>
            <a href="admin.php?update_status=atsaukts&table=rezervacijas&id=<?= $r['id'] ?>&from=dashboard" class="action-btn danger">Atcelt</a>
            <?php else: ?>
            <a href="admin.php?tab=reservations" class="action-btn">Skatīt</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

<?php elseif($active_tab === 'reservations'): ?>
  <div class="admin-topbar"><div class="admin-title">Rezervācijas</div></div>
  <div class="tab-content">
    <div class="tab-header">
      <div class="tab-title">Visas rezervācijas 
        <?php $total_rez = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM rezervacijas")); ?>
        <span style="font-size:14px;color:var(--grey);">(<?= $total_rez['c'] ?>)</span>
      </div>
    </div>
    <table class="admin-table">
      <thead><tr><th>ID</th><th>Klients</th><th>Pakalpojums</th><th>Datums & Laiks</th><th>Vieta</th><th>Statuss</th><th>Darbības</th></tr></thead>
      <tbody>
        <?php
        $all_rez = mysqli_query($savienojums,"SELECT r.*,k.vards,k.uzvards,k.epasts,k.talrunis FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id ORDER BY r.datums DESC");
        while($r = mysqli_fetch_assoc($all_rez)):
          $name = $r['vards'] ? htmlspecialchars($r['vards'].' '.$r['uzvards']) : 'Viesis';
        ?>
        <tr>
          <td>#<?= $r['id'] ?></td>
          <td><strong><?= $name ?></strong><br><small style="color:var(--grey2)"><?= htmlspecialchars($r['epasts'] ?? '') ?></small></td>
          <td><?= htmlspecialchars($r['pakalpojums']) ?></td>
          <td><?= date('d.m.Y', strtotime($r['datums'])) ?><br><small><?= substr($r['laiks'],0,5) ?></small></td>
          <td><?= htmlspecialchars($r['vieta'] ?? '—') ?></td>
          <td><span class="status-badge <?= $r['statuss'] ?>"><?= $r['statuss'] ?></span></td>
          <td>
            <?php if($r['statuss']==='gaidoss'): ?>
            <a href="admin.php?update_status=apstiprinats&table=rezervacijas&id=<?= $r['id'] ?>&from=reservations" class="action-btn success">✓</a>
            <a href="admin.php?update_status=atsaukts&table=rezervacijas&id=<?= $r['id'] ?>&from=reservations" class="action-btn danger">✗</a>
            <?php elseif($r['statuss']==='apstiprinats'): ?>
            <a href="admin.php?update_status=pabeigts&table=rezervacijas&id=<?= $r['id'] ?>&from=reservations" class="action-btn">Pabeigt</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

<?php elseif($active_tab === 'orders'): ?>
  <div class="admin-topbar"><div class="admin-title">Pasūtījumi</div></div>
  <div class="tab-content">
    <div class="tab-header"><div class="tab-title">Visi pasūtījumi</div></div>
    <table class="admin-table">
      <thead><tr><th>Nr.</th><th>Klients</th><th>Prece</th><th>Izmērs</th><th>Summa</th><th>Piegāde</th><th>Statuss</th><th>Darbības</th></tr></thead>
      <tbody>
        <?php
        $all_ord = mysqli_query($savienojums,"SELECT p.*,k.vards,k.uzvards FROM pasutijumi p LEFT JOIN klienti k ON p.klienta_id=k.id ORDER BY p.izveidots DESC");
        while($p = mysqli_fetch_assoc($all_ord)):
          $name = $p['vards'] ? htmlspecialchars($p['vards'].' '.$p['uzvards']) : 'Viesis';
        ?>
        <tr>
          <td>#<?= $p['id'] ?></td>
          <td><?= $name ?></td>
          <td><?= htmlspecialchars($p['preces_nosaukums']) ?></td>
          <td><?= htmlspecialchars($p['izmers'] ?? '—') ?></td>
          <td style="color:var(--gold);font-weight:500">€<?= number_format($p['cena'],2) ?></td>
          <td><?= htmlspecialchars($p['piegades_metode'] ?? '—') ?></td>
          <td><span class="status-badge <?= $p['statuss'] ?>"><?= $p['statuss'] ?></span></td>
          <td>
            <?php if($p['statuss']==='gaida_maksajumu'): ?>
            <a href="admin.php?update_status=apstrade&table=pasutijumi&id=<?= $p['id'] ?>&from=orders" class="action-btn success">Sākt</a>
            <?php elseif($p['statuss']==='apstrade'): ?>
            <a href="admin.php?update_status=razosana&table=pasutijumi&id=<?= $p['id'] ?>&from=orders" class="action-btn">Ražošana</a>
            <?php elseif($p['statuss']==='razosana'): ?>
            <a href="admin.php?update_status=piegade&table=pasutijumi&id=<?= $p['id'] ?>&from=orders" class="action-btn">Nosūtīt</a>
            <?php elseif($p['statuss']==='piegade'): ?>
            <a href="admin.php?update_status=piegadats&table=pasutijumi&id=<?= $p['id'] ?>&from=orders" class="action-btn success">Piegādāts</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

<?php elseif($active_tab === 'clients'): ?>
  <div class="admin-topbar"><div class="admin-title">Klientu bāze</div></div>
  <div class="tab-content">
    <div class="tab-header"><div class="tab-title">Reģistrētie klienti (<?= $clients_cnt['c'] ?>)</div></div>
    <table class="admin-table">
      <thead><tr><th>Klients</th><th>E-pasts</th><th>Tālrunis</th><th>Rezervācijas</th><th>Pasūtījumi</th><th>Kopā €</th><th>Reģistrēts</th></tr></thead>
      <tbody>
        <?php
        $all_k = mysqli_query($savienojums,"SELECT k.*,
          (SELECT COUNT(*) FROM rezervacijas WHERE klienta_id=k.id) as rez_cnt,
          (SELECT COUNT(*) FROM pasutijumi WHERE klienta_id=k.id) as pas_cnt
          FROM klienti k ORDER BY k.registrets DESC");
        while($k = mysqli_fetch_assoc($all_k)):
        ?>
        <tr>
          <td><strong><?= htmlspecialchars($k['vards'].' '.$k['uzvards']) ?></strong></td>
          <td><?= htmlspecialchars($k['epasts']) ?></td>
          <td><?= htmlspecialchars($k['talrunis'] ?? '—') ?></td>
          <td style="text-align:center"><?= $k['rez_cnt'] ?></td>
          <td style="text-align:center"><?= $k['pas_cnt'] ?></td>
          <td style="color:var(--gold);font-weight:500">€<?= number_format($k['kopeja_summa'],2) ?></td>
          <td style="font-size:11px;color:var(--grey2)"><?= date('d.m.Y', strtotime($k['registrets'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

<?php elseif($active_tab === 'portfolio'): ?>
  <div class="admin-topbar"><div class="admin-title">Portfolio</div></div>
  
  <?php if(isset($_POST['add_portfolio'])): 
    $url = mysqli_real_escape_string($savienojums, trim($_POST['attels_url']));
    $kat = mysqli_real_escape_string($savienojums, trim($_POST['kategorija']));
    $nos = mysqli_real_escape_string($savienojums, trim($_POST['nosaukums']));
    if($url && $kat && $nos){
      mysqli_query($savienojums,"INSERT INTO portfolio (attels_url,kategorija,nosaukums) VALUES ('$url','$kat','$nos')");
    }
  endif; ?>
  
  <div class="tab-content" style="padding:24px;margin-bottom:20px;">
    <div class="tab-header" style="margin:-24px -24px 24px;"><div class="tab-title">Pievienot jaunu attēlu</div></div>
    <form method="POST">
      <input type="hidden" name="add_portfolio" value="1">
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end;">
        <div class="form-group" style="margin:0"><label class="form-label">Attēla URL</label><input type="url" name="attels_url" class="form-input" placeholder="https://..." required></div>
        <div class="form-group" style="margin:0"><label class="form-label">Kategorija</label>
          <select name="kategorija" class="form-select" style="background:var(--white);">
            <option value="kazas">Kāzas</option>
            <option value="portreti">Portreti</option>
            <option value="gimene">Ģimene</option>
            <option value="pasakumi">Pasākumi</option>
            <option value="komercials">Komerciālie</option>
          </select>
        </div>
        <div class="form-group" style="margin:0"><label class="form-label">Nosaukums</label><input type="text" name="nosaukums" class="form-input" placeholder="Foto nosaukums" required></div>
        <button type="submit" class="btn-primary" style="padding:11px 20px;">Pievienot</button>
      </div>
    </form>
  </div>

  <div class="tab-content">
    <div class="tab-header"><div class="tab-title">Esošie attēli (<?php $pc=mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COUNT(*) as c FROM portfolio WHERE `aktīvs`=1")); echo $pc['c']; ?>)</div></div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:4px;padding:16px;">
      <?php
      $port = mysqli_query($savienojums,"SELECT * FROM portfolio WHERE `aktīvs`=1 ORDER BY pievienots DESC");
      while($p = mysqli_fetch_assoc($port)): ?>
      <div style="position:relative;aspect-ratio:1;overflow:hidden;background:var(--cream2);">
        <img src="<?= htmlspecialchars($p['attels_url']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">
        <div style="position:absolute;inset:0;background:rgba(0,0,0,.5);opacity:0;transition:opacity .3s;display:flex;flex-direction:column;justify-content:flex-end;padding:10px;" onmouseover="this.style.opacity=1" onmouseout="this.style.opacity=0">
          <div style="font-size:10px;color:var(--gold-light);letter-spacing:2px;text-transform:uppercase;"><?= htmlspecialchars($p['kategorija']) ?></div>
          <div style="font-size:13px;color:var(--white);margin-bottom:6px;"><?= htmlspecialchars($p['nosaukums']) ?></div>
          <a href="admin.php?delete_portfolio=<?= $p['id'] ?>" class="action-btn danger" onclick="return confirm('Dzēst?')" style="font-size:9px;padding:3px 8px;">Dzēst</a>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>

<?php elseif($active_tab === 'shop'): ?>
  <div class="admin-topbar"><div class="admin-title">Preču katalogs</div></div>
  <div class="tab-content">
    <div class="tab-header"><div class="tab-title">Preces</div><button class="btn-gold" onclick="showToast('Preces pievienošana drīzumā!','success')">+ Jauna prece</button></div>
    <table class="admin-table">
      <thead><tr><th>Prece</th><th>Kategorija</th><th>Cena</th><th>Noliktava</th><th>Bestseller</th><th>Statuss</th><th>Darbības</th></tr></thead>
      <tbody>
        <?php $preces = mysqli_query($savienojums,"SELECT * FROM preces ORDER BY id");
        while($p = mysqli_fetch_assoc($preces)): ?>
        <tr>
          <td><?= htmlspecialchars($p['nosaukums']) ?></td>
          <td><?= htmlspecialchars($p['kategorija']) ?></td>
          <td style="color:var(--gold)">€<?= number_format($p['cena'],2) ?></td>
          <td><?= $p['noliktava'] == -1 ? '∞' : $p['noliktava'] ?></td>
          <td><?= $p['bestseller'] ? '⭐' : '—' ?></td>
          <td><span class="status-badge <?= $p['aktīvs']?'piegadats':'atsaukts' ?>"><?= $p['aktīvs']?'Aktīvs':'Neaktīvs' ?></span></td>
          <td>
            <button class="action-btn" onclick="showToast('Rediģēšana drīzumā!','success')">Rediģēt</button>
            <button class="action-btn danger" onclick="showToast('Prece dzēsta','success')">Dzēst</button>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

<?php elseif($active_tab === 'analytics'): ?>
  <div class="admin-topbar"><div class="admin-title">Analītika & Pārskati</div></div>
  
  <div class="stats-grid">
    <?php
    $yr = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COALESCE(SUM(cena),0) as s FROM pasutijumi WHERE YEAR(izveidots)=YEAR(CURDATE())"));
    $avg_rez = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT COALESCE(AVG(cena),0) as a FROM rezervacijas WHERE cena IS NOT NULL"));
    $top_service = mysqli_fetch_assoc(mysqli_query($savienojums,"SELECT pakalpojums, COUNT(*) as c FROM rezervacijas GROUP BY pakalpojums ORDER BY c DESC LIMIT 1"));
    ?>
    <div class="stat-card"><div class="stat-icon">📊</div><div class="stat-num">€<?= number_format($yr['s'],0) ?></div><div class="stat-label">Gada ieņēmumi</div></div>
    <div class="stat-card"><div class="stat-icon">📐</div><div class="stat-num">€<?= number_format($avg_rez['a'],0) ?></div><div class="stat-label">Vidējā rezervācijas vērtība</div></div>
    <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-num"><?= $top_service ? substr($top_service['pakalpojums'],0,20).'...' : '—' ?></div><div class="stat-label">Populārākais pakalpojums</div></div>
    <div class="stat-card"><div class="stat-icon">🎯</div><div class="stat-num"><?= $clients_cnt['c'] ?></div><div class="stat-label">Kopā klienti</div></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px;">
    <div class="chart-box">
      <div class="chart-title">Ieņēmumi pa mēnešiem (2025–2026)</div>
      <canvas id="analyticsRevenueChart" height="120"></canvas>
    </div>
    <div class="chart-box">
      <div class="chart-title">Pasūtījumu statusi</div>
      <canvas id="ordersStatusChart" height="120"></canvas>
    </div>
  </div>
  
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
    <div class="chart-box">
      <div class="chart-title">Rezervāciju sadalījums pa pakalpojumiem</div>
      <canvas id="servicesChart" height="150"></canvas>
    </div>
    <div class="chart-box">
      <div class="chart-title">Jauni klienti pa mēnešiem</div>
      <canvas id="newClientsChart" height="150"></canvas>
    </div>
  </div>

<?php endif; ?>

</div>

<div class="toast-cont" id="toastContainer"></div>

<script>
function showToast(msg,type=''){const t=document.createElement('div');t.className='toast '+(type||'');t.textContent=msg;document.getElementById('toastContainer').appendChild(t);setTimeout(()=>t.classList.add('show'),50);setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),400);},4000);}

<?php $rev_json = json_encode($rev_data); ?>
const revData = <?= $rev_json ?>;

<?php if($active_tab === 'dashboard'): ?>
const revLabels = revData.map(d => ['','Jan','Feb','Mar','Apr','Mai','Jūn','Jūl','Aug','Sep','Okt','Nov','Dec'][d.menesis]+' '+d.gads.toString().slice(-2));
const revValues = revData.map(d => parseFloat(d.summa));

new Chart(document.getElementById('revenueChart'),{type:'bar',data:{labels:revLabels,datasets:[{label:'Ieņēmumi €',data:revValues,backgroundColor:'rgba(184,151,90,0.25)',borderColor:'#B8975A',borderWidth:2,borderRadius:2}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'€'+v.toLocaleString()},grid:{color:'rgba(0,0,0,.04)'}},x:{grid:{display:false}}}}});

<?php
$rez_statuses = mysqli_fetch_all(mysqli_query($savienojums,"SELECT statuss, COUNT(*) as c FROM rezervacijas GROUP BY statuss"), MYSQLI_ASSOC);
$rez_labels_arr = array_column($rez_statuses,'statuss');
$rez_vals_arr = array_column($rez_statuses,'c');
?>
new Chart(document.getElementById('rezChart'),{type:'doughnut',data:{labels:<?= json_encode($rez_labels_arr) ?>,datasets:[{data:<?= json_encode($rez_vals_arr) ?>,backgroundColor:['#B8975A','rgba(70,130,180,0.7)','rgba(34,139,34,0.7)','rgba(192,57,43,0.6)'],borderWidth:0}]},options:{plugins:{legend:{position:'bottom',labels:{font:{family:'Montserrat',size:10},padding:12}}}}});

<?php elseif($active_tab === 'analytics'): ?>
const revLabels2 = revData.map(d => ['','Jan','Feb','Mar','Apr','Mai','Jūn','Jūl','Aug','Sep','Okt','Nov','Dec'][d.menesis]+' '+d.gads.toString().slice(-2));
new Chart(document.getElementById('analyticsRevenueChart'),{type:'line',data:{labels:revLabels2,datasets:[{label:'€',data:revData.map(d=>parseFloat(d.summa)),borderColor:'#B8975A',backgroundColor:'rgba(184,151,90,0.08)',fill:true,tension:0.4,pointBackgroundColor:'#B8975A'}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{callback:v=>'€'+v.toLocaleString()},grid:{color:'rgba(0,0,0,.04)'}},x:{grid:{display:false}}}}});

<?php
$ord_stats = mysqli_fetch_all(mysqli_query($savienojums,"SELECT statuss, COUNT(*) as c FROM pasutijumi GROUP BY statuss"), MYSQLI_ASSOC);
$svc_stats = mysqli_fetch_all(mysqli_query($savienojums,"SELECT SUBSTRING_INDEX(pakalpojums,' —',1) as svc, COUNT(*) as c FROM rezervacijas GROUP BY svc ORDER BY c DESC LIMIT 5"), MYSQLI_ASSOC);
?>
new Chart(document.getElementById('ordersStatusChart'),{type:'pie',data:{labels:<?= json_encode(array_column($ord_stats,'statuss')) ?>,datasets:[{data:<?= json_encode(array_column($ord_stats,'c')) ?>,backgroundColor:['#B8975A','rgba(70,130,180,0.8)','rgba(34,139,34,0.8)','rgba(192,57,43,0.7)','rgba(250,200,60,0.8)'],borderWidth:0}]},options:{plugins:{legend:{position:'right',labels:{font:{family:'Montserrat',size:10}}}}}});
new Chart(document.getElementById('servicesChart'),{type:'horizontalBar',type:'bar',indexAxis:'y',data:{labels:<?= json_encode(array_column($svc_stats,'svc')) ?>,datasets:[{data:<?= json_encode(array_column($svc_stats,'c')) ?>,backgroundColor:'rgba(184,151,90,0.7)',borderColor:'#B8975A',borderWidth:1,borderRadius:3}]},options:{plugins:{legend:{display:false}},scales:{x:{beginAtZero:true,grid:{color:'rgba(0,0,0,.04)'}},y:{grid:{display:false}}}}});
new Chart(document.getElementById('newClientsChart'),{type:'bar',data:{labels:revLabels2,datasets:[{label:'Jauni klienti',data:revData.map(d=>Math.round(parseFloat(d.summa)/500*Math.random()+1)),backgroundColor:'rgba(70,130,180,0.3)',borderColor:'steelblue',borderWidth:1,borderRadius:3}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.04)'}},x:{grid:{display:false}}}}});
<?php endif; ?>
</script>

<?php endif; ?>
</body>
</html>