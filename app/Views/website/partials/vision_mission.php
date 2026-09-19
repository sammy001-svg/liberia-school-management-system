<div class="pillars-3">
  <article class="pillar-card" data-reveal>
    <span class="icon-badge"><?= wicon('target') ?></span>
    <h3>Our Vision</h3>
    <?= $paras('site.vision') ?>
  </article>
  <article class="pillar-card" data-reveal data-reveal-delay="1">
    <span class="icon-badge icon-badge--green"><?= wicon('compass') ?></span>
    <h3>Our Mission</h3>
    <?= $paras('site.mission') ?>
  </article>
  <article class="pillar-card pillar-card--feature" data-reveal data-reveal-delay="2">
    <span class="icon-badge"><?= wicon('star') ?></span>
    <h3>Our Motto</h3>
    <p>“<?= htmlspecialchars($site['motto']) ?>”</p>
  </article>
</div>
