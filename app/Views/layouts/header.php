<?php
$cfg     = require ROOT_DIR . '/config/app.php';
$user    = $_SESSION['user'] ?? [];
$branding = $_SESSION['branding'] ?? [];
$appName  = $branding['name'] ?? $cfg['name'];
$base     = $cfg['url'];
$role     = $_SESSION['role_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?> — <?= htmlspecialchars($appName) ?></title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<?php if (!empty($branding['primary_color'])): ?>
<style>:root{--primary:<?= $branding['primary_color'] ?>;--secondary:<?= $branding['secondary_color'] ?? '#7C3AED' ?>;}</style>
<?php endif; ?>
</head>
<body>
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

  <!-- PAGE BODY -->
  <main class="page-body">
