<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'about'; $crumbs = ['About Us' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Story</span>
      <h2 class="display"><?= $h('about.story_title') ?></h2>
      <?= $paras('about.story_body') ?>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $im('about.story_image1') ?>" alt="" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $im('about.story_image2') ?>" alt="" loading="lazy"></div>
      <div class="collage-badge">
        <img src="<?= $im('site.logo') ?>" alt="" loading="lazy">
        <div><strong>K–12</strong><span>Daycare to Senior High</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Vision · Mission · Motto</span>
      <h2 class="display">What we <em>stand</em> for</h2>
    </div>
    <?php require __DIR__ . '/partials/vision_mission.php'; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/values.php'; ?>

<section class="section">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Why Choose <?= htmlspecialchars($site['name']) ?>?</span>
      <h2 class="display"><?= $h('about.why_title') ?></h2>
      <?= $paras('about.why_body') ?>
      <ul class="icon-list" style="margin-top:32px;">
        <?php $whyIcons = [['book', ''], ['users', 'icon-badge--green'], ['award', 'icon-badge--accent']]; ?>
        <?php foreach ($list('about.why_items') as $i => [$title, $text]): [$icon, $cls] = $whyIcons[$i % 3]; ?>
          <li>
            <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
            <div><h3><?= $fmt($title) ?></h3><p><?= $fmt($text) ?></p></div>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame photo-frame--tall"><img src="<?= $im('about.why_image') ?>" alt="" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--lilac">
  <div class="container">
    <div class="community" data-reveal>
      <div class="community-media"><img src="<?= $im('about.community_image') ?>" alt="" loading="lazy"></div>
      <div class="community-copy">
        <span class="eyebrow eyebrow--light">Community Partnership</span>
        <h2 class="display display--light"><?= $h('about.community_title') ?></h2>
        <?= $paras('about.community_body') ?>
      </div>
    </div>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <div class="split" style="gap:48px;">
      <div data-reveal>
        <span class="eyebrow">Our Affiliation</span>
        <h2 class="display"><?= $h('about.affiliation_title') ?></h2>
      </div>
      <div data-reveal data-reveal-delay="1">
        <?php if (trim($raw('site.affiliation_logo')) !== ''): ?>
          <img src="<?= $im('site.affiliation_logo') ?>" alt="" style="width:300px;margin-bottom:20px;" loading="lazy">
        <?php endif; ?>
        <?= $paras('about.affiliation_body', 'lead') ?>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
