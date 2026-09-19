<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'junior-high.jpg',
    'eyebrow' => 'Junior High · Grades 7–9',
    'title'   => 'From student to <em>servant-leader</em>.',
    'lead'    => 'Advanced critical thinking, specialized skill acquisition and personalized guidance on the road to Senior High and beyond.',
    'crumbs'  => ['Divisions' => $url('divisions'), 'Junior High' => null],
    'chips'   => [['target', 'College-preparatory'], ['monitor', 'Computer & software'], ['users', 'Personalized guidance']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">The Junior High Program</span>
      <h2 class="display">Bridging curiosity and <em>focused ambition</em></h2>
      <p>At the Junior High level (Grades 7–9), CELDI Academy transitions students from foundational learning to advanced critical thinking and specialized skill acquisition. This stage is designed to bridge the gap between childhood curiosity and the focused ambition required for Senior High School and beyond.</p>
      <p>Our program centers on the transition from being a student to becoming a servant-leader, ensuring that as academic rigor increases, so does the commitment to the CELDI pillars: Excellence, Integrity, Respect, Service, Innovation, Leadership, Discipline, and Patriotism.</p>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('geography.jpg') ?>" alt="Junior high students studying together" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--cream">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--center">What Defines Junior High at CELDI</span>
      <h2 class="display">Rigor, skills, and <em>character</em></h2>
    </div>
    <div class="feature-grid">
      <article class="feature" data-reveal>
        <span class="feature-num">01</span>
        <span class="icon-badge"><?= wicon('target') ?></span>
        <h3>College-Preparatory Curriculum</h3>
        <p>A rigorous curriculum with personalized guidance that prepares every student for the demands of Senior High School and beyond.</p>
      </article>
      <article class="feature" data-reveal data-reveal-delay="1">
        <span class="feature-num">02</span>
        <span class="icon-badge icon-badge--green"><?= wicon('monitor') ?></span>
        <h3>Technical &amp; Digital Skills</h3>
        <p>Through CELDI’s signature computer and software curriculum, students build the digital proficiency the modern world requires.</p>
      </article>
      <article class="feature" data-reveal data-reveal-delay="2">
        <span class="feature-num">03</span>
        <span class="icon-badge icon-badge--gold"><?= wicon('graduation') ?></span>
        <h3>Leadership Development</h3>
        <p>Our “chapters” model continues in Junior High, preparing students to lead organizations at their level and serve their communities.</p>
      </article>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Growing Commitment</span>
      <h2 class="display">As rigor rises, so does <em>character</em></h2>
      <p>Junior High is where students begin to take ownership of their learning and their leadership. Every stage of the program is anchored in the eight CELDI pillars.</p>
      <div class="pillar-list pillar-list--dark" style="justify-content:flex-start;margin-top:24px;">
        <?php foreach (['Excellence', 'Integrity', 'Respect', 'Service', 'Innovation', 'Leadership', 'Discipline', 'Patriotism'] as $p): ?><span><?= $p ?></span><?php endforeach; ?>
      </div>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('science-lab.jpg') ?>" alt="Students in a science lab" loading="lazy"></div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
