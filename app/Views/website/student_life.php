<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'students-walk.jpg',
    'eyebrow' => 'Student Life',
    'title'   => 'Learning that extends <em>beyond the bell</em>.',
    'lead'    => 'Mentorship, leadership, service and community: the life of a CELDI student reaches well past the classroom.',
    'crumbs'  => ['About' => $url('about-us'), 'Student Life' => null],
    'chips'   => [['users', 'Mentorship'], ['utensils', 'Feeding program'], ['graduation', 'Leadership chapters']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">The Third Place Initiative</span>
      <h2 class="display">A sanctuary after <em>school hours</em></h2>
      <p>CELDI Academy’s roots run deep in the communities it serves. Through our “Third Place Initiative,” the Academy serves as a sanctuary during after-school hours — a safe, positive place between home and classroom.</p>
      <p>Our mentorship, feeding, and leadership programs reflect our commitment to the long-term growth and prosperity of our students, so that the progress made in the classroom translates into transformation for the neighborhood.</p>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $img('assembly-courtyard.jpg') ?>" alt="Students gathered in the school courtyard" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $img('students-lineup.jpg') ?>" alt="CELDI students in uniform" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Beyond the Classroom</span>
      <h2 class="display">Programs that shape <em>the whole child</em></h2>
    </div>
    <div class="card-grid-3">
      <article class="info-card" data-reveal>
        <span class="icon-badge"><?= wicon('users') ?></span>
        <h3>Mentorship</h3>
        <p>Our teachers are developed as mentors, and our mentorship programs walk alongside students as they grow in confidence, character, and faith.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="1">
        <span class="icon-badge icon-badge--green"><?= wicon('utensils') ?></span>
        <h3>Feeding Program</h3>
        <p>Part of our commitment to the whole child: students who are cared for are students who are ready to learn and thrive.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="2">
        <span class="icon-badge icon-badge--gold"><?= wicon('graduation') ?></span>
        <h3>Leadership Chapters</h3>
        <p>Our “chapters” model for Elementary, Junior High and Senior High ensures every student graduates with a Leadership Development Certificate.</p>
      </article>
      <article class="info-card" data-reveal>
        <span class="icon-badge icon-badge--gold"><?= wicon('monitor') ?></span>
        <h3>Computer &amp; Software</h3>
        <p>CELDI’s signature computer and software curriculum builds the digital proficiency required for the modern world.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="1">
        <span class="icon-badge"><?= wicon('hand') ?></span>
        <h3>Service</h3>
        <p>Students learn that true leadership begins with a heart for service to their community, county, and nation.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="2">
        <span class="icon-badge icon-badge--green"><?= wicon('flag') ?></span>
        <h3>Patriotism &amp; Heritage</h3>
        <p>We instill pride in Liberian heritage, culture, and civic responsibility in every student.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Gallery</span>
      <h2 class="display">Life at <em>CELDI</em></h2>
    </div>
    <?php $galleryItems = [
        ['assembly-panorama.jpg', 'The whole school gathered for assembly', 'gallery-item--big'],
        ['students-walk.jpg', 'Students walking together on campus', 'gallery-item--wide'],
        ['assembly-courtyard.jpg', 'Morning assembly in the courtyard', ''],
        ['students-lineup.jpg', 'Students in uniform', ''],
        ['hero-assembly.jpg', 'Students at assembly', 'gallery-item--wide'],
        ['gallery-5.jpg', 'Students exploring technology', ''],
        ['gallery-2.jpg', 'Students in class', ''],
    ]; require __DIR__ . '/partials/gallery.php'; ?>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow eyebrow--light">Through the Year</span>
      <h2 class="display display--light">The rhythm of the <em>school year</em></h2>
    </div>
    <?php require __DIR__ . '/partials/calendar.php'; ?>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
