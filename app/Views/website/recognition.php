<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroKey = 'recognition'; $crumbs = ['News & Events' => $url('academy-news'), 'Academic Recognition' => null]; require __DIR__ . '/partials/page_hero.php'; ?>

<?php if ($raw('recognition.intro_body') !== ''): ?>
<section class="section section--tight">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Honour Board</span>
      <h2 class="display"><?= $h('recognition.intro_title') ?></h2>
      <?= $paras('recognition.intro_body', 'lead') ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
$boards = [
    'academic'   => ['Academic Recognition', 'Merit', 'Best students of the period, semester and year, and the teachers who taught them.', ''],
    'leadership' => ['Good Leadership', 'Service', 'Students and staff who lead by example, on and off the campus.', 'section--cream'],
    'behaviour'  => ['Excellence in Behaviour', 'Character', 'Conduct that sets the standard for everyone else.', 'section--lilac'],
];
$anyAward = false;
foreach ($awardGroups as $rows) { if ($rows) { $anyAward = true; } }
?>

<?php foreach ($boards as $key => [$title, $eyebrow, $lead, $tone]): $rows = $awardGroups[$key] ?? []; ?>
  <?php if ($rows): ?>
  <section class="section <?= $tone ?>">
    <div class="container">
      <div class="section-head" data-reveal>
        <span class="eyebrow"><?= htmlspecialchars($eyebrow) ?></span>
        <h2 class="display"><?= htmlspecialchars($title) ?></h2>
        <p class="lead"><?= htmlspecialchars($lead) ?></p>
      </div>
      <div class="award-grid">
        <?php foreach ($rows as $i => $a): ?>
          <article class="award-card" data-reveal data-reveal-delay="<?= $i % 3 ?>">
            <div class="award-photo">
              <?php if (!empty($a['photo_url'])): ?>
                <img src="<?= htmlspecialchars($a['photo_url']) ?>" alt="<?= htmlspecialchars($a['recipient_name']) ?>" loading="lazy">
              <?php else: ?>
                <span class="award-initials"><?= htmlspecialchars(mb_strtoupper(mb_substr($a['recipient_name'], 0, 1))) ?></span>
              <?php endif; ?>
              <span class="award-ribbon"><?= htmlspecialchars($a['recipient_role'] === 'teacher' ? 'Teacher' : ($a['recipient_role'] === 'staff' ? 'Staff' : 'Student')) ?></span>
            </div>
            <div class="award-body">
              <span class="award-title"><?= htmlspecialchars($a['title']) ?></span>
              <h3><?= htmlspecialchars($a['recipient_name']) ?></h3>
              <span class="award-meta">
                <?= htmlspecialchars(trim(($a['class_name'] ?? '') . (!empty($a['class_name']) && !empty($a['period_label']) ? ' · ' : '') . ($a['period_label'] ?? ''))) ?>
              </span>
              <?php if (!empty($a['citation'])): ?><p><?= nl2br(htmlspecialchars($a['citation']), false) ?></p><?php endif; ?>
              <?php if (!empty($a['awarded_on'])): ?>
                <span class="award-date"><?= wicon('calendar') ?> <?= date('j M Y', strtotime($a['awarded_on'])) ?></span>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
<?php endforeach; ?>

<?php if (!$anyAward): ?>
<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <h2 class="display">The first awards are on their way</h2>
      <p class="lead">Winners are announced at assembly at the end of each period, semester and year, and appear here once announced.</p>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/cta.php'; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
