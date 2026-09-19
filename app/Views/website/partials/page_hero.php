<?php
// Inner-page banner. Expects $hero = ['image', 'title' (trusted HTML), 'eyebrow', 'lead',
// 'crumbs' => [label => href|null], 'chips' => [[icon, text], ...]].
?>
<section class="page-hero">
  <div class="page-hero-media"><img src="<?= $img($hero['image']) ?>" alt="" fetchpriority="high"></div>
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= $url() ?>">Home</a>
      <?php foreach ($hero['crumbs'] ?? [] as $label => $href): ?>
        <span aria-hidden="true">/</span>
        <?php if ($href): ?><a href="<?= $href ?>"><?= htmlspecialchars($label) ?></a><?php else: ?><span aria-current="page"><?= htmlspecialchars($label) ?></span><?php endif; ?>
      <?php endforeach; ?>
    </nav>
    <?php if (!empty($hero['eyebrow'])): ?><span class="eyebrow eyebrow--light"><?= htmlspecialchars($hero['eyebrow']) ?></span><?php endif; ?>
    <h1><?= $hero['title'] ?></h1>
    <?php if (!empty($hero['lead'])): ?><p class="lead lead--light"><?= htmlspecialchars($hero['lead']) ?></p><?php endif; ?>
    <?php if (!empty($hero['chips'])): ?>
      <div class="chips">
        <?php foreach ($hero['chips'] as [$icon, $text]): ?>
          <span class="chip"><?= wicon($icon) ?> <?= htmlspecialchars($text) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
