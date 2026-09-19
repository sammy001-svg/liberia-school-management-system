<div class="calendar">
  <?php foreach ([
    ['Before the year', 'Enrollment', 'July – August', ['Pre-registration', 'Entrance and placement exams']],
    ['First Semester', 'Semester One', 'September – January', ['Opening date', 'Periodic tests', 'First PTA meeting', 'Christmas break', 'Semester exams']],
    ['Second Semester', 'Semester Two', 'February – June', ['Resumption', 'Second PTA meeting', 'Periodic tests', 'Easter break', 'Final PTA meeting', 'Semester exams', 'Final exams']],
  ] as $i => [$label, $title, $months, $items]): ?>
    <article class="term" data-reveal data-reveal-delay="<?= $i ?>">
      <span class="term-label"><?= $label ?></span>
      <h3><?= $title ?></h3>
      <span class="term-months"><?= wicon('calendar') ?> <?= $months ?></span>
      <ul>
        <?php foreach ($items as $item): ?><li><?= $item ?></li><?php endforeach; ?>
      </ul>
    </article>
  <?php endforeach; ?>
</div>
