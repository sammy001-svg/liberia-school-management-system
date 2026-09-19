<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'junior'; $crumbs = ['Divisions' => $url('divisions'), 'Junior High' => null];
$heroChipIcons = ['target', 'monitor', 'users'];
require __DIR__ . '/partials/page_hero.php';
?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">The Junior High Program</span>
      <h2 class="display"><?= $h('junior.intro_title') ?></h2>
      <?= $paras('junior.intro_body') ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('junior.intro_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">What Defines Junior High</span>
      <h2 class="display"><?= $h('junior.features_title') ?></h2>
    </div>
    <div class="feature-grid">
      <?php $featIcons = [['target', ''], ['monitor', 'icon-badge--green'], ['graduation', 'icon-badge--gold']]; ?>
      <?php foreach ($list('junior.features') as $i => [$title, $text]): [$icon, $cls] = $featIcons[$i % 3]; ?>
        <article class="feature" data-reveal data-reveal-delay="<?= $i % 3 ?>">
          <span class="feature-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
          <h3><?= $fmt($title) ?></h3>
          <p><?= $fmt($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Growing Commitment</span>
      <h2 class="display"><?= $h('junior.commit_title') ?></h2>
      <?= $paras('junior.commit_body') ?>
      <?php if ($pillars = $lines('site.pillars')): ?>
        <div class="pillar-list pillar-list--dark" style="justify-content:flex-start;margin-top:24px;">
          <?php foreach ($pillars as $p): ?><span><?= htmlspecialchars($p) ?></span><?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('junior.commit_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
