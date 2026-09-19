<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'news'; $crumbs = ['News & Events' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<?php if (!empty($posts)): ?>
<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">News &amp; Events</span>
      <h2 class="display"><?= $h('news.posts_title') ?></h2>
    </div>
    <?php require __DIR__ . '/partials/post_cards.php'; ?>
  </div>
</section>
<?php endif; ?>

<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Calendar of Events</span>
      <h2 class="display"><?= $h('news.calendar_title') ?></h2>
      <?= $paras('news.calendar_lead', 'lead') ?>
    </div>
    <?php require __DIR__ . '/partials/calendar.php'; ?>
  </div>
</section>

<?php if ($highlights = $list('news.highlights', 3)): ?>
<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Upcoming Highlights</span>
      <h2 class="display"><?= $h('news.highlights_title') ?></h2>
    </div>
    <div class="card-grid-3">
      <?php $hlIcons = [['clipboard', ''], ['sun', 'icon-badge--green'], ['users', 'icon-badge--accent']]; ?>
      <?php foreach ($highlights as $i => [$title, $when, $text]): [$icon, $cls] = $hlIcons[$i % 3]; ?>
        <article class="info-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
          <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
          <h3><?= $fmt($title) ?></h3>
          <p><?php if ($when !== ''): ?><strong><?= htmlspecialchars($when) ?>.</strong> <?php endif; ?><?= $fmt($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:36px;" data-reveal>
      <a href="<?= $url('apply') ?>" class="text-link">Start an application online <?= wicon('arrow-right') ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--lilac">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">From Campus</span>
      <h2 class="display"><?= $h('news.gallery_title') ?></h2>
    </div>
    <?php require __DIR__ . '/partials/gallery.php'; ?>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <div class="split" style="gap:48px;align-items:center;">
      <div data-reveal>
        <span class="eyebrow">Stay Informed</span>
        <h2 class="display"><?= $h('news.portal_title') ?></h2>
      </div>
      <div data-reveal data-reveal-delay="1">
        <?= $paras('news.portal_text', 'lead') ?>
        <a href="<?= $url('login') ?>" class="btn btn-plum"><?= wicon('login') ?> <?= $portalLabel ?></a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
