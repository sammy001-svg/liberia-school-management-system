<?php
// News/event cards for $posts. A post without its own photo borrows one of the school's.
$postFallbacks = ['assembly-courtyard.jpg', 'students-walk.jpg', 'students-lineup.jpg', 'assembly-panorama.jpg'];
?>
<div class="post-grid">
  <?php foreach ($posts as $i => $post): ?>
    <a class="post-card" href="<?= $url('academy-news/' . (int)$post['id']) ?>" data-reveal data-reveal-delay="<?= $i % 3 ?>">
      <div class="post-media">
        <img src="<?= !empty($post['image_url']) ? htmlspecialchars($post['image_url']) : $img($postFallbacks[$i % count($postFallbacks)]) ?>" alt="" loading="lazy">
        <span class="post-tag post-tag--<?= $post['category'] === 'event' ? 'event' : 'news' ?>"><?= $post['category'] === 'event' ? 'Event' : 'News' ?></span>
      </div>
      <div class="post-body">
        <span class="post-date">
          <?= wicon('calendar') ?>
          <?php if ($post['category'] === 'event' && !empty($post['event_date'])): ?>
            <?= date('D, j M Y', strtotime($post['event_date'])) ?>
          <?php else: ?>
            <?= date('j M Y', strtotime($post['published_on'])) ?>
          <?php endif; ?>
        </span>
        <h3><?= htmlspecialchars($post['title']) ?></h3>
        <?php $summary = $post['excerpt'] ?: mb_strimwidth(trim(preg_replace('/\s+/', ' ', strip_tags((string)$post['body']))), 0, 150, '…'); ?>
        <?php if ($summary !== ''): ?><p><?= htmlspecialchars($summary) ?></p><?php endif; ?>
        <span class="text-link">Read more <?= wicon('arrow-right') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
