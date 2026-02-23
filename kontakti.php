<?php 
require_once 'header.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_contact'])){
    $vards = mysqli_real_escape_string($savienojums, trim($_POST['vards']));
    $epasts = mysqli_real_escape_string($savienojums, trim($_POST['epasts']));
    $temai = mysqli_real_escape_string($savienojums, trim($_POST['temats']));
    $zina = mysqli_real_escape_string($savienojums, trim($_POST['zina']));
    
    if($vards && $epasts && $zina){
        $_SESSION['toast'] = ['msg' => 'Ziņa nosūtīta! Atbildēsim 24 stundu laikā.', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Lūdzu aizpildiet visus laukus!', 'type' => 'error'];
    }
    header('Location: kontakti.php');
    exit;
}
?>

<style>
.kontakts-hero{padding:180px 64px 80px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);border-bottom:1px solid var(--grey3);text-align:center;}
.kontakts-layout{padding:80px 64px;display:grid;grid-template-columns:1fr 1.4fr;gap:60px;background:var(--white);}
.contact-info-item{display:flex;align-items:flex-start;gap:16px;margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid var(--grey3);}
.contact-info-item:last-child{border-bottom:none;}
.contact-icon{font-size:24px;flex-shrink:0;margin-top:2px;}
.contact-label{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:6px;}
.contact-value{font-size:15px;color:var(--ink);margin-bottom:3px;}
.contact-sub{font-size:12px;color:var(--grey2);}
.map-placeholder{height:300px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream3) 100%);display:flex;align-items:center;justify-content:center;margin-top:28px;border:1px solid var(--grey3);position:relative;overflow:hidden;}
.map-placeholder::before{content:'📍';font-size:48px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);}
.socials-section{padding:60px 64px;background:var(--cream2);text-align:center;border-top:1px solid var(--grey3);}
.social-links{display:flex;justify-content:center;gap:12px;margin-top:28px;}
.social-link{width:52px;height:52px;border:1px solid var(--grey3);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;transition:all .3s;text-decoration:none;color:var(--ink2);}
.social-link:hover{border-color:var(--gold);color:var(--gold);background:var(--gold-dim);}
.working-hours{margin-top:28px;}
.hours-row{display:flex;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--grey3);font-size:13px;}
.hours-row:last-child{border-bottom:none;}
.hours-day{color:var(--grey);}
.hours-time{color:var(--ink);font-weight:500;}
.hours-time.closed{color:var(--grey3);}
@media(max-width:900px){.kontakts-layout{grid-template-columns:1fr;padding:50px 22px;}.socials-section{padding:50px 22px;}.kontakts-hero{padding:120px 22px 50px;}}
</style>

<div class="kontakts-hero">
  <div class="section-label">Kontakti</div>
  <h1 class="section-title" style="margin-bottom:16px;">Sazinieties <em style="font-style:italic;color:var(--gold)">ar mums</em></h1>
  <p style="font-size:14px;color:var(--grey);max-width:460px;margin:0 auto;">Esam gatavi atbildēt uz jautājumiem un palīdzēt jums izveidot sapņu fotosesiju</p>
</div>

<div class="kontakts-layout">
  <div class="reveal">
    <div class="section-label">Kontaktinformācija</div>
    
    <div class="contact-info-item">
      <div class="contact-icon">📍</div>
      <div>
        <div class="contact-label">Adrese</div>
        <div class="contact-value">Brīvības iela 123</div>
        <div class="contact-sub">Rīga, LV-1001, Latvija</div>
      </div>
    </div>
    
    <div class="contact-info-item">
      <div class="contact-icon">📞</div>
      <div>
        <div class="contact-label">Tālrunis</div>
        <div class="contact-value">+371 20 000 000</div>
        <div class="contact-sub">P–Pk: 9:00–18:00</div>
      </div>
    </div>
    
    <div class="contact-info-item">
      <div class="contact-icon">📧</div>
      <div>
        <div class="contact-label">E-pasts</div>
        <div class="contact-value">info@lumina.lv</div>
        <div class="contact-sub">Atbildam 24 stundu laikā</div>
      </div>
    </div>

    <div class="working-hours">
      <div class="contact-label" style="margin-bottom:8px;">Darba laiks</div>
      <?php
      $hours = [
        ['P','9:00 – 18:00'],['O','9:00 – 18:00'],['T','9:00 – 18:00'],['C','9:00 – 18:00'],
        ['Pk','9:00 – 18:00'],['S','10:00 – 16:00'],['Sv','Slēgts'],
      ];
      foreach($hours as $h): ?>
      <div class="hours-row">
        <span class="hours-day"><?= $h[0] ?></span>
        <span class="hours-time <?= $h[1]==='Slēgts'?'closed':'' ?>"><?= $h[1] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    
    <div class="map-placeholder"><span style="font-family:'Cormorant Garamond',serif;font-size:16px;color:var(--grey);margin-top:60px;">Brīvības iela 123, Rīga</span></div>
  </div>

  <div class="reveal reveal-delay-1">
    <div class="section-label">Rakstiet mums</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:32px;font-weight:300;color:var(--ink);margin:10px 0 28px;">Nosūtiet ziņu</h2>
    
    <form method="POST" action="kontakti.php">
      <input type="hidden" name="send_contact" value="1">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Vārds *</label><input type="text" name="vards" class="form-input" placeholder="Jūsu vārds" required></div>
        <div class="form-group"><label class="form-label">E-pasts *</label><input type="email" name="epasts" class="form-input" placeholder="jusu@epasts.lv" required></div>
      </div>
      <div class="form-group"><label class="form-label">Tēma</label>
        <select name="temats" class="form-select">
          <option value="rezervacija">Rezervācija</option>
          <option value="cenas">Cenas & paketes</option>
          <option value="pasutijums">Pasūtījums</option>
          <option value="sadarbiba">Sadarbība</option>
          <option value="cits">Cits jautājums</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Ziņa *</label><textarea name="zina" class="form-textarea" style="height:140px;" placeholder="Pastāstiet par savu jautājumu vai vajadzību..." required></textarea></div>
      <button type="submit" class="btn-primary" style="width:100%">Nosūtīt ziņu →</button>
    </form>
  </div>
</div>

<div class="socials-section">
  <div class="section-label" style="display:flex;justify-content:center;">Sekojiet mums</div>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:36px;font-weight:300;color:var(--ink);margin-top:10px;">Sociālie tīkli</h2>
  <p style="font-size:13px;color:var(--grey);margin-top:10px;">Uzziniet par jaunākajiem darbiem un piedāvājumiem</p>
  <div class="social-links">
    <a class="social-link" href="#" title="Instagram">ig</a>
    <a class="social-link" href="#" title="Facebook">fb</a>
    <a class="social-link" href="#" title="YouTube">yt</a>
    <a class="social-link" href="#" title="LinkedIn">in</a>
    <a class="social-link" href="#" title="Pinterest">pt</a>
  </div>
</div>

<?php require_once 'footer.php'; ?>