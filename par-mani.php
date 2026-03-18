<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Par mani';
$extraCss  = 'par-mani.css';
include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<div class="par-hero">
  <div class="par-hero-img"></div>
  <div class="par-hero-overlay"></div>
  <div class="par-hero-content">
    <div class="section-label" style="color:var(--gold-light);">Fotogrāfe</div>
    <h1 class="par-hero-title">
      Katrīna<br><em>Blažkova</em>
    </h1>
    <p class="par-hero-sub">Latvijas fotogrāfe · Kāzas · Portreti · Dzīves mirkļi</p>
  </div>
</div>

<!-- INTRO SECTION -->
<section class="par-intro">
  <div class="par-intro-grid">

    <div class="par-intro-img reveal">
      <img src="https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=800&q=80"
           alt="Katrīna Blažkova — fotogrāfe"
           style="width:100%;height:clamp(450px,60vw,640px);object-fit:cover;object-position:top;display:block;">
      <div class="par-intro-badge">
        <div class="par-badge-num">500+</div>
        <div class="par-badge-label">Iemūžināti mirkļi</div>
      </div>
    </div>

    <div class="par-intro-text reveal reveal-delay-2">
      <div class="section-label">Par mani</div>
      <h2 class="section-title" style="margin:14px 0 30px;">
        Sveiki, esmu<br>
        <em style="font-style:italic;color:var(--gold)">Katrīna</em>
      </h2>
      <p>Esmu fotogrāfe no Latvijas, kura dzīvo un elpo bildēšanu. Man ir īpaša pieeja katram cilvēkam un katrai sesijā — nevis tikai uzņemšana, bet īsta pieredze, kas beidzas ar attēliem, kurus tu glabāsi visu mūžu.</p>
      <p>Fotografēju kāzas, portretus, ģimenes, pasākumus. Mana darba stils ir naturāls, maigs un emocionāls — bez pārmērīgas pēcapstrādes, bez izlikšanās, tikai tu, kāds tu esi patiesībā.</p>
      <p>Darbojos visā Latvijā. Īpašos gadījumos arī ārpus tās.</p>
      <div class="par-contacts" style="margin-top:28px;padding:22px;background:var(--cream2);border-left:3px solid var(--gold);">
        <div style="font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--grey);margin-bottom:12px;">Sazinies ar mani</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
          <a href="mailto:katrinablazkova06@gmail.com" style="font-size:13px;color:var(--ink);text-decoration:none;display:flex;align-items:center;gap:10px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            katrinablazkova06@gmail.com
          </a>
          <a href="tel:+37122322130" style="font-size:13px;color:var(--ink);text-decoration:none;display:flex;align-items:center;gap:10px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--gold)" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.08 9.81a19.79 19.79 0 01-3.07-8.68A2 2 0 012 .95h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 8.86a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>
            +371 22 322 130
          </a>
        </div>
      </div>
      <div style="margin-top:28px;display:flex;gap:12px;flex-wrap:wrap;">
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt sesiju</a>
        <a href="/4pt/blazkova/lumina/Lumina/portfolio.php" class="btn-outline">Skatīt portfolio</a>
      </div>
    </div>

  </div>
</section>

<!-- STATS BAR -->
<div class="par-stats-bar">
  <div class="par-stat reveal">
    <div class="par-stat-num">500+</div>
    <div class="par-stat-label">Fotosesijas</div>
  </div>
  <div class="par-stat-divider"></div>
  <div class="par-stat reveal reveal-delay-1">
    <div class="par-stat-num">6+</div>
    <div class="par-stat-label">Gadu pieredze</div>
  </div>
  <div class="par-stat-divider"></div>
  <div class="par-stat reveal reveal-delay-2">
    <div class="par-stat-num">100+</div>
    <div class="par-stat-label">Kāzu sesijas</div>
  </div>
  <div class="par-stat-divider"></div>
  <div class="par-stat reveal reveal-delay-3">
    <div class="par-stat-num">LV</div>
    <div class="par-stat-label">Visa Latvija</div>
  </div>
</div>

<!-- PROCESS -->
<section class="par-process">
  <div style="text-align:center;margin-bottom:clamp(40px,6vw,72px);">
    <div class="section-label" style="display:flex;justify-content:center;">Kā strādāju</div>
    <h2 class="section-title" style="margin-top:12px;">Vienkāršs un<br><em style="font-style:italic;color:var(--gold)">personisks process</em></h2>
  </div>
  <div class="par-steps">
    <div class="par-step reveal">
      <div class="par-step-num">01</div>
      <h3 class="par-step-title">Iepazīšanās</h3>
      <p class="par-step-desc">Sazinamies, aprunājamies par taviem vēlmēm, stilu un ideālajiem mirkļiem. Varam satikties vai sazināties tiešsaistē.</p>
    </div>
    <div class="par-step reveal reveal-delay-1">
      <div class="par-step-num">02</div>
      <h3 class="par-step-title">Plānošana</h3>
      <p class="par-step-desc">Kopā izvēlamies lokāciju, laiku un konceptu. Es rūpējos par katru detaļu, lai sesija noritētu lieliski.</p>
    </div>
    <div class="par-step reveal reveal-delay-2">
      <div class="par-step-num">03</div>
      <h3 class="par-step-title">Fotosesija</h3>
      <p class="par-step-desc">Sesija notiek dabiskā, relaksētā atmosfērā. Nekas piespiests — tikai īsti mirkļi un sajūtas.</p>
    </div>
    <div class="par-step reveal reveal-delay-3">
      <div class="par-step-num">04</div>
      <h3 class="par-step-title">Rezultāts</h3>
      <p class="par-step-desc">Rediģētas fotogrāfijas saņem privātā galerijā 2–3 nedēļu laikā. Mūžīgas atmiņas.</p>
    </div>
  </div>
</section>

<!-- QUOTE -->
<div class="par-quote">
  <div class="par-quote-mark">"</div>
  <div class="par-quote-text">Katrā sejā, katrā skatienā un katrā smaidā slēpjas stāsts. Mans uzdevums ir to saglabāt.</div>
  <div class="par-quote-line"></div>
  <div class="par-quote-author">Katrīna Blažkova</div>
</div>

<!-- CTA -->
<section style="padding:clamp(60px,8vw,100px) var(--px);background:var(--ink);position:relative;overflow:hidden;">
  <div style="position:absolute;inset:0;background:url('https://images.unsplash.com/photo-1551636898-47668aa61de2?w=1400&q=80') center/cover;opacity:.1;"></div>
  <div style="position:relative;z-index:1;max-width:560px;">
    <div class="section-label" style="color:var(--gold)">Sāksim kopā</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(34px,5vw,60px);font-weight:300;color:#fff;line-height:1.1;margin:14px 0 20px;">
      Gatavs savam<br><em style="font-style:italic;color:var(--gold-light)">īpašajam mirklim?</em>
    </h2>
    <p style="font-size:14px;color:rgba(255,255,255,.55);line-height:1.8;margin-bottom:32px;max-width:420px;">Sazinies ar mani un kopā veidosim atmiņas, ko glabāsi mūžīgi.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt sesiju</a>
      <a href="mailto:katrinablazkova06@gmail.com" class="btn-outline" style="color:rgba(255,255,255,.7);border-color:rgba(255,255,255,.2);">Rakstīt e-pastu</a>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
