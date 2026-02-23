<?php require_once 'header.php'; ?>

<style>
.about-hero{padding:180px 64px 100px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;}
.team-section{padding:100px 64px;background:var(--white);}
.team-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;margin-top:52px;}
.team-card{text-align:center;}
.team-img{width:180px;height:180px;border-radius:50%;margin:0 auto 20px;overflow:hidden;background:var(--cream2);border:3px solid var(--cream3);}
.team-img img{width:100%;height:100%;object-fit:cover;}
.team-name{font-family:'Cormorant Garamond',serif;font-size:24px;color:var(--ink);margin-bottom:4px;}
.team-role{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:12px;}
.team-desc{font-size:13px;color:var(--grey);line-height:1.7;}
.values-section{padding:100px 64px;background:var(--cream2);}
.values-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:24px;margin-top:52px;}
.value-card{padding:32px 24px;background:var(--white);border:1px solid var(--grey3);text-align:center;transition:transform .3s,box-shadow .3s;}
.value-card:hover{transform:translateY(-6px);box-shadow:0 12px 40px var(--shadow);}
.value-icon{font-size:36px;margin-bottom:14px;display:block;}
.value-name{font-family:'Cormorant Garamond',serif;font-size:22px;color:var(--ink);margin-bottom:10px;}
.value-desc{font-size:13px;color:var(--grey);line-height:1.7;}
.awards-section{padding:100px 64px;background:var(--white);}
.awards-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:40px;}
.award-item{padding:24px;border:1px solid var(--grey3);display:flex;align-items:center;gap:16px;}
.award-year{font-family:'Cormorant Garamond',serif;font-size:42px;color:var(--gold);font-weight:300;flex-shrink:0;}
.award-name{font-size:14px;font-weight:500;color:var(--ink);margin-bottom:4px;}
.award-org{font-size:11px;color:var(--grey2);}
.stats-section{padding:100px 64px;background:var(--ink);text-align:center;}
.stats-big{display:grid;grid-template-columns:repeat(4,1fr);gap:40px;margin-top:52px;}
.stat-big-num{font-family:'Cormorant Garamond',serif;font-size:72px;font-weight:300;color:var(--gold);line-height:1;}
.stat-big-label{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.45);margin-top:8px;}
@media(max-width:900px){.about-hero{grid-template-columns:1fr;padding:120px 22px 60px;}.team-grid,.values-grid,.awards-grid,.stats-big{grid-template-columns:1fr;}.team-section,.values-section,.awards-section,.stats-section{padding:60px 22px;}}
</style>

<div class="about-hero">
  <div class="reveal">
    <div class="section-label">Par LUMINA</div>
    <h1 class="section-title" style="margin-bottom:24px;">Vairāk nekā<br><em style="font-style:italic;color:var(--gold)">fotogrāfija.</em></h1>
    <p style="font-size:15px;line-height:1.9;color:var(--grey);margin-bottom:18px;">LUMINA studija tika dibināta 2014. gadā ar vienu mērķi — uztvert dzīves skaistākos mirkļus ar māksliniecisko redzējumu un sirsnību.</p>
    <p style="font-size:15px;line-height:1.9;color:var(--grey);">Šobrīd mūsu komandā strādā 5 profesionāli fotogrāfi, un esam fotografējuši vairāk nekā 800 kāzu, ģimenes un korporatīvo sesiju visā Latvijā un ārzemēs.</p>
    <a href="rezervet.php" class="btn-primary" style="margin-top:28px;display:inline-block;">Rezervēt sesiju →</a>
  </div>
  <div class="reveal reveal-delay-2">
    <div style="position:relative;height:480px;">
      <div style="position:absolute;top:0;left:30px;right:0;bottom:80px;background:url('https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800&q=80') center/cover;box-shadow:0 16px 48px var(--shadow);"></div>
      <div style="position:absolute;bottom:0;left:0;width:50%;height:220px;background:url('https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?w=400&q=80') center/cover;border:6px solid var(--white);box-shadow:0 8px 32px var(--shadow);"></div>
      <div style="position:absolute;bottom:80px;right:0;padding:20px 24px;background:var(--white);border-left:3px solid var(--gold);box-shadow:0 4px 20px var(--shadow);width:160px;">
        <div style="font-family:'Cormorant Garamond',serif;font-size:38px;color:var(--gold);line-height:1;">10+</div>
        <div style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);margin-top:4px;">Gadi pieredzē</div>
      </div>
    </div>
  </div>
</div>

