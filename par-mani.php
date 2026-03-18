<?php
session_name('lumina_klient');
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Par mani';
$extraCss  = 'par-mani.css';
include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<div class="page-header pm-hero">
  <div class="pm-hero-img"></div>
  <div class="pm-hero-inner">
    <div class="section-label">Fotogrāfe</div>
    <h1>Par <em>mani</em></h1>
    <p>Katrīna Blažkova — fotogrāfe no Latvijas</p>
  </div>
</div>

<!-- INTRO -->
<section class="pm-intro">
  <div class="pm-intro-grid">
    <div class="pm-photo-wrap reveal">
      <div class="pm-photo">
        <img src="https://images.unsplash.com/photo-1531746020798-e6953c6e8e04?w=800&q=85" alt="Katrīna Blažkova">
      </div>
      <div class="pm-photo-badge">
        <div class="pm-badge-num">500+</div>
        <div class="pm-badge-label">Sesijas</div>
      </div>
    </div>

    <div class="pm-intro-text reveal reveal-delay-1">
      <div class="section-label">Iepazīstieties</div>
      <h2 class="section-title">Sveiki, esmu<br><em style="font-style:italic;color:var(--gold)">Katrīna</em></h2>
      <p>Esmu fotogrāfe no Latvijas, kuras sirds deg par īstiem, autentiskiem mirkļiem. Mana kamera nav tikai instruments — tā ir veids, kā es redzu un saglabāju pasauli ap mums.</p>
      <p>Specializējos kāzu, portretu un ģimenes fotosesijās. Strādājot ar katru klientu, svarīgākais man ir radīt komfortablu atmosfēru, kurā cilvēki jūtas brīvi — tad rodas patiesākās emocijas un skaistākās fotogrāfijas.</p>
      <p>Ticēju, ka labas bildes nerodas no perfektas pozes, bet no īstiem smiekliem, pieskaršanās un mirkļiem, kad esat vienkārši jūs paši.</p>
      <div class="pm-signature">Katrīna Blažkova</div>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="pm-stats-bar">
  <div class="pm-stat reveal">
    <div class="pm-stat-num">500+</div>
    <div class="pm-stat-label">Fotosesijas</div>
  </div>
  <div class="pm-stat-divider"></div>
  <div class="pm-stat reveal reveal-delay-1">
    <div class="pm-stat-num">5+</div>
    <div class="pm-stat-label">Gadi pieredzē</div>
  </div>
  <div class="pm-stat-divider"></div>
  <div class="pm-stat reveal reveal-delay-2">
    <div class="pm-stat-num">200+</div>
    <div class="pm-stat-label">Kāzu sesijas</div>
  </div>
  <div class="pm-stat-divider"></div>
  <div class="pm-stat reveal reveal-delay-3">
    <div class="pm-stat-num">100%</div>
    <div class="pm-stat-label">Ar sirdi</div>
  </div>
</div>

<!-- APPROACH -->
<section class="pm-approach">
  <div class="pm-approach-header reveal" style="text-align:center;max-width:600px;margin:0 auto clamp(40px,5vw,64px);">
    <div class="section-label">Mana pieeja</div>
    <h2 class="section-title">Kā es <em style="font-style:italic;color:var(--gold)">strādāju</em></h2>
  </div>
  <div class="pm-approach-grid">
    <div class="pm-approach-card reveal">
      <div class="pm-approach-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </div>
      <h3>Autentiskums</h3>
      <p>Vislabākās bildes rodas no īstiem mirkļiem. Es nerada poses — es uztver dzīvi tādu, kāda tā ir.</p>
    </div>
    <div class="pm-approach-card reveal reveal-delay-1">
      <div class="pm-approach-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      </div>
      <h3>Rūpīga sagatavošana</h3>
      <p>Katru sesiju rūpīgi plānoju — atrodu labāko gaismu, vietu un laiku, lai rezultāts būtu izcils.</p>
    </div>
    <div class="pm-approach-card reveal reveal-delay-2">
      <div class="pm-approach-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </div>
      <h3>Personīga attieksme</h3>
      <p>Katrs klients saņem pilnu uzmanību. Es ieklausos, saprotu vēlmes un radīšu fotogrāfijas, kas stāsta tieši jūsu stāstu.</p>
    </div>
  </div>
</section>

<!-- GALLERY TEASER -->
<section style="padding:0 0 clamp(60px,8vw,100px);">
  <div class="pm-gallery-grid">
    <div class="pm-gallery-item reveal">
      <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80" alt="Kāzu fotogrāfija">
      <div class="pm-gallery-item-overlay"><span>Kāzu fotogrāfija</span></div>
    </div>
    <div class="pm-gallery-item pm-gallery-tall reveal reveal-delay-1">
      <img src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=800&q=80" alt="Portrets">
      <div class="pm-gallery-item-overlay"><span>Portrets</span></div>
    </div>
    <div class="pm-gallery-item reveal reveal-delay-2">
      <img src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&q=80" alt="Ģimene">
      <div class="pm-gallery-item-overlay"><span>Ģimene</span></div>
    </div>
    <div class="pm-gallery-item reveal reveal-delay-1">
      <img src="https://images.unsplash.com/photo-1551218808-94e220e084d2?w=800&q=80" alt="Kāzas">
      <div class="pm-gallery-item-overlay"><span>Kāzas</span></div>
    </div>
  </div>
</section>

<!-- CTA -->
<div class="pm-cta">
  <div class="pm-cta-inner reveal">
    <div class="section-label" style="color:var(--gold)">Sazināties</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,5vw,56px);font-weight:300;color:#fff;line-height:1.1;margin:14px 0 20px;">Gatavs savai<br><em style="font-style:italic;color:var(--gold-light)">fotosesijai?</em></h2>
    <p style="font-size:14px;color:rgba(255,255,255,.6);max-width:400px;line-height:1.8;margin-bottom:34px;">Sazinies ar mani un kopā plānosim jūsu ideālo sesiju. Atbildu 24 stundu laikā.</p>
    <div style="display:flex;gap:12px;flex-wrap:wrap;">
      <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt sesiju →</a>
      <a href="mailto:katrinablazkova06@gmail.com" class="btn-outline" style="border-color:rgba(255,255,255,.3);color:rgba(255,255,255,.75);">Rakstīt e-pastu</a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
