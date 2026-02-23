<?php require_once 'header.php'; ?>

<style>
.shop-hero{padding:180px 64px 80px;background:linear-gradient(135deg,var(--cream2) 0%,var(--cream) 100%);border-bottom:1px solid var(--grey3);text-align:center;}
.shop-layout{padding:60px 64px;}
.shop-sidebar{display:grid;grid-template-columns:220px 1fr;gap:40px;align-items:start;}
.filter-box{background:var(--white);padding:28px;border:1px solid var(--grey3);position:sticky;top:100px;}
.filter-title{font-size:9px;letter-spacing:3px;text-transform:uppercase;color:var(--grey2);margin-bottom:16px;}
.filter-option{display:flex;align-items:center;gap:10px;padding:7px 0;cursor:pointer;font-size:13px;color:var(--ink2);transition:color .2s;}
.filter-option:hover,.filter-option.active{color:var(--gold);}
.filter-option input{accent-color:var(--gold);}
.shop-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:28px;}
.product-card{cursor:pointer;transition:transform .3s;}
.product-card:hover{transform:translateY(-4px);}
.product-img{position:relative;overflow:hidden;aspect-ratio:3/4;margin-bottom:16px;background:var(--cream2);}
.product-img img{width:100%;height:100%;object-fit:cover;transition:transform .5s;}
.product-card:hover .product-img img{transform:scale(1.05);}
.product-tag{position:absolute;top:13px;left:13px;padding:5px 11px;background:var(--gold);font-size:9px;letter-spacing:2px;text-transform:uppercase;color:var(--white);font-weight:600;}
.product-overlay{position:absolute;inset:0;background:rgba(28,28,28,.32);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;}
.product-card:hover .product-overlay{opacity:1;}
.add-cart-btn{padding:12px 26px;background:var(--white);color:var(--ink);font-family:'Montserrat',sans-serif;font-size:10px;letter-spacing:2.5px;text-transform:uppercase;font-weight:600;border:none;cursor:pointer;transform:translateY(8px);transition:all .3s;text-decoration:none;display:block;}
.add-cart-btn:hover{background:var(--gold);color:var(--white);}
.product-card:hover .add-cart-btn{transform:translateY(0);}
.product-name{font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:400;color:var(--ink);margin-bottom:3px;}
.product-price{font-size:15px;color:var(--gold);font-weight:500;}
.product-sub{font-size:11px;color:var(--grey2);margin-top:3px;}
.upload-cta{margin-top:52px;padding:50px;background:var(--white);border:1px solid var(--grey3);text-align:center;position:relative;overflow:hidden;}
.upload-cta::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,var(--gold-dim) 0%,transparent 60%);pointer-events:none;}
.modal-body{padding:34px 38px;}
.modal-title{font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:400;color:var(--ink);margin-bottom:13px;}
.modal-meta{font-size:10px;color:var(--gold);letter-spacing:3.5px;text-transform:uppercase;margin-bottom:18px;}
.modal-desc{font-size:14px;line-height:1.8;color:var(--grey);margin-bottom:26px;}
.modal-price{font-family:'Cormorant Garamond',serif;font-size:32px;color:var(--gold);margin-bottom:20px;}
.size-options{display:flex;gap:7px;margin-bottom:20px;flex-wrap:wrap;}
.size-opt{padding:8px 17px;border:1px solid var(--grey3);background:transparent;color:var(--grey);font-family:'Montserrat',sans-serif;font-size:11px;cursor:pointer;transition:all .2s;}
.size-opt:hover,.size-opt.active{border-color:var(--gold);color:var(--gold);background:var(--gold-dim);}
@media(max-width:900px){.shop-sidebar{grid-template-columns:1fr;}.shop-grid{grid-template-columns:repeat(2,1fr);}.shop-layout{padding:40px 22px;}.shop-hero{padding:120px 22px 50px;}.filter-box{position:static;}}
</style>

<div class="shop-hero">
  <div class="section-label">Veikals</div>
  <h1 class="section-title" style="margin-bottom:16px;">Mākslas Darbi <em style="font-style:italic;color:var(--gold)">Jūsu Telpai</em></h1>
  <p style="font-size:14px;color:var(--grey);max-width:480px;margin:0 auto;">Augstas kvalitātes drukas darbi, canvas un personalizēti produkti no LUMINA fotostudijas</p>
