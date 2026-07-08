  </main>
</div><!-- /.main-content -->
</div><!-- /.app-layout -->

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
