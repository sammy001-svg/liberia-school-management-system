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
