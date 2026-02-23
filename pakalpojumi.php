<?php require_once 'header.php'; ?>

<style>
.services-hero{padding:180px 64px 100px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);border-bottom:1px solid var(--grey3);text-align:center;}
.service-detail{padding:100px 64px;display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center;border-bottom:1px solid var(--grey3);background:var(--cream);}
.service-detail.white{background:var(--white);}
.service-detail.reverse .service-img{order:2;}
.service-detail.reverse .service-info{order:1;}
.service-img{position:relative;overflow:hidden;}
.service-img-bg{width:100%;height:420px;background-size:cover;background-position:center;transition:transform .7s;}
.service-img:hover .service-img-bg{transform:scale(1.04);}
.service-img-badge{position:absolute;bottom:20px;left:20px;background:var(--gold);color:var(--white);padding:10px 18px;font-size:10px;letter-spacing:3px;text-transform:uppercase;font-weight:600;z-index:2;}
.service-info h2{font-family:'Cormorant Garamond',serif;font-size:42px;font-weight:300;color:var(--ink);margin:12px 0 20px;line-height:1.1;}
.service-info p{font-size:14px;line-height:1.9;color:var(--grey);margin-bottom:14px;}
.service-includes{margin:24px 0;}
.service-includes-title{font-size:10px;letter-spacing:3px;text-transform:uppercase;color:var(--grey2);margin-bottom:12px;}
.include-item{display:flex;align-items:flex-start;gap:10px;margin-bottom:10px;font-size:13px;color:var(--ink2);line-height:1.5;}
.include-item::before{content:'✦';color:var(--gold);font-size:8px;flex-shrink:0;margin-top:4px;}
.price-box{background:var(--cream2);border-left:3px solid var(--gold);padding:20px 24px;margin:24px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.price-from{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);}
.price-amount{font-family:'Cormorant Garamond',serif;font-size:38px;color:var(--gold);font-weight:300;}
.packages-section{padding:100px 64px;background:var(--cream2);}
.packages-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:2px;margin-top:52px;}
.package-card{background:var(--white);padding:44px 34px;position:relative;transition:transform .3s,box-shadow .3s;}
.package-card:hover{transform:translateY(-6px);box-shadow:0 20px 60px var(--shadow2);}
.package-card.featured{background:var(--ink);color:var(--white);}
.package-card.featured .pkg-name,.package-card.featured .pkg-price{color:var(--white);}
.package-card.featured .pkg-feature{color:rgba(255,255,255,.7);}
.package-card.featured .pkg-duration{color:rgba(255,255,255,.45);}
.featured-badge{position:absolute;top:-14px;left:50%;transform:translateX(-50%);background:var(--gold);color:var(--white);padding:4px 16px;font-size:9px;letter-spacing:3px;text-transform:uppercase;white-space:nowrap;}
.pkg-type{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:var(--gold);margin-bottom:10px;}
.pkg-name{font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:300;color:var(--ink);margin-bottom:8px;}
.pkg-price{font-family:'Cormorant Garamond',serif;font-size:48px;font-weight:300;color:var(--gold);line-height:1;margin-bottom:6px;}
.pkg-duration{font-size:11px;color:var(--grey);margin-bottom:24px;}
.pkg-divider{width:40px;height:1px;background:var(--gold-border);margin:20px 0;}
.pkg-feature{font-size:13px;color:var(--ink2);margin-bottom:10px;display:flex;align-items:center;gap:8px;}
.pkg-feature::before{content:'✓';color:var(--gold);font-size:11px;font-weight:700;flex-shrink:0;}
.faq-section{padding:100px 64px;background:var(--white);}
.faq-item{border-bottom:1px solid var(--grey3);padding:24px 0;cursor:pointer;}
.faq-q{display:flex;justify-content:space-between;align-items:center;gap:20px;}
.faq-q h3{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;color:var(--ink);}
.faq-toggle{font-size:20px;color:var(--gold);transition:transform .3s;flex-shrink:0;}
.faq-item.open .faq-toggle{transform:rotate(45deg);}
.faq-a{font-size:14px;color:var(--grey);line-height:1.8;max-height:0;overflow:hidden;transition:max-height .5s ease,padding .3s;}
.faq-item.open .faq-a{max-height:200px;padding-top:14px;}
.cta-banner{padding:80px 64px;background:var(--ink);text-align:center;}
.cta-banner h2{font-family:'Cormorant Garamond',serif;font-size:48px;font-weight:300;color:var(--white);margin-bottom:16px;}
.cta-banner p{font-size:14px;color:rgba(255,255,255,.5);max-width:460px;margin:0 auto 32px;}
@media(max-width:900px){
  .service-detail{grid-template-columns:1fr;padding:60px 22px;gap:36px;}
  .service-detail.reverse .service-img,.service-detail.reverse .service-info{order:unset;}
  .packages-grid{grid-template-columns:1fr;gap:16px;}
  .faq-section,.packages-section{padding:60px 22px;}
  .services-hero{padding:120px 22px 60px;}
  .cta-banner{padding:60px 22px;}
}
</style>

