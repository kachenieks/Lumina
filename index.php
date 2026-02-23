<?php
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Sākums';
$extraCss = 'homepage.css';

// Fetch portfolio items for hero gallery (6 items)
$portfolio = mysqli_query($savienojums, "SELECT * FROM portfolio WHERE aktivs = 1 ORDER BY id ASC LIMIT 6");
$portfolioItems = [];
while ($row = mysqli_fetch_assoc($portfolio)) $portfolioItems[] = $row;

// Fetch products (4 items)
$preces = mysqli_query($savienojums, "SELECT * FROM preces WHERE aktivs = 1 ORDER BY bestseller DESC, id ASC LIMIT 4");
$products = [];
while ($row = mysqli_fetch_assoc($preces)) $products[] = $row;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-img"></div>
  <div class="hero-content">
    <div class="hero-label">✦ Profesionālā Fotogrāfija</div>
    <h1 class="hero-title">Mirkļi, kas<br><em>paliek mūžīgi</em></h1>
    <p class="hero-sub">Uztverams ar māksliniecisko redzējumu un emociju dziļumu</p>
    <div class="hero-cta">
      <a href="/4pt/blazkova/lumina/Lumina/portfolio.php" class="btn-primary">Skatīt Portfolio</a>
      <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-outline">Rezervēt Sesiju</a>
    </div>
  </div>
  <div class="hero-scroll"><span>Ritināt</span><div class="scroll-line"></div></div>
</section>

<!-- TICKER -->
<div class="ticker-wrap"><div class="ticker">
  <div class="ticker-item">Kāzu Fotogrāfija</div>
  <div class="ticker-item">Portretu Sesijas</div>
  <div class="ticker-item">Komerciālā Fotogrāfija</div>
  <div class="ticker-item">Ģimenes Fotosesijas</div>
  <div class="ticker-item">Pasākumu Dokumentācija</div>
  <div class="ticker-item">Produktu Fotogrāfija</div>
  <div class="ticker-item">Kāzu Fotogrāfija</div>
  <div class="ticker-item">Portretu Sesijas</div>
  <div class="ticker-item">Komerciālā Fotogrāfija</div>
  <div class="ticker-item">Ģimenes Fotosesijas</div>
  <div class="ticker-item">Pasākumu Dokumentācija</div>
  <div class="ticker-item">Produktu Fotogrāfija</div>
</div></div>

<!-- ABOUT -->
<section id="about" class="about-section">
  <div class="about-text reveal">
    <div class="section-label">Par LUMINA</div>
    <h2 class="section-title">Fotogrāfija ir<br><em style="font-style:italic;color:var(--gold)">sajūtu valoda.</em></h2>
    <p>LUMINA ir vairāk nekā fotogrāfijas studija — tā ir vieta, kur dzimst māksla un saglabājas atmiņas. Ar vairāk nekā 10 gadu pieredzi, mēs radām vizuālus stāstus, kas runā bez vārdiem.</p>
    <p>Mūsu filozofija balstās uz autentiskumu, estētiku un dziļu izpratni par katru klientu. Katrs kadrs ir rūpīgi komponēts, katrs mirklis — mūžīgs.</p>
    <a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php" class="about-link">Uzzināt vairāk <span>→</span></a>
  </div>
  <div class="about-images reveal reveal-delay-2">
    <div class="about-img-main"></div>
    <div class="about-img-secondary"></div>
    <div class="about-stats">
      <div class="stat-num">800+</div>
      <div class="stat-label">Veiksmīgas sesijas</div>
    </div>
  </div>
</section>

<!-- PORTFOLIO PREVIEW -->
<div class="portfolio-preview-section">
  <div class="portfolio-preview-header reveal">
    <div>
      <div class="section-label">Mūsu Darbi</div>
      <h2 class="section-title">Portfolio Galerija</h2>
    </div>
    <a href="/4pt/blazkova/lumina/Lumina/portfolio.php" class="btn-outline">Skatīt visu →</a>
  </div>
  <div class="portfolio-preview-grid" id="portfolioGrid">
    <?php foreach ($portfolioItems as $i => $item): ?>
    <div class="portfolio-item reveal reveal-delay-<?= ($i % 3) + 1 ?>" onclick="openLightbox(<?= $i ?>)">
      <?php if (!empty($item['attels_url']) && filter_var($item['attels_url'], FILTER_VALIDATE_URL)): ?>
        <img src="<?= htmlspecialchars($item['attels_url']) ?>" alt="<?= htmlspecialchars($item['nosaukums']) ?>">
      <?php elseif (!empty($item['attels_url'])): ?>
        <img src="/4pt/blazkova/lumina/Lumina/uploads/portfolio/<?= htmlspecialchars($item['attels_url']) ?>" alt="<?= htmlspecialchars($item['nosaukums']) ?>">
      <?php else: ?>
        <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80" alt="">
      <?php endif; ?>
      <div class="portfolio-overlay">
        <div class="portfolio-cat"><?= htmlspecialchars($item['kategorija']) ?></div>
        <div class="portfolio-name"><?= htmlspecialchars($item['nosaukums']) ?></div>
      </div>
      <div class="portfolio-num">0<?= $i + 1 ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- QUOTE -->
<div class="quote-banner reveal">
  <div class="quote-text">"Fotogrāfija ir veids, kā sajust, pieskarties, mīlēt. Tas, ko esat uzņēmis, ir uztverams uz visiem laikiem."</div>
  <div class="quote-line"></div>
  <div class="quote-author">— LUMINA Studija</div>
