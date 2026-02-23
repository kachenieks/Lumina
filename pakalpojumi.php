<?php
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Pakalpojumi';
$extraCss = 'pakalpojumi.css';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div class="section-label">Ko mēs piedāvājam</div>
  <h1>Mūsu <em>Pakalpojumi</em></h1>
  <p>Katrs pakalpojums ir pielāgots jūsu individuālajām vajadzībām un vīzijai.</p>
</div>

<section class="pakalpojumi-section">

  <!-- Service 1: Wedding -->
  <div class="service-detail reveal">
    <div class="service-detail-img">
      <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=900&q=80" alt="Kāzu fotogrāfija">
      <div class="service-detail-badge">No €600</div>
    </div>
    <div class="service-detail-content">
      <div class="section-label">01 / Kāzas</div>
      <h2 class="section-title">Kāzu <em style="font-style:italic;color:var(--gold)">Fotogrāfija</em></h2>
      <p>Jūsu kāzu diena ir īpašākais brīdis dzīvē. Mēs dokumentējam katru emociju, katru smaidu, katru skatienu ar cieņu un māksliniecisko redzējumu.</p>
      <p>No mīlestības stāstu sesijām pirms kāzām līdz svētku vakara reibinošajiem mirkļiem — mēs esam blakus visu dienu.</p>
      <div class="service-includes">
        <div class="service-include">✦ Pilna dienas dokumentācija</div>
        <div class="service-include">✦ 2 profesionāli fotogrāfi</div>
        <div class="service-include">✦ Privātā online galerija</div>
        <div class="service-include">✦ 400+ apstrādātas fotogrāfijas</div>
        <div class="service-include">✦ USB diskdziņš ar fotogrāfijām</div>
      </div>
      <div style="margin-top:32px;display:flex;gap:14px;flex-wrap:wrap;">
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt →</a>
        <span style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--gold);line-height:1;padding:15px 0;">no €600</span>
      </div>
    </div>
  </div>

  <!-- Service 2: Family -->
  <div class="service-detail reverse reveal">
    <div class="service-detail-img">
      <img src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=900&q=80" alt="Ģimenes fotosesija">
      <div class="service-detail-badge">No €150</div>
    </div>
    <div class="service-detail-content">
      <div class="section-label">02 / Ģimene</div>
      <h2 class="section-title">Ģimenes <em style="font-style:italic;color:var(--gold)">Fotosesija</em></h2>
      <p>Jautra, relaksējoša sesija visai ģimenei — bērniem, vecvecākiem, mājdzīvniekiem. Mēs radām dabiskus mirkļus, kas saglabājas uz mūžu.</p>
      <p>Izvēlieties vidi — mūsu studijā, parkā, pludmalē vai jūsu mājās. Mēs pielāgojamies jūsu vajadzībām.</p>
      <div class="service-includes">
        <div class="service-include">✦ 1-2 stundu sesija</div>
        <div class="service-include">✦ Studija vai dabas vide</div>
        <div class="service-include">✦ 80+ apstrādātas fotogrāfijas</div>
        <div class="service-include">✦ Privātā online galerija</div>
      </div>
      <div style="margin-top:32px;display:flex;gap:14px;flex-wrap:wrap;">
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt →</a>
        <span style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--gold);line-height:1;padding:15px 0;">no €150</span>
      </div>
    </div>
  </div>

  <!-- Service 3: Portrait -->
  <div class="service-detail reveal">
    <div class="service-detail-img">
      <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=900&q=80" alt="Portretu sesija">
      <div class="service-detail-badge">No €85</div>
    </div>
    <div class="service-detail-content">
      <div class="section-label">03 / Portreti</div>
      <h2 class="section-title">Portretu <em style="font-style:italic;color:var(--gold)">Fotosesija</em></h2>
      <p>Profesionāla portretu sesija, kas izceļ jūsu personību un autentiskumu. Ideāla LinkedIn profila attēlam, portfelim vai vienkārši skaistam dāvanai sev.</p>
      <div class="service-includes">
        <div class="service-include">✦ 45-60 minūšu sesija</div>
        <div class="service-include">✦ Studija vai izvēlēta vide</div>
        <div class="service-include">✦ 30+ apstrādātas fotogrāfijas</div>
        <div class="service-include">✦ Privātā online galerija</div>
      </div>
      <div style="margin-top:32px;display:flex;gap:14px;flex-wrap:wrap;">
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt →</a>
        <span style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--gold);line-height:1;padding:15px 0;">no €85</span>
      </div>
    </div>
  </div>

  <!-- Service 4: Events -->
  <div class="service-detail reverse reveal">
    <div class="service-detail-img">
      <img src="https://images.unsplash.com/photo-1511578314322-379afb476865?w=900&q=80" alt="Pasākumu fotogrāfija">
      <div class="service-detail-badge">No €300</div>
    </div>
    <div class="service-detail-content">
      <div class="section-label">04 / Pasākumi</div>
      <h2 class="section-title">Pasākumu <em style="font-style:italic;color:var(--gold)">Dokumentācija</em></h2>
      <p>Korporatīvie pasākumi, konferences, jubilejas, dzimšanas dienas un citi notikumi — mēs uztverams katru svarīgo mirkli profesionāli un diskrēti.</p>
      <div class="service-includes">
        <div class="service-include">✦ No 2 līdz 8 stundām</div>
        <div class="service-include">✦ Ātra piegāde 48 stundās</div>
        <div class="service-include">✦ Neierobežots fotogrāfiju skaits</div>
      </div>
      <div style="margin-top:32px;display:flex;gap:14px;flex-wrap:wrap;">
        <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt →</a>
        <span style="font-family:'Cormorant Garamond',serif;font-size:26px;color:var(--gold);line-height:1;padding:15px 0;">no €300</span>
      </div>
    </div>
  </div>

</section>

<!-- CTA -->
<div class="pakalpojumi-cta reveal">
  <div class="section-label" style="display:flex;justify-content:center;margin-bottom:20px;">Sāksim kopā</div>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,64px);font-weight:300;color:var(--ink);margin-bottom:20px;">Jūsu stāsts gaida, kad<br><em style="font-style:italic;color:var(--gold)">to iemūžināsim</em></h2>
  <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary" style="margin-right:14px;">Rezervēt Sesiju →</a>
  <a href="/4pt/blazkova/lumina/Lumina/index.php#contact" class="btn-outline">Sazināties</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