<!-- HERO -->
<div class="services-hero">
  <div class="section-label">Pakalpojumi</div>
  <h1 class="section-title" style="margin-bottom:16px;">Profesionāla Fotogrāfija<br><em style="font-style:italic;color:var(--gold)">Katram Notikumam</em></h1>
  <p style="font-size:14px;color:var(--grey);max-width:560px;margin:0 auto 30px;line-height:1.8;">No intīmām portretu sesijām līdz grandiozām kāzu ceremonijām — LUMINA iemūžina katru nozīmīgo mirkli jūsu dzīvē.</p>
  <a href="rezervet.php" class="btn-primary">Rezervēt Sesiju →</a>
</div>

<!-- WEDDING -->
<div id="kazas" class="service-detail white">
  <div class="service-img reveal">
    <div class="service-img-bg" style="background-image:url('https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80');"></div>
    <div class="service-img-badge">No €600</div>
  </div>
  <div class="service-info reveal reveal-delay-1">
    <div class="section-label">Kāzu Pakalpojumi</div>
    <h2>Kāzu Fotogrāfija</h2>
    <p>Jūsu kāzu diena ir viens no dzīves svarīgākajiem notikumiem. Mēs esam šeit, lai iemūžinātu katru smeidu, katru asaru, katru svinīgo mirkli ar elegantu māksliniecisko pieeju.</p>
    <p>Mūsu kāzu pakalpojums ietver pilnu dienas dokumentāciju — no gatavojuma līdz ballītes beidzamajai dejai.</p>
    <div class="service-includes">
      <div class="service-includes-title">Iekļauts paketē</div>
      <div class="include-item">Pilna kāzu dienas dokumentācija (8–12 h)</div>
      <div class="include-item">200–400 apstrādātas fotogrāfijas</div>
      <div class="include-item">Privāta online galerija 6 mēneši</div>
      <div class="include-item">Iespēja iegādāties drukas darbus</div>
      <div class="include-item">Elektroniski fails augstā kvalitātē</div>
    </div>
    <div class="price-box">
      <div><div class="price-from">Cenas no</div><div class="price-amount">€600</div></div>
      <a href="rezervet.php?pakalpojums=kazas" class="btn-primary">Rezervēt</a>
    </div>
  </div>
</div>

<!-- FAMILY -->
<div id="gimene" class="service-detail reverse">
  <div class="service-info reveal">
    <div class="section-label">Ģimenes Sesijas</div>
    <h2>Ģimenes Fotosesija</h2>
    <p>Dabiskas, jautras un sirsnīgas fotosesijas visai ģimenei. Mēs radām ērtu atmosfēru, kurā visi jūtas brīvi un dabīgi, lai iegūtu autentiskus un skaistus attēlus.</p>
    <p>Piedāvājam sesijas dabā, studijā vai jūsu mājās — jūsu izvēle!</p>
    <div class="service-includes">
      <div class="service-includes-title">Iekļauts paketē</div>
      <div class="include-item">2 stundu fotosesija</div>
      <div class="include-item">50–80 apstrādātas fotogrāfijas</div>
      <div class="include-item">Privāta online galerija 3 mēneši</div>
      <div class="include-item">10% atlaide drukas darbiem</div>
    </div>
    <div class="price-box">
      <div><div class="price-from">Cenas no</div><div class="price-amount">€150</div></div>
      <a href="rezervet.php?pakalpojums=gimene" class="btn-primary">Rezervēt</a>
    </div>
  </div>
  <div class="service-img reveal reveal-delay-1">
    <div class="service-img-bg" style="background-image:url('https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=900&q=80');"></div>
    <div class="service-img-badge">No €150</div>
  </div>
