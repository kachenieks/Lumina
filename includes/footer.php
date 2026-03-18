<footer id="contact">
  <div class="footer-grid">
    <div>
      <div class="footer-logo">LUMIN<span>A</span></div>
      <div class="footer-desc">Katrīnas Blažkovas personīgā fotogrāfijas studija. Katrs kadrs — ar sirdi, sajūtu un māksliniecisko redzējumu.</div>
      <div class="footer-social">
        <a class="social-btn" href="#" aria-label="Instagram">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
        </a>
        <a class="social-btn" href="#" aria-label="Facebook">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
        </a>
        <a class="social-btn" href="#" aria-label="TikTok">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 12a4 4 0 100 8 4 4 0 000-8z"/><path d="M15 2s.5 4 5 5"/><path d="M15 2v14"/></svg>
        </a>
      </div>
    </div>
    <div>
      <div class="footer-heading">Saites</div>
      <ul class="footer-links">
        <li><a href="/4pt/blazkova/lumina/Lumina/index.php">Sākums</a></li>
        <li><a href="/4pt/blazkova/lumina/Lumina/par-mani.php">Par mani</a></li>
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
        <a href="mailto:katrinablazkova06@gmail.com" style="color:rgba(255,255,255,.38);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.38)'">katrinablazkova06@gmail.com</a><br>
        <a href="tel:+37122322130" style="color:rgba(255,255,255,.38);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.38)'"">+371 22 322 130</a><br>
        Latvija
      </div>
    </div>
  </div>
  <div class="footer-bottom">
    <div class="footer-copy">© 2026 Katrīna Blažkova. Visas tiesības aizsargātas.</div>
    <div class="footer-copy" style="display:flex;align-items:center;gap:14px;">
      <a href="/4pt/blazkova/lumina/Lumina/index.php" style="color:rgba(255,255,255,.18);text-decoration:none;transition:color .2s;" onmouseover="this.style.color='rgba(255,255,255,.5)'" onmouseout="this.style.color='rgba(255,255,255,.18)'">Privātuma politika</a>
    </div>
  </div>
</footer>

<div class="lightbox" id="lightbox">
  <button class="lightbox-close" onclick="closeLightbox()">×</button>
  <button class="lightbox-nav lightbox-prev" onclick="navLightbox(-1)">‹</button>
  <img id="lightboxImg" src="" alt="">
  <button class="lightbox-nav lightbox-next" onclick="navLightbox(1)">›</button>
  <div style="position:absolute;bottom:30px;left:50%;transform:translateX(-50%);text-align:center;">
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
