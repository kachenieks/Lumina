<?php
session_name('lumina_admin');
session_start();
require_once __DIR__ . '/../includes/db.php';
if (!isset($_SESSION['admin_auth'])) { header('Location: /4pt/blazkova/lumina/Lumina/admin/login.php'); exit; }
$pageTitle = 'Rezervācijas & Kalendārs';

$ADMIN_EMAIL = 'blazkova@example.com'; // ← NOMAINIET uz jūsu e-pastu!

// Status update
if (isset($_GET['status'])) {
  $id = (int)$_GET['id'];
  $status = escape($savienojums, $_GET['status']);
  mysqli_query($savienojums, "UPDATE rezervacijas SET statuss='$status' WHERE id=$id");
  $rez = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT r.*, k.epasts, k.vards, k.talrunis FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id WHERE r.id=$id"));
  // Determine recipient — registered client or viesis
  $recipEmail = ($rez['epasts'] ?? '') ?: ($rez['viesis_epasts'] ?? '');
  $recipVards = ($rez['vards'] ?? '') ?: ($rez['viesis_vards'] ?? 'Viesis');
  if ($rez && $recipEmail) {
    try { require_once __DIR__ . '/../includes/mailer.php'; mailRezervacijaStatuss($recipEmail, $recipVards, $rez, $status); } catch(\Throwable $e) { error_log('Mail err: '.$e->getMessage()); }
  }
  header('Location: /4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?msg=updated'); exit;
}

// Create gallery from reservation
if (isset($_GET['create_gallery'])) {
  $rezId = (int)$_GET['create_gallery'];
  $rez = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT r.*, k.id as kid, k.vards, k.uzvards, k.epasts FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id WHERE r.id=$rezId"));
  if ($rez) {
    $nosaukums = escape($savienojums, ($rez['vards'] ? $rez['vards'].' '.$rez['uzvards'] : ($rez['viesis_vards'] ?? 'Viesis')) . ' — ' . date('d.m.Y', strtotime($rez['datums'])));
    $deriga    = date('Y-m-d', strtotime('+90 days'));
    $klientaId = (int)($rez['kid'] ?? 0);

    // Generate access code for guests
    $kods = $klientaId ? null : strtoupper(substr(md5(uniqid()), 0, 8));
    $kodsEsc = $kods ? "'$kods'" : 'NULL';

    // Viesis email
    $vEmail = escape($savienojums, $rez['epasts'] ?: ($rez['viesis_epasts'] ?? ''));
    $vEmailVal = $vEmail ? "'$vEmail'" : 'NULL';

    mysqli_query($savienojums, "INSERT INTO galerijas (klienta_id, nosaukums, foto_skaits, deriga_lidz, izveidota, piekluves_kods, viesis_epasts) VALUES ($klientaId,'$nosaukums',0,'$deriga',NOW(),$kodsEsc,$vEmailVal)");
    $newGalId = mysqli_insert_id($savienojums);

    // Send access code to viesis by email
    if ($kods && $vEmail) {
      try {
        require_once __DIR__ . '/../includes/mailer.php';
        if (function_exists('mailGalerijaViesis')) {
          mailGalerijaViesis($vEmail, $rez['viesis_vards'] ?? 'Klients', $nosaukums, $kods);
        }
      } catch(\Throwable $e) { error_log('Gallery mail err: '.$e->getMessage()); }
    }

    header('Location: /4pt/blazkova/lumina/Lumina/admin/galerijas.php?view='.$newGalId.'&msg=saved'); exit;
  }
}

// Delete
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  mysqli_query($savienojums, "DELETE FROM rezervacijas WHERE id=$id");
  header('Location: /4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?msg=deleted'); exit;
}

// ── AVAILABILITY (pieejamība) ─────────────────────────────
// Save availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_availability'])) {
  $datums = escape($savienojums, $_POST['datums'] ?? '');
  $pieejams = isset($_POST['pieejams']) ? 1 : 0;
  $laiks_no = escape($savienojums, $_POST['laiks_no'] ?? '09:00');
  $laiks_lidz = escape($savienojums, $_POST['laiks_lidz'] ?? '18:00');
  $piezime = escape($savienojums, $_POST['piezime'] ?? '');
  if ($datums) {
    $sql = "INSERT INTO pieejamiba (datums, pieejams, laiks_no, laiks_lidz, piezime) VALUES ('$datums',$pieejams,'$laiks_no','$laiks_lidz','$piezime')
            ON DUPLICATE KEY UPDATE pieejams=$pieejams, laiks_no='$laiks_no', laiks_lidz='$laiks_lidz', piezime='$piezime'";
    mysqli_query($savienojums, $sql);
  }
  header('Location: /4pt/blazkova/lumina/Lumina/admin/rezervacijas.php?tab=calendar&msg=saved'); exit;
}