</div>

<!-- PORTRAIT -->
<div id="portreti" class="service-detail white">
  <div class="service-img reveal">
    <div class="service-img-bg" style="background-image:url('https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=900&q=80');"></div>
    <div class="service-img-badge">No €85</div>
  </div>
  <div class="service-info reveal reveal-delay-1">
    <div class="section-label">Portretu Sesijas</div>
    <h2>Portretu Fotosesija</h2>
    <p>Profesionāli portreti, kas izceļ jūsu personību, pārliecību un unikalitāti. Lieliski piemēroti LinkedIn profilam, CV vai personīgam lietojumam.</p>
    <div class="service-includes">
      <div class="service-includes-title">Iekļauts paketē</div>
      <div class="include-item">1 stundas fotosesija studijā</div>
      <div class="include-item">20–30 apstrādātas fotogrāfijas</div>
      <div class="include-item">Privāta galerija 1 mēnesis</div>
      <div class="include-item">2 tērpu maiņas</div>
    </div>
    <div class="price-box">
      <div><div class="price-from">Cenas no</div><div class="price-amount">€85</div></div>
      <a href="rezervet.php?pakalpojums=portreti" class="btn-primary">Rezervēt</a>
    </div>
  </div>
</div>

<!-- EVENTS -->
<div id="pasakumi" class="service-detail reverse">
  <div class="service-info reveal">
    <div class="section-label">Pasākumu Fotogrāfija</div>
    <h2>Korporatīvie Pasākumi</h2>
    <p>Konferences, produktu prezentācijas, korporatīvās ballītes, izstādes — dokumentējam visus nozīmīgos biznesa notikumus profesionālā kvalitātē.</p>
    <div class="service-includes">
      <div class="service-includes-title">Iekļauts paketē</div>
      <div class="include-item">Pilna pasākuma dokumentācija</div>
      <div class="include-item">Apstrādātas foto 72 stundu laikā</div>
      <div class="include-item">Korporatīvā licence izmantošanai</div>
      <div class="include-item">Rēķins uzņēmumam</div>
    </div>
    <div class="price-box">
      <div><div class="price-from">Cenas no</div><div class="price-amount">€300</div></div>
      <a href="rezervet.php?pakalpojums=pasakumi" class="btn-primary">Rezervēt</a>
    </div>
  </div>
  <div class="service-img reveal reveal-delay-1">
    <div class="service-img-bg" style="background-image:url('https://images.unsplash.com/photo-1511578314322-379afb476865?w=900&q=80');"></div>
    <div class="service-img-badge">No €300</div>
  </div>
</div>

<!-- PACKAGES -->
<div class="packages-section">
  <div class="reveal" style="text-align:center;">
    <div class="section-label" style="justify-content:center;display:flex;">Cenu plāni</div>
    <h2 class="section-title">Kāzu Paketes</h2>
    <p style="font-size:14px;color:var(--grey);margin-top:14px;">Izvēlieties paketi, kas vislabāk atbilst jūsu vajadzībām</p>
  </div>
  <div class="packages-grid">
    <div class="package-card reveal reveal-delay-1">
      <div class="pkg-type">Pamata</div>
      <div class="pkg-name">Silver</div>
      <div class="pkg-price">€600</div>
      <div class="pkg-duration">8 stundas · 1 fotogrāfs</div>
      <div class="pkg-divider"></div>
      <div class="pkg-feature">200 apstrādātas foto</div>
      <div class="pkg-feature">Online galerija 3 mēn.</div>
      <div class="pkg-feature">Digitālie faili</div>
      <div class="pkg-feature">Piegāde 2 nedēļās</div>
      <a href="rezervet.php" class="btn-outline" style="width:100%;text-align:center;margin-top:24px;display:block;box-sizing:border-box;">Izvēlēties</a>
    </div>
    <div class="package-card featured reveal reveal-delay-2">
      <div class="featured-badge">Populārākais</div>
      <div class="pkg-type" style="color:var(--gold-light)">Premium</div>
      <div class="pkg-name">Gold</div>
      <div class="pkg-price">€950</div>
      <div class="pkg-duration">10 stundas · 2 fotogrāfi</div>
      <div class="pkg-divider" style="background:rgba(255,255,255,.15);"></div>
      <div class="pkg-feature">350 apstrādātas foto</div>
      <div class="pkg-feature">Online galerija 6 mēn.</div>
      <div class="pkg-feature">USB atmiņas karte</div>
      <div class="pkg-feature">30×40 druka komplektā</div>
      <div class="pkg-feature">Videoklips 3 min.</div>
      <a href="rezervet.php" class="btn-primary" style="width:100%;text-align:center;margin-top:24px;display:block;box-sizing:border-box;">Izvēlēties</a>
    </div>
    <div class="package-card reveal reveal-delay-3">
      <div class="pkg-type">Luksusa</div>
      <div class="pkg-name">Platinum</div>
      <div class="pkg-price">€1500</div>
      <div class="pkg-duration">Visa diena · 2 fotogrāfi + video</div>
      <div class="pkg-divider"></div>
      <div class="pkg-feature">500+ apstrādātas foto</div>
      <div class="pkg-feature">Online galerija 12 mēn.</div>
      <div class="pkg-feature">Luksusa fotogrāmata</div>
      <div class="pkg-feature">Pilns videostāsts</div>
      <div class="pkg-feature">Pirms kāzu sesija</div>
      <a href="rezervet.php" class="btn-outline" style="width:100%;text-align:center;margin-top:24px;display:block;box-sizing:border-box;">Izvēlēties</a>
    </div>
  </div>
