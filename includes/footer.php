<footer id="contact">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">LUMIN<span>A</span></div>
      <div class="footer-desc">Profesionālā fotogrāfija, kas uztver jūsu dzīves skaistākos brīžus ar māksliniecisko redzējumu.</div>
      <div class="footer-social">
        <a class="social-btn" href="#">ig</a>
        <a class="social-btn" href="#">fb</a>
        <a class="social-btn" href="#">yt</a>
        <a class="social-btn" href="#">in</a>
      </div>
    </div>
    <div>
      <div class="footer-heading">Ātrās saites</div>
      <ul class="footer-links">
        <li><a href="/4pt/blazkova/lumina/Lumina/index.php">Sākums</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/portfolio.php">Portfelis</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php">Pakalpojumi</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/veikals.php">Veikals</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/galerijas.php">Galerijas</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-heading">Pakalpojumi</div>
      <ul class="footer-links">
        <li><a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php">Kāzu fotogrāfija</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php">Portretu sesijas</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php">Pasākumu fotogrāfija</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/pakalpojumi.php">Ģimenes fotosesijas</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/rezervacija.php" style="color:var(--gold)">Rezervēt sesiju →</a></li>
      </ul>
    </div>
    <div>
      <div class="footer-heading">Kontakti</div>
      <div class="footer-contact">
        📧 info@lumina.lv<br>
        📞 +371 20 000 000<br>
        📍 Brīvības iela 123<br>
        Rīga, LV-1001, Latvija
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© 2026 LUMINA. Visas tiesības aizsargātas.</div>
    <div class="footer-copy">Privātuma politika · Lietošanas noteikumi</div>
  </div>
</footer>

<div class="lightbox" id="lightbox">
  <button class="lightbox-close" onclick="closeLightbox()">×</button>
  <button class="lightbox-nav lightbox-prev" onclick="navLightbox(-1)">‹</button>
  <img id="lightboxImg" src="" alt="">
  <button class="lightbox-nav lightbox-next" onclick="navLightbox(1)">›</button>
  <div style="position:absolute;bottom:34px;left:50%;transform:translateX(-50%);text-align:center;">
    <div id="lightboxCat" style="font-size:9px;letter-spacing:4px;text-transform:uppercase;color:var(--gold);margin-bottom:6px;"></div>
    <div id="lightboxTitle" style="font-family:'Cormorant Garamond',serif;font-size:21px;font-weight:300;color:#fff;"></div>
  </div>
</div>

<script src="/4pt/blazkova/lumina/Lumina/js/global.js"></script>
<?php if (isset($extraJs)): ?>
<script src="/4pt/blazkova/lumina/Lumina/js/<?= $extraJs ?>"></script>
<?php endif; ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('keydown', e => {
      if (e.key === 'ArrowLeft') navLightbox(-1);
      if (e.key === 'ArrowRight') navLightbox(1);
    });
  });
</script>
</body>
</html>
