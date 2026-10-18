<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'news'; $crumbs = ['News & Events' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<?php if (!empty($upcoming)): ?>
<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">What's Coming</span>
      <h2 class="display">Upcoming <em>events</em></h2>
      <p class="lead">Dates for the diary — assemblies, examinations, sports and parent meetings.</p>
    </div>
    <div class="event-list">
      <?php foreach ($upcoming as $i => $e): ?>
        <a class="event-row" href="<?= $url('academy-news/' . (int)$e['id']) ?>" data-reveal data-reveal-delay="<?= $i % 3 ?>">
          <div class="event-date">
            <?php if (!empty($e['event_date'])): ?>
              <span class="event-day"><?= date('j', strtotime($e['event_date'])) ?></span>
              <span class="event-month"><?= date('M', strtotime($e['event_date'])) ?></span>
              <span class="event-year"><?= date('Y', strtotime($e['event_date'])) ?></span>
            <?php else: ?>
              <span class="event-day">TBC</span>
            <?php endif; ?>
          </div>
          <div class="event-copy">
            <h3><?= htmlspecialchars($e['title']) ?></h3>
            <?php if (!empty($e['event_location'])): ?>
              <span class="event-where"><?= wicon('map-pin') ?> <?= htmlspecialchars($e['event_location']) ?></span>
            <?php endif; ?>
            <?php $sum = $e['excerpt'] ?: mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string)$e['body']))), 0, 160, '…'); ?>
            <?php if ($sum !== ''): ?><p><?= htmlspecialchars($sum) ?></p><?php endif; ?>
          </div>
          <span class="event-go"><?= wicon('arrow-right') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($projects)): ?>
<section class="section section--lilac">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">In Progress</span>
      <h2 class="display">Ongoing <em>projects</em></h2>
      <p class="lead">What the school is building, running and raising funds for right now.</p>
    </div>
    <div class="card-grid-3">
      <?php foreach ($projects as $i => $pr): ?>
        <article class="info-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
          <span class="icon-badge icon-badge--green"><?= wicon('sun') ?></span>
          <h3><?= htmlspecialchars($pr['title']) ?></h3>
          <?php if (!empty($pr['event_date']) || !empty($pr['end_date'])): ?>
            <span class="project-dates">
              <?= wicon('calendar') ?>
              <?= !empty($pr['event_date']) ? date('j M Y', strtotime($pr['event_date'])) : 'Started' ?>
              <?= !empty($pr['end_date']) ? ' – ' . date('j M Y', strtotime($pr['end_date'])) : ' – ongoing' ?>
            </span>
          <?php endif; ?>
          <?php $sum = $pr['excerpt'] ?: mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string)$pr['body']))), 0, 170, '…'); ?>
          <?php if ($sum !== ''): ?><p><?= htmlspecialchars($sum) ?></p><?php endif; ?>
          <a class="text-link" href="<?= $url('academy-news/' . (int)$pr['id']) ?>">Read more <?= wicon('arrow-right') ?></a>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

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
