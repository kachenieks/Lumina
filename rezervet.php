<?php 
require_once 'header.php';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $vards = trim($_POST['vards'] ?? '');
    $epasts = trim($_POST['epasts'] ?? '');
    $pakalpojums = trim($_POST['pakalpojums'] ?? '');
    $datums = trim($_POST['datums'] ?? '');
    $laiks = trim($_POST['laiks'] ?? '12:00');
    $papildu = trim($_POST['papildu'] ?? '');
    $vieta = trim($_POST['vieta'] ?? '');
    
    if($vards && $epasts && $pakalpojums && $datums){
        $klienta_id = null;
        if(isset($_SESSION['klients_id'])) $klienta_id = $_SESSION['klients_id'];
        
        // Try to find client by email
        if(!$klienta_id){
            $res = mysqli_query($savienojums, "SELECT id FROM klienti WHERE epasts='".mysqli_real_escape_string($savienojums,$epasts)."'");
            if($row = mysqli_fetch_assoc($res)) $klienta_id = $row['id'];
        }
        
        $sql = "INSERT INTO rezervacijas (klienta_id, pakalpojums, datums, laiks, vieta, papildu_info, statuss) VALUES (
            ".($klienta_id ? $klienta_id : 'NULL').",
            '".mysqli_real_escape_string($savienojums,$pakalpojums)."',
            '".mysqli_real_escape_string($savienojums,$datums)."',
            '".mysqli_real_escape_string($savienojums,$laiks)."',
            '".mysqli_real_escape_string($savienojums,$vieta)."',
            '".mysqli_real_escape_string($savienojums,$papildu)."',
            'gaidoss'
        )";
        
        if(mysqli_query($savienojums, $sql)){
            $_SESSION['toast'] = ['msg' => 'Pieteikums nosūtīts! Sazināsimies 24 stundu laikā.', 'type' => 'success'];
        } else {
            $_SESSION['toast'] = ['msg' => 'Kļūda! Lūdzu mēģiniet vēlreiz.', 'type' => 'error'];
        }
        header('Location: rezervet.php');
        exit;
    }
}

// Get booked dates from DB
$booked_result = mysqli_query($savienojums, "SELECT datums FROM rezervacijas WHERE statuss IN ('gaidoss','apstiprinats') AND datums >= CURDATE()");
$booked_dates = [];
while($r = mysqli_fetch_assoc($booked_result)) $booked_dates[] = $r['datums'];

$preselected = $_GET['pakalpojums'] ?? '';
?>

<style>
.rezervet-hero{padding:180px 64px 80px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);border-bottom:1px solid var(--grey3);text-align:center;}
.booking-layout{display:grid;grid-template-columns:1fr 1.3fr;gap:0;min-height:80vh;}
.booking-left{padding:70px 54px;background:var(--white);border-right:1px solid var(--grey3);}
.booking-right{padding:70px 54px;background:var(--cream2);}
.cal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.cal-nav{background:none;border:1px solid var(--grey3);color:var(--gold);cursor:pointer;font-size:15px;padding:8px 14px;transition:all .2s;font-family:'Montserrat',sans-serif;}
.cal-nav:hover{background:var(--gold);color:var(--white);border-color:var(--gold);}
.cal-month{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--ink);}
.calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:8px;}
.cal-day{text-align:center;padding:10px 5px;font-size:13px;cursor:pointer;transition:all .2s;color:var(--ink2);border-radius:2px;}
.cal-day.available:hover{background:var(--gold-dim);color:var(--gold);}
.cal-day.booked{color:var(--grey3);cursor:not-allowed;text-decoration:line-through;}
.cal-day.selected{background:var(--gold);color:var(--white);font-weight:600;}
.cal-day.today{border:1px solid var(--gold-border);}
.cal-day.past{color:var(--grey3);cursor:not-allowed;}
.cal-day-name{text-align:center;font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--grey2);padding:8px 0;}
.cal-legend{display:flex;gap:20px;margin-top:14px;font-size:11px;color:var(--grey);}
.legend-dot{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:5px;}
.time-slots{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:16px;}
.time-slot{padding:10px;text-align:center;border:1px solid var(--grey3);cursor:pointer;font-size:12px;transition:all .2s;}
.time-slot:hover{border-color:var(--gold);color:var(--gold);}
.time-slot.selected{background:var(--gold);color:var(--white);border-color:var(--gold);}
@media(max-width:900px){.booking-layout{grid-template-columns:1fr;}.booking-left,.booking-right{padding:40px 22px;}.rezervet-hero{padding:120px 22px 50px;}}
</style>