</div>

<!-- SERVICES PREVIEW -->
<section class="services-preview">
  <div class="reveal">
    <div class="section-label">Pakalpojumi</div>
    <h2 class="section-title">Jūsu Stāsta <em style="font-style:italic;color:var(--gold)">Iemūžināšana</em></h2>
  </div>
  <div class="services-grid">
    <div class="service-card reveal reveal-delay-1" onclick="window.location='/4pt/blazkova/lumina/Lumina/pakalpojumi.php'">
      <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=700&q=80" alt="">
      <div class="service-line"></div>
      <div class="service-content">
        <div class="service-price">No €600</div>
        <div class="service-name">Kāzu Fotogrāfija</div>
        <div class="service-desc">Iemūžiniet savu īpašo dienu svarīgākos mirkļus ar elegantu un profesionālu pieeju.</div>
        <div class="service-arrow">Uzzināt vairāk <span>→</span></div>
      </div>
    </div>
    <div class="service-card reveal reveal-delay-2" onclick="window.location='/4pt/blazkova/lumina/Lumina/pakalpojumi.php'">
      <img src="https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?w=700&q=80" alt="">
      <div class="service-line"></div>
      <div class="service-content">
        <div class="service-price">No €150</div>
        <div class="service-name">Ģimenes Fotosesija</div>
        <div class="service-desc">Jautra un relaksējoša sesija visai ģimenei. Dabas vai studijas vide pēc jūsu izvēles.</div>
        <div class="service-arrow">Uzzināt vairāk <span>→</span></div>
      </div>
    </div>
    <div class="service-card reveal reveal-delay-3" onclick="window.location='/4pt/blazkova/lumina/Lumina/pakalpojumi.php'">
      <img src="https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?w=700&q=80" alt="">
      <div class="service-line"></div>
      <div class="service-content">
        <div class="service-price">No €85</div>
        <div class="service-name">Portretu Fotosesija</div>
        <div class="service-desc">Profesionāla portretu fotosesija, kas izceļ jūsu personību.</div>
        <div class="service-arrow">Uzzināt vairāk <span>→</span></div>
      </div>
    </div>
  </div>
</section>

<!-- SHOP PREVIEW -->
<section class="shop-preview">
  <div class="reveal" style="display:flex;justify-content:space-between;align-items:flex-end;flex-wrap:wrap;gap:18px;margin-bottom:52px;">
    <div>
      <div class="section-label">Veikals</div>
      <h2 class="section-title">Mākslas Darbi <em style="font-style:italic;color:var(--gold)">Jūsu Telpai</em></h2>
    </div>
    <a href="/4pt/blazkova/lumina/Lumina/veikals.php" class="btn-outline">Visi produkti →</a>
  </div>
  <div class="shop-grid">
    <?php foreach ($products as $i => $p): ?>
    <div class="product-card reveal reveal-delay-<?= ($i % 3) + 1 ?>" onclick="window.location='/4pt/blazkova/lumina/Lumina/veikals.php?prece=<?= $p['id'] ?>'">
      <div class="product-img">
        <?php
        $imgSrc = !empty($p['attels_url']) ? (filter_var($p['attels_url'], FILTER_VALIDATE_URL) ? $p['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/preces/' . $p['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=500&q=80';
        ?>
        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($p['nosaukums']) ?>">
        <?php if ($p['bestseller']): ?><div class="product-tag">Bestseller</div><?php endif; ?>
        <div class="product-overlay">
          <button class="add-cart-btn" onclick="event.stopPropagation();addToCartAjax(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nosaukums']) ?>')">Pievienot →</button>
        </div>
      </div>
      <div class="product-name"><?= htmlspecialchars($p['nosaukums']) ?></div>
      <div class="product-price">€<?= number_format($p['cena'], 2) ?></div>
      <div class="product-sub"><?= htmlspecialchars($p['kategorija']) ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- BOOKING CTA -->
<div class="booking-cta reveal">
  <div class="booking-cta-inner">
    <div class="section-label" style="color:var(--gold)">Rezervācijas</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(36px,5vw,64px);font-weight:300;color:var(--white);line-height:1.1;margin:14px 0;">Gatavs savai<br><em style="font-style:italic;color:var(--gold-light)">sapņu sesijā?</em></h2>
    <p style="font-size:14px;color:rgba(255,255,255,.65);max-width:420px;line-height:1.8;margin-bottom:36px;">Izvēlieties datumu un laiku — mēs atbildēsim 24 stundu laikā un sāksim plānot jūsu ideālo fotosesiju.</p>
    <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt Tagad →</a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// Portfolio lightbox data
const lbData = <?= json_encode(array_map(function($item) {
  $src = !empty($item['attels_url']) ? (filter_var($item['attels_url'], FILTER_VALIDATE_URL) ? $item['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $item['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=1400&q=80';
  return ['src' => $src, 'cat' => $item['kategorija'], 'title' => $item['nosaukums']];
}, $portfolioItems)) ?>;

initLightbox(lbData);

function addToCartAjax(id, name) {
  fetch('/4pt/blazkova/lumina/Lumina/cart.php?action=add&id=' + id)
    .then(r => r.json())
    .then(data => {
      document.getElementById('cartCount').textContent = data.count;
      showToast(name + ' pievienots grozam ✓', 'success');
    });
}
</script>
