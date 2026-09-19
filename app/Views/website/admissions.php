<?php require __DIR__ . '/partials/header.php'; ?>

<?php
$heroKey = 'admissions'; $crumbs = ['Admissions' => null];
$heroChipIcons = ['clipboard', 'calendar', 'building'];
require __DIR__ . '/partials/page_hero.php';
?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Why Enroll Your Child at <?= htmlspecialchars($site['name']) ?></span>
      <h2 class="display"><?= $h('admissions.why_title') ?></h2>
      <?= $paras('admissions.why_body') ?>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:28px;">
        <a href="<?= $url('apply') ?>" class="btn btn-plum btn-lg">Start Online Application <?= wicon('arrow-right') ?></a>
      </div>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $im('admissions.why_image1') ?>" alt="" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $im('admissions.why_image2') ?>" alt="" loading="lazy"></div>
      <?php [$badgeTitle, $badgeText] = $list('admissions.badge')[0] ?? ['', '']; ?>
      <?php if ($badgeTitle !== ''): ?>
        <div class="collage-badge">
          <img src="<?= $im('site.logo') ?>" alt="" loading="lazy">
          <div><strong><?= htmlspecialchars($badgeTitle) ?></strong><span><?= htmlspecialchars($badgeText) ?></span></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--cream" id="how-to-apply">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">How to Enroll</span>
      <h2 class="display"><?= $h('admissions.steps_title') ?></h2>
    </div>
    <div class="steps">
      <?php foreach ($list('admissions.steps') as $i => [$title, $text]): ?>
        <article class="step" data-reveal data-reveal-delay="<?= $i % 4 ?>">
          <h3><?= $fmt($title) ?></h3>
          <p><?= $fmt($text) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <div style="text-align:center;margin-top:44px;" data-reveal>
      <a href="<?= $url('apply') ?>" class="btn btn-gold btn-lg">Begin the Online Application <?= wicon('arrow-right') ?></a>
    </div>
  </div>
</section>

<?php if ($fees = $list('admissions.fees', 3)): ?>
<section class="section" id="fees">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Our Fees</span>
      <h2 class="display"><?= $h('admissions.fees_title') ?></h2>
    </div>
    <div class="fee-grid">
      <?php $feeImages = ['early-childhood.jpg', 'elementary.jpg', 'upper-elementary.jpg', 'junior-high.jpg', 'computer-lab.jpg']; ?>
      <?php foreach ($fees as $i => [$level, $grades, $amount]): ?>
        <article class="fee-card" data-reveal data-reveal-delay="<?= $i % 4 ?>">
          <img src="<?= $img($feeImages[$i % count($feeImages)]) ?>" alt="" loading="lazy">
          <div class="fee-body">
            <span class="fee-level"><?= htmlspecialchars($level) ?></span>
            <h3><?= htmlspecialchars($grades) ?></h3>
            <div class="fee-amount"><strong><?= htmlspecialchars($amount) ?></strong><span><?= $t('admissions.fees_label') ?></span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="fee-note" data-reveal>
      <?= wicon('mail') ?>
      <span>
        <?= $h('admissions.fees_note') ?>
        <?php if ($site['email'] !== ''): ?> Email <a href="mailto:<?= htmlspecialchars($site['email']) ?>" style="text-decoration:underline;"><?= htmlspecialchars($site['email']) ?></a> or call<?php else: ?> Call<?php endif; ?>
        <a href="<?= $telHref($site['phones'][0]) ?>" style="text-decoration:underline;"><?= htmlspecialchars($site['phones'][0]) ?></a>.
      </span>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $ctaKey = 'admissions'; require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
