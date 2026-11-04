<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'sponsorship'; $crumbs = ['Divisions' => $url('divisions'), 'Sponsorship' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container">
    <div class="split">
      <div class="split-copy" data-reveal>
        <span class="eyebrow">How It Works</span>
        <h2 class="display"><?= $h('sponsorship.intro_title') ?></h2>
        <?= $paras('sponsorship.intro_body', 'lead') ?>
        <?php if (WebsiteContent::linkHref($raw('home.sponsor_url')) !== ''): ?>
          <a class="btn btn-accent btn-lg" href="<?= htmlspecialchars(WebsiteContent::linkHref($raw('home.sponsor_url'))) ?>" target="_blank" rel="noopener">
            <?= htmlspecialchars($raw('home.sponsor_button') ?: 'Sponsor a student') ?> <?= wicon('arrow-right') ?>
          </a>
          <p class="sponsor-note">Sponsorship is handled on our partner's secure site.</p>
        <?php endif; ?>
      </div>
      <div class="photo-frame" data-reveal data-reveal-delay="1">
        <img src="<?= $im('sponsorship.hero_image') ?>" alt="Students at CELDI Academy" loading="lazy">
      </div>
    </div>

    <?php if ($steps = $list('sponsorship.steps')): ?>
      <div class="card-grid-3" style="margin-top:44px;">
        <?php $stepIcons = ['heart', 'hand', 'clipboard', 'graduation']; ?>
        <?php foreach ($steps as $i => [$title, $text]): ?>
          <article class="info-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
            <span class="icon-badge <?= ['', 'icon-badge--green', 'icon-badge--accent'][$i % 3] ?>"><?= wicon($stepIcons[$i % count($stepIcons)]) ?></span>
            <h3><?= $fmt($title) ?></h3>
            <p><?= $fmt($text) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Sponsored Students</span>
      <h2 class="display"><?= $h('sponsorship.students_title') ?></h2>
      <?= $paras('sponsorship.students_lead', 'lead') ?>
    </div>

    <?php if ($sponsored): ?>
      <div class="sponsored-grid">
        <?php foreach ($sponsored as $i => $child): ?>
          <article class="sponsored-card" data-reveal data-reveal-delay="<?= $i % 4 ?>">
            <div class="sponsored-photo">
              <?php if (!empty($child['photo'])): ?>
                <img src="<?= htmlspecialchars($child['photo']) ?>" alt="<?= htmlspecialchars($child['name']) ?>" loading="lazy">
              <?php else: ?>
                <span class="sponsored-initials"><?= htmlspecialchars(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($child['name'])), 0, 2)))) ?></span>
              <?php endif; ?>
            </div>
            <div class="sponsored-body">
              <h3><?= htmlspecialchars($child['name']) ?></h3>
              <?php if (!empty($child['class_name'])): ?><span class="sponsored-class"><?= htmlspecialchars($child['class_name']) ?></span><?php endif; ?>
              <span class="sponsored-scheme"><?= wicon('heart') ?> <?= htmlspecialchars($child['scheme_name']) ?></span>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <p class="sponsored-note" data-reveal>
        Shown with their families' written permission. Children whose families have asked us not to publish their photograph appear by name only.
      </p>
    <?php else: ?>
      <div class="sponsored-empty" data-reveal><?= $paras('sponsorship.students_empty', 'lead') ?></div>
    <?php endif; ?>

    <?php if (!empty($schemes)): ?>
      <div class="scheme-strip" data-reveal>
        <span>Our sponsorship partners:</span>
        <?php foreach ($schemes as $name): ?><span class="chip"><?= htmlspecialchars($name) ?></span><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
