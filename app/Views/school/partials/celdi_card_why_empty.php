<?php
/**
 * Why this card has no marks on it.
 *
 * Expects $emptyReasons from Controller::celdiEmptyCardReasons(). Hidden when
 * printing: the parent's copy should not carry the school's own to-do list.
 */
$reasons = $emptyReasons ?? [];
if (!$reasons) { return; }

// A parent or student sees a plain sentence. Which exam needs a period, or
// publishing, is the school's work and not theirs to read about.
if (!empty($familyView)) {
    $unpublishedOnly = array_keys($reasons) === ['unpublished'];
    echo '<div class="notice">'
       . ($unpublishedOnly
            ? 'The marks for this period have not been released yet. They will appear here once the school publishes them.'
            : 'There are no marks on this card yet. Please contact the school office if you were expecting them.')
       . '</div>';
    return;
}
$fix = [
    'no_slot'     => ['Grades &amp; Exams &rarr; Marking Periods', 'give each of those exams a report-card period'],
    'other_class' => ['Grades &amp; Exams', 'check which class each of those exams belongs to'],
    'other_year'  => ['the year picker above', 'switch to the year those marks were entered in'],
    'unpublished' => ['Grades &amp; Exams', 'publish those exams so families can see the marks'],
    'no_subjects' => ['Subjects', 'assign this class its subjects'],
];
?>
<div class="notice notice-why">
  <strong>This card has no marks on it, and here is why.</strong>
  <p>A mark only reaches the grid when the exam it was entered against belongs to this class,
     sits in the year being shown, and has been given a report-card period. These do not:</p>
  <ul>
    <?php foreach ($reasons as $key => $r): [$where, $what] = $fix[$key] ?? ['Grades &amp; Exams', 'review those exams']; ?>
      <li>
        <?php if (!empty($r['marks'])): ?>
          <strong><?= (int)$r['marks'] ?></strong> mark<?= $r['marks'] === 1 ? '' : 's' ?>
          <?= $r['label'] ?><?php if ($r['exams']): ?>
            — <?= htmlspecialchars(implode(', ', array_slice($r['exams'], 0, 4))) ?><?= count($r['exams']) > 4 ? ' and ' . (count($r['exams']) - 4) . ' more' : '' ?>
          <?php endif; ?>.
        <?php else: ?>
          <?= $r['label'] ?>.
        <?php endif; ?>
        <em>Open <?= $where ?> to <?= $what ?>.</em>
      </li>
    <?php endforeach; ?>
  </ul>
  <p class="notice-foot">Nothing has been lost — every mark is still recorded and visible under
     a student's <strong>Grade Report</strong>.</p>
</div>
