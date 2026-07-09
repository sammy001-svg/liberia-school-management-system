<?php
$cfg      = require ROOT_DIR . '/config/app.php';
$branding = $_SESSION['branding'] ?? null;
$appName  = $branding['name'] ?? $cfg['name'];
$appLogo  = $branding['logo'] ?? null;
$primaryColor   = $branding['primary_color']   ?? null;
$secondaryColor = $branding['secondary_color'] ?? null;
$faviconSvg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='" . ($primaryColor ?: '#10B981') . "'/><text x='50' y='68' font-family='Arial,sans-serif' font-size='58' font-weight='900' fill='white' text-anchor='middle'>" . strtoupper(substr($appName, 0, 1)) . "</text></svg>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Login') ?> — <?= htmlspecialchars($appName) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'A comprehensive and modern school management system for tracking attendance, grades, and student growth.') ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($cfg['url'] ?? '') ?>/">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Login') ?> — <?= htmlspecialchars($appName) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription ?? 'A comprehensive and modern school management system for tracking attendance, grades, and student growth.') ?>">
<meta property="og:image" content="<?= htmlspecialchars($pageImage ?? ($cfg['url'] . '/assets/img/og-image.jpg')) ?>">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?= htmlspecialchars($cfg['url'] ?? '') ?>/">
<meta property="twitter:title" content="<?= htmlspecialchars($pageTitle ?? 'Login') ?> — <?= htmlspecialchars($appName) ?>">
<meta property="twitter:description" content="<?= htmlspecialchars($pageDescription ?? 'A comprehensive and modern school management system for tracking attendance, grades, and student growth.') ?>">
<meta property="twitter:image" content="<?= htmlspecialchars($pageImage ?? ($cfg['url'] . '/assets/img/og-image.jpg')) ?>">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($faviconSvg) ?>">
<script>(function(){try{if(localStorage.getItem('theme')==='light')document.documentElement.setAttribute('data-theme','light');}catch(e){}})();</script>
<link rel="stylesheet" href="<?= $cfg['url'] ?>/assets/css/style.css">
<?php if ($primaryColor): ?>
<style>
  :root {
    --primary: <?= htmlspecialchars($primaryColor) ?>;
    --secondary: <?= htmlspecialchars($secondaryColor ?? '#059669') ?>;
  }
</style>
<?php endif; ?>
</head>
<body>
<div class="login-split">

  <div class="login-carousel">
    <div class="carousel-brand">
      <?php if ($appLogo): ?>
        <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo">
      <?php else: ?>
        <div style="width:30px;height:30px;background:rgba(255,255,255,0.18);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:#fff;"><?= strtoupper(substr($appName,0,1)) ?></div>
      <?php endif; ?>
      <span><?= htmlspecialchars($appName) ?></span>
    </div>

    <div class="carousel-slide active" style="background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.8)), url('https://images.unsplash.com/photo-1509062522246-3755977927d7?auto=format&fit=crop&q=80&w=1600') center/cover no-repeat;">
      <div class="carousel-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 6.5c-1.5-1-4-1.5-6-1.2v11c2-.3 4.5.2 6 1.2c1.5-1 4-1.5 6-1.2v-11c-2-.3-4.5.2-6 1.2z" stroke-linejoin="round" stroke-linecap="round"/>
          <path d="M12 6.5v11" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="carousel-caption">
        <h2>Empowering Every Learner</h2>
        <p>Track attendance, grades, and growth — all in one place, built for the way your school actually works.</p>
      </div>
    </div>

    <div class="carousel-slide" style="background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.8)), url('https://images.unsplash.com/photo-1577896851231-70ef18881754?auto=format&fit=crop&q=80&w=1600') center/cover no-repeat;">
      <div class="carousel-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="9" cy="8" r="3"/>
          <circle cx="16.5" cy="9.5" r="2.5"/>
          <path d="M3.5 19c.5-3 2.7-5 5.5-5s5 2 5.5 5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M14.5 19c.3-2 1.8-3.3 3.6-3.5c1.8-.2 3.4.8 4.1 2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="carousel-caption">
        <h2>Connecting School &amp; Family</h2>
        <p>Parents, teachers and admins — working together in real time for every child's success.</p>
      </div>
    </div>

    <div class="carousel-slide" style="background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.8)), url('https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&q=80&w=1600') center/cover no-repeat;">
      <div class="carousel-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M4 19V13M9 19V9M14 19v-4M19 19V6" stroke-linecap="round"/>
          <path d="M4 8l5-4 4 3 6-5" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M15 2h4v4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </div>
      <div class="carousel-caption">
        <h2>Building Tomorrow, Today</h2>
        <p>Modern, reliable tools that help every learner grow — one milestone at a time.</p>
      </div>
    </div>

    <div class="carousel-dots">
      <button type="button" class="carousel-dot active" data-slide="0" aria-label="Slide 1"></button>
      <button type="button" class="carousel-dot" data-slide="1" aria-label="Slide 2"></button>
      <button type="button" class="carousel-dot" data-slide="2" aria-label="Slide 3"></button>
    </div>
  </div>

  <div class="login-form-panel">
    <div class="login-box">
      <div class="login-logo">
        <?php if ($appLogo): ?>
          <img src="<?= htmlspecialchars($appLogo) ?>" alt="Logo">
        <?php else: ?>
          <div style="width:56px;height:56px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-size:26px;font-weight:900;color:#fff;">S</div>
        <?php endif; ?>
        <h1><?= htmlspecialchars($appName) ?></h1>
        <p>Sign in to your account</p>
      </div>

      <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>">
          <?= htmlspecialchars($flash['message']) ?>
        </div>
      <?php endif; ?>

      <form action="<?= $cfg['url'] ?>/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="you@school.com" required autofocus>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
          Sign In
        </button>
      </form>
      <p style="text-align:center;margin-top:30px;font-size:12px;color:var(--text-muted);">
        Powered by <?= htmlspecialchars($appName) ?> &copy; <?= date('Y') ?>
      </p>
    </div>
  </div>

</div>
<script>
(function(){
  var slides = document.querySelectorAll('.carousel-slide');
  var dots = document.querySelectorAll('.carousel-dot');
  var idx = 0, timer;
  function show(i){
    slides.forEach(function(s,j){ s.classList.toggle('active', j===i); });
    dots.forEach(function(d,j){ d.classList.toggle('active', j===i); });
    idx = i;
  }
  function restart(){
    clearInterval(timer);
    timer = setInterval(function(){ show((idx+1) % slides.length); }, 5000);
  }
  dots.forEach(function(d,j){
    d.addEventListener('click', function(){ show(j); restart(); });
  });
  if (slides.length > 1) restart();
})();
</script>
</body>
</html>