<!-- STATS -->
<div class="stats-section">
  <div class="section-label" style="display:flex;justify-content:center;color:rgba(255,255,255,.5);">Mūsu sasniegumi</div>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:42px;font-weight:300;color:var(--white);margin-top:10px;">Cipari runā paši par sevi</h2>
  <div class="stats-big">
    <div class="reveal"><div class="stat-big-num">800+</div><div class="stat-big-label">Fotosesijas</div></div>
    <div class="reveal reveal-delay-1"><div class="stat-big-num">10+</div><div class="stat-big-label">Gadi darbībā</div></div>
    <div class="reveal reveal-delay-2"><div class="stat-big-num">5</div><div class="stat-big-label">Profesionāļi</div></div>
    <div class="reveal reveal-delay-3"><div class="stat-big-num">98%</div><div class="stat-big-label">Apmierināti klienti</div></div>
  </div>
</div>

<!-- TEAM -->
<section class="team-section">
  <div class="reveal"><div class="section-label">Mūsu komanda</div><h2 class="section-title">Cilvēki aiz <em style="font-style:italic;color:var(--gold)">objektīva</em></h2></div>
  <div class="team-grid">
    <div class="team-card reveal reveal-delay-1">
      <div class="team-img"><img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&q=80" alt="Katrīna"></div>
      <div class="team-name">Katrīna Bergmane</div>
      <div class="team-role">Galvenā Fotogrāfe & Dibinātāja</div>
      <div class="team-desc">10+ gadu pieredze kāzu un portretu fotogrāfijā. Mākslas maģistra grāds no LMA.</div>
    </div>
    <div class="team-card reveal reveal-delay-2">
      <div class="team-img"><img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&q=80" alt="Mārtiņš"></div>
      <div class="team-name">Mārtiņš Kalniņš</div>
      <div class="team-role">Pasākumu & Komerciālais Fotogrāfs</div>
      <div class="team-desc">Specializējas korporatīvajos pasākumos un produktu fotogrāfijā. Strādā ar vadošajiem Latvijas uzņēmumiem.</div>
    </div>
    <div class="team-card reveal reveal-delay-3">
      <div class="team-img"><img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&q=80" alt="Sintija"></div>
      <div class="team-name">Sintija Ozoliņa</div>
      <div class="team-role">Ģimenes & Bērnu Fotogrāfe</div>
      <div class="team-desc">Radoša pieeja un bezgalīga pacietība — tās ir Sintijas stiprākās īpašības darbā ar ģimenēm un bērniem.</div>
    </div>
  </div>
</section>

<!-- VALUES -->
<div class="values-section">
  <div class="reveal" style="text-align:center;"><div class="section-label" style="display:flex;justify-content:center;">Mūsu vērtības</div><h2 class="section-title">Ko mēs ticam</h2></div>
  <div class="values-grid">
    <div class="value-card reveal reveal-delay-1"><span class="value-icon">🎨</span><div class="value-name">Māksla</div><div class="value-desc">Katrs kadrs ir mākslas darbs, ne tikai foto.</div></div>
    <div class="value-card reveal reveal-delay-2"><span class="value-icon">💫</span><div class="value-name">Autentiskums</div><div class="value-desc">Mēs uztverams patiesas emocijas, ne pozētu smaidu.</div></div>
    <div class="value-card reveal reveal-delay-3"><span class="value-icon">🤝</span><div class="value-name">Uzticamība</div><div class="value-desc">Katrā sesijā — precizitāte, profesionalitāte un cieņa.</div></div>
    <div class="value-card reveal"><span class="value-icon">✨</span><div class="value-name">Izcilība</div><div class="value-desc">Mēs nepieņemam viduvējību. Tikai labākais ir pietiekami labs.</div></div>
  </div>
</div>

<!-- AWARDS -->
<section class="awards-section">
  <div class="reveal"><div class="section-label">Atzinība</div><h2 class="section-title">Apbalvojumi</h2></div>
  <div class="awards-grid">
    <?php
    $awards = [
      ['year'=>'2025','name'=>'Gada Kāzu Fotogrāfs Latvijā','org'=>'Latvijas Fotogrāfu Asociācija'],
      ['year'=>'2024','name'=>'Labākā Portretu Studija Rīgā','org'=>'City Life Awards'],
      ['year'=>'2023','name'=>'Excellence in Photography','org'=>'Baltic Media Awards'],
      ['year'=>'2022','name'=>'Top 10 Wedding Photographers','org'=>'Junebug Weddings'],
      ['year'=>'2021','name'=>'Radošais Fotogrāfs','org'=>'Latvijas Tirdzniecības kamera'],
      ['year'=>'2020','name'=>'Best Studio Design','org'=>'Interior Design Awards'],
    ];
    foreach($awards as $a): ?>
    <div class="award-item reveal">
      <div class="award-year"><?= $a['year'] ?></div>
      <div>
        <div class="award-name"><?= htmlspecialchars($a['name']) ?></div>
        <div class="award-org"><?= htmlspecialchars($a['org']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require_once 'footer.php'; ?>