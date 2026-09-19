<?php
// Inner-page banner. Expects $heroKey (the page's content prefix, e.g. 'about') and
// $crumbs = [label => href|null]. Tags come from "<prefix>.hero_chips", one per line.
$heroChipIcons = $heroChipIcons ?? ['sparkles', 'check', 'star'];
$heroChips = isset($content["$heroKey.hero_chips"]) ? $lines("$heroKey.hero_chips") : [];
?>
<section class="page-hero">
  <div class="page-hero-media"><img src="<?= $im("$heroKey.hero_image") ?>" alt="" fetchpriority="high"></div>
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= $url() ?>">Home</a>
      <?php foreach ($crumbs ?? [] as $label => $href): ?>
        <span aria-hidden="true">/</span>
        <?php if ($href): ?><a href="<?= $href ?>"><?= htmlspecialchars($label) ?></a><?php else: ?><span aria-current="page"><?= htmlspecialchars($label) ?></span><?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php if ($raw("$heroKey.hero_eyebrow") !== ''): ?><span class="eyebrow eyebrow--light"><?= $t("$heroKey.hero_eyebrow") ?></span><?php endif; ?>
    <h1><?= $h("$heroKey.hero_title") ?></h1>
    <?= $paras("$heroKey.hero_lead", 'lead lead--light') ?>
    <?php if ($heroChips): ?>
      <div class="chips">
        <?php foreach ($heroChips as $i => $chip): ?>
          <span class="chip"><?= wicon($heroChipIcons[$i % count($heroChipIcons)]) ?> <?= htmlspecialchars($chip) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
