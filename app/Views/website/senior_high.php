<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'senior'; $crumbs = ['Divisions' => $url('divisions'), 'Senior High' => null];
$heroChipIcons = ['graduation', 'monitor', 'compass'];
require __DIR__ . '/partials/page_hero.php';
?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">The Senior High Program</span>
      <h2 class="display"><?= $h('senior.intro_title') ?></h2>
      <?= $paras('senior.intro_body') ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('senior.intro_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">What Our Graduates Leave With</span>
      <h2 class="display display--light"><?= $h('senior.outcomes_title') ?></h2>
    </div>
    <div class="values-grid" data-reveal>
      <?php $outIcons = ['graduation', 'monitor', 'award', 'star']; ?>
      <?php foreach ($list('senior.outcomes') as $i => [$title, $text]): ?>
        <div class="value"><?= wicon($outIcons[$i % 4]) ?><h3><?= $fmt($title) ?></h3><p><?= $fmt($text) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Beyond Graduation</span>
      <h2 class="display"><?= $h('senior.beyond_title') ?></h2>
      <?= $paras('senior.beyond_body') ?>
      <?php if ($points = $lines('senior.beyond_points')): ?>
        <ul class="check-list">
          <?php foreach ($points as $point): ?><li><?= wicon('check') ?> <?= $fmt($point) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('senior.beyond_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