// Fetch reservations
$filter = isset($_GET['filter']) ? escape($savienojums, $_GET['filter']) : '';
$where = $filter ? "WHERE r.statuss='$filter'" : '';
$result = mysqli_query($savienojums, "SELECT r.*, k.vards, k.uzvards, k.epasts, k.talrunis FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id $where ORDER BY r.datums DESC");
$items = [];
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

// Fetch availability for next 60 days
$avail = [];
$avRes = mysqli_query($savienojums, "SELECT * FROM pieejamiba WHERE datums >= CURDATE() ORDER BY datums");
if ($avRes) while ($a = mysqli_fetch_assoc($avRes)) $avail[$a['datums']] = $a;

// Booked dates
$booked = [];
$bRes = mysqli_query($savienojums, "SELECT datums, COUNT(*) as cnt FROM rezervacijas WHERE statuss != 'atcelts' AND datums >= CURDATE() GROUP BY datums");
while ($b = mysqli_fetch_assoc($bRes)) $booked[$b['datums']] = $b['cnt'];

$tab = $_GET['tab'] ?? 'rezervacijas';
include __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success"><?= ['updated'=>'Statuss atjaunināts.','deleted'=>'Dzēsts.','saved'=>'Pieejamība saglabāta!'][$_GET['msg']] ?? 'Saglabāts.' ?></div>
<?php endif; ?>

<!-- Tabs -->
<div style="display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid var(--border);">
  <a href="?tab=rezervacijas" style="padding:10px 22px;font-size:13px;font-weight:500;text-decoration:none;border-bottom:<?= $tab==='rezervacijas'?'2px solid var(--gold)':'2px solid transparent' ?>;color:<?= $tab==='rezervacijas'?'var(--gold)':'var(--grey2)' ?>;margin-bottom:-2px;">Rezervācijas (<?= count($items) ?>)</a>
  <a href="?tab=calendar" style="padding:10px 22px;font-size:13px;font-weight:500;text-decoration:none;border-bottom:<?= $tab==='calendar'?'2px solid var(--gold)':'2px solid transparent' ?>;color:<?= $tab==='calendar'?'var(--gold)':'var(--grey2)' ?>;margin-bottom:-2px;">Mans Kalendārs</a>
</div>

<?php if ($tab === 'rezervacijas'): ?>
<!-- ── REZERVĀCIJAS TAB ── -->
<div class="section-header">
  <div class="section-heading">Rezervācijas</div>
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <a href="?tab=rezervacijas" class="action-btn <?= !$filter?'success':'' ?>">Visas</a>
    <a href="?tab=rezervacijas&filter=gaida" class="action-btn">Gaida</a>
    <a href="?tab=rezervacijas&filter=apstiprinats" class="action-btn">Apstiprinātas</a>
    <a href="?tab=rezervacijas&filter=pabeigts" class="action-btn">Pabeigtas</a>
    <a href="?tab=rezervacijas&filter=atcelts" class="action-btn">Atceltas</a>
  </div>
</div>

