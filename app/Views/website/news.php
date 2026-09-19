<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'assembly-courtyard.jpg',
    'eyebrow' => 'News & Events',
    'title'   => 'What’s happening at <em>CELDI Academy</em>.',
    'lead'    => 'Key dates for the academic year, PTA meetings, exams and breaks, and highlights from campus.',
    'crumbs'  => ['News & Events' => null],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Calendar of Events</span>
      <h2 class="display">The academic <em>year</em></h2>
      <p class="lead">Plan ahead with the main milestones of the CELDI Academy school year. Exact dates are shared with families through the school and the parent portal.</p>
    </div>
    <?php require __DIR__ . '/partials/calendar.php'; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Upcoming Highlights</span>
      <h2 class="display">Moments to <em>mark</em></h2>
    </div>
    <div class="card-grid-3">
      <article class="info-card" data-reveal>
        <span class="icon-badge"><?= wicon('clipboard') ?></span>
        <h3>Pre-registration &amp; Placement</h3>
        <p><strong>July – August.</strong> Pre-registration opens along with entrance and placement exams for new students. Applications can be started online at any time.</p>
        <p style="margin-top:16px;"><a href="<?= $url('apply') ?>" class="text-link">Apply online <?= wicon('arrow-right') ?></a></p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="1">
        <span class="icon-badge icon-badge--green"><?= wicon('sun') ?></span>
        <h3>Opening Day</h3>
        <p><strong>September.</strong> The first semester opens, followed by periodic tests, the first PTA meeting, the Christmas break and semester exams.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="2">
        <span class="icon-badge icon-badge--gold"><?= wicon('users') ?></span>
        <h3>PTA Meetings</h3>
        <p><strong>Three times a year.</strong> One PTA meeting in the first semester and two in the second, keeping families and teachers working together.</p>
      </article>
    </div>
  </div>
</section>

<section class="section section--lilac">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">From Campus</span>
      <h2 class="display">Photo <em>highlights</em></h2>
    </div>
    <?php require __DIR__ . '/partials/gallery.php'; ?>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <div class="split" style="gap:48px;align-items:center;">
      <div data-reveal>
        <span class="eyebrow">Stay Informed</span>
        <h2 class="display">Already a CELDI <em>family</em>?</h2>
      </div>
      <div data-reveal data-reveal-delay="1">
        <p class="lead">Parents and students receive announcements, report cards, attendance and fee information directly in the school portal.</p>
        <a href="<?= $url('login') ?>" class="btn btn-plum"><?= wicon('login') ?> <?= $portalLabel ?></a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
