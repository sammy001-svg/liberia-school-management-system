<?php require __DIR__ . '/partials/header.php'; ?>

<section class="hero">
  <div class="hero-media"><img src="<?= $img('hero-assembly.jpg') ?>" alt="" fetchpriority="high"></div>
  <div class="container hero-content">
    <div class="hero-copy">
      <span class="eyebrow eyebrow--light">K–12 · Ben Town, Margibi County</span>
      <h1>Changing Liberia, <em>one child</em> at a time.</h1>
      <p class="lead">A Christ-centered, technological and vocational education, from nurturing daycare to rigorous senior high. <?= htmlspecialchars($site['motto']) ?></p>
      <div class="hero-actions">
        <a href="<?= $url('apply') ?>" class="btn btn-gold btn-lg">Apply Online <?= wicon('arrow-right') ?></a>
        <a href="<?= $url('divisions') ?>" class="btn btn-outline-light btn-lg">Explore Our Divisions</a>
      </div>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><strong>K–12</strong><span>Daycare to Grade 12</span></div>
      <div class="hero-stat"><strong>4</strong><span>School divisions</span></div>
      <div class="hero-stat"><strong>8</strong><span>Core CELDI pillars</span></div>
      <div class="hero-stat"><strong>2022</strong><span>Year established</span></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">About CELDI Academy</span>
      <h2 class="display">A sanctuary for <em>excellence</em>, rooted in faith and community.</h2>
      <p>CELDI Academy is a transformative K-12 educational institution dedicated to “changing Liberia one child at a time,” by “Pursuing Truth, Transforming Lives, and Serving God.” Grounded in its core pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — the Academy provides a holistic learning experience that spans from nurturing early childhood daycare to rigorous senior high school.</p>
      <p>By integrating high academic standards with practical technical training, such as its signature computer and software curriculum, CELDI ensures students graduate with both the intellectual competence and digital proficiency required for the modern world.</p>
      <a href="<?= $url('about-us') ?>" class="text-link">Discover our story <?= wicon('arrow-right') ?></a>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $img('assembly-courtyard.jpg') ?>" alt="CELDI Academy students at morning assembly" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $img('students-lineup.jpg') ?>" alt="CELDI students in their purple and teal uniforms" loading="lazy"></div>
      <div class="collage-badge">
        <img src="<?= $img('celdi-logo.png') ?>" alt="" loading="lazy">
        <div><strong>Est. 2022</strong><span>Serving Margibi County</span></div>
      </div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Who We Are</span>
      <h2 class="display">Guided by a clear <em>purpose</em></h2>
    </div>
    <?php require __DIR__ . '/partials/vision_mission.php'; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Our School Divisions</span>
      <h2 class="display">One journey, <em>four</em> divisions</h2>
      <p class="lead">From a child’s very first day in daycare to graduation from senior high, every stage is designed to build both competence and character.</p>
    </div>
    <?php require __DIR__ . '/partials/division_cards.php'; ?>
  </div>
</section>

<section class="section section--lilac">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Why Choose CELDI Academy?</span>
      <h2 class="display">More than a school — <em>a movement</em></h2>
      <p class="lead">Our method is more than traditional learning: we’re developing future leaders who are proactive and serve others. By choosing CELDI Academy, you invest in a future where excellence and empathy go hand in hand.</p>
    </div>
    <div class="feature-grid">
      <article class="feature" data-reveal>
        <span class="feature-num">01</span>
        <span class="icon-badge"><?= wicon('book') ?></span>
        <h3>Quality Education</h3>
        <p>We follow the Liberian educational structure with enhanced learning materials and modern teaching methods that work for all learners. We prioritize critical thinking and practical application over memorization, because quality education is the most powerful tool for breaking the cycle of poverty.</p>
      </article>
      <article class="feature" data-reveal data-reveal-delay="1">
        <span class="feature-num">02</span>
        <span class="icon-badge icon-badge--green"><?= wicon('users') ?></span>
        <h3>Qualified Teachers</h3>
        <p>We don’t just hire teachers; we develop mentors. Our staff take part in regular training and professional workshops, and through global partnerships they benefit from cross-cultural training that meets international standards of excellence.</p>
      </article>
      <article class="feature" data-reveal data-reveal-delay="2">
        <span class="feature-num">03</span>
        <span class="icon-badge icon-badge--gold"><?= wicon('award') ?></span>
        <h3>Proven Excellence</h3>
        <p>Excellence here is a measurable reality. Our “chapters” model for Elementary, Junior High and Senior High ensures every student graduates with a <strong>Leadership Development Certificate</strong>, ready to lead and to serve their community with distinction.</p>
      </article>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/values.php'; ?>

<section class="section">
  <div class="container">
    <div class="community" data-reveal>
      <div class="community-media"><img src="<?= $img('students-walk.jpg') ?>" alt="CELDI students walking together on campus" loading="lazy"></div>
      <div class="community-copy">
        <span class="eyebrow eyebrow--light">Community Partnership</span>
        <h2 class="display display--light">Roots that run <em>deep</em></h2>
        <p>Through our <strong>“Third Place Initiative,”</strong> CELDI Academy serves as a sanctuary during after-school hours. Our mentorship, feeding and leadership programs reflect our commitment to the long-term growth and prosperity of our students.</p>
        <p>We work closely with parents and community leaders so that progress made in the classroom becomes transformation for the neighborhood.</p>
        <div class="community-points">
          <span><?= wicon('users') ?> Mentorship</span>
          <span><?= wicon('utensils') ?> Feeding program</span>
          <span><?= wicon('graduation') ?> Leadership</span>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Calendar of Events</span>
      <h2 class="display">The academic <em>year</em> at a glance</h2>
    </div>
    <?php require __DIR__ . '/partials/calendar.php'; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">Life at CELDI</span>
      <h2 class="display">Moments from <em>campus</em></h2>
    </div>
    <?php require __DIR__ . '/partials/gallery.php'; ?>
  </div>
</section>

<section class="section section--tight section--lilac">
  <div class="container">
    <p class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      Education is a partnership between the school and the home. We commit to raising the next generation of <em>visionary leaders</em>, starting from the very first step they take through our doors.
    </p>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
