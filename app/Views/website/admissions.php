<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'students-lineup.jpg',
    'eyebrow' => 'Admissions',
    'title'   => 'Begin your child’s enrollment <em>online, today</em>.',
    'lead'    => 'Start the application online in a few minutes, then complete the process on campus. The process is easy and fast, and space is limited.',
    'crumbs'  => ['Admissions' => null],
    'chips'   => [['clipboard', 'Apply online'], ['calendar', 'Placement exams July – August'], ['building', 'Complete on campus']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Why Enroll Your Child at CELDI Academy</span>
      <h2 class="display">An investment in <em>who your child becomes</em></h2>
      <p>CELDI Academy is a transformative K-12 educational institution dedicated to “changing Liberia one child at a time,” by “Pursuing Truth, Transforming Lives, and Serving God.” Grounded in its core pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — the Academy provides a holistic learning experience from nurturing early childhood daycare to rigorous senior high school.</p>
      <p>By integrating high academic standards with practical technical training, such as its signature computer and software curriculum, CELDI ensures students graduate with both the intellectual competence and digital proficiency required for the modern world.</p>
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:28px;">
        <a href="<?= $url('apply') ?>" class="btn btn-plum btn-lg">Start Online Application <?= wicon('arrow-right') ?></a>
      </div>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $img('assembly-courtyard.jpg') ?>" alt="CELDI students at assembly" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $img('early-childhood.jpg') ?>" alt="Young children playing and learning" loading="lazy"></div>
      <div class="collage-badge">
        <img src="<?= $img('celdi-logo.png') ?>" alt="" loading="lazy">
        <div><strong>Limited space</strong><span>Apply early to secure a place</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section--cream" id="how-to-apply">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">How to Enroll</span>
      <h2 class="display">Four simple <em>steps</em></h2>
    </div>
    <div class="steps">
      <article class="step" data-reveal>
        <h3>Apply online</h3>
        <p>Complete the online application with your child’s details, parent or guardian information and supporting documents.</p>
      </article>
      <article class="step" data-reveal data-reveal-delay="1">
        <h3>Pre-registration</h3>
        <p>The school reviews your application and contacts you. Pre-registration runs from July to August.</p>
      </article>
      <article class="step" data-reveal data-reveal-delay="2">
        <h3>Entrance &amp; placement</h3>
        <p>Your child sits the entrance and placement exams so we can place them in the right class.</p>
      </article>
      <article class="step" data-reveal data-reveal-delay="3">
        <h3>Complete on campus</h3>
        <p>Finalize enrollment with the Business Office on campus, and get ready for opening day in September.</p>
      </article>
    </div>
    <div style="text-align:center;margin-top:44px;" data-reveal>
      <a href="<?= $url('apply') ?>" class="btn btn-gold btn-lg">Begin the Online Application <?= wicon('arrow-right') ?></a>
    </div>
  </div>
</section>

<section class="section" id="fees">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Our Fees</span>
      <h2 class="display">Tuition <em>by level</em></h2>
    </div>
    <div class="fee-grid">
      <?php foreach ([
        ['early-childhood.jpg', 'Early Childhood', 'Daycare – K2', '$42,500'],
        ['elementary.jpg', 'Lower Elementary', 'Grades 1 – 3', '$46,500'],
        ['upper-elementary.jpg', 'Upper Elementary', 'Grades 4 – 6', '$47,300'],
        ['junior-high.jpg', 'Junior High', 'Grades 7 – 9', '$50,800'],
      ] as $i => [$image, $level, $grades, $amount]): ?>
        <article class="fee-card" data-reveal data-reveal-delay="<?= $i ?>">
          <img src="<?= $img($image) ?>" alt="" loading="lazy">
          <div class="fee-body">
            <span class="fee-level"><?= $level ?></span>
            <h3><?= $grades ?></h3>
            <div class="fee-amount"><strong><?= $amount ?></strong><span>Tuition</span></div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="fee-note" data-reveal>
      <?= wicon('mail') ?>
      <span>For full details, including Senior High (Grades 10–12) fees, contact the <strong>Business Office</strong> for the information brochure<?php if ($site['email'] !== ''): ?> — <a href="mailto:<?= htmlspecialchars($site['email']) ?>" style="text-decoration:underline;"><?= htmlspecialchars($site['email']) ?></a><?php endif; ?> or call <a href="<?= $telHref($site['phones'][0]) ?>" style="text-decoration:underline;"><?= htmlspecialchars($site['phones'][0]) ?></a>.</span>
    </div>
  </div>
</section>

<?php
$ctaTitle = 'Begin your child’s enrollment <em>online, today!</em>';
$ctaText  = 'You can begin your child’s enrollment online today and complete the process on campus. The process is easy and fast. We have limited space — act now!';
require __DIR__ . '/partials/cta.php';
?>

<?php require __DIR__ . '/partials/footer.php'; ?>