</div>

<div class="shop-layout">
  <div class="shop-sidebar">
    <div class="filter-box reveal">
      <div class="filter-title">Kategorijas</div>
      <?php
      $cats = mysqli_fetch_all(mysqli_query($savienojums,"SELECT DISTINCT kategorija FROM preces WHERE `aktīvs`=1"), MYSQLI_ASSOC);
      ?>
      <div class="filter-option active" onclick="filterShop('',this)">Visas preces</div>
      <?php foreach($cats as $cat): ?>
      <div class="filter-option" onclick="filterShop('<?= htmlspecialchars($cat['kategorija']) ?>',this)"><?= htmlspecialchars($cat['kategorija']) ?></div>
      <?php endforeach; ?>
      <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--grey3);">
        <div class="filter-title">Cenu diapazons</div>
        <div style="font-size:12px;color:var(--grey);margin-bottom:8px;">€0 – €200</div>
        <input type="range" min="0" max="200" value="200" style="width:100%;accent-color:var(--gold);" oninput="this.previousElementSibling.textContent='€0 – €'+this.value">
      </div>
    </div>

    <div>
      <?php $preces = mysqli_query($savienojums,"SELECT * FROM preces WHERE `aktīvs`=1 ORDER BY bestseller DESC, id"); ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
        <div style="font-size:11px;color:var(--grey);"><?php $cnt_preces = mysqli_num_rows($preces); mysqli_data_seek($preces,0); echo $cnt_preces; ?> preces</div>
        <select class="form-select" style="width:auto;padding:8px 30px 8px 12px;font-size:11px;">
          <option>Kārtot: Populārākās</option>
          <option>Cena: zemākā</option>
          <option>Cena: augstākā</option>
        </select>
      </div>
      
      <div class="shop-grid" id="shopGrid">
        <?php
        $prod_data = [];
        while($p = mysqli_fetch_assoc($preces)):
          $prod_data[] = $p;
        ?>
        <div class="product-card reveal" data-cat="<?= htmlspecialchars($p['kategorija']) ?>" onclick="openProductModal(<?= count($prod_data)-1 ?>)">
          <div class="product-img">
            <img src="<?= htmlspecialchars($p['attels_url']) ?>" alt="<?= htmlspecialchars($p['nosaukums']) ?>">
            <?php if($p['bestseller']): ?><div class="product-tag">Bestseller</div><?php endif; ?>
            <div class="product-overlay">
              <a href="add_cart.php?id=<?= $p['id'] ?>" class="add-cart-btn" onclick="event.stopPropagation()">Pievienot grozam →</a>
            </div>
          </div>
          <div class="product-name"><?= htmlspecialchars($p['nosaukums']) ?></div>
          <div class="product-price">€<?= number_format($p['cena'],2) ?></div>
          <div class="product-sub"><?= htmlspecialchars($p['kategorija']) ?></div>
        </div>
        <?php endwhile; ?>
      </div>

      <div class="upload-cta reveal">
        <div class="section-label" style="display:flex;justify-content:center;margin-bottom:12px;">Personalizēts Pasūtījums</div>
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:33px;font-weight:300;color:var(--ink);margin-bottom:13px;">Augšupielādējiet savus foto<br><em style="font-style:italic;color:var(--gold)">un pasūtiet drukas darbus</em></h3>
        <p style="font-size:13px;color:var(--grey);max-width:460px;margin:0 auto 26px;line-height:1.8;">Augšupielādējiet fotogrāfijas no savas sesijas un izveidojiet unikālas drukas, canvas vai fotogrāmatas.</p>
        <button class="btn-primary" onclick="openModal('uploadModal')">Augšupielādēt Foto →</button>
      </div>
    </div>
  </div>
</div>

