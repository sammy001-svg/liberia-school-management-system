<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'divisions'; $crumbs = ['Divisions' => null];
$heroChipIcons = ['sun', 'book', 'compass', 'graduation'];
require __DIR__ . '/partials/page_hero.php';
?>

<section class="section">
  <div class="container division-rows">
    <?php foreach (['early' => 'early-childhood', 'elementary' => 'elementary', 'junior' => 'junior-high', 'senior' => 'senior-high'] as $key => $href): ?>
      <article class="division-row">
        <div class="photo-accent" data-reveal>
          <div class="photo-frame"><img src="<?= $im("$key.card_image") ?>" alt="" loading="lazy"></div>
        </div>
        <div data-reveal data-reveal-delay="1">
          <span class="division-grade"><?= $t("$key.card_grade") ?></span>
          <h2 class="display"><?= $t("$key.card_title") ?></h2>
          <?= $paras("$key.overview_text") ?>
          <div class="actions">
            <a href="<?= $url($href) ?>" class="btn btn-plum">Explore the program <?= wicon('arrow-right') ?></a>
            <a href="<?= $url('apply') ?>" class="btn btn-outline-dark">Apply now</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($raw('divisions.statement') !== ''): ?>
<?php if ($raw('divisions.makeup_body') !== ''): ?>
<section class="section section--cream">
  <div class="container">
    <div class="split split--reverse">
      <div class="split-copy" data-reveal>
        <span class="eyebrow">Academic Support</span>
        <h2 class="display"><?= $h('divisions.makeup_title') ?></h2>
        <?= $paras('divisions.makeup_body', 'lead') ?>
      </div>
      <div class="photo-frame" data-reveal data-reveal-delay="1">
        <img src="<?= $img($raw('divisions.makeup_image') ?: 'geography.jpg') ?>" alt="A make-up class in progress" loading="lazy">
      </div>
    </div>
    <?php if ($items = $list('divisions.makeup')): ?>
      <div class="card-grid-3" style="margin-top:40px;">
        <?php foreach ($items as $i => [$title, $text]): ?>
          <article class="info-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
            <span class="icon-badge <?= ['', 'icon-badge--green', 'icon-badge--accent'][$i % 3] ?>"><?= wicon(['calendar','clipboard','users','check'][$i % 4]) ?></span>
            <h3><?= $fmt($title) ?></h3>
            <p><?= $fmt($text) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<section class="section section--tight section--lilac">
  <div class="container">
    <div class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      <?= $h('divisions.statement') ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
