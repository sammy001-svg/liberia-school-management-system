<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">Our Core Values</span>
      <h2 class="display display--light"><?= $h('site.values_title') ?></h2>
    </div>
    <div class="values-grid" data-reveal>
      <?php $valueIcons = ['award', 'shield', 'heart', 'hand', 'lightbulb', 'flag', 'star', 'target', 'compass']; ?>
      <?php foreach ($list('site.values') as $i => [$title, $text]): ?>
        <div class="value">
          <?= wicon($valueIcons[$i % count($valueIcons)]) ?>
          <h3><?= $fmt($title) ?></h3>
          <p><?= $fmt($text) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if ($pillars = $lines('site.pillars')): ?>
      <div class="pillar-list" data-reveal>
        <?php foreach ($pillars as $p): ?><span><?= htmlspecialchars($p) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
