<div class="division-grid">
  <?php foreach ([
    ['early-childhood.jpg', 'Daycare – K', 'Early Childhood & Daycare', 'A nurturing environment where character and intellect take root from the very first step.', 'early-childhood'],
    ['elementary.jpg', 'Grades 1 – 6', 'Elementary', 'The bridge between early discovery and academic mastery, with arts, technology and critical thinking.', 'elementary'],
    ['junior-high.jpg', 'Grades 7 – 9', 'Junior High', 'Advanced critical thinking, specialized skills and the journey from student to servant-leader.', 'junior-high'],
    ['computer-lab.jpg', 'Grades 10 – 12', 'Senior High', 'The culmination: emerging professionals with purpose and real technical proficiency.', 'senior-high'],
  ] as $i => [$image, $grade, $title, $text, $href]): ?>
    <a class="division-card" href="<?= $url($href) ?>" data-reveal data-reveal-delay="<?= $i ?>">
      <img src="<?= $img($image) ?>" alt="" loading="lazy">
      <div class="division-body">
        <span class="division-grade"><?= $grade ?></span>
        <h3><?= htmlspecialchars($title) ?></h3>
        <p><?= $text ?></p>
        <span class="text-link">Explore <?= wicon('arrow-right') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