<div class="admin-card" style="padding:0;overflow:hidden;">
  <table class="admin-table">
    <thead>
      <tr><th>#</th><th>Klients</th><th>Pakalpojums</th><th>Datums/Laiks</th><th>Vieta</th><th>Cena</th><th>Statuss</th><th>Darbības</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $r): ?>
      <tr>
        <td style="color:var(--grey2);font-size:11px;"><?= $r['id'] ?></td>
        <td>
          <?php
          $displayVards = $r['vards'] ? htmlspecialchars($r['vards'].' '.$r['uzvards']) : ($r['viesis_vards'] ?? '');
          $displayEmail = $r['epasts'] ?: ($r['viesis_epasts'] ?? '');
          $displayTalr  = $r['talrunis'] ?: ($r['viesis_talrunis'] ?? '');
          ?>
          <?php if ($r['vards']): ?>
          <strong><?= htmlspecialchars($r['vards'].' '.$r['uzvards']) ?></strong>
          <?php else: ?>
          <em style="color:var(--grey2);">Viesis</em>
          <?php if (!empty($r['viesis_vards'])): ?><strong style="color:var(--ink);font-style:normal;"> <?= htmlspecialchars($r['viesis_vards']) ?></strong><?php endif; ?>
          <?php endif; ?>
          <?php if ($displayEmail): ?><div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars($displayEmail) ?></div><?php endif; ?>
          <?php if ($displayTalr): ?><div style="font-size:11px;color:var(--grey2);"><?= htmlspecialchars($displayTalr) ?></div><?php endif; ?>
        </td>
        <td style="max-width:160px;font-size:13px;"><?= htmlspecialchars($r['pakalpojums']) ?></td>
        <td><?= date('d.m.Y', strtotime($r['datums'])) ?><br><small style="color:var(--grey2);"><?= substr($r['laiks'],0,5) ?></small></td>
        <td style="font-size:12px;"><?= $r['vieta'] ? htmlspecialchars($r['vieta']) : '—' ?></td>
        <td style="color:var(--gold);font-weight:600;"><?= $r['cena'] ? '€'.number_format($r['cena'],0) : '—' ?></td>
        <td>
          <?php
          $sc = ['gaida'=>'#e67e22','apstiprinats'=>'#27ae60','pabeigts'=>'#2980b9','atcelts'=>'#c0392b'];
          $sl = $r['statuss'] ?? 'gaida';
          ?>
          <span style="font-size:10px;padding:3px 8px;border-radius:10px;background:<?= $sc[$sl]??'#999' ?>22;color:<?= $sc[$sl]??'#999' ?>;font-weight:600;"><?= ucfirst($sl) ?></span>
        </td>
        <td>
          <div style="display:flex;gap:5px;flex-wrap:wrap;">
            <?php if ($sl === 'gaida'): ?>
            <a href="?status=apstiprinats&id=<?= $r['id'] ?>&tab=rezervacijas"
               onclick="return confirm('Apstiprināt un nosūtīt e-pastu klientam?')"
               style="display:inline-flex;align-items:center;gap:3px;padding:5px 9px;background:#e8f5e9;color:#2e7d32;border:1px solid #c8e6c9;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;"
               title="Apstiprināt — klients saņems e-pastu ✉">Apstiprināt</a>
            <?php elseif ($sl === 'apstiprinats'): ?>
            <span style="padding:5px 9px;background:#f5f5f5;color:#aaa;border:1px solid #e0e0e0;border-radius:4px;font-size:11px;">Apstiprināts</span>
            <?php endif; ?>
            <?php if ($sl !== 'pabeigts' && $sl !== 'atcelts'): ?>
            <a href="?status=pabeigts&id=<?= $r['id'] ?>&tab=rezervacijas"
               onclick="return confirm('Atzīmēt kā pabeigtu? Klients saņems paziņojumu.')"
               style="display:inline-flex;align-items:center;gap:3px;padding:5px 9px;background:#e8f4fd;color:#1565c0;border:1px solid #90caf9;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;"
               title="Sesija pabeigta — klients saņems e-pastu">Pabeigts</a>
            <?php endif; ?>
            <?php if ($sl === 'pabeigts'): ?>
            <a href="?create_gallery=<?= $r['id'] ?>&tab=rezervacijas"
               onclick="return confirm('Izveidot jaunu galeriju šim klientam?')"
               style="display:inline-flex;align-items:center;gap:3px;padding:5px 9px;background:#f3e5f5;color:#6a1b9a;border:1px solid #ce93d8;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;"
               title="Izveidot galeriju un nosūtīt piekļuves kodu">Galeriju</a>
            <?php endif; ?>
            <?php if ($sl !== 'atcelts'): ?>
            <a href="?status=atcelts&id=<?= $r['id'] ?>&tab=rezervacijas"
               onclick="return confirm('Atcelt? Klients saņems atcelšanas e-pastu.')"
               style="display:inline-flex;align-items:center;gap:3px;padding:5px 9px;background:#fef0f0;color:#b71c1c;border:1px solid #ef9a9a;border-radius:4px;font-size:11px;font-weight:600;text-decoration:none;"
               title="Atcelt un informēt klientu">Atcelt</a>
            <?php endif; ?>
            <a href="?delete=<?= $r['id'] ?>&tab=rezervacijas"
               onclick="return confirm('Dzēst rezervāciju #<?= $r['id'] ?>? Nevar atsaukt.')"
               style="padding:5px 8px;background:#f5f5f5;color:#888;border:1px solid #ddd;border-radius:4px;font-size:11px;text-decoration:none;"
               title="Dzēst ierakstu">Dzēst</a>
          </div>
        </td>
      </tr>
      <?php if ($r['papildu_info']): ?>
      <tr><td colspan="8" style="background:var(--cream2);font-size:11px;color:var(--grey2);font-style:italic;padding:6px 16px;"><?= htmlspecialchars($r['papildu_info']) ?></td></tr>
      <?php endif; ?>
      <?php endforeach; ?>
      <?php if (empty($items)): ?><tr><td colspan="8" style="text-align:center;padding:36px;color:var(--grey2);">Nav rezervāciju</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php else: ?>
