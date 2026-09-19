<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'elementary.jpg',
    'eyebrow' => 'Elementary · Grades 1–6',
    'title'   => 'The bridge from discovery to <em>academic mastery</em>.',
    'lead'    => 'A curriculum that challenges students intellectually while grounding them in the values that shape character.',
    'crumbs'  => ['Divisions' => $url('divisions'), 'Elementary' => null],
    'chips'   => [['book', 'Language Arts'], ['layers', 'Mathematics'], ['compass', 'Sciences & Social Studies']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Our Elementary Program</span>
      <h2 class="display">Formative years that shape <em>character and competence</em></h2>
      <p>The Elementary Program at CELDI Academy serves as the bridge between early discovery and academic mastery. Recognizing that these formative years are essential for shaping both character and competence, our curriculum is designed to challenge students intellectually while grounding them in the core values of excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism.</p>
      <p>Core academic subjects are enhanced with arts, technology, and critical thinking for grades 1–6.</p>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('upper-elementary.jpg') ?>" alt="Elementary students working together" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head" data-reveal>
      <span class="eyebrow">Academic Excellence &amp; Foundational Skills</span>
      <h2 class="display">Literacy and numeracy — the keys to <em>all future learning</em></h2>
      <p class="lead">We use a balanced instructional approach that combines rigorous academic standards with multisensory, hands-on activities.</p>
    </div>
    <div class="card-grid-3">
      <article class="info-card" data-reveal>
        <span class="icon-badge"><?= wicon('book') ?></span>
        <h3>Language Arts</h3>
        <p>Focus on reading comprehension, critical writing, and public speaking to ensure students can articulate their ideas with confidence.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="1">
        <span class="icon-badge icon-badge--green"><?= wicon('layers') ?></span>
        <h3>Mathematics</h3>
        <p>Emphasis on logical reasoning and practical problem-solving, moving beyond rote memorization to a deep understanding of mathematical concepts.</p>
      </article>
      <article class="info-card" data-reveal data-reveal-delay="2">
        <span class="icon-badge icon-badge--gold"><?= wicon('compass') ?></span>
        <h3>Sciences &amp; Social Studies</h3>
        <p>Integrated units that encourage students to explore the world around them, fostering curiosity and a sense of global citizenship.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Beyond the Classroom</span>
      <h2 class="display">Educating the <em>whole child</em></h2>
      <p>What sets the CELDI Academy Elementary program apart is our commitment to the “whole child.” We integrate leadership development and character education into the daily school experience.</p>
      <ul class="icon-list" style="margin-top:30px;">
        <li>
          <span class="icon-badge"><?= wicon('hand') ?></span>
          <div><h3>Service-Oriented Leadership</h3><p>Students learn that true leadership begins with a heart for service and a commitment to the community.</p></div>
        </li>
        <li>
          <span class="icon-badge icon-badge--green"><?= wicon('scale') ?></span>
          <div><h3>Ethical Discipline</h3><p>A culture of self-regulation and integrity, where students take ownership of their actions and understand the impact of their choices on others.</p></div>
        </li>
        <li>
          <span class="icon-badge icon-badge--gold"><?= wicon('lightbulb') ?></span>
          <div><h3>Creative Innovation</h3><p>Dedicated time for projects and collaborative problem-solving teaches students to approach challenges with an innovative, “can-do” mindset.</p></div>
        </li>
      </ul>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame photo-frame--tall"><img src="<?= $img('science-lab.jpg') ?>" alt="Students exploring science with microscopes" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">Our Learning Environment</span>
      <h2 class="display display--light">Structured, dynamic, and <em>supportive</em></h2>
      <p class="lead lead--light">We provide an environment where every child feels seen and supported.</p>
    </div>
    <div class="values-grid" data-reveal>
      <?php foreach ([
        ['heart', 'Holistic Growth', 'Our educators focus on the social-emotional well-being of each student, so they develop the empathy and resilience needed to thrive.'],
        ['compass', 'Active Discovery', 'Classrooms at CELDI Academy are labs of learning: children learn best when actively engaged in discovery, experimentation, and inquiry.'],
        ['users', 'Collaborative Partnership', 'A strong link between teachers and parents, working together to monitor progress and celebrate every milestone.'],
      ] as [$icon, $title, $text]): ?>
        <div class="value"><?= wicon($icon) ?><h3><?= $title ?></h3><p><?= $text ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split">
    <div data-reveal>
      <span class="eyebrow">Empowering Exploration</span>
      <h2 class="display" style="margin-bottom:22px;">Confident <em>explorers</em>, strong foundations</h2>
      <p class="muted">At CELDI, our learning spaces are vibrant hubs of activity. We empower children to become confident explorers, fostering deep understanding through hands-on experiences, creative problem-solving, and guided investigation.</p>
      <p class="muted">Our dedicated educators prioritize the complete development of each child — not only academic skills but also social-emotional intelligence, physical health, and creative expression — building a strong foundation for lifelong success and happiness.</p>
    </div>
    <div class="pillar-card pillar-card--feature" data-reveal data-reveal-delay="1">
      <span class="icon-badge"><?= wicon('target') ?></span>
      <h3>Our Program Goal</h3>
      <p>To produce well-rounded scholars who are prepared for the academic rigors of junior high and are emerging as disciplined, empathetic, and innovative leaders in their own right.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
