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

  // Reveal on scroll — also trigger for already-visible elements
  const ro = new IntersectionObserver(entries => {
    entries.forEach(x => { if (x.isIntersecting) x.target.classList.add('visible'); });
  }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });
  document.querySelectorAll('.reveal').forEach(el => {
    ro.observe(el);
    // Immediately show if already in viewport
    const rect = el.getBoundingClientRect();
    if (rect.top < window.innerHeight && rect.bottom > 0) {
      el.classList.add('visible');
    }
  });

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

// ── Global Cart sidebar (works on every page) ─────────────
function openCart() {
  // Prefer local cart sidebar (veikals.php), fallback to global
  const local = document.getElementById('cartSidebar');
  if (local) {
    local.classList.add('open');
    loadCartItems();
    return;
  }
  // Global sidebar in header
  const sidebar = document.getElementById('globalCartSidebar');
  const overlay = document.getElementById('globalCartOverlay');
  if (sidebar) {
    overlay.style.display = 'block';
    requestAnimationFrame(() => {
      overlay.style.background = 'rgba(0,0,0,0.35)';
      sidebar.style.transform = 'translateX(0)';
    });
    loadGlobalCartItems();
  }
}

function closeGlobalCart() {
  const sidebar = document.getElementById('globalCartSidebar');
  const overlay = document.getElementById('globalCartOverlay');
  if (!sidebar) return;
  sidebar.style.transform = 'translateX(100%)';
  overlay.style.background = 'rgba(0,0,0,0)';
  setTimeout(() => { overlay.style.display = 'none'; }, 350);
}

function loadGlobalCartItems() {
  fetch('/4pt/blazkova/lumina/Lumina/cart.php?action=get')
    .then(r => r.json())
    .then(data => {
      const container = document.getElementById('globalCartItems');
      const totalEl   = document.getElementById('globalCartTotal');
      const btn       = document.getElementById('globalCheckoutBtn');
      if (!container) return;
      if (!data.items || data.items.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:48px 20px;color:#aaa;"><div style="font-size:40px;margin-bottom:12px;opacity:.3;">🛒</div><div style="font-size:13px;">Grozs ir tukšs</div></div>';
        if (totalEl) totalEl.innerHTML = '';
        if (btn) { btn.disabled = true; btn.style.opacity = '.4'; }
        return;
      }
      if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
      let html = '', total = 0;
      data.items.forEach(item => {
        total += item.cena * item.qty;
        html += `<div style="display:flex;gap:12px;align-items:center;padding:14px 0;border-bottom:1px solid #f5f0e8;">
          <div style="flex:1;min-width:0;">
            <div style="font-size:13px;color:#1C1C1C;margin-bottom:2px;">${item.name}</div>
            <div style="font-size:11px;color:#aaa;">× ${item.qty}</div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <div style="font-family:'Cormorant Garamond',serif;font-size:20px;color:#B8975A;">€${(item.cena*item.qty).toFixed(2)}</div>
            <button onclick="removeFromGlobalCart(${item.id})" style="background:none;border:none;cursor:pointer;color:#ccc;font-size:18px;padding:2px 4px;line-height:1;" title="Noņemt">×</button>
          </div>
        </div>`;
      });
      container.innerHTML = html;
      if (totalEl) totalEl.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:baseline;">
          <span style="font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#aaa;">Kopā</span>
          <span style="font-family:'Cormorant Garamond',serif;font-size:26px;color:#B8975A;">€${total.toFixed(2)}</span>
        </div>
        <div style="font-size:10px;color:#ccc;text-align:right;margin-top:2px;">Ieskaitot PVN</div>`;
    }).catch(() => {});
}

function removeFromGlobalCart(id) {
  fetch('/4pt/blazkova/lumina/Lumina/cart.php?action=remove&id=' + id)
    .then(r => r.json())
    .then(data => {
      document.getElementById('cartCount').textContent = data.count;
      loadGlobalCartItems();
    });
}

function globalCheckout() {
  const btn = document.getElementById('globalCheckoutBtn');
  if (btn) { btn.textContent = 'Apstrādā...'; btn.disabled = true; }
  fetch('/4pt/blazkova/lumina/Lumina/stripe_checkout.php?action=create_checkout', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.url) {
        window.location.href = data.url;
      } else {
        showToast(data.error || 'Kļūda. Mēģiniet vēlreiz.', 'error');
        if (btn) { btn.textContent = 'Apmaksāt ar karti →'; btn.disabled = false; btn.style.opacity = '1'; }
      }
    }).catch(() => {
      showToast('Savienojuma kļūda.', 'error');
      if (btn) { btn.textContent = 'Apmaksāt ar karti →'; btn.disabled = false; btn.style.opacity = '1'; }
    });
}

function loadCartItems() {
  fetch('/4pt/blazkova/lumina/Lumina/cart.php?action=get')
    .then(r => r.json())
    .then(data => {
      const container = document.getElementById('cartItems');
      const totalEl = document.getElementById('cartTotal');
      const checkoutBtn = document.getElementById('checkoutBtn');
      if (!container) return;
      if (!data.items || data.items.length === 0) {
        container.innerHTML = '<div style="text-align:center;padding:40px;color:#888;font-size:14px;line-height:1.8;"><div style="font-size:32px;margin-bottom:12px;opacity:.3;">🛒</div>Grozs ir tukšs</div>';
        if (totalEl) totalEl.textContent = '';
        if (checkoutBtn) { checkoutBtn.disabled = true; checkoutBtn.style.opacity = '0.4'; }
        return;
      }
      if (checkoutBtn) { checkoutBtn.disabled = false; checkoutBtn.style.opacity = '1'; }
      let html = '';
      let total = 0;
      data.items.forEach(item => {
        total += item.cena * item.qty;
        html += `<div class="cart-item">
          <div style="flex:1;">
            <div class="cart-item-name">${item.name}</div>
            <div class="cart-item-qty" style="display:flex;align-items:center;gap:8px;margin-top:4px;">
              <span style="color:#888;font-size:12px;">× ${item.qty}</span>
            </div>
          </div>
          <div style="display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <div class="cart-item-price">€${(item.cena * item.qty).toFixed(2)}</div>
            <button class="cart-item-remove" onclick="removeFromCart(${item.id})" title="Noņemt">×</button>
          </div>
        </div>`;
      });
      container.innerHTML = html;
      if (totalEl) totalEl.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:4px;">
          <span style="font-size:11px;letter-spacing:2px;text-transform:uppercase;color:var(--grey2);">Kopā</span>
          <span style="font-family:'Cormorant Garamond',serif;font-size:28px;color:var(--gold);">€${total.toFixed(2)}</span>
        </div>
        <div style="font-size:10px;color:var(--grey2);text-align:right;">Ieskaitot PVN</div>`;
    }).catch(() => {});
}

function removeFromCart(id) {
  fetch('/4pt/blazkova/lumina/Lumina/cart.php?action=remove&id=' + id)
    .then(r => r.json())
    .then(data => {
      document.getElementById('cartCount').textContent = data.count;
      loadCartItems();
    });
}

// Open cart when clicking cart icon
document.addEventListener('DOMContentLoaded', () => {
  const cartIcon = document.querySelector('.cart-icon');
  if (cartIcon) {
    cartIcon.addEventListener('click', e => {
      e.preventDefault();
      openCart();
    });
  }
});
