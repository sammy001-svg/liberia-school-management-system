<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$isEvent = $post['category'] === 'event';
$bodyParas = array_filter(array_map('trim', preg_split('/\R\s*\R/', (string)$post['body'])), 'strlen');
?>
<section class="page-hero page-hero--article">
  <div class="page-hero-media"><img src="<?= !empty($post['image_url']) ? htmlspecialchars($post['image_url']) : $im('news.hero_image') ?>" alt="" fetchpriority="high"></div>
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="<?= $url() ?>">Home</a><span aria-hidden="true">/</span>
      <a href="<?= $url('academy-news') ?>">News &amp; Events</a><span aria-hidden="true">/</span>
      <span aria-current="page"><?= $isEvent ? 'Event' : 'News' ?></span>
    </nav>
    <span class="eyebrow eyebrow--light"><?= $isEvent ? 'Event' : 'News' ?> · <?= date('j F Y', strtotime($post['published_on'])) ?></span>
    <h1><?= htmlspecialchars($post['title']) ?></h1>
    <?php if (!empty($post['excerpt'])): ?><p class="lead lead--light"><?= htmlspecialchars($post['excerpt']) ?></p><?php endif; ?>
    <?php if ($isEvent && (!empty($post['event_date']) || !empty($post['event_location']))): ?>
      <div class="chips">
        <?php if (!empty($post['event_date'])): ?><span class="chip"><?= wicon('calendar') ?> <?= date('l, j F Y', strtotime($post['event_date'])) ?></span><?php endif; ?>
        <?php if (!empty($post['event_location'])): ?><span class="chip"><?= wicon('map-pin') ?> <?= htmlspecialchars($post['event_location']) ?></span><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <article class="article">
      <?php foreach ($bodyParas as $para): ?>
        <p><?= nl2br($fmt($para), false) ?></p>
      <?php endforeach; ?>
      <div class="article-foot">
        <a href="<?= $url('academy-news') ?>" class="btn btn-outline-dark">&larr; All news &amp; events</a>
        <a href="<?= $url('apply') ?>" class="btn btn-accent">Apply Online <?= wicon('arrow-right') ?></a>
      </div>
    </article>
  </div>
</section>

<?php if (!empty($more)): ?>
<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Keep Reading</span>
      <h2 class="display">More from <em>campus</em></h2>
    </div>
    <?php $posts = array_slice($more, 0, 3); require __DIR__ . '/partials/post_cards.php'; ?>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
