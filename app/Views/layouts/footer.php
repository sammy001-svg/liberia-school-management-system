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
</script>
</body>
</html>
