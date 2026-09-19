<?php require __DIR__ . '/partials/header.php'; ?>

<?php $hero = [
    'image'   => 'hero-assembly.jpg',
    'eyebrow' => 'Our School Divisions',
    'title'   => 'Four divisions. <em>One</em> journey of growth.',
    'lead'    => 'CELDI Academy is arranged into four sections, each building on the last, from a child’s first steps in daycare to graduation from senior high.',
    'crumbs'  => ['Divisions' => null],
    'chips'   => [['sun', 'Early Childhood'], ['book', 'Elementary'], ['compass', 'Junior High'], ['graduation', 'Senior High']],
]; require __DIR__ . '/partials/page_hero.php'; ?>

<section class="section">
  <div class="container division-rows">
    <?php foreach ([
      ['early-childhood.jpg', 'Daycare – Kindergarten', 'Early Childhood & <em>Daycare</em>', [
          'At CELDI Academy, we believe that the earliest years of a child’s life are the most critical for building a foundation of character and intellect.',
          'Our Early Childhood Program and Daycare go beyond supervision; we provide a nurturing environment where the CELDI pillars — excellence, integrity, respect, service, innovation, leadership, discipline, and patriotism — are introduced at the very beginning of the educational journey.',
        ], 'early-childhood'],
      ['elementary.jpg', 'Grades 1 – 6', '<em>Elementary</em>', [
          'The elementary section at CELDI Academy serves as the bridge between early discovery and academic mastery. We recognize that these formative years shape character and competence.',
          'Our curriculum challenges students intellectually while grounding them in our core values. Core academic subjects are enhanced with arts, technology, and critical thinking for grades 1–6.',
        ], 'elementary'],
      ['junior-high.jpg', 'Grades 7 – 9', 'Junior <em>High</em>', [
          'At the Junior High level, CELDI Academy transitions students from foundational learning to advanced critical thinking and specialized skill acquisition, bridging the gap between childhood curiosity and the focused ambition required for Senior High School and beyond.',
          'Our program centers on the transition from being a student to becoming a servant-leader, with a rigorous college-preparatory curriculum and personalized guidance.',
        ], 'junior-high'],
      ['computer-lab.jpg', 'Grades 10 – 12', 'Senior <em>High</em>', [
          'The Senior High School program is the culmination of our educational journey. At this stage, students transition from academic learners to emerging professionals and community architects.',
          'Our curriculum refines their specialized interests, ensuring they graduate not only with a diploma but with a clear sense of purpose and the technical proficiency to excel in higher education or the global workforce.',
        ], 'senior-high'],
    ] as [$image, $grade, $title, $paras, $href]): ?>
      <article class="division-row">
        <div class="photo-accent" data-reveal>
          <div class="photo-frame"><img src="<?= $img($image) ?>" alt="" loading="lazy"></div>
        </div>
        <div data-reveal data-reveal-delay="1">
          <span class="division-grade"><?= $grade ?></span>
          <h2 class="display"><?= $title ?></h2>
          <?php foreach ($paras as $p): ?><p><?= $p ?></p><?php endforeach; ?>
          <div class="actions">
            <a href="<?= $url($href) ?>" class="btn btn-plum">Explore the program <?= wicon('arrow-right') ?></a>
            <a href="<?= $url('apply') ?>" class="btn btn-outline-dark">Apply now</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section--tight section--lilac">
  <div class="container">
    <p class="statement" data-reveal>
      <span class="statement-mark" aria-hidden="true">“</span>
      Every student graduates with a <em>Leadership Development Certificate</em>, ready to lead organizations at their level and serve their communities with distinction.
    </p>
  </div>
</section>

<?php require __DIR__ . '/partials/cta.php'; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
