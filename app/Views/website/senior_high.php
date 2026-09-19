<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'computer-lab.jpg',
    'eyebrow' => 'Senior High · Grades 10–12',
    'title'   => 'Emerging professionals. <em>Community architects.</em>',
    'lead'    => 'The culmination of the CELDI journey: graduating with a diploma, a clear sense of purpose, and real technical proficiency.',
    'crumbs'  => ['Divisions' => $url('divisions'), 'Senior High' => null],
    'chips'   => [['graduation', 'Diploma + Leadership Certificate'], ['monitor', 'Technical proficiency'], ['compass', 'Higher education ready']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container split">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">The Senior High Program</span>
      <h2 class="display">The culmination of our <em>educational journey</em></h2>
      <p>The Senior High School program (Grades 10–12) at CELDI Academy is the culmination of our educational journey. At this stage, students transition from academic learners to emerging professionals and community architects.</p>
      <p>Our curriculum is designed to refine their specialized interests, ensuring they graduate not only with a diploma but with a clear sense of purpose and the technical proficiency to excel in higher education or the global workforce.</p>
      <p>Core academic subjects are enhanced with arts, technology, and critical thinking for grades 10–12.</p>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('junior-high.jpg') ?>" alt="Senior high students in class" loading="lazy"></div>
    </div>
  </div>
</section>

<section class="section section--plum">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow eyebrow--light eyebrow--center">What Our Graduates Leave With</span>
      <h2 class="display display--light">Ready to lead, ready to <em>serve</em></h2>
    </div>
    <div class="values-grid" data-reveal>
      <?php foreach ([
        ['graduation', 'A Diploma — and a Purpose', 'Students graduate with a clear sense of direction, having refined their specialized interests through the program.'],
        ['monitor', 'Technical Proficiency', 'CELDI’s signature computer and software curriculum gives graduates the digital skills required for the modern world.'],
        ['award', 'Leadership Development Certificate', 'Through our “chapters” model, every graduate earns a Leadership Development Certificate, ready to lead and serve.'],
      ] as [$icon, $title, $text]): ?>
        <div class="value"><?= wicon($icon) ?><h3><?= $title ?></h3><p><?= $text ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container split split--reverse">
    <div class="split-copy" data-reveal>
      <span class="eyebrow">Beyond Graduation</span>
      <h2 class="display">Disciplined, innovative <em>architects of change</em></h2>
      <p>CELDI Academy exists to empower students to become disciplined, innovative architects of change, ready to serve their nation with integrity. Our senior students are prepared to excel whether they continue to higher education or step into the global workforce.</p>
      <ul class="check-list">
        <li><?= wicon('check') ?> Refined specialized interests and a clear sense of purpose</li>
        <li><?= wicon('check') ?> Intellectual competence alongside digital proficiency</li>
        <li><?= wicon('check') ?> Servant leadership grounded in the eight CELDI pillars</li>
        <li><?= wicon('check') ?> Preparation for higher education or the global workforce</li>
      </ul>
    </div>
    <div class="photo-accent" data-reveal data-reveal-delay="1">
      <div class="photo-frame"><img src="<?= $img('gallery-1.jpg') ?>" alt="Senior students studying" loading="lazy"></div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
