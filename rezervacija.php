<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Rezervācija';
$extraCss = 'rezervacija.css';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $klienta_id = isset($_SESSION['klients_id']) ? (int)$_SESSION['klients_id'] : null;
  $pakalpojums = escape($savienojums, $_POST['pakalpojums'] ?? '');
  $datums = escape($savienojums, $_POST['datums'] ?? '');
  $laiks = escape($savienojums, $_POST['laiks'] ?? '14:00:00');
  $vieta = escape($savienojums, $_POST['vieta'] ?? '');
  $papildu = escape($savienojums, $_POST['papildu'] ?? '');
  
  // Price from service
  $cenas = [
    'Kāzu fotogrāfija — no €600' => 600,
    'Ģimenes fotosesija — no €150' => 150,
    'Portretu sesija — no €85' => 85,
    'Korporatīvs pasākums — no €300' => 300,
    'Produktu fotogrāfija — no €200' => 200,
  ];
  $cena = $cenas[$pakalpojums] ?? null;
  
  if (empty($pakalpojums) || empty($datums)) {
    $error = 'Lūdzu aizpildiet visus obligātos laukus.';
  } else {
    $sql = "INSERT INTO rezervacijas (klienta_id, pakalpojums, datums, laiks, vieta, papildu_info, statuss, cena) VALUES (?, ?, ?, ?, ?, ?, 'apstiprinats', ?)";
    $stmt = mysqli_prepare($savienojums, $sql);
    mysqli_stmt_bind_param($stmt, 'isssssd', $klienta_id, $pakalpojums, $datums, $laiks, $vieta, $papildu, $cena);
    if (mysqli_stmt_execute($stmt)) {
      $success = 'Pieteikums nosūtīts! Apstiprinājums nosūtīts uz jūsu e-pastu.';
      $klientaVards = isset($_SESSION['klients_vards']) ? $_SESSION['klients_vards'] : 'Viesis';
      $klientaEmail = isset($_SESSION['klients_epasts']) ? $_SESSION['klients_epasts'] : '';
      // Fetch client phone
      $talrunis = '';
      if (isset($_SESSION['klients_id'])) {
        $kl = mysqli_fetch_assoc(mysqli_query($savienojums, "SELECT talrunis FROM klienti WHERE id=" . (int)$_SESSION['klients_id']));
        $talrunis = $kl['talrunis'] ?? '';
      }
      $rezData = ['pakalpojums'=>$pakalpojums,'datums'=>$datums,'laiks'=>$laiks,'vieta'=>$vieta,'cena'=>$cena,'papildu_info'=>$papildu];
      require_once __DIR__ . '/includes/mailer.php';
      mailRezervacijaAdmin($rezData, $klientaVards, $klientaEmail, $talrunis);
      if ($klientaEmail) mailRezervacijaKlients($klientaEmail, $klientaVards, $rezData);
    } else {
      $error = 'Kļūda: ' . mysqli_error($savienojums);
    }
  }
}

