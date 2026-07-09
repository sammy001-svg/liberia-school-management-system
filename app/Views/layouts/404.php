<?php
$cfg  = require ROOT_DIR . '/config/app.php';
$base = $cfg['url'];
if (session_status() === PHP_SESSION_NONE) {
    session_name($cfg['session_name']);
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? null) == 443
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => $isHttps, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
$isLoggedIn = isset($_SESSION['user_id']);
$homeUrl   = '/login';
$homeLabel = 'Back to Login';
if ($isLoggedIn) {
    $homeLabel = 'Back to Dashboard';
    $homeUrl = match ($_SESSION['role'] ?? '') {
        'Student' => '/student/dashboard',
        'Parent'  => '/parent/dashboard',
        default   => '/school/dashboard',
    };
}
$appName = $_SESSION['branding']['name'] ?? $cfg['name'];
$primaryColor = $_SESSION['branding']['primary_color'] ?? '#10B981';
$faviconSvg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='{$primaryColor}'/><text x='50' y='68' font-family='Arial,sans-serif' font-size='58' font-weight='900' fill='white' text-anchor='middle'>" . strtoupper(substr($appName, 0, 1)) . "</text></svg>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>404 Not Found</title>
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($faviconSvg) ?>">
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-box" style="text-align:center;padding:60px 40px">
    <div style="font-size:72px;font-weight:900;color:var(--primary);line-height:1">404</div>
    <h2 style="margin-top:12px;font-size:18px">Page Not Found</h2>
    <p style="color:var(--text-muted);margin-top:8px;font-size:13px">The page you're looking for doesn't exist.</p>
    <a href="<?= $base . $homeUrl ?>" class="btn btn-primary btn-lg" style="margin-top:24px"><?= htmlspecialchars($homeLabel) ?></a>
  </div>
</div>
</body>
</html>
