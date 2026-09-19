<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'early-childhood.jpg',
    'eyebrow' => 'Early Childhood & Daycare',
    'title'   => 'Where character and intellect <em>take root</em>.',
    'lead'    => 'A nurturing, joyful start where the CELDI pillars are introduced at the very beginning of the educational journey.',
    'crumbs'  => ['Divisions' => $url('divisions'), 'Early Childhood' => null],
    'chips'   => [['home', 'Daycare'], ['sun', 'Nursery & Kindergarten'], ['heart', 'Safe & nurturing']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Early Childhood Program</span>
      <h2 class="display">The most important years <em>begin here</em></h2>
      <p>At CELDI Academy, we believe that the earliest years of a child’s life are the most critical for building a foundation of character and intellect.</p>
      <p>Our Early Childhood Program and Daycare are designed to provide more than just supervision; we provide a nurturing environment where the “CELDI” pillars — Creativity, Empathy, Leadership, Discipline, and Innovation — are introduced at the very beginning of the educational journey.</p>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('teacher-reading.jpg') ?>" alt="A teacher reading with young children" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Educational Philosophy</span>
      <h2 class="display">Every child is <em>unique</em></h2>
      <p class="lead">We view every child as a unique individual with the potential to learn, lead, and inspire.</p>
      <p>We ensure the holistic development of each child by balancing structured learning and purposeful play, meeting their social, emotional, physical, and cognitive needs.</p>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('upper-elementary.jpg') ?>" alt="Children learning together at a table" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">C · E · L · D · I</span>
      <h2 class="display display--light">The CELDI pillars in <em>early learning</em></h2>
    </div>
    <div class="values-grid values-grid--5" data-reveal>
      <?php foreach ([
        ['palette', 'Creativity', 'We help children express themselves and think in new ways by letting them create art, play music, and imagine.'],
        ['heart', 'Empathy', 'We foster a kind and inclusive classroom culture where children learn to understand their emotions and care for their peers.'],
        ['star', 'Leadership', 'Even our youngest learners are taught the value of responsibility, service, and taking initiative in small, age-appropriate ways.'],
        ['shield', 'Discipline', 'We provide a stable routine that helps children develop self-control, respect for boundaries, and a love for order.'],
        ['lightbulb', 'Innovation', 'We introduce basic problem-solving and curiosity-driven activities that prepare children for a transforming world.'],
      ] as [$icon, $title, $text]): ?>
        <div class="value">
          <?= wicon($icon) ?>
          <h3><?= $title ?></h3>
          <p><?= $text ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Program Highlights</span>
      <h2 class="display">A <em>home away from home</em></h2>
    </div>
    <div class="card-grid-2">
      <?php foreach ([
        ['book', '', 'Foundational Literacy & Numeracy', 'Our daycare and preschool programs introduce the building blocks of reading and mathematics through engaging, multi-sensory activities.'],
        ['home', 'icon-badge--green', 'Safe & Nurturing Environment', 'Our facilities are a “home away from home,” prioritizing safety, hygiene, and the emotional well-being of every student.'],
        ['compass', 'icon-badge--gold', 'Active Discovery', 'We emphasize “learning by doing.” From tactile sensory bins to outdoor exploration, children remain active participants in their education.'],
        ['award', '', 'Character Building', 'Beyond academics, we prioritize the development of core values that will guide students as they transition into our primary and secondary programs.'],
      ] as $i => [$icon, $cls, $title, $text]): ?>
        <article class="info-card" data-reveal data-reveal-delay="<?= $i % 2 ?>">
          <span class="icon-badge <?= $cls ?>"><?= wicon($icon) ?></span>
          <h3><?= $title ?></h3>
          <p><?= $text ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--tight section--lilac">
  <div class="container">
    <p class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      Education is a partnership between the school and the home. We maintain open communication with parents so that the family supports and <em>celebrates</em> the growth witnessed in the classroom.
    </p>
  </div>
</section>

<?php $ctaImage = 'students-lineup.jpg'; require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
