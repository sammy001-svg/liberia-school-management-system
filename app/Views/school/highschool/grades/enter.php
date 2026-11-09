<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a>
  <span>/</span><span>Enter Grades</span>
</div>
<div class="page-header">
  <div>
    <div class="page-header-title">Enter Grades</div>
    <div class="page-header-sub">Pick a class and the exam or marking period, then type the marks. Saved marks stay on this sheet — come back to the same class and exam to see them.</div>
  </div>
</div>
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $selectedClass==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <select name="year_id" class="form-control" style="max-width:190px;" title="Which academic year's marking periods to show">
      <?php foreach (($years ?? []) as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)($selectedYear ?? '') === (string)$y['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($y['name']) ?><?= (int)$y['is_current'] === 1 ? ' (current)' : '' ?>
        </option>
      <?php endforeach; ?>
      <option value="all" <?= ($selectedYear ?? '') === 'all' ? 'selected' : '' ?>>All years</option>
    </select>
    <select name="exam_id" class="form-control" style="max-width:260px;" required>
      <option value="">— Select Exam / Marking Period —</option>
      <?php foreach($exams as $e): ?>
        <option value="<?= $e['id'] ?>" <?= (string)$selectedExam===(string)$e['id']?'selected':'' ?>>
          <?= htmlspecialchars($e['name']) ?><?php
            // The year is spelled out because every year's periods carry the same
            // names, and two identical entries are impossible to choose between.
            echo empty($e['academic_year_id'])
                ? '  (no year)'
                : '  · ' . htmlspecialchars($e['year_name'] ?? '');
            echo empty($e['report_column']) ? '  — not on the report card' : '';
          ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load Students</button>
  </div>
</form>
<?php
$chosen = null;
foreach ($exams as $e) { if ((string)$selectedExam === (string)$e['id']) { $chosen = $e; } }
?>
<?php if (!empty($students) && !$selectedExam): ?>
<div class="alert alert-info" style="margin-bottom:16px;">
  Choose the exam or marking period these marks belong to, then press <strong>Load Students</strong>.
  Every mark has to sit on a period for it to reach the report card.
</div>
<?php endif; ?>
<?php if (!empty($students) && $chosen && empty($chosen['report_column'])): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
  <strong><?= htmlspecialchars($chosen['name']) ?></strong> has not been given a report-card period,
  so marks entered here are saved and appear in each student's Grade Report, but
  <strong>will not show on the printed report card</strong>.
  Give it a period under <a href="<?= $cfg['url'] ?>/school/grades/marking-periods">Marking Periods</a>,
  or pick one of the exams above that already has one.
</div>
<?php endif; ?>
<?php
// Where this class's marks actually are.
//
// A school that has entered a period's marks and then finds the sheet blank
// needs to be told where that work went, not left to try each look-alike exam
// in turn. Every mark these students hold is listed with the exam, class and
// year it is filed under, and whether this sheet or the report card can see it
// — with a one-press move onto the period being worked on.
$elsewhere = $marksElsewhere ?? [];
$chosenExam = null;
foreach ($exams as $e) { if ((string)$selectedExam === (string)$e['id']) { $chosenExam = $e; } }
$sheetIsEmpty = empty($recordedCount);
if ($elsewhere && $sheetIsEmpty):
  $total = array_sum(array_map(fn($r) => (int)$r['marks'], $elsewhere));
?>
<div class="card" style="padding:16px 20px;margin-bottom:16px;border-left:3px solid var(--warning);">
  <div class="fw-600" style="margin-bottom:4px;">
    This sheet is empty, but <?= (int)$total ?> mark<?= $total === 1 ? '' : 's' ?>
    for these students <?= $total === 1 ? 'is' : 'are' ?> recorded elsewhere.
  </div>
  <div style="font-size:13px;color:var(--text-light);margin-bottom:12px;">
    Nothing has been lost. Below is every place this class's marks are filed.
    Move the right set onto the period you are working on and it will fill this
    sheet and print on the report card.
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Filed under</th><th>Class</th><th>Year</th><th>On the report card</th><th>Marks</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($elsewhere as $r): ?>
        <tr>
          <td class="fw-600"><?= $r['exam_id'] ? htmlspecialchars($r['exam_name']) : '<em>no exam at all</em>' ?></td>
          <td><?= $r['exam_id']
                ? htmlspecialchars($r['exam_class_name'] ?? '(no class)')
                : '—' ?></td>
          <td><?= htmlspecialchars($r['exam_year_name'] ?? '—') ?></td>
          <td>
            <?php if (!empty($r['report_column'])): ?>
              <span class="badge badge-info"><?= htmlspecialchars($slotLabels[$r['report_column']] ?? $r['report_column']) ?></span>
            <?php else: ?>
              <span class="badge badge-muted">not on the card</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$r['marks'] ?> <span class="text-muted" style="font-size:11.5px;">(<?= (int)$r['students'] ?> students)</span></td>
          <td>
            <?php if ($selectedExam && $chosenExam): ?>
              <form method="POST" action="<?= $cfg['url'] ?>/school/grades/attach-marks"
                    data-confirm="Move these <?= (int)$r['marks'] ?> marks onto <?= htmlspecialchars(addslashes($chosenExam['name'])) ?>?"
                    data-confirm-title="Move Marks" data-confirm-label="Move them">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="class_id" value="<?= htmlspecialchars($selectedClass) ?>">
                <input type="hidden" name="exam_id" value="<?= (int)$selectedExam ?>">
                <input type="hidden" name="from_exam_id" value="<?= $r['exam_id'] !== null ? (int)$r['exam_id'] : '' ?>">
                <button type="submit" class="btn btn-sm btn-primary">Move onto <?= htmlspecialchars($chosenExam['name']) ?></button>
              </form>
            <?php else: ?>
              <span class="text-muted" style="font-size:12px;">Choose the period above first</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php if (!empty($students) && $selectedExam): ?>
<?php if (!empty($recordedCount)): ?>
<div class="alert alert-success" style="margin-bottom:16px;">
  <strong><?= (int)$recordedCount ?></strong> mark<?= $recordedCount === 1 ? ' is' : 's are' ?> already recorded on this sheet and shown in the boxes below.
  They stay here — reopen this class and exam any time to see or change them.
</div>
<?php endif; ?>
<?php if(!$canEditGrades): ?>
<div class="alert alert-info" style="margin-bottom:16px;">Cells with an existing grade are locked — only a School Admin can change a grade once it's recorded. You can still enter marks for anything left blank.</div>
<?php endif; ?>
<form method="POST" action="<?= $cfg['url'] ?>/school/grades/store">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="exam_id" value="<?= htmlspecialchars($selectedExam) ?>">
  <input type="hidden" name="class_id" value="<?= htmlspecialchars($selectedClass) ?>">
  <div class="card">
    <div class="card-header">
      <div>
        <div class="card-title">Enter Marks (out of 100) — <?= count($students) ?> students</div>
        <div style="font-size:11.5px;color:var(--text-muted);margin-top:3px;">
          <span class="mark-pass">Blue</span> = pass (<?= Controller::PASS_MARK ?> and above) ·
          <span class="mark-fail">Red</span> = fail (<?= Controller::PASS_MARK - 1 ?> and below)
        </div>
      </div>
      <button type="submit" class="btn btn-sm btn-primary">Save Grades</button>
    </div>
    <?php if(empty($courses)): ?>
    <div class="empty-state">
      <div class="empty-state-icon">📚</div>
      <div class="empty-state-text">This class has no subjects assigned yet. Add subjects on the <a href="<?= $cfg['url'] ?>/school/courses">Courses</a> page first.</div>
    </div>
    <?php else: ?>
    <div class="table-wrapper"><table>
      <thead><tr><th>Student</th><?php foreach($courses as $c): ?><th><?= htmlspecialchars($c['name']) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach($students as $s): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
          <?php foreach($courses as $c): ?>
          <?php
            $existingMark = $existingGrades[$s['id']][$c['id']] ?? null;
            $locked = $existingMark !== null && !$canEditGrades;
          ?>
          <td><input type="number" name="grades[<?= $s['id'] ?>][<?= $c['id'] ?>]" value="<?= $existingMark !== null ? htmlspecialchars($existingMark) : '' ?>" class="form-control mark-input" min="0" max="100" style="width:70px;padding:6px;<?= $locked ? 'background:var(--bg-muted,#f3f4f6);cursor:not-allowed;' : '' ?>" <?= $locked ? 'readonly title="Already graded — only a School Admin can change this."' : '' ?>></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </div>
</form>
<script>
// Marks on this sheet are out of 100, so the pass mark applies to the figure as
// typed. A fail reads red and a pass reads blue, the same as on the report card,
// and marks already recorded are coloured as soon as the sheet opens - not only
// while someone is typing.
(function () {
  const PASS = <?= (int)Controller::PASS_MARK ?>;
  function paint(input) {
    const v = parseFloat(input.value);
    input.classList.remove('mark-pass', 'mark-fail');
    input.style.borderColor = '';
    if (isNaN(v)) { return; }
    const fail = v < PASS;
    input.classList.add(fail ? 'mark-fail' : 'mark-pass');
    input.style.borderColor = fail ? '#F87171' : '#60A5FA';
  }
  document.querySelectorAll('.mark-input').forEach(function (input) {
    paint(input);
    input.addEventListener('input', function () { paint(this); });
  });
})();
</script>
<?php else: ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">✏️</div>
  <div class="empty-state-text">Select a class above to enter grades for its students.</div>
</div></div>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
