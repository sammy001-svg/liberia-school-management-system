<?php
$cfg      = require ROOT_DIR . '/config/app.php';
$branding = $_SESSION['branding'] ?? null;
$appName  = $branding['name'] ?? $cfg['name'];
$appLogo  = $branding['logo'] ?? null;
$primaryColor   = $branding['primary_color']   ?? null;
$secondaryColor = $branding['secondary_color'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Login') ?> — <?= htmlspecialchars($appName) ?></title>
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
<div class="login-page">
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
    <p style="text-align:center;margin-top:20px;font-size:14px;">
      Don't have an account?<br>
      <a href="<?= $cfg['url'] ?>/register/school" style="color:var(--primary);font-weight:600;">Register your School</a> or 
      <a href="<?= $cfg['url'] ?>/register/reseller" style="color:var(--secondary);font-weight:600;">Become a Reseller</a>
    </p>

    <p style="text-align:center;margin-top:30px;font-size:12px;color:var(--text-muted);">
      Powered by <?= htmlspecialchars($appName) ?> &copy; <?= date('Y') ?>
    </p>
  </div>
</div>
</body>
</html>
