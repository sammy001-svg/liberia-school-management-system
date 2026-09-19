<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'leadership'; $crumbs = ['About' => $url('about-us'), 'Our Leadership' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<?php if ($raw('leadership.statement') !== ''): ?>
<section class="section section--tight section--lilac">
  <div class="container">
    <div class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      <?= $h('leadership.statement') ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($leaders)): ?>
<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Our People</span>
      <h2 class="display"><?= $h('leadership.team_title') ?></h2>
    </div>
    <div class="team-grid">
      <?php foreach ($leaders as $i => $leader): ?>
        <article class="team-card" data-reveal data-reveal-delay="<?= $i % 4 ?>">
          <div class="team-photo">
            <?php if (!empty($leader['photo_url'])): ?>
              <img src="<?= htmlspecialchars($leader['photo_url']) ?>" alt="<?= htmlspecialchars($leader['name']) ?>" loading="lazy">
            <?php else: ?>
              <span class="team-initials"><?= htmlspecialchars(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($leader['name'])), 0, 2)))) ?></span>
            <?php endif; ?>
          </div>
          <div class="team-body">
            <h3><?= htmlspecialchars($leader['name']) ?></h3>
            <span class="team-role"><?= htmlspecialchars($leader['position']) ?></span>
            <?php if (!empty($leader['bio'])): ?><p><?= nl2br(htmlspecialchars($leader['bio']), false) ?></p><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section<?= !empty($leaders) ? ' section--cream' : '' ?>">
  <div class="container">
    <?php foreach ([
      ['board', 'building', '', 'Governance'],
      ['admin', 'compass', 'icon-badge--green', 'Administration'],
      ['staff', 'users', 'icon-badge--accent', 'Faculty & Staff'],
      ['pta', 'heart', '', 'Parents'],
    ] as [$key, $icon, $cls, $kicker]): ?>
      <?php if ($raw("leadership.{$key}_title") === '' && $raw("leadership.{$key}_body") === '') continue; ?>
      <div class="tier" data-reveal>
        <div class="tier-head">
          <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
          <span class="tier-kicker"><?= $kicker ?></span>
          <h3><?= $t("leadership.{$key}_title") ?></h3>
        </div>
        <div class="tier-body">
          <?php $tierParas = preg_split('/\R\s*\R/', trim($raw("leadership.{$key}_body"))); ?>
          <?php foreach ($tierParas as $n => $para): ?>
            <p<?= $n === 0 ? ' class="lead" style="margin-top:0;"' : '' ?>><?= nl2br($fmt(trim($para)), false) ?></p>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/values.php'; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
