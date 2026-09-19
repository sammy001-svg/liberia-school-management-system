<?php
require_once __DIR__ . '/icons.php';

$cfg  = require ROOT_DIR . '/config/app.php';
$base = rtrim($cfg['url'], '/');
$img  = fn(string $file) => $base . '/assets/website/img/' . $file;
$url  = fn(string $path = '') => $base . '/' . ltrim($path, '/');

// Everything editable comes from $content (WebsiteContent::load): the school's saved
// edits over the built-in defaults. These helpers are the only way views read it.
$content = $content ?? [];
$raw = fn(string $key) => (string)($content[$key] ?? '');
// Escaped text with the light markup admins can type: **bold** and *highlight*.
$fmt = function (string $text): string {
    $html = htmlspecialchars($text);
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
    return preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);
};
$t = fn(string $key) => htmlspecialchars($raw($key));              // plain text
$h = fn(string $key) => $fmt($raw($key));                          // one line with *highlight*
$paras = function (string $key, string $class = '') use ($raw, $fmt): string {  // blank line = new paragraph
    $out = '';
    foreach (preg_split('/\R\s*\R/', trim($raw($key))) as $para) {
        if (trim($para) === '') { continue; }
        $out .= '<p' . ($class ? ' class="' . $class . '"' : '') . '>' . nl2br($fmt(trim($para)), false) . '</p>';
    }
    return $out;
};
$lines = fn(string $key) => array_values(array_filter(array_map('trim', preg_split('/\R/', $raw($key))), 'strlen'));
// "A | B | C" per line -> [[A, B, C], ...], padded so views can destructure safely.
$list = fn(string $key, int $parts = 2) => array_map(
    fn($line) => array_pad(array_map('trim', explode('|', $line, $parts)), $parts, ''),
    $lines($key)
);
// A bundled filename lives in assets/website/img; an uploaded image is already a URL.
$im = function (string $key) use ($raw, $img): string {
    $v = trim($raw($key));
    return preg_match('#^(https?:)?/#', $v) ? htmlspecialchars($v) : $img($v);
};

$site = [
    'name'    => $raw('site.name'),
    'motto'   => $raw('site.motto'),
    'tagline' => $raw('site.tagline'),
    'address' => $raw('site.address'),
    'phones'  => array_values(array_filter([trim($raw('site.phone1')), trim($raw('site.phone2'))], 'strlen')) ?: [''],
    'email'   => trim($raw('site.email')) !== '' ? trim($raw('site.email')) : trim((string)($tenant['email'] ?? '')),
];
$telHref = fn(string $p) => 'tel:' . preg_replace('/[^\d+]/', '', $p);

$activeNav  = $activeNav ?? '';
$isLoggedIn = $isLoggedIn ?? false;
$portalLabel = $isLoggedIn ? 'My Portal' : 'Portal Login';

$nav = [
    ['key' => 'home', 'label' => 'Home', 'href' => $url()],
    ['key' => 'about', 'label' => 'About', 'href' => $url('about-us'), 'children' => [
        ['label' => 'About CELDI Academy', 'href' => $url('about-us'), 'desc' => 'Our story, vision and values'],
        ['label' => 'Our Leadership', 'href' => $url('our-leadership'), 'desc' => 'Board, administration and faculty'],
        ['label' => 'Student Life', 'href' => $url('student-life'), 'desc' => 'Mentorship, community and service'],
    ]],
    ['key' => 'divisions', 'label' => 'Divisions', 'href' => $url('divisions'), 'children' => [
        ['label' => 'Early Childhood & Daycare', 'href' => $url('early-childhood'), 'desc' => 'Daycare, nursery and kindergarten'],
        ['label' => 'Elementary', 'href' => $url('elementary'), 'desc' => 'Grades 1 – 6'],
        ['label' => 'Junior High', 'href' => $url('junior-high'), 'desc' => 'Grades 7 – 9'],
        ['label' => 'Senior High', 'href' => $url('senior-high'), 'desc' => 'Grades 10 – 12'],
    ]],
    ['key' => 'admissions', 'label' => 'Admissions', 'href' => $url('admissions')],
    ['key' => 'news', 'label' => 'News & Events', 'href' => $url('academy-news')],
];
$assetVer = '3';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars(($pageTitle ?? '') === 'Home' ? $site['name'] . ' — ' . $site['tagline'] : ($pageTitle ?? '') . ' — ' . $site['name']) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDescription ?? '') ?>">
<meta name="theme-color" content="#3B1A52">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= htmlspecialchars($site['name']) ?>">
<meta property="og:title" content="<?= htmlspecialchars(($pageTitle ?? '') . ' — ' . $site['name']) ?>">
<meta property="og:description" content="<?= htmlspecialchars($pageDescription ?? '') ?>">
<meta property="og:image" content="<?= $im('home.hero_image') ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="<?= $im('site.logo') ?>">
<link rel="apple-touch-icon" href="<?= $im('site.logo') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/website/site.css?v=<?= $assetVer ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>

