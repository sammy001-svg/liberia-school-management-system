<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'elementary'; $crumbs = ['Divisions' => $url('divisions'), 'Elementary' => null];
$heroChipIcons = ['book', 'layers', 'compass'];
require __DIR__ . '/partials/page_hero.php';
$cardIcons = [['book', ''], ['layers', 'icon-badge--green'], ['compass', 'icon-badge--accent']];
?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Elementary Program</span>
      <h2 class="display"><?= $h('elementary.intro_title') ?></h2>
      <?= $paras('elementary.intro_body') ?>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $im('elementary.intro_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Academic Excellence &amp; Foundational Skills</span>
      <h2 class="display"><?= $h('elementary.academic_title') ?></h2>
      <?= $paras('elementary.academic_lead', 'lead') ?>
    </div>
    <div class="card-grid-3">
      <?php foreach ($list('elementary.academic_items') as $i => [$title, $text]): [$icon, $cls] = $cardIcons[$i % 3]; ?>
        <article class="info-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
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
      <span class="eyebrow">Beyond the Classroom</span>
      <h2 class="display"><?= $h('elementary.beyond_title') ?></h2>
      <?= $paras('elementary.beyond_body') ?>
      <ul class="icon-list" style="margin-top:30px;">
        <?php $beyondIcons = [['hand', ''], ['scale', 'icon-badge--green'], ['lightbulb', 'icon-badge--accent']]; ?>
        <?php foreach ($list('elementary.beyond_items') as $i => [$title, $text]): [$icon, $cls] = $beyondIcons[$i % 3]; ?>
          <li>
            <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
            <div><h3><?= $fmt($title) ?></h3><p><?= $fmt($text) ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame photo-frame--tall"><img src="<?= $im('elementary.beyond_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">Our Learning Environment</span>
      <h2 class="display display--light"><?= $h('elementary.env_title') ?></h2>
      <?= $paras('elementary.env_lead', 'lead lead--light') ?>
    </div>
    <div class="values-grid" data-reveal>
      <?php $envIcons = ['heart', 'compass', 'users', 'star']; ?>
      <?php foreach ($list('elementary.env_items') as $i => [$title, $text]): ?>
        <div class="value"><?= wicon($envIcons[$i % 4]) ?><h3><?= $fmt($title) ?></h3><p><?= $fmt($text) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div data-reveal>
      <span class="eyebrow">Empowering Exploration</span>
      <h2 class="display" style="margin-bottom:22px;"><?= $h('elementary.explore_title') ?></h2>
      <?= $paras('elementary.explore_body', 'muted') ?>
    </div>
    <div class="pillar-card pillar-card--feature" data-reveal data-reveal-delay="1">
      <span class="icon-badge"><?= wicon('target') ?></span>
      <h3>Our Program Goal</h3>
      <?= $paras('elementary.goal') ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
