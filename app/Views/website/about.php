<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'assembly-panorama.jpg',
    'eyebrow' => 'About Us',
    'title'   => 'A movement dedicated to <em>changing Liberia</em>, one child at a time.',
    'lead'    => 'A K-12 academy in Ben Town, Margibi County, pursuing truth, transforming lives, and serving God.',
    'crumbs'  => ['About Us' => null],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Story</span>
      <h2 class="display">About <em>CELDI</em> Academy</h2>
      <p>CELDI Academy is a transformative K-12 educational institution dedicated to “changing Liberia one child at a time,” by “Pursuing Truth, Transforming Lives, and Serving God.”</p>
      <p>Grounded in its core pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — the Academy provides a holistic learning experience that spans from nurturing early childhood daycare to rigorous senior high school.</p>
      <p>By integrating high academic standards with practical technical training, such as its signature computer and software curriculum, CELDI ensures students graduate with both the intellectual competence and digital proficiency required for the modern world. Through its unique community partnership model and a steadfast commitment to professional ethics and mentorship, the Academy serves as a sanctuary for excellence, empowering students to become disciplined, innovative architects of change, ready to serve their nation with integrity.</p>
    </div>
    <div class="collage" data-reveal data-reveal-delay="1">
      <div class="collage-main"><img src="<?= $img('students-lineup.jpg') ?>" alt="CELDI Academy students lined up in uniform" loading="lazy"></div>
      <div class="collage-inset"><img src="<?= $img('students-walk.jpg') ?>" alt="Students walking together" loading="lazy"></div>
      <div class="collage-badge">
        <img src="<?= $img('celdi-logo.png') ?>" alt="" loading="lazy">
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
      <span class="eyebrow">Why Choose CELDI Academy?</span>
      <h2 class="display">Where excellence and <em>empathy</em> go hand in hand</h2>
      <p>At CELDI Academy, we are more than just a school; we are a movement dedicated to “Changing Liberia one child at a time.” Our method is more than traditional learning; we’re developing future leaders who are proactive and serve others.</p>
      <ul class="icon-list" style="margin-top:32px;">
        <li>
          <span class="icon-badge"><?= wicon('book') ?></span>
          <div>
            <h3>Quality Education</h3>
            <p>The Liberian educational structure, enhanced learning materials and modern teaching that works for all learners. We prioritize critical thinking and practical application over simple memorization.</p>
          </div>
        </li>
        <li>
          <span class="icon-badge icon-badge--green"><?= wicon('users') ?></span>
          <div>
            <h3>Qualified Teachers</h3>
            <p>We don’t just hire teachers; we develop mentors, through regular training, professional workshops and cross-cultural seminars with global partners.</p>
          </div>
        </li>
        <li>
          <span class="icon-badge icon-badge--gold"><?= wicon('award') ?></span>
          <div>
            <h3>Proven Excellence</h3>
            <p>Ambitious goals for graduation and success rates, and a “chapters” model that ensures every student graduates with a Leadership Development Certificate.</p>
          </div>
        </li>
      </ul>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame photo-frame--tall"><img src="<?= $img('geography.jpg') ?>" alt="Students studying a world map together" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--lilac">
  <div class="container">
    <div class="community" data-reveal>
      <div class="community-media"><img src="<?= $img('community-team.jpg') ?>" alt="Educators and community partners in a meeting" loading="lazy"></div>
      <div class="community-copy">
        <span class="eyebrow eyebrow--light">Community Partnership</span>
        <h2 class="display display--light">A “third place” for our <em>community</em></h2>
        <p>CELDI Academy’s roots run deep in the communities it serves. Through our “Third Place Initiative,” we serve as a sanctuary during after-school hours.</p>
        <p>Our mentorship, feeding, and leadership programs reassure the communities of our commitment to the long-term growth and prosperity of our students. We work closely with parents and community leaders to ensure that the progress made in the classroom translates into transformation for the neighborhood.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <div class="split" style="gap:48px;">
      <div data-reveal>
        <span class="eyebrow">Our Affiliation</span>
        <h2 class="display">Part of the <em>UrbanPromise</em> family</h2>
      </div>
      <div data-reveal data-reveal-delay="1">
        <img src="<?= $img('urbanpromise-logo.png') ?>" alt="UrbanPromise International" style="width:300px;margin-bottom:20px;" loading="lazy">
        <p class="lead" style="margin:0;">CELDI Academy is an affiliate ministry of UrbanPromise International.</p>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
