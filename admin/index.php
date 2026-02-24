<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Pārskats';

$totalRez    = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n, COALESCE(SUM(cena),0) s FROM rezervacijas WHERE statuss!='atcelts'"));
$monthRez    = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n, COALESCE(SUM(cena),0) s FROM rezervacijas WHERE MONTH(datums)=MONTH(NOW()) AND YEAR(datums)=YEAR(NOW()) AND statuss!='atcelts'"));
$waitingRez  = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n FROM rezervacijas WHERE statuss='gaida'"));
$nextWeekRez = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n FROM rezervacijas WHERE datums BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND statuss!='atcelts'"));
$klienti     = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n FROM klienti"));
$newKlientiR = @mysqli_query($savienojums, "SELECT COUNT(*) n FROM klienti WHERE DATE(izveidots) >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)");
$newKlienti  = $newKlientiR ? mysqli_fetch_assoc($newKlientiR) : ['n' => '?'];
$galerijas   = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n FROM galerijas"));
$totalFoto   = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n FROM galeriju_foto"));
$portfolio   = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT COUNT(*) n FROM portfolio WHERE aktivs=1"));

$monthlyRevenue = []; $monthlyCount = []; $monthLabels = [];
for ($m = 11; $m >= 0; $m--) {
  $row = mysqli_fetch_assoc(mysqli_query($savienojums,
    "SELECT COALESCE(SUM(cena),0) rev, COUNT(*) cnt,
     DATE_FORMAT(DATE_SUB(NOW(),INTERVAL $m MONTH),'%b') lbl
     FROM rezervacijas
     WHERE DATE_FORMAT(datums,'%Y-%m')=DATE_FORMAT(DATE_SUB(NOW(),INTERVAL $m MONTH),'%Y-%m')
     AND statuss!='atcelts'"));
  $monthlyRevenue[] = (float)$row['rev'];
  $monthlyCount[]   = (int)$row['cnt'];
  $monthLabels[]    = $row['lbl'];
}

$services = [];
$sRes = mysqli_query($savienojums, "SELECT pakalpojums, COUNT(*) cnt, COALESCE(SUM(cena),0) rev FROM rezervacijas WHERE statuss!='atcelts' GROUP BY pakalpojums ORDER BY cnt DESC LIMIT 6");
while ($r = mysqli_fetch_assoc($sRes)) $services[] = $r;

$statusCounts = ['gaida'=>0,'apstiprinats'=>0,'pabeigts'=>0,'atcelts'=>0];
$stRes = mysqli_query($savienojums, "SELECT statuss, COUNT(*) n FROM rezervacijas GROUP BY statuss");
while ($r = mysqli_fetch_assoc($stRes)) if (isset($statusCounts[$r['statuss']])) $statusCounts[$r['statuss']] = (int)$r['n'];

$rezervacijas = [];
$rRes = mysqli_query($savienojums, "SELECT r.*, k.vards, k.uzvards, k.epasts, k.talrunis FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id ORDER BY r.id DESC LIMIT 10");
while ($r = mysqli_fetch_assoc($rRes)) $rezervacijas[] = $r;

$upcoming = [];
$uRes = mysqli_query($savienojums, "SELECT r.*, k.vards, k.uzvards, k.talrunis FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id WHERE r.datums BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND r.statuss!='atcelts' ORDER BY r.datums ASC, r.laiks ASC LIMIT 8");
while ($r = mysqli_fetch_assoc($uRes)) $upcoming[] = $r;

include __DIR__ . '/includes/header.php';
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.kpi{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:22px 24px;position:relative;overflow:hidden;transition:.2s;}
.kpi:hover{box-shadow:0 4px 20px rgba(0,0,0,.08);transform:translateY(-2px);}
.kpi::after{content:'';position:absolute;bottom:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--gold),transparent);}
.kpi-val{font-family:'Cormorant Garamond',serif;font-size:40px;font-weight:400;color:var(--ink);line-height:1;}
.kpi-label{font-size:9px;letter-spacing:2.5px;text-transform:uppercase;color:var(--grey2);margin-top:6px;}
.kpi-sub{font-size:11px;color:var(--gold);margin-top:4px;}
.kpi-badge{position:absolute;top:14px;right:14px;background:var(--gold);color:#fff;border-radius:20px;padding:3px 10px;font-size:10px;font-weight:700;}
.kpi-badge.red{background:#e53935;}
.charts-grid{display:grid;grid-template-columns:3fr 1.2fr;gap:20px;margin-bottom:28px;}
.chart-card{background:var(--white);border:1px solid var(--border);border-radius:10px;padding:22px 24px;}
.chart-title{font-size:10px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;color:var(--grey2);margin-bottom:18px;}
.charts-row2{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:28px;}
.sp{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;white-space:nowrap;}
.sp::before{content:'';width:5px;height:5px;border-radius:50%;flex-shrink:0;}
.sp-gaida{background:#fff3e0;color:#e65100;}.sp-gaida::before{background:#e65100;}
.sp-apstiprinats{background:#e8f5e9;color:#2e7d32;}.sp-apstiprinats::before{background:#2e7d32;}
.sp-pabeigts{background:#e3f2fd;color:#1565c0;}.sp-pabeigts::before{background:#1565c0;}
.sp-atcelts{background:#fce4ec;color:#c62828;}.sp-atcelts::before{background:#c62828;}
.ab{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;font-size:10px;font-family:'Montserrat',sans-serif;font-weight:600;letter-spacing:.3px;border:1.5px solid;border-radius:4px;cursor:pointer;text-decoration:none;white-space:nowrap;transition:.15s;}
.ab-confirm{background:#f1f8f1;color:#2e7d32;border-color:#a5d6a7;}.ab-confirm:hover{background:#2e7d32;color:#fff;border-color:#2e7d32;}
.ab-done{background:#e8f4fd;color:#0d47a1;border-color:#90caf9;}.ab-done:hover{background:#0d47a1;color:#fff;border-color:#0d47a1;}
.ab-cancel{background:#fef0f0;color:#b71c1c;border-color:#ef9a9a;}.ab-cancel:hover{background:#b71c1c;color:#fff;border-color:#b71c1c;}
.ab-del{background:#f5f5f5;color:#666;border-color:#ddd;}.ab-del:hover{background:#444;color:#fff;border-color:#444;}
.week-item{display:flex;align-items:center;gap:10px;padding:11px 0;border-bottom:1px solid var(--border);}
@media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(2,1fr);}.charts-grid,.charts-row2{grid-template-columns:1fr;}}
</style>

<!-- KPI -->
<div class="kpi-grid">
  <div class="kpi">
    <div class="kpi-val">€<?= number_format($totalRez['s'],0) ?></div>
    <div class="kpi-label">Kopējie ieņēmumi</div>
    <div class="kpi-sub">+€<?= number_format($monthRez['s'],0) ?> šomēnes</div>
  </div>
  <div class="kpi">
    <div class="kpi-val"><?= $totalRez['n'] ?></div>
    <div class="kpi-label">Kopā rezervācijas</div>
    <div class="kpi-sub"><?= $monthRez['n'] ?> šomēnes · <?= $nextWeekRez['n'] ?> šonedēļ</div>
    <?php if ($waitingRez['n'] > 0): ?><div class="kpi-badge red"><?= $waitingRez['n'] ?> gaida</div><?php endif; ?>
  </div>
  <div class="kpi">
    <div class="kpi-val"><?= $klienti['n'] ?></div>
    <div class="kpi-label">Reģistrētie klienti</div>
    <div class="kpi-sub">+<?= $newKlienti['n'] ?> pēdējās 30 dienās</div>
  </div>
  <div class="kpi">
    <div class="kpi-val"><?= $galerijas['n'] ?></div>
    <div class="kpi-label">Klientu galerijas</div>
    <div class="kpi-sub"><?= $totalFoto['n'] ?> foto · <?= $portfolio['n'] ?> portfolio</div>
  </div>
</div>

<!-- Charts row 1 -->
<div class="charts-grid">
  <div class="chart-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <div class="chart-title" style="margin-bottom:0;">Ieņēmumi & rezervācijas — 12 mēneši</div>
      <div style="display:flex;gap:14px;font-size:10px;color:var(--grey2);">
        <span><span style="display:inline-block;width:10px;height:10px;background:#B8975A;border-radius:2px;margin-right:4px;vertical-align:middle;"></span>€ ieņēmumi</span>
        <span><span style="display:inline-block;width:10px;height:2px;background:rgba(184,151,90,.5);margin-right:4px;vertical-align:middle;"></span>Rezervācijas</span>
      </div>
    </div>
    <canvas id="revenueChart" height="95"></canvas>
  </div>
  <div class="chart-card">
    <div class="chart-title">Statusi</div>
    <div style="display:flex;justify-content:center;margin-bottom:14px;"><canvas id="statusChart" style="max-height:180px;max-width:180px;"></canvas></div>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php
      $stInfo=[['gaida','Gaida','#e65100'],['apstiprinats','Apstiprinātas','#2e7d32'],['pabeigts','Pabeigtas','#1565c0'],['atcelts','Atceltas','#c62828']];
      foreach ($stInfo as [$k,$lbl,$col]):
        $c=$statusCounts[$k]??0;
        $tot=max(array_sum($statusCounts),1);
        $pct=$tot>0?round($c/$tot*100):0;
      ?>
      <div style="display:flex;align-items:center;gap:8px;font-size:12px;">
        <div style="width:8px;height:8px;border-radius:50%;background:<?=$col?>;flex-shrink:0;"></div>
        <span style="flex:1;color:var(--grey2);font-size:11px;"><?=$lbl?></span>
        <b><?=$c?></b><span style="color:var(--grey2);font-size:10px;"><?=$pct?>%</span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Charts row 2 -->
<div class="charts-row2">
  <div class="chart-card">
    <div class="chart-title">Pakalpojumu pieprasījums</div>
    <?php if (!empty($services)): ?>
    <canvas id="serviceChart" height="150"></canvas>
    <?php else: ?>
    <div style="text-align:center;padding:30px;color:var(--grey2);">Nav datu</div>
    <?php endif; ?>
  </div>
  <div class="chart-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
      <div class="chart-title" style="margin-bottom:0;">Šīs nedēļas sesijas</div>
      <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?tab=calendar" style="font-size:10px;color:var(--gold);text-decoration:none;">Kalendārs →</a>
    </div>
    <?php if (empty($upcoming)): ?>
    <div style="text-align:center;padding:30px;color:var(--grey2);font-size:12px;">Šonedēļ nav sesiju</div>
    <?php else: foreach ($upcoming as $u):
      $tod=$u['datums']===date('Y-m-d');
    ?>
    <div class="week-item">
      <div style="width:9px;height:9px;border-radius:50%;flex-shrink:0;background:<?=$tod?'var(--gold)':($u['statuss']==='apstiprinats'?'#2e7d32':'#ccc')?>;<?=$tod?'box-shadow:0 0 0 3px rgba(184,151,90,.2);':''?>"></div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:12px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars($u['vards'].' '.$u['uzvards'])?></div>
        <div style="font-size:10px;color:var(--grey2);"><?=$tod?'<b style="color:var(--gold)">ŠODIEN</b>':date('d.m (D)',strtotime($u['datums']))?> · <?=substr($u['laiks'],0,5)?>
          <span style="margin-left:4px;"><?=htmlspecialchars(preg_replace('/\s*—.*$/','',$u['pakalpojums']))?></span>
        </div>
      </div>
      <span class="sp sp-<?=$u['statuss']?>" style="font-size:8px;"><?=substr($u['statuss'],0,5)?></span>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<!-- Reservations table -->
<div class="admin-card" style="padding:0;overflow:hidden;">
  <div class="section-header" style="padding:18px 22px;">
    <div class="section-heading">Rezervācijas — pēdējās 10</div>
    <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php" class="action-btn">Skatīt visas →</a>
  </div>
  <table class="admin-table">
    <thead><tr><th>Klients</th><th>Pakalpojums</th><th>Datums</th><th>€</th><th>Statuss</th><th style="min-width:280px;">Darbības</th></tr></thead>
    <tbody>
      <?php foreach ($rezervacijas as $r): $sl=$r['statuss']??'gaida'; ?>
      <tr>
        <td>
          <b style="font-size:13px;"><?=$r['vards']?htmlspecialchars($r['vards'].' '.$r['uzvards']):'<em style="color:var(--grey2)">Viesis</em>'?></b>
          <?php if($r['epasts']):?><div style="font-size:10px;color:var(--grey2);"><?=htmlspecialchars($r['epasts'])?></div><?php endif;?>
        </td>
        <td style="font-size:11px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?=htmlspecialchars(preg_replace('/\s*—.*$/','',$r['pakalpojums']))?></td>
        <td style="font-size:11px;white-space:nowrap;"><?=date('d.m.Y',strtotime($r['datums']))?><br><span style="color:var(--grey2);"><?=substr($r['laiks'],0,5)?></span></td>
        <td style="color:var(--gold);font-weight:600;"><?=$r['cena']?'€'.number_format($r['cena'],0):'—'?></td>
        <td><span class="sp sp-<?=$sl?>"><?=ucfirst($sl)?></span></td>
        <td>
          <div style="display:flex;gap:5px;flex-wrap:wrap;align-items:center;">
            <?php if($sl==='gaida'):?>
            <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?status=apstiprinats&id=<?=$r['id']?>&tab=rezervacijas"
               class="ab ab-confirm" title="Apstiprināt un nosūtīt e-pastu klientam">✓ Apstiprināt</a>
            <?php elseif($sl==='apstiprinats'):?>
            <span class="ab" style="background:#e8f5e9;color:#2e7d32;border-color:#c8e6c9;cursor:default;opacity:.7;">✓ Apstiprin.</span>
            <?php endif;?>
            <?php if($sl!=='pabeigts'&&$sl!=='atcelts'):?>
            <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?status=pabeigts&id=<?=$r['id']?>&tab=rezervacijas"
               class="ab ab-done" title="Sesija ir pabeigta">✔ Pabeigts</a>
            <?php endif;?>
            <?php if($sl!=='atcelts'):?>
            <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?status=atcelts&id=<?=$r['id']?>&tab=rezervacijas"
               onclick="return confirm('Atcelt un informēt klientu?')"
               class="ab ab-cancel" title="Atcelt rezervāciju">✕ Atcelt</a>
            <?php endif;?>
            <a href="/4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?delete=<?=$r['id']?>&tab=rezervacijas"
               onclick="return confirm('Dzēst rezervāciju #<?=$r['id']?>?')"
               class="ab ab-del" title="Dzēst">🗑</a>
          </div>
        </td>
      </tr>
      <?php endforeach;?>
      <?php if(empty($rezervacijas)):?><tr><td colspan="6" style="text-align:center;padding:36px;color:var(--grey2);">Nav rezervāciju</td></tr><?php endif;?>
    </tbody>
  </table>
</div>

<script>
// Revenue chart
new Chart(document.getElementById('revenueChart'),{
  data:{
    labels:<?=json_encode($monthLabels)?>,
    datasets:[
      {type:'bar',label:'Ieņēmumi €',data:<?=json_encode($monthlyRevenue)?>,
       backgroundColor:<?=json_encode(array_map(fn($i)=>$i==count($monthlyRevenue)-1?'#B8975A':'rgba(184,151,90,0.28)',range(0,count($monthlyRevenue)-1)))?>,
       borderRadius:4,yAxisID:'y'},
      {type:'line',label:'Rezervācijas',data:<?=json_encode($monthlyCount)?>,
       borderColor:'rgba(184,151,90,0.6)',backgroundColor:'transparent',borderWidth:2,
       pointBackgroundColor:'#B8975A',pointRadius:4,tension:0.4,yAxisID:'y1'}
    ]
  },
  options:{responsive:true,interaction:{mode:'index',intersect:false},
    plugins:{legend:{display:false},
      tooltip:{callbacks:{label:ctx=>ctx.datasetIndex===0?` €${ctx.parsed.y.toLocaleString('lv')}`:`${ctx.parsed.y} rez.`}}},
    scales:{
      x:{grid:{display:false},ticks:{font:{size:10},color:'#888'}},
      y:{type:'linear',position:'left',grid:{color:'rgba(0,0,0,.05)'},ticks:{font:{size:10},color:'#888',callback:v=>'€'+v.toLocaleString('lv')}},
      y1:{type:'linear',position:'right',grid:{display:false},ticks:{font:{size:10},color:'#888',stepSize:1}}
    }}
});
// Status donut
new Chart(document.getElementById('statusChart'),{
  type:'doughnut',
  data:{labels:['Gaida','Apstiprināts','Pabeigts','Atcelts'],
    datasets:[{data:[<?=$statusCounts['gaida']?>,<?=$statusCounts['apstiprinats']?>,<?=$statusCounts['pabeigts']?>,<?=$statusCounts['atcelts']?>],
      backgroundColor:['#e65100','#2e7d32','#1565c0','#c62828'],borderWidth:3,borderColor:'#fff',hoverOffset:8}]},
  options:{cutout:'68%',responsive:true,plugins:{legend:{display:false}}}
});
<?php if(!empty($services)):?>
// Services chart
new Chart(document.getElementById('serviceChart'),{
  type:'bar',
  data:{labels:<?=json_encode(array_map(fn($s)=>preg_replace('/\s*—.*$/','',$s['pakalpojums']),$services))?>,
    datasets:[
      {label:'Rez. skaits',data:<?=json_encode(array_column($services,'cnt'))?>,backgroundColor:'rgba(184,151,90,0.75)',borderRadius:3},
      {label:'Ieņēmumi (x100€)',data:<?=json_encode(array_map(fn($s)=>round($s['rev']/100),$services))?>,backgroundColor:'rgba(184,151,90,0.2)',borderRadius:3}
    ]},
  options:{indexAxis:'y',responsive:true,
    plugins:{legend:{labels:{font:{size:10},color:'#888',boxWidth:10}}},
    scales:{x:{grid:{color:'rgba(0,0,0,.04)'},ticks:{font:{size:9},color:'#888'}},y:{grid:{display:false},ticks:{font:{size:10},color:'#555'}}}}
});
<?php endif;?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