<!-- ── KALENDĀRS TAB ── -->
<div style="display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start;">

  <!-- Calendar -->
  <div>
    <div class="section-heading" style="margin-bottom:16px;">Mana Pieejamība — Nākamie 3 mēneši</div>
    <div style="background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;">
      <div style="display:flex;gap:16px;margin-bottom:14px;font-size:11px;flex-wrap:wrap;">
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:3px;background:#27ae6022;border:1px solid #27ae60;display:inline-block;"></span> Pieejama</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:3px;background:#c0392b22;border:1px solid #c0392b;display:inline-block;"></span> Nav pieejama</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:3px;background:#e67e2222;border:1px solid #e67e22;display:inline-block;"></span> Rezervēts</span>
        <span style="display:flex;align-items:center;gap:5px;"><span style="width:12px;height:12px;border-radius:3px;background:#f0f0f0;border:1px solid #ccc;display:inline-block;"></span> Nav iestatīts</span>
      </div>
      <div id="calendarContainer"></div>
    </div>
  </div>

  <!-- Day settings form -->
  <div class="admin-card" style="position:sticky;top:80px;">
    <div class="section-heading" style="margin-bottom:16px;">Rediģēt dienu</div>
    <div style="margin-bottom:14px;padding:10px 14px;background:var(--cream2);border-radius:6px;font-size:13px;color:var(--grey2);">
      Klikšķiniet uz datuma kalendārā lai to rediģētu
    </div>
    <form method="POST">
      <input type="hidden" name="save_availability">
      <div class="form-group">
        <label class="form-label">Datums</label>
        <input type="date" name="datums" id="availDate" class="form-input" min="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:10px;">
        <input type="checkbox" id="availCheck" name="pieejams" checked style="width:auto;cursor:pointer;">
        <label for="availCheck" class="form-label" style="margin:0;cursor:pointer;">Esmu pieejama šajā dienā</label>
      </div>
      <div class="form-group" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <div>
          <label class="form-label">Darba laiks no</label>
          <input type="time" name="laiks_no" id="availFrom" class="form-input" value="09:00">
        </div>
        <div>
          <label class="form-label">Līdz</label>
          <input type="time" name="laiks_lidz" id="availTo" class="form-input" value="18:00">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Piezīme (privāta)</label>
        <input type="text" name="piezime" id="availNote" class="form-input" placeholder="Atvaļinājums, slimnīca...">
      </div>
      <button type="submit" class="btn-primary" style="width:100%;">Saglabāt dienu →</button>
    </form>

    <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
      <div class="section-heading" style="margin-bottom:10px;font-size:13px;">Ātrie iestatījumi</div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <button onclick="setWeek(true)" class="action-btn success" style="text-align:left;">Nākamā nedēļa — pieejama</button>
        <button onclick="setWeek(false)" class="action-btn danger" style="text-align:left;">Nākamā nedēļa — nav pieejama</button>
      </div>
    </div>
  </div>
</div>

<!-- Rezervācijas šajā periodā -->
<div class="section-heading" style="margin:24px 0 12px;">Rezervācijas nākamajās 60 dienās</div>
<div class="admin-card" style="padding:0;overflow:hidden;">
  <table class="admin-table">
    <thead><tr><th>Datums</th><th>Klients</th><th>Pakalpojums</th><th>Statuss</th></tr></thead>
    <tbody>
      <?php
      $upcoming = mysqli_query($savienojums, "SELECT r.*,k.vards,k.uzvards FROM rezervacijas r LEFT JOIN klienti k ON r.klienta_id=k.id WHERE r.datums BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 60 DAY) AND r.statuss!='atcelts' ORDER BY r.datums");
      $any = false;
      while ($u = mysqli_fetch_assoc($upcoming)): $any = true; ?>
      <tr>
        <td><?= date('d.m.Y (l)', strtotime($u['datums'])) ?><br><small style="color:var(--grey2);"><?= substr($u['laiks'],0,5) ?></small></td>
        <td><?= $u['vards'] ? htmlspecialchars($u['vards'].' '.$u['uzvards']) : 'Viesis' ?></td>
        <td><?= htmlspecialchars($u['pakalpojums']) ?></td>
        <td><span style="font-size:10px;color:var(--gold);"><?= $u['statuss'] ?></span></td>
      </tr>
      <?php endwhile; ?>
      <?php if (!$any): ?><tr><td colspan="4" style="text-align:center;padding:24px;color:var(--grey2);">Nav gaidāmu rezervāciju</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<script>