</div>

<!-- FAQ -->
<div class="faq-section">
  <div class="reveal"><div class="section-label">Biežāk uzdotie jautājumi</div><h2 class="section-title" style="margin-bottom:40px;">FAQ</h2></div>
  <?php
  $faqs = [
    ['q'=>'Cik laika iepriekš jārezervē?','a'=>'Kāzām iesakām rezervēt vismaz 6–12 mēnešus iepriekš. Portretu un ģimenes sesijām parasti pietiek ar 2–4 nedēļu iepriekšēju pieteikumu.'],
    ['q'=>'Kad es saņemšu savas fotogrāfijas?','a'=>'Kāzu sesijām — 3–4 nedēļas. Portretu un ģimenes sesijām — 7–10 darba dienas. Steidzamos gadījumos iespējama ātrāka piegāde par papildus samaksu.'],
    ['q'=>'Ko darīt sliktā laika gadījumā?','a'=>'Laikapstākļu dēļ varam pārcelt sesiju bez papildus maksas. Daudzreiz lietus vai mākoņains laiks rada unikālu atmosfēru — mēs esam gatavi strādāt jebkuros apstākļos!'],
    ['q'=>'Vai jūs strādājat ārpus Rīgas?','a'=>'Jā! Strādājam visā Latvijā un arī ārzemēs. Ceļa izmaksas tiek pievienotas cenai atkarībā no attāluma.'],
    ['q'=>'Kā notiek maksājums?','a'=>'Rezervācijas brīdī tiek samaksāts 30% avanss, atlikums — pēc sesijas. Pieņemam bankas pārskaitījumu, Revolut un skaidru naudu.'],
  ];
  foreach($faqs as $i=>$faq): ?>
  <div class="faq-item" onclick="this.classList.toggle('open')">
    <div class="faq-q">
      <h3><?= htmlspecialchars($faq['q']) ?></h3>
      <span class="faq-toggle">+</span>
    </div>
    <div class="faq-a"><?= htmlspecialchars($faq['a']) ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- CTA BANNER -->
<div class="cta-banner">
  <div class="section-label" style="color:rgba(255,255,255,.4);display:flex;justify-content:center;margin-bottom:16px;">Gatavi sākt?</div>
  <h2>Rezervējiet savu sesiju <em style="font-style:italic;color:var(--gold)">jau šodien</em></h2>
  <p>Pieteikties ir vienkārši — aizpildiet formu un mēs sazināsimies 24 stundu laikā.</p>
  <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
    <a href="rezervet.php" class="btn-primary" style="background:var(--gold);border-color:var(--gold);">Rezervēt Sesiju →</a>
    <a href="kontakti.php" class="btn-outline" style="border-color:rgba(255,255,255,.3);color:rgba(255,255,255,.7);">Uzdot jautājumu</a>
  </div>
</div>

<?php require_once 'footer.php'; ?>