<?php require_once 'header.php'; ?>

<style>
.portfolio-hero{padding:180px 64px 80px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);border-bottom:1px solid var(--grey3);text-align:center;}
.portfolio-layout{padding:60px 64px;background:var(--cream);}
.portfolio-filters-bar{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:36px;justify-content:center;}
.filter-btn{padding:9px 20px;background:transparent;border:1px solid var(--grey3);font-family:'Montserrat',sans-serif;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--grey);cursor:pointer;transition:all .3s;}
.filter-btn.active,.filter-btn:hover{border-color:var(--gold);color:var(--gold);background:var(--gold-dim);}
.portfolio-masonry{columns:3;column-gap:4px;}
.masonry-item{break-inside:avoid;margin-bottom:4px;position:relative;overflow:hidden;cursor:pointer;}
.masonry-item img{width:100%;display:block;transition:transform .6s;filter:brightness(.97);}
.masonry-item:hover img{transform:scale(1.04);filter:brightness(.7);}
.masonry-overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(28,28,28,.8) 0%,transparent 50%);opacity:0;transition:opacity .4s;display:flex;flex-direction:column;justify-content:flex-end;padding:20px;}
.masonry-item:hover .masonry-overlay{opacity:1;}
.masonry-cat{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:var(--gold-light);margin-bottom:4px;}
.masonry-title{font-family:'Cormorant Garamond',serif;font-size:19px;color:var(--white);}
.lightbox{position:fixed;inset:0;background:rgba(28,28,28,.96);z-index:2000;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .3s;}
.lightbox.open{opacity:1;pointer-events:all;}
.lightbox img{max-width:88vw;max-height:84vh;object-fit:contain;box-shadow:0 24px 80px rgba(0,0,0,.4);}
.lightbox-close{position:absolute;top:22px;right:22px;width:42px;height:42px;background:none;border:1px solid rgba(255,255,255,.2);color:var(--white);cursor:pointer;font-size:17px;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.lightbox-close:hover{border-color:var(--gold);color:var(--gold);}
.lightbox-nav{position:absolute;top:50%;transform:translateY(-50%);width:50px;height:50px;background:none;border:1px solid rgba(255,255,255,.2);color:var(--white);cursor:pointer;font-size:19px;display:flex;align-items:center;justify-content:center;transition:all .2s;}
.lightbox-nav:hover{border-color:var(--gold);color:var(--gold);}
.lightbox-prev{left:34px;}.lightbox-next{right:34px;}
.lightbox-info{position:absolute;bottom:30px;left:50%;transform:translateX(-50%);text-align:center;}
.lightbox-cat{font-size:9px;letter-spacing:4px;text-transform:uppercase;color:var(--gold);margin-bottom:5px;}
.lightbox-title{font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:300;color:var(--white);}
@media(max-width:900px){.portfolio-masonry{columns:2;}.portfolio-layout{padding:40px 22px;}.portfolio-hero{padding:120px 22px 50px;}}
</style>

<div class="portfolio-hero">
  <div class="section-label">Portfolio</div>
  <h1 class="section-title" style="margin-bottom:16px;">Mūsu Darbu <em style="font-style:italic;color:var(--gold)">Galerija</em></h1>
  <p style="font-size:14px;color:var(--grey);max-width:480px;margin:0 auto;">Vairāk nekā 10 gadu mākslinieciskā pieredze katrā attēlā</p>
</div>

<div class="portfolio-layout">
  <div class="portfolio-filters-bar">
    <button class="filter-btn active" onclick="filterMasonry('all',this)">Visi</button>
    <button class="filter-btn" onclick="filterMasonry('kazas',this)">Kāzas</button>
    <button class="filter-btn" onclick="filterMasonry('portreti',this)">Portreti</button>
    <button class="filter-btn" onclick="filterMasonry('pasakumi',this)">Pasākumi</button>
    <button class="filter-btn" onclick="filterMasonry('gimene',this)">Ģimene</button>
    <button class="filter-btn" onclick="filterMasonry('komercials',this)">Komerciālie</button>
  </div>
  
  <?php
  $port = mysqli_query($savienojums,"SELECT * FROM portfolio WHERE `aktīvs`=1 ORDER BY pievienots DESC");
  $port_all = mysqli_fetch_all($port, MYSQLI_ASSOC);
  
  // Add more variety
  $extra = [
    ['attels_url'=>'https://images.unsplash.com/photo-1529636798458-92182e662485?w=800&q=80','kategorija'=>'kazas','nosaukums'=>'Kāzu Ceremonija'],
    ['attels_url'=>'https://images.unsplash.com/photo-1541252260730-0412e8e2108e?w=800&q=80','kategorija'=>'kazas','nosaukums'=>'Romantisks Portrets'],
    ['attels_url'=>'https://images.unsplash.com/photo-1516589178581-6cd7833ae3b2?w=800&q=80','kategorija'=>'kazas','nosaukums'=>'Kāzu Detaļas'],
    ['attels_url'=>'https://images.unsplash.com/photo-1537907510278-4c5e9ad4cc5d?w=800&q=80','kategorija'=>'pasakumi','nosaukums'=>'Nakts Pasākums'],
    ['attels_url'=>'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80','kategorija'=>'komercials','nosaukums'=>'Gastronomija'],
    ['attels_url'=>'https://images.unsplash.com/photo-1465495976277-4387d4b0b4c6?w=800&q=80','kategorija'=>'gimene','nosaukums'=>'Dabas Sesija'],
  ];
  $all_items = array_merge($port_all, $extra);
  ?>
  
  <div class="portfolio-masonry" id="masonryGrid">
    <?php foreach($all_items as $i => $item): ?>
    <div class="masonry-item" data-cat="<?= htmlspecialchars($item['kategorija']) ?>" onclick="openLightbox(<?= $i ?>)">
      <img src="<?= htmlspecialchars($item['attels_url']) ?>" alt="<?= htmlspecialchars($item['nosaukums']) ?>">
      <div class="masonry-overlay">
        <div class="masonry-cat"><?= htmlspecialchars(ucfirst($item['kategorija'])) ?></div>
        <div class="masonry-title"><?= htmlspecialchars($item['nosaukums']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="lightbox" id="lightbox">
  <button class="lightbox-close" onclick="closeLightbox()">×</button>
  <button class="lightbox-nav lightbox-prev" onclick="navLightbox(-1)">‹</button>
  <img id="lightboxImg" src="" alt="">
  <button class="lightbox-nav lightbox-next" onclick="navLightbox(1)">›</button>
  <div class="lightbox-info"><div class="lightbox-cat" id="lightboxCat"></div><div class="lightbox-title" id="lightboxTitle"></div></div>
</div>

<?php require_once 'footer.php'; ?>

<script>
const lbData = <?= json_encode(array_map(fn($i) => [
  'src' => $i['attels_url'],
  'cat' => ucfirst($i['kategorija']),
  'title' => $i['nosaukums']
], $all_items)) ?>;

let curLB = 0;
function openLightbox(i){curLB=i;document.getElementById('lightboxImg').src=lbData[i].src;document.getElementById('lightboxCat').textContent=lbData[i].cat;document.getElementById('lightboxTitle').textContent=lbData[i].title;document.getElementById('lightbox').classList.add('open');document.body.style.overflow='hidden';}
function closeLightbox(){document.getElementById('lightbox').classList.remove('open');document.body.style.overflow='';}
function navLightbox(d){
    const items = document.querySelectorAll('.masonry-item:not([style*="display: none"])');
    const visibleIndices = Array.from(document.querySelectorAll('.masonry-item')).map((el,i)=>el.style.display!=='none'?i:-1).filter(i=>i>=0);
    const pos = visibleIndices.indexOf(curLB);
    const newPos = (pos+d+visibleIndices.length)%visibleIndices.length;
    openLightbox(visibleIndices[newPos]);
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLightbox();if(e.key==='ArrowLeft')navLightbox(-1);if(e.key==='ArrowRight')navLightbox(1);});
function filterMasonry(cat,btn){
    document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.masonry-item').forEach(item=>{
        const show=cat==='all'||item.dataset.cat===cat;
        item.style.display=show?'':'none';
    });
}
</script>