<div class="rezervet-hero">
  <div class="section-label">Rezervācija</div>
  <h1 class="section-title" style="margin-bottom:16px;">Rezervēt <em style="font-style:italic;color:var(--gold)">Fotosesiju</em></h1>
  <p style="font-size:14px;color:var(--grey);max-width:500px;margin:0 auto;">Izvēlieties datumu un aizpildiet pieteikumu. Atbildēsim 24 stundu laikā.</p>
</div>

<div class="booking-layout">
  <div class="booking-left">
    <div class="section-label">Izvēlieties datumu</div>
    <div class="cal-header" style="margin-top:12px;">
      <button type="button" class="cal-nav" onclick="prevMonth()">‹</button>
      <div class="cal-month" id="calMonthTitle"></div>
      <button type="button" class="cal-nav" onclick="nextMonth()">›</button>
    </div>
    <div class="calendar-grid">
      <div class="cal-day-name">P</div><div class="cal-day-name">O</div><div class="cal-day-name">T</div><div class="cal-day-name">C</div><div class="cal-day-name">Pk</div><div class="cal-day-name">S</div><div class="cal-day-name">Sv</div>
    </div>
    <div class="calendar-grid" id="calGrid"></div>
    <div id="selectedDateInfo" style="font-size:11px;color:var(--gold);letter-spacing:2px;text-transform:uppercase;margin:16px 0 8px;min-height:17px;"></div>
    
    <div style="margin-top:20px;">
      <div class="section-label">Izvēlieties laiku</div>
      <div class="time-slots" id="timeSlots">
        <?php foreach(['09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00'] as $t): ?>
        <div class="time-slot" onclick="selectTime('<?= $t ?>',this)"><?= $t ?></div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="cal-legend" style="margin-top:20px;">
      <span><span class="legend-dot" style="background:var(--gold);"></span>Pieejams</span>
      <span><span class="legend-dot" style="background:var(--grey3);"></span>Aizņemts</span>
      <span><span class="legend-dot" style="border:1px solid var(--gold-border);background:transparent;"></span>Šodiena</span>
    </div>
  </div>

  <div class="booking-right">
    <div class="section-label">Pieteikuma forma</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:300;color:var(--ink);margin:10px 0 28px;">Jūsu informācija</h2>
    
    <form method="POST" action="rezervet.php">
      <input type="hidden" name="datums" id="formDate">
      <input type="hidden" name="laiks" id="formTime" value="12:00">
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Vārds *</label>
          <input type="text" name="vards" class="form-input" placeholder="Jūsu vārds" required
            value="<?= isset($_SESSION['klients_vards']) ? htmlspecialchars($_SESSION['klients_vards']) : '' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Uzvārds</label>
          <input type="text" name="uzvards" class="form-input" placeholder="Uzvārds">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">E-pasts *</label>
        <input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required
          value="<?= isset($_SESSION['klients_epasts']) ? htmlspecialchars($_SESSION['klients_epasts']) : '' ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Tālrunis</label>
        <input type="tel" name="talrunis" class="form-input" placeholder="+371 XX XXX XXX">
      </div>
      <div class="form-group">
        <label class="form-label">Pakalpojuma veids *</label>
        <select name="pakalpojums" class="form-select" required>
          <option value="">Izvēlieties pakalpojumu</option>
          <?php
          $services = [
            'kazas' => 'Kāzu fotogrāfija — no €600',
            'gimene' => 'Ģimenes fotosesija — no €150',
            'portreti' => 'Portretu sesija — no €85',
            'pasakumi' => 'Korporatīvs pasākums — no €300',
            'komercials' => 'Produktu fotogrāfija — no €200',
          ];
          foreach($services as $val => $label): ?>
          <option value="<?= $label ?>" <?= $preselected === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Vēlamā vieta / lokācija</label>
        <input type="text" name="vieta" class="form-input" placeholder="piem. Mežaparks, studija, jūsu mājas...">
      </div>
      <div class="form-group">
        <label class="form-label">Papildu informācija & vēlmes</label>
        <textarea name="papildu" class="form-textarea" placeholder="Pastāstiet par savu vīziju un vēlmēm..."></textarea>
      </div>
      
      <div id="bookingSummary" style="display:none;padding:16px 20px;background:var(--white);border-left:3px solid var(--gold);margin-bottom:20px;">
        <div style="font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:6px;">Jūsu izvēle</div>
        <div id="summaryText" style="font-size:13px;color:var(--ink2);"></div>
      </div>
      
      <button type="submit" class="btn-primary" style="width:100%">Nosūtīt Pieteikumu →</button>
      <p style="font-size:11px;color:var(--grey2);margin-top:12px;text-align:center;">✦ Atbildēsim 24 stundu laikā · Avanss 30% rezervācijas brīdī</p>
    </form>
  </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