const availData = <?= json_encode($avail) ?>;
const bookedData = <?= json_encode($booked) ?>;

function renderCalendar() {
  const container = document.getElementById('calendarContainer');
  const today = new Date();
  let html = '';
  
  for (let m = 0; m < 3; m++) {
    const d = new Date(today.getFullYear(), today.getMonth() + m, 1);
    const monthName = d.toLocaleDateString('lv-LV', {month:'long', year:'numeric'});
    const daysInMonth = new Date(d.getFullYear(), d.getMonth()+1, 0).getDate();
    const firstDay = (d.getDay() + 6) % 7; // Monday=0
    
    html += `<div style="margin-bottom:20px;">
      <div style="font-weight:600;font-size:14px;color:var(--ink);margin-bottom:10px;text-transform:capitalize;">${monthName}</div>
      <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;font-size:10px;">
        ${['Pi','Ot','Tr','Ce','Pk','Se','Sv'].map(d=>`<div style="text-align:center;color:var(--grey2);padding:4px;">${d}</div>`).join('')}`;
    
    for (let i = 0; i < firstDay; i++) html += '<div></div>';
    
    for (let day = 1; day <= daysInMonth; day++) {
      const date = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
      const isPast = new Date(date) < new Date(today.toDateString());
      const booked = bookedData[date] || 0;
      const av = availData[date];
      
      let bg = '#f5f5f5', border = '#ddd', color = '#999', title = '';
      if (!isPast) {
        if (booked > 0) { bg='#e67e2215'; border='#e67e22'; color='#e67e22'; title=`${booked} rezervācija`; }
        else if (av) {
          if (av.pieejams) { bg='#27ae6012'; border='#27ae60'; color='#27ae60'; title=av.laiks_no+'-'+av.laiks_lidz+(av.piezime?' · '+av.piezime:''); }
          else { bg='#c0392b10'; border='#c0392b'; color='#c0392b'; title=av.piezime||'Nav pieejama'; }
        }
      }
      
      html += `<div onclick="${!isPast ? `selectDate('${date}',${av?av.pieejams:1},'${av?av.laiks_no:'09:00'}','${av?av.laiks_lidz:'18:00'}','${av?av.piezime.replace(/'/g,''):''}')` : ''}" 
        style="text-align:center;padding:5px 3px;border-radius:5px;background:${bg};border:1.5px solid ${border};color:${color};cursor:${isPast?'default':'pointer'};font-size:11px;font-weight:${booked?'700':'400'};"
        title="${title}">${day}${booked?'<br><span style="font-size:8px;">●</span>':''}</div>`;
    }
    html += '</div></div>';
  }
  container.innerHTML = html;
}

function selectDate(date, pieejams, from, to, note) {
  document.getElementById('availDate').value = date;
  document.getElementById('availCheck').checked = !!pieejams;
  document.getElementById('availFrom').value = from || '09:00';
  document.getElementById('availTo').value = to || '18:00';
  document.getElementById('availNote').value = note || '';
  document.querySelector('.admin-card .section-heading').textContent = 'Rediģēt: ' + date;
  document.querySelector('.admin-card .section-heading').scrollIntoView({behavior:'smooth',block:'nearest'});
}

function setWeek(available) {
  const today = new Date();
  const nextMon = new Date(today);
  nextMon.setDate(today.getDate() + (8 - today.getDay()) % 7 + 1);
  const promises = [];
  for (let i = 0; i < 5; i++) {
    const d = new Date(nextMon);
    d.setDate(nextMon.getDate() + i);
    const date = d.toISOString().split('T')[0];
    const fd = new FormData();
    fd.append('save_availability','1');
    fd.append('datums', date);
    fd.append('laiks_no','09:00');
    fd.append('laiks_lidz','18:00');
    if (available) fd.append('pieejams','1');
    promises.push(fetch('', {method:'POST', body:fd}));
  }
  Promise.all(promises).then(() => location.reload());
}

renderCalendar();
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