<div class="utility-bar">
  <div class="container utility-inner">
    <div class="utility-items">
      <span><?= wicon('map-pin') ?> <?= htmlspecialchars($site['address']) ?></span>
      <a href="<?= $telHref($site['phones'][0]) ?>"><?= wicon('phone') ?> <?= htmlspecialchars($site['phones'][0]) ?></a>
    </div>
    <div class="utility-items">
      <a href="<?= $url('apply') ?>">Online Application</a>
      <a href="<?= $url('login') ?>"><?= wicon('login') ?> <?= $portalLabel ?></a>
    </div>
  </div>
</div>

<header class="site-header" data-header>
  <div class="container header-inner">
    <a class="brand" href="<?= $url() ?>" aria-label="<?= htmlspecialchars($site['name']) ?> — home">
      <img src="<?= $im('site.logo') ?>" alt="" width="44" height="56">
      <span class="brand-text">
        <span class="brand-name"><?= htmlspecialchars($site['name']) ?></span>
        <span class="brand-sub"><?= $t('site.brand_sub') ?></span>
      </span>
    </a>

    <nav class="main-nav" aria-label="Main">
      <ul>
        <?php foreach ($nav as $item): $isActive = $activeNav === $item['key']; ?>
          <?php if (!empty($item['children'])): ?>
            <li class="has-dropdown">
              <a href="<?= $item['href'] ?>" class="nav-link<?= $isActive ? ' is-active' : '' ?>" aria-haspopup="true"><?= $item['label'] ?> <?= wicon('chevron-down', 'nav-caret') ?></a>
              <div class="dropdown">
                <?php foreach ($item['children'] as $child): ?>
                  <a href="<?= $child['href'] ?>">
                    <strong><?= htmlspecialchars($child['label']) ?></strong>
                    <span><?= htmlspecialchars($child['desc']) ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </li>
          <?php else: ?>
            <li><a href="<?= $item['href'] ?>" class="nav-link<?= $isActive ? ' is-active' : '' ?>"<?= $isActive ? ' aria-current="page"' : '' ?>><?= $item['label'] ?></a></li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </nav>

    <div class="header-actions">
      <a href="<?= $url('login') ?>" class="btn btn-ghost header-portal"><?= $portalLabel ?></a>
      <a href="<?= $url('apply') ?>" class="btn btn-gold">Apply Now</a>
      <button class="menu-toggle" type="button" aria-label="Open menu" aria-expanded="false" aria-controls="mobileNav" data-menu-open><?= wicon('menu') ?></button>
    </div>
  </div>
</header>

<div class="mobile-nav" id="mobileNav" hidden data-mobile-nav>
  <div class="mobile-nav-panel" role="dialog" aria-modal="true" aria-label="Menu">
    <div class="mobile-nav-head">
      <a class="brand" href="<?= $url() ?>">
        <img src="<?= $im('site.logo') ?>" alt="" width="36" height="46">
        <span class="brand-name"><?= htmlspecialchars($site['name']) ?></span>
      </a>
      <button class="menu-close" type="button" aria-label="Close menu" data-menu-close><?= wicon('close') ?></button>
    </div>
    <ul class="mobile-links">
      <?php foreach ($nav as $item): ?>
        <li>
          <?php if (!empty($item['children'])): ?>
            <details<?= $activeNav === $item['key'] ? ' open' : '' ?>>
              <summary><?= $item['label'] ?> <?= wicon('chevron-down') ?></summary>
              <div class="mobile-sub">
                <?php foreach ($item['children'] as $child): ?>
                  <a href="<?= $child['href'] ?>"><?= htmlspecialchars($child['label']) ?></a>
                <?php endforeach; ?>
              </div>
            </details>
          <?php else: ?>
            <a href="<?= $item['href'] ?>"<?= $activeNav === $item['key'] ? ' class="is-active"' : '' ?>><?= $item['label'] ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="mobile-nav-actions">
      <a href="<?= $url('apply') ?>" class="btn btn-gold btn-block">Apply Now</a>
      <a href="<?= $url('login') ?>" class="btn btn-outline-dark btn-block"><?= wicon('login') ?> <?= $portalLabel ?></a>
    </div>
    <div class="mobile-contact">
      <a href="<?= $telHref($site['phones'][0]) ?>"><?= wicon('phone') ?> <?= htmlspecialchars($site['phones'][0]) ?></a>
      <span><?= wicon('map-pin') ?> <?= htmlspecialchars($site['address']) ?></span>
    </div>
  </div>
</div>

<main id="main">