const bookedDates = <?= json_encode($booked_dates) ?>;
const months=['Janvāris','Februāris','Marts','Aprīlis','Maijs','Jūnijs','Jūlijs','Augusts','Septembris','Oktobris','Novembris','Decembris'];
let calYear=2026,calMonth=1,selectedDay=null,selectedTime='12:00';
const today = new Date();

function isBooked(y,m,d){
    const ds = y+'-'+(m+1).toString().padStart(2,'0')+'-'+d.toString().padStart(2,'0');
    return bookedDates.includes(ds);
}
function isPast(y,m,d){
    const dt = new Date(y,m,d);
    return dt < today;
}

function renderCalendar(){
    document.getElementById('calMonthTitle').textContent=months[calMonth]+' '+calYear;
    const g=document.getElementById('calGrid');
    g.innerHTML='';
    const first=new Date(calYear,calMonth,1).getDay();
    const off=first===0?6:first-1;
    const dim=new Date(calYear,calMonth+1,0).getDate();
    for(let i=0;i<off;i++) g.appendChild(document.createElement('div'));
    for(let d=1;d<=dim;d++){
        const el=document.createElement('div');
        el.className='cal-day';
        el.textContent=d;
        if(isPast(calYear,calMonth,d)) el.classList.add('past');
        else if(isBooked(calYear,calMonth,d)) el.classList.add('booked');
        else{el.classList.add('available');el.onclick=()=>selectDay(d,el);}
        if(d===today.getDate()&&calMonth===today.getMonth()&&calYear===today.getFullYear()) el.classList.add('today');
        if(d===selectedDay) el.classList.add('selected');
        g.appendChild(el);
    }
}
function selectDay(d,el){
    selectedDay=d;
    document.querySelectorAll('.cal-day.selected').forEach(x=>x.classList.remove('selected'));
    el.classList.add('selected');
    const ds=calYear+'-'+(calMonth+1).toString().padStart(2,'0')+'-'+d.toString().padStart(2,'0');
    document.getElementById('formDate').value=ds;
    document.getElementById('selectedDateInfo').textContent='✦ '+d+'. '+months[calMonth]+' '+calYear;
    updateSummary();
}
function selectTime(t,el){
    selectedTime=t;
    document.querySelectorAll('.time-slot').forEach(x=>x.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('formTime').value=t;
    updateSummary();
}
function updateSummary(){
    const dateVal=document.getElementById('formDate').value;
    const pkg=document.querySelector('[name=pakalpojums]');
    if(dateVal){
        document.getElementById('bookingSummary').style.display='block';
        document.getElementById('summaryText').textContent='📅 '+dateVal+' plkst. '+selectedTime+(pkg&&pkg.value?' · '+pkg.value.split(' —')[0]:'');
    }
}
function prevMonth(){calMonth--;if(calMonth<0){calMonth=11;calYear--;}selectedDay=null;renderCalendar();}
function nextMonth(){calMonth++;if(calMonth>11){calMonth=0;calYear++;}selectedDay=null;renderCalendar();}
renderCalendar();

document.querySelector('[name=pakalpojums]').addEventListener('change',updateSummary);
</script>