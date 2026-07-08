  </main>
</div><!-- /.main-content -->
</div><!-- /.app-layout -->

<!-- CONFIRM MODAL -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title" id="confirmModalTitle">Please Confirm</div>
      <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
    </div>
    <div class="modal-body">
      <p id="confirmModalMessage" style="color:var(--text-light);font-size:13.5px;line-height:1.6;"></p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Cancel</button>
      <button type="button" class="btn btn-danger" id="confirmModalOkBtn">Confirm</button>
    </div>
  </div>
</div>

<script>
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').style.display =
    document.getElementById('sidebar').classList.contains('open') ? 'block' : 'none';
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').style.display = 'none';
}
// Active nav link
(function(){
  const path = window.location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    if(path.startsWith(a.getAttribute('href'))) a.classList.add('active');
  });
})();

// ── THEME TOGGLE ──
function applyThemeIcon(){
  const isLight = document.documentElement.getAttribute('data-theme') === 'light';
  const moon = document.getElementById('themeIconMoon');
  const sun  = document.getElementById('themeIconSun');
  if (moon) moon.style.display = isLight ? 'none' : 'block';
  if (sun)  sun.style.display  = isLight ? 'block' : 'none';
}
function toggleTheme(){
  const html = document.documentElement;
  const isLight = html.getAttribute('data-theme') === 'light';
  if (isLight) { html.removeAttribute('data-theme'); localStorage.setItem('theme','dark'); }
  else { html.setAttribute('data-theme','light'); localStorage.setItem('theme','light'); }
  applyThemeIcon();
}
applyThemeIcon();

// ── CONFIRM MODAL ──
let _confirmCallback = null;
function showConfirm(message, onConfirm, opts){
  document.getElementById('confirmModalMessage').textContent = message;
  document.getElementById('confirmModalTitle').textContent = (opts && opts.title) || 'Please Confirm';
  document.getElementById('confirmModalOkBtn').textContent = (opts && opts.okLabel) || 'Confirm';
  _confirmCallback = onConfirm;
  document.getElementById('confirmModal').classList.add('open');
}
function closeConfirmModal(){
  document.getElementById('confirmModal').classList.remove('open');
  _confirmCallback = null;
}
document.getElementById('confirmModalOkBtn').addEventListener('click', function(){
  const cb = _confirmCallback;
  closeConfirmModal();
  if (cb) cb();
});
// Any form with data-confirm="message" gets a styled confirm modal instead of the native dialog.
document.querySelectorAll('form[data-confirm]').forEach(function(form){
  form.addEventListener('submit', function(e){
    if (form.dataset.confirmed === 'true') return;
    e.preventDefault();
    showConfirm(form.dataset.confirm, function(){
      form.dataset.confirmed = 'true';
      form.submit();
    }, { title: form.dataset.confirmTitle, okLabel: form.dataset.confirmLabel });
  });
});

// ── TOASTS ──
function showToast(message, type){
  const stack = document.getElementById('toastStack');
  if (!stack || !message) return;
  const el = document.createElement('div');
  el.className = 'toast toast-' + (type === 'error' ? 'danger' : (type || 'success'));
  el.textContent = message;
  stack.appendChild(el);
  setTimeout(() => {
    el.classList.add('toast-out');
    setTimeout(() => el.remove(), 200);
  }, 3500);
}

// ── AJAX MODAL FORMS ──
document.querySelectorAll('.modal-overlay form').forEach(function(form){
  form.addEventListener('submit', async function(e){
    e.preventDefault();
    const modal = form.closest('.modal-overlay');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalLabel = submitBtn ? submitBtn.textContent : '';
    const existingError = form.querySelector('.ajax-error');
    if (existingError) existingError.remove();
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.field-error').forEach(el => el.remove());

    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving…'; }

    try {
      const res = await fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: new FormData(form)
      });
      const data = await res.json();

      if (data.error) {
        const box = document.createElement('div');
        box.className = 'alert alert-danger ajax-error';
        box.textContent = data.error;
        const body = form.querySelector('.modal-body') || form;
        body.prepend(box);

        if (data.errors) {
          Object.keys(data.errors).forEach(function(field){
            const input = form.querySelector('[name="' + field + '"]');
            if (!input) return;
            input.classList.add('is-invalid');
            const msg = document.createElement('div');
            msg.className = 'field-error';
            msg.textContent = data.errors[field];
            input.insertAdjacentElement('afterend', msg);
          });
          const firstInvalid = form.querySelector('.is-invalid');
          if (firstInvalid) firstInvalid.focus();
        }
      } else if (data.redirect) {
        if (data.flash && data.flash.message) showToast(data.flash.message, data.flash.type);
        if (modal) modal.classList.remove('open');
        form.reset();
        setTimeout(() => { window.location.href = data.redirect; }, 500);
      }
    } catch (err) {
      showToast('Network error. Please try again.', 'danger');
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = originalLabel; }
    }
  });
});
</script>
</body>
</html>
