<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroSlides = [
    [$im('home.hero_image'),  'CELDI students gathered in the school courtyard'],
    [$im('home.hero_image2'), 'The whole school waving at morning assembly'],
    [$im('home.hero_image3'), 'CELDI students in their purple and teal uniforms'],
];
?>
<section class="hero" data-hero-carousel aria-roledescription="carousel" aria-label="Life at <?= htmlspecialchars($site['name']) ?>">
  <div class="hero-media">
    <?php foreach ($heroSlides as $i => [$src, $alt]): ?>
      <img class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" src="<?= $src ?>" alt="<?= htmlspecialchars($alt) ?>"
           aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>" data-hero-slide <?= $i === 0 ? 'fetchpriority="high"' : 'loading="eager" fetchpriority="low"' ?>>
    <?php endforeach; ?>
  </div>
  <div class="hero-controls">
    <button type="button" class="hero-arrow" data-hero-prev aria-label="Previous photo"><?= wicon('arrow-right', 'wi-flip') ?></button>
    <div class="hero-dots" role="group" aria-label="Choose a photo">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <button type="button" class="hero-dot<?= $i === 0 ? ' is-active' : '' ?>" data-hero-dot="<?= $i ?>" aria-label="Photo <?= $i + 1 ?> of <?= count($heroSlides) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
          <span class="hero-dot-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <span class="hero-dot-bar"><span></span></span>
        </button>
      <?php endforeach; ?>
    </div>
    <button type="button" class="hero-arrow" data-hero-next aria-label="Next photo"><?= wicon('arrow-right') ?></button>
  </div>
  <div class="container hero-content">
    <div class="hero-copy">
      <?php if ($raw('home.hero_eyebrow') !== ''): ?><span class="eyebrow eyebrow--light"><?= $t('home.hero_eyebrow') ?></span><?php endif; ?>
      <h1><?= $h('home.hero_title') ?></h1>
      <?= $paras('home.hero_lead', 'lead') ?>
      <div class="hero-actions">
        <a href="<?= $url('apply') ?>" class="btn btn-gold btn-lg">Apply Online <?= wicon('arrow-right') ?></a>
        <a href="<?= $url('divisions') ?>" class="btn btn-outline-light btn-lg">Explore Our Divisions</a>
      </div>
    </div>
    <?php if ($stats = array_slice($list('home.hero_stats'), 0, 4)): ?>
      <div class="hero-stats">
        <?php foreach ($stats as [$num, $label]): ?>
          <div class="hero-stat"><strong><?= htmlspecialchars($num) ?></strong><span><?= htmlspecialchars($label) ?></span></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">About <?= htmlspecialchars($site['name']) ?></span>
      <h2 class="display"><?= $h('home.about_title') ?></h2>
      <?= $paras('home.about_body') ?>
      <a href="<?= $url('about-us') ?>" class="text-link">Discover our story <?= wicon('arrow-right') ?></a>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $im('home.about_image1') ?>" alt="" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $im('home.about_image2') ?>" alt="" loading="lazy"></div>
      <?php [$badgeTitle, $badgeText] = $list('home.about_badge')[0] ?? ['', '']; ?>
      <?php if ($badgeTitle !== ''): ?>
        <div class="collage-badge">
          <img src="<?= $im('site.logo') ?>" alt="" loading="lazy">
          <div><strong><?= htmlspecialchars($badgeTitle) ?></strong><span><?= htmlspecialchars($badgeText) ?></span></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Who We Are</span>
      <h2 class="display">Guided by a clear <em>purpose</em></h2>
    </div>
    <?php require __DIR__ . '/partials/vision_mission.php'; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Our School Divisions</span>
      <h2 class="display"><?= $h('home.divisions_title') ?></h2>
      <?= $paras('home.divisions_lead', 'lead') ?>
    </div>
    <?php require __DIR__ . '/partials/division_cards.php'; ?>
  </div>
</section>

<section class="section section--lilac">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Why Choose <?= htmlspecialchars($site['name']) ?>?</span>
      <h2 class="display"><?= $h('home.why_title') ?></h2>
      <?= $paras('home.why_lead', 'lead') ?>
    </div>
    <div class="feature-grid">
      <?php $whyIcons = [['book', ''], ['users', 'icon-badge--green'], ['award', 'icon-badge--gold']]; ?>
      <?php foreach ($list('home.why_items') as $i => [$title, $text]): [$icon, $cls] = $whyIcons[$i % 3]; ?>
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

<?php require __DIR__ . '/partials/values.php'; ?>

<section class="section">
  <div class="container">
    <div class="community" data-reveal>
      <div class="community-media"><img src="<?= $im('home.community_image') ?>" alt="" loading="lazy"></div>
      <div class="community-copy">
        <span class="eyebrow eyebrow--light">Community Partnership</span>
        <h2 class="display display--light"><?= $h('home.community_title') ?></h2>
        <?= $paras('home.community_body') ?>
        <?php if ($tags = $lines('home.community_tags')): ?>
          <div class="community-points">
            <?php $tagIcons = ['users', 'utensils', 'graduation', 'heart', 'star']; ?>
            <?php foreach ($tags as $i => $tag): ?>
              <span><?= wicon($tagIcons[$i % count($tagIcons)]) ?> <?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($posts)): ?>
<section class="section section--lilac">
  <div class="container">
    <div class="section-head-row" data-reveal>
      <div>
        <span class="eyebrow">News &amp; Events</span>
        <h2 class="display">Latest from <em>campus</em></h2>
      </div>
      <a href="<?= $url('academy-news') ?>" class="text-link">All news &amp; events <?= wicon('arrow-right') ?></a>
    </div>
    <?php require __DIR__ . '/partials/post_cards.php'; ?>
  </div>
</section>
<?php endif; ?>

<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Calendar of Events</span>
      <h2 class="display">The academic <em>year</em> at a glance</h2>
    </div>
    <?php require __DIR__ . '/partials/calendar.php'; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Life at <?= htmlspecialchars($site['name']) ?></span>
      <h2 class="display">Moments from <em>campus</em></h2>
    </div>
    <?php require __DIR__ . '/partials/gallery.php'; ?>
  </div>
</section>

<?php if ($raw('home.statement') !== ''): ?>
<section class="section section--tight section--lilac">
  <div class="container">
    <div class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      <?= $h('home.statement') ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
