<?php
session_start();
require_once __DIR__ . '/includes/db.php';
$pageTitle = 'Portfolio';
$extraCss = 'portfolio.css';

// Fetch all active portfolio items
$sql = "SELECT * FROM portfolio WHERE aktivs = 1 ORDER BY pievienots DESC";
$result = mysqli_query($savienojums, $sql);
$items = [];
while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

// Get unique categories
$categories = array_unique(array_column($items, 'kategorija'));
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<div class="page-header">
  <div class="section-label">Mūsu darbi</div>
  <h1>Portfolio <em>Galerija</em></h1>
  <p>Katrs kadrs stāsta stāstu — apskatiet mūsu radošo darbu kolekciju.</p>
</div>

<section class="portfolio-section">
  <!-- Filters -->
  <div class="portfolio-filters reveal">
    <button class="filter-btn active" onclick="filterPortfolio('all', this)">Visi</button>
    <?php foreach ($categories as $cat): ?>
    <button class="filter-btn" onclick="filterPortfolio('<?= htmlspecialchars($cat) ?>', this)">
      <?= ucfirst(htmlspecialchars($cat)) ?>
    </button>
    <?php endforeach; ?>
  </div>

  <!-- Grid -->
  <div class="portfolio-masonry" id="portfolioGrid">
    <?php foreach ($items as $i => $item): ?>
    <?php
    $src = !empty($item['attels_url']) ? (filter_var($item['attels_url'], FILTER_VALIDATE_URL) ? $item['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $item['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=800&q=80';
    ?>
    <div class="portfolio-item reveal" data-cat="<?= htmlspecialchars($item['kategorija']) ?>" onclick="openLightbox(<?= $i ?>)">
      <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($item['nosaukums']) ?>">
      <div class="portfolio-overlay">
        <div class="portfolio-cat"><?= htmlspecialchars($item['kategorija']) ?></div>
        <div class="portfolio-name"><?= htmlspecialchars($item['nosaukums']) ?></div>
        <?php if (!empty($item['apraksts'])): ?>
        <div class="portfolio-desc"><?= htmlspecialchars($item['apraksts']) ?></div>
        <?php endif; ?>
      </div>
      <div class="portfolio-num">0<?= $i + 1 ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- CTA -->
<div class="portfolio-cta reveal">
  <div class="section-label" style="display:flex;justify-content:center;">Jūsu stāsts</div>
  <h2 style="font-family:'Cormorant Garamond',serif;font-size:clamp(32px,5vw,56px);font-weight:300;color:var(--ink);margin:14px 0;">Gatavs savai <em style="font-style:italic;color:var(--gold)">fotosesijai?</em></h2>
  <a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" class="btn-primary">Rezervēt sesiju →</a>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
const lbData = <?= json_encode(array_map(function($item) {
  $src = !empty($item['attels_url']) ? (filter_var($item['attels_url'], FILTER_VALIDATE_URL) ? $item['attels_url'] : '/4pt/blazkova/lumina/Lumina/uploads/portfolio/' . $item['attels_url']) : 'https://images.unsplash.com/photo-1519741497674-611481863552?w=1400&q=80';
  return ['src' => $src, 'cat' => $item['kategorija'], 'title' => $item['nosaukums']];
}, $items)) ?>;

initLightbox(lbData);

function filterPortfolio(cat, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.portfolio-item').forEach(item => {
    const show = cat === 'all' || item.dataset.cat === cat;
    item.style.transition = 'opacity .4s,transform .4s';
    item.style.opacity = show ? '1' : '.1';
    item.style.transform = show ? 'scale(1)' : 'scale(.97)';
    item.style.pointerEvents = show ? 'all' : 'none';
  });
}
</script>
