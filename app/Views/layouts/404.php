<?php
$cfg = require ROOT_DIR . '/config/app.php';
$base = $cfg['url'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>404 Not Found</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="text-align:center;padding:60px 40px">
    <div style="font-size:72px;font-weight:900;color:var(--primary);line-height:1">404</div>
    <h2 style="margin-top:12px;font-size:18px">Page Not Found</h2>
    <p style="color:var(--text-muted);margin-top:8px;font-size:13px">The page you're looking for doesn't exist.</p>
    <a href="<?= $base ?>/login" class="btn btn-primary btn-lg" style="margin-top:24px">Back to Login</a>
  </div>
</div>
</body>
</html>
