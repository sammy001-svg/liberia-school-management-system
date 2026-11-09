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
    <select name="exam_id" class="form-control" style="max-width:220px;" required>
      <option value="">— Select Exam / Marking Period —</option>
      <?php foreach($exams as $e): ?>
        <option value="<?= $e['id'] ?>" <?= (string)$selectedExam===(string)$e['id']?'selected':'' ?>>
          <?= htmlspecialchars($e['name']) ?><?= empty($e['report_column']) ? '  — not on the report card' : '' ?>
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
<?php if (!empty($orphanCount)): ?>
<div class="card" style="padding:14px 18px;margin-bottom:16px;border-left:3px solid var(--warning);">
  <div class="fw-600" style="margin-bottom:4px;">
    <?= (int)$orphanCount ?> mark<?= $orphanCount === 1 ? '' : 's' ?> in this class
    <?= $orphanCount === 1 ? 'is' : 'are' ?> not attached to any exam.
  </div>
  <div style="font-size:13px;color:var(--text-light);margin-bottom:10px;">
    They were saved with the exam box left blank, so they show on this sheet but on no report card.
    Nothing is lost — choose the exam they belong to and they will be moved onto it.
  </div>
  <form method="POST" action="<?= $cfg['url'] ?>/school/grades/attach-marks"
        style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;"
        data-confirm="Move these <?= (int)$orphanCount ?> marks onto the chosen exam?"
        data-confirm-title="Attach Marks" data-confirm-label="Move them">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="class_id" value="<?= htmlspecialchars($selectedClass) ?>">
    <select name="exam_id" class="form-control" style="max-width:280px;" required>
      <option value="">— Which exam do they belong to? —</option>
      <?php foreach ($exams as $e): ?>
        <option value="<?= $e['id'] ?>">
          <?= htmlspecialchars($e['name']) ?><?= empty($e['report_column']) ? '  — not on the report card' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Attach them</button>
  </form>
  <?php if (empty($exams)): ?>
    <div style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">
      This class has no exams yet. Create one under
      <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a> first, giving it the report-card
      period these marks belong to, then come back here.
    </div>
  <?php endif; ?>
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