// Get booked dates for calendar
$bookedResult = mysqli_query($savienojums, "SELECT datums FROM rezervacijas WHERE statuss != 'atcelts'");
$bookedDates = [];
while ($row = mysqli_fetch_assoc($bookedResult)) {
  $bookedDates[] = $row['datums'];
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-header" style="background: var(--ink); position: relative; overflow: hidden;">
  <div style="position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1551636898-47668aa61de2?w=1200&q=80') center/cover;opacity:.15;"></div>
  <div style="position:relative;z-index:1;">
    <div class="section-label">Rezervācijas</div>
    <h1 style="color:var(--white);">Rezervēt <em>Sesiju</em></h1>
    <p style="color:rgba(255,255,255,.6);">Izvēlieties datumu un mēs atbildēsim 24 stundu laikā.</p>
  </div>
</div>

<section class="rezervacija-section">
  <div class="rezervacija-grid">
    
    <!-- Left: Calendar + Info -->
    <div class="rezervacija-left">
      <div class="cal-card reveal">
        <div class="section-label">Izvēlieties datumu</div>
        <div class="cal-header" style="margin-top:10px;">
          <button class="cal-nav" onclick="prevMonth()">‹</button>
          <div class="cal-month" id="calMonthTitle"></div>
          <button class="cal-nav" onclick="nextMonth()">›</button>
        </div>
        <div class="calendar-grid" style="margin-bottom:3px;">
          <div class="cal-day-name">P</div><div class="cal-day-name">O</div>
          <div class="cal-day-name">T</div><div class="cal-day-name">C</div>
          <div class="cal-day-name">Pk</div><div class="cal-day-name">S</div>
          <div class="cal-day-name">Sv</div>
        </div>
        <div class="calendar-grid" id="calGrid"></div>
        <div id="selectedDateInfo"></div>
      </div>

      <div class="info-card reveal reveal-delay-1">
        <div class="section-label">Pakalpojumu cenas</div>
        <div class="price-list">
          <div class="price-item"><span>Kāzu fotogrāfija</span><span class="price">no €600</span></div>
          <div class="price-item"><span>Ģimenes fotosesija</span><span class="price">no €150</span></div>
          <div class="price-item"><span>Portretu sesija</span><span class="price">no €85</span></div>
          <div class="price-item"><span>Korporatīvs pasākums</span><span class="price">no €300</span></div>
          <div class="price-item"><span>Produktu fotogrāfija</span><span class="price">no €200</span></div>
        </div>
      </div>
    </div>

    <!-- Right: Form -->
    <div class="rezervacija-right reveal reveal-delay-2">
      <div class="section-label">Rezervācijas pieteikums</div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;color:var(--ink);margin:10px 0 28px;">Rakstiet mums</h2>

      <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="datums" id="selectedDate" value="">
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Vārds *</label>
            <input type="text" name="vards" class="form-input" placeholder="Jūsu vārds" required value="<?= isset($_SESSION['klients_vards']) ? htmlspecialchars($_SESSION['klients_vards']) : '' ?>">
          </div>
          <div class="form-group">
            <label class="form-label">E-pasts *</label>
            <input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required value="<?= isset($_SESSION['klients_epasts']) ? htmlspecialchars($_SESSION['klients_epasts']) : '' ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Izvēlētais datums *</label>
          <input type="date" name="datums_display" id="dateDisplay" class="form-input" required readonly style="background:var(--cream2);">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Laiks</label>
            <select name="laiks" class="form-select">
              <option value="09:00:00">09:00</option>
              <option value="10:00:00">10:00</option>
              <option value="11:00:00">11:00</option>
              <option value="12:00:00">12:00</option>
              <option value="13:00:00">13:00</option>
              <option value="14:00:00" selected>14:00</option>
              <option value="15:00:00">15:00</option>
              <option value="16:00:00">16:00</option>
              <option value="17:00:00">17:00</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Pakalpojums *</label>
            <select name="pakalpojums" class="form-select" required>
              <option value="">Izvēlieties...</option>
              <option>Kāzu fotogrāfija — no €600</option>
              <option>Ģimenes fotosesija — no €150</option>
              <option>Portretu sesija — no €85</option>
              <option>Korporatīvs pasākums — no €300</option>
              <option>Produktu fotogrāfija — no €200</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Vieta / Atrašanās vieta</label>
          <input type="text" name="vieta" class="form-input" placeholder="Studija, Rīga / Jūrmala / pēc vienošanās">
        </div>

        <div class="form-group">
          <label class="form-label">Papildu informācija</label>
          <textarea name="papildu" class="form-textarea" placeholder="Pastāstiet par savu vīziju, dalībnieku skaitu un citām vēlmēm..."></textarea>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;">Nosūtīt Pieteikumu →</button>
      </form>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const bookedDates = <?= json_encode($bookedDates) ?>;
let calYear = new Date().getFullYear();
let calMonth = new Date().getMonth();
let selectedDay = null;
const months = ['Janvāris','Februāris','Marts','Aprīlis','Maijs','Jūnijs','Jūlijs','Augusts','Septembris','Oktobris','Novembris','Decembris'];

function pad(n) { return n < 10 ? '0' + n : n; }

function renderCalendar() {
  document.getElementById('calMonthTitle').textContent = months[calMonth] + ' ' + calYear;
  const g = document.getElementById('calGrid');
  g.innerHTML = '';
  const first = new Date(calYear, calMonth, 1).getDay();
  const off = first === 0 ? 6 : first - 1;
  const dim = new Date(calYear, calMonth + 1, 0).getDate();
  const today = new Date();

  for (let i = 0; i < off; i++) g.appendChild(document.createElement('div'));

  for (let d = 1; d <= dim; d++) {
    const el = document.createElement('div');
    el.className = 'cal-day';
    el.textContent = d;
    const dateStr = calYear + '-' + pad(calMonth + 1) + '-' + pad(d);
    const isPast = new Date(calYear, calMonth, d) < new Date(today.getFullYear(), today.getMonth(), today.getDate());
    const isBooked = bookedDates.includes(dateStr);
    const isToday = d === today.getDate() && calMonth === today.getMonth() && calYear === today.getFullYear();

    if (isToday) el.classList.add('today');
    if (isPast || isBooked) {
      el.classList.add('booked');
      if (isBooked) el.title = 'Rezervēts';
    } else {
      el.classList.add('available');
      el.onclick = () => selectDay(d, el, dateStr);
    }
    if (selectedDay && dateStr === selectedDay) el.classList.add('selected');
    g.appendChild(el);
  }
}

function selectDay(d, el, dateStr) {
  selectedDay = dateStr;
  document.querySelectorAll('.cal-day.selected').forEach(x => x.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('selectedDateInfo').textContent = '✦ Izvēlēts: ' + d + '. ' + months[calMonth] + ' ' + calYear;
  document.getElementById('selectedDate').value = dateStr;
  document.getElementById('dateDisplay').value = dateStr;
}

function prevMonth() {
  calMonth--;
  if (calMonth < 0) { calMonth = 11; calYear--; }
  renderCalendar();
}
function nextMonth() {
  calMonth++;
  if (calMonth > 11) { calMonth = 0; calYear++; }
  renderCalendar();
}

renderCalendar();
</script>
