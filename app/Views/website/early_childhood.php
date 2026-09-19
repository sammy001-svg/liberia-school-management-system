<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'early'; $crumbs = ['Divisions' => $url('divisions'), 'Early Childhood' => null];
$heroChipIcons = ['home', 'sun', 'heart'];
require __DIR__ . '/partials/page_hero.php';
?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Early Childhood Program</span>
      <h2 class="display"><?= $h('early.intro_title') ?></h2>
      <?= $paras('early.intro_body') ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('early.intro_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Educational Philosophy</span>
      <h2 class="display"><?= $h('early.philosophy_title') ?></h2>
      <?= $paras('early.philosophy_lead', 'lead') ?>
      <?= $paras('early.philosophy_body') ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('early.philosophy_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">Pillars in Early Learning</span>
      <h2 class="display display--light"><?= $h('early.pillars_title') ?></h2>
    </div>
    <?php $pillarItems = $list('early.pillars'); ?>
    <div class="values-grid<?= count($pillarItems) === 5 ? ' values-grid--5' : '' ?>" data-reveal>
      <?php $pillarIcons = ['palette', 'heart', 'star', 'shield', 'lightbulb', 'sparkles']; ?>
      <?php foreach ($pillarItems as $i => [$title, $text]): ?>
        <div class="value">
          <?= wicon($pillarIcons[$i % count($pillarIcons)]) ?>
          <h3><?= $fmt($title) ?></h3>
          <p><?= $fmt($text) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Program Highlights</span>
      <h2 class="display"><?= $h('early.highlights_title') ?></h2>
    </div>
    <div class="card-grid-2">
      <?php $hlIcons = [['book', ''], ['home', 'icon-badge--green'], ['compass', 'icon-badge--accent'], ['award', '']]; ?>
      <?php foreach ($list('early.highlights') as $i => [$title, $text]): [$icon, $cls] = $hlIcons[$i % 4]; ?>
        <article class="info-card" data-reveal data-reveal-delay="<?= $i % 2 ?>">
          <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
          <h3><?= $fmt($title) ?></h3>
          <p><?= $fmt($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($raw('early.statement') !== ''): ?>
<section class="section section--tight section--lilac">
  <div class="container">
    <div class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      <?= $h('early.statement') ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
