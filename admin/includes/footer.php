</div><!-- end admin-content -->
</main><!-- end admin-main -->

<script>
function showToast(msg, type = '') {
  const container = document.getElementById('toastContainer');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  container.appendChild(t);
  setTimeout(() => t.classList.add('show'), 50);
  setTimeout(() => { t.classList.remove('show'); setTimeout(() => t.remove(), 400); }, 4000);
}
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); }));
</script>
<?php if (isset($extraJs)): ?>
<script src="/4pt/blazkova/lumina/Lumina/admin/<?= $extraJs ?>"></script>
<?php endif; ?>
</body>
</html>
