/* LUMINA — Global JavaScript */

// Custom cursor
document.addEventListener('DOMContentLoaded', function() {
  const cursor = document.getElementById('cursor');
  const cursorRing = document.getElementById('cursorRing');
  if (!cursor || !cursorRing) return;

  let mx = 0, my = 0, rx = 0, ry = 0;
  document.addEventListener('mousemove', e => {
    mx = e.clientX; my = e.clientY;
    cursor.style.left = mx + 'px';
    cursor.style.top = my + 'px';
  });

  function animRing() {
    rx += (mx - rx) * .12;
    ry += (my - ry) * .12;
    cursorRing.style.left = rx + 'px';
    cursorRing.style.top = ry + 'px';
    requestAnimationFrame(animRing);
  }
  animRing();

  document.querySelectorAll('a, button, .portfolio-item, .product-card, .service-card, .cart-icon, [data-hover]').forEach(el => {
    el.addEventListener('mouseenter', () => cursorRing.classList.add('hovering'));
    el.addEventListener('mouseleave', () => cursorRing.classList.remove('hovering'));
  });

  // Nav scroll
  const nav = document.getElementById('nav');
  if (nav) {
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 60);
    });
  }

  // Reveal on scroll
  const ro = new IntersectionObserver(entries => {
    entries.forEach(x => { if (x.isIntersecting) x.target.classList.add('visible'); });
  }, { threshold: .1 });
  document.querySelectorAll('.reveal').forEach(el => ro.observe(el));

  // Modal backdrop close
  document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) closeAllModals(); });
  });

  // ESC key
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeAllModals();
  });
});

// Toast
function showToast(msg, type = '') {
  const container = document.getElementById('toastContainer');
  if (!container) return;
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  container.appendChild(t);
  setTimeout(() => t.classList.add('show'), 50);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 4000);
}

// Modal
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
function closeAllModals() {
  document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
  document.body.style.overflow = '';
}

// Lightbox
let lbImages = [];
let curLB = 0;

function initLightbox(images) {
  lbImages = images;
}

function openLightbox(i) {
  curLB = i;
  const lb = document.getElementById('lightbox');
  const img = document.getElementById('lightboxImg');
  if (!lb || !img || !lbImages[i]) return;
  img.src = lbImages[i].src;
  if (document.getElementById('lightboxCat')) document.getElementById('lightboxCat').textContent = lbImages[i].cat || '';
  if (document.getElementById('lightboxTitle')) document.getElementById('lightboxTitle').textContent = lbImages[i].title || '';
  lb.classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeLightbox() {
  const lb = document.getElementById('lightbox');
  if (lb) lb.classList.remove('open');
  document.body.style.overflow = '';
}
function navLightbox(d) {
  curLB = (curLB + d + lbImages.length) % lbImages.length;
  openLightbox(curLB);
}

// Cart counter (session-based visual)
function updateCartCount() {
  const el = document.getElementById('cartCount');
  if (el && window.cartItems !== undefined) el.textContent = window.cartItems;
}

// Upload drag and drop
function initDropZone(zoneId, inputId, previewId, onUpload) {
  const zone = document.getElementById(zoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;

  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('dragover');
    const dt = new DataTransfer();
    Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
    input.files = dt.files;
    input.dispatchEvent(new Event('change'));
  });
}

// Image preview
function handleImagePreview(files, previewContainerId) {
  const container = document.getElementById(previewContainerId);
  if (!container) return;
  container.innerHTML = '';
  Array.from(files).forEach(f => {
    const item = document.createElement('div');
    item.className = 'preview-item';
    const r = new FileReader();
    r.onload = ev => {
      item.innerHTML = `<img src="${ev.target.result}" alt=""><button class="preview-remove" onclick="this.parentNode.remove()">✕</button>`;
    };
    r.readAsDataURL(f);
    container.appendChild(item);
  });
}