<!-- PRODUCT MODAL -->
<div class="modal-overlay" id="productModal">
  <div class="modal" style="max-width:660px;padding:0;">
    <button class="modal-close" onclick="closeModal('productModal')">×</button>
    <img id="prodModalImg" style="width:100%;height:370px;object-fit:cover;display:block;" src="" alt="">
    <div class="modal-body">
      <div class="modal-meta" id="prodModalCat"></div>
      <div class="modal-title" id="prodModalTitle"></div>
      <div class="modal-desc" id="prodModalDesc"></div>
      <div class="modal-price" id="prodModalPrice"></div>
      <div class="size-options" id="prodSizes"></div>
      <a id="prodAddBtn" href="#" class="btn-primary">Pievienot Grozam →</a>
    </div>
  </div>
</div>

<!-- UPLOAD MODAL -->
<div class="modal-overlay" id="uploadModal">
  <div class="modal" style="max-width:580px;padding:42px;">
    <button class="modal-close" onclick="closeModal('uploadModal')">×</button>
    <div class="section-label">Personalizēts pasūtījums</div>
    <h2 style="font-family:'Cormorant Garamond',serif;font-size:29px;font-weight:300;color:var(--ink);margin-bottom:26px;">Augšupielādēt Foto</h2>
    <div style="border:2px dashed var(--grey3);padding:40px;text-align:center;cursor:pointer;transition:all .3s;margin-bottom:20px;" id="uploadZone">
      <input type="file" style="display:none;" id="uploadInput" multiple accept="image/*" onchange="handleUpload(event)">
      <span style="font-size:36px;display:block;margin-bottom:10px;">📷</span>
      <div style="font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:300;color:var(--ink);margin-bottom:7px;">Ievilciet foto šeit</div>
      <div style="font-size:11px;color:var(--grey);">vai klikšķiniet · JPEG, PNG</div>
    </div>
    <div id="uploadPreviews" style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:20px;"></div>
    <div class="form-group"><label class="form-label">Produkta veids</label>
      <select class="form-select">
        <option>Fotodruka 30×40 cm — €29</option>
        <option>Canvas 50×70 cm — €79</option>
        <option>Fotogrāmata 30×30 cm — €129</option>
      </select>
    </div>
    <div class="form-group"><label class="form-label">Papildu vēlmes</label><textarea class="form-textarea" style="height:76px;" placeholder="Melnbalta versija, piezīmes..."></textarea></div>
    <button class="btn-primary" style="width:100%;" onclick="showToast('Augšupielādēts! Sazināsimies ar jums.','success');closeModal('uploadModal')">Nosūtīt Pasūtījumu →</button>
  </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
const products = <?= json_encode($prod_data) ?>;

function openProductModal(i){
    const p = products[i];
    document.getElementById('prodModalImg').src = p.attels_url;
    document.getElementById('prodModalCat').textContent = p.kategorija;
    document.getElementById('prodModalTitle').textContent = p.nosaukums;
    document.getElementById('prodModalDesc').textContent = p.apraksts || '';
    document.getElementById('prodModalPrice').textContent = '€' + parseFloat(p.cena).toFixed(2);
    document.getElementById('prodAddBtn').href = 'add_cart.php?id=' + p.id;
    const sizes = ['20×30','30×40','40×60','50×70','80×120'];
    document.getElementById('prodSizes').innerHTML = sizes.map((s,j)=>`<button class="size-opt${j===0?' active':''}" onclick="this.parentNode.querySelectorAll('.size-opt').forEach(x=>x.classList.remove('active'));this.classList.add('active')">${s}</button>`).join('');
    openModal('productModal');
}

function filterShop(cat, el){
    document.querySelectorAll('.filter-option').forEach(o=>o.classList.remove('active'));
    el.classList.add('active');
    document.querySelectorAll('.product-card').forEach(card=>{
        const show = !cat || card.dataset.cat === cat;
        card.style.display = show ? '' : 'none';
    });
}

document.getElementById('uploadZone').addEventListener('click',()=>document.getElementById('uploadInput').click());
function handleUpload(e){
    const prev = document.getElementById('uploadPreviews');
    Array.from(e.target.files).forEach(f=>{
        const item = document.createElement('div');
        item.style.cssText = 'aspect-ratio:1;overflow:hidden;background:var(--cream2);';
        prev.appendChild(item);
        const r = new FileReader();
        r.onload = ev => {
            const img = document.createElement('img');
            img.src = ev.target.result;
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
            item.appendChild(img);
        };
        r.readAsDataURL(f);
    });
}
</script>