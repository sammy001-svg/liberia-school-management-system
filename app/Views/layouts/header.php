<?php
$cfg     = require ROOT_DIR . '/config/app.php';
$user    = $_SESSION['user'] ?? [];
$branding = $_SESSION['branding'] ?? [];
$appName  = $branding['name'] ?? $cfg['name'];
$base     = $cfg['url'];
$role     = $_SESSION['role_name'] ?? '';
$faviconColor = $branding['primary_color'] ?? '#10B981';
$faviconSvg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='22' fill='{$faviconColor}'/><text x='50' y='68' font-family='Arial,sans-serif' font-size='58' font-weight='900' fill='white' text-anchor='middle'>" . strtoupper(substr($appName, 0, 1)) . "</text></svg>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= htmlspecialchars($appName) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'A comprehensive and modern school management system for tracking attendance, grades, and student growth.') ?>">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($base ?? '') ?>/">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= htmlspecialchars($appName) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription ?? 'A comprehensive and modern school management system for tracking attendance, grades, and student growth.') ?>">
<meta property="og:image" content="<?= htmlspecialchars($pageImage ?? ($base . '/assets/img/og-image.jpg')) ?>">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?= htmlspecialchars($base ?? '') ?>/">
<meta property="twitter:title" content="<?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= htmlspecialchars($appName) ?>">
<meta property="twitter:description" content="<?= htmlspecialchars($pageDescription ?? 'A comprehensive and modern school management system for tracking attendance, grades, and student growth.') ?>">
<meta property="twitter:image" content="<?= htmlspecialchars($pageImage ?? ($base . '/assets/img/og-image.jpg')) ?>">
<link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode($faviconSvg) ?>">
<script>(function(){try{if(localStorage.getItem('theme')==='light')document.documentElement.setAttribute('data-theme','light');}catch(e){}})();</script>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<?php if (!empty($branding['primary_color'])): ?>
<style>:root{--primary:<?= $branding['primary_color'] ?>;--secondary:<?= $branding['secondary_color'] ?? '#7C3AED' ?>;}</style>
<?php endif; ?>
</head>
<body>
<div class="toast-stack" id="toastStack"></div>
<div class="app-layout">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <?php if (!empty($branding['logo'])): ?>
      <img src="<?= htmlspecialchars($branding['logo']) ?>" alt="Logo">
    <?php else: ?>
      <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--primary),var(--secondary));border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#fff;font-size:16px;flex-shrink:0;">S</div>
    <?php endif; ?>
    <div>
      <div class="brand-name"><?= htmlspecialchars($appName) ?></div>
      <div class="brand-sub"><?= htmlspecialchars($role) ?></div>
    </div>
  </div>

  <?php require __DIR__ . "/sidebar_{$panelType}.php"; ?>

  <div class="sidebar-bottom">
    <a href="<?= $base ?>/logout" class="btn btn-outline btn-sm btn-block">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
      Sign Out
    </a>
  </div>
</aside>

<!-- OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:99;"></div>

<!-- MAIN -->
<div class="main-content">
  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
      </button>
      <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
    </div>
    <div class="topbar-right">
      <button class="theme-toggle" onclick="toggleTheme()" title="Toggle dark / light theme">
        <svg id="themeIconMoon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/></svg>
        <svg id="themeIconSun" style="display:none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
      </button>
      <div class="dropdown">
        <button onclick="this.nextElementSibling.classList.toggle('open')" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;color:var(--text);">
          <div class="avatar"><?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?></div>
          <span style="font-size:13px;font-weight:600;"><?= htmlspecialchars($user['name'] ?? '') ?></span>
        </button>
        <div class="dropdown-menu">
          <div class="dropdown-item" style="font-size:11px;color:var(--text-muted);cursor:default;"><?= htmlspecialchars($user['email'] ?? '') ?></div>
          <hr class="dropdown-divider">
          <a href="<?= $base ?>/logout" class="dropdown-item">Sign Out</a>
        </div>
      </div>
    </div>
  </header>

  <!-- FLASH -->
  <?php if (!empty($flash)): ?>
  <div style="padding:0 24px;margin-top:16px;">
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : $flash['type'] ?>">
      <?= htmlspecialchars($flash['message']) ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- IMPORT ERRORS -->
  <?php if (!empty($importErrors)): ?>
  <div style="padding:0 24px;margin-top:16px;">
    <div class="card" style="border-color:var(--danger);">
      <div class="card-header"><div class="card-title" style="color:var(--danger);">Import Errors (<?= count($importErrors) ?>)</div></div>
      <div class="card-body" style="max-height:220px;overflow-y:auto;">
        <ul style="padding-left:18px;font-size:12.5px;color:var(--text-light);line-height:1.9;">
          <?php foreach ($importErrors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- PAGE BODY -->
  <main class="page-body">
