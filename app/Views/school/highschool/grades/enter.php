<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a>
  <span>/</span><span>Enter Grades</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Enter Grades</div>
    <div class="page-header-sub">
      Choose the class, the academic year and the marking period, then type the marks.
      Saved marks stay on that period — choose the same three again to see them.
    </div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <select name="class_id" class="form-control" style="max-width:220px;" required>
      <option value="">— Select Class —</option>
      <?php foreach ($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (string)$selectedClass === (string)$c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($c['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="year_id" class="form-control" style="max-width:210px;" required>
      <?php foreach ($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$selectedYear === (string)$y['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($y['name']) ?><?= (int)$y['is_current'] === 1 ? ' (current)' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="period" class="form-control" style="max-width:230px;" required>
      <option value="">— Select Marking Period —</option>
      <?php foreach ($periods as $p): ?>
        <option value="<?= htmlspecialchars($p['value']) ?>" <?= $selectedPeriod === $p['value'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['label']) ?><?= $p['on_card'] ? '' : ' (not on the report card)' ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn btn-secondary">Load Students</button>
  </div>
</form>

<?php if ($selectedClass !== '' && $selectedPeriod === ''): ?>
<div class="alert alert-info" style="margin-bottom:16px;">
  Choose the marking period as well, then press <strong>Load Students</strong>.
</div>
<?php endif; ?>

<?php if ($selectedClass !== '' && $selectedPeriod !== '' && empty($courses)): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
  This class has no subjects assigned, so there is nothing to mark. Add its subjects on the
  <a href="<?= $cfg['url'] ?>/school/courses">Subjects</a> page first.
</div>
<?php endif; ?>

<?php if (!empty($students) && $selectedPeriod !== '' && !empty($courses)): ?>

  <?php if (!empty($recordedCount)): ?>
  <div class="alert alert-success" style="margin-bottom:16px;">
    <strong><?= (int)$recordedCount ?></strong> mark<?= $recordedCount === 1 ? ' is' : 's are' ?>
    already saved for this period and shown below. Change any of them and save again to update.
  </div>
  <?php elseif (!$periodIsSetUp): ?>
  <div class="alert alert-info" style="margin-bottom:16px;">
    Nothing has been entered for this period yet. Type the marks and save — the period will be
    created for this class and year as you do.
  </div>
  <?php else: ?>
  <div class="alert alert-info" style="margin-bottom:16px;">
    Nothing has been entered for this period yet.
  </div>
  <?php endif; ?>

  <?php if (!$canEditGrades): ?>
  <div class="alert alert-info" style="margin-bottom:16px;">
    Cells with an existing grade are locked — only a School Admin can change a grade once it is
    recorded. You can still enter marks for anything left blank.
  </div>
  <?php endif; ?>

  <form method="POST" action="<?= $cfg['url'] ?>/school/grades/store">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="class_id" value="<?= htmlspecialchars($selectedClass) ?>">
    <input type="hidden" name="year_id"  value="<?= htmlspecialchars($selectedYear) ?>">
    <input type="hidden" name="period"   value="<?= htmlspecialchars($selectedPeriod) ?>">
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
      <div class="table-wrapper"><table>
        <thead><tr><th>Student</th><?php foreach ($courses as $c): ?><th><?= htmlspecialchars($c['name']) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($students as $s): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
            <?php foreach ($courses as $c): ?>
              <?php
                $existingMark = $existingGrades[$s['id']][$c['id']] ?? null;
                $locked = $existingMark !== null && !$canEditGrades;
              ?>
              <td><input type="number" name="grades[<?= $s['id'] ?>][<?= $c['id'] ?>]"
                         value="<?= $existingMark !== null ? htmlspecialchars($existingMark) : '' ?>"
                         class="form-control mark-input" min="0" max="100"
                         style="width:70px;padding:6px;<?= $locked ? 'background:var(--bg-muted,#f3f4f6);cursor:not-allowed;' : '' ?>"
                         <?= $locked ? 'readonly title="Already graded — only a School Admin can change this."' : '' ?>></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  </form>

  <script>
  // Marks on this sheet are out of 100, so the pass mark applies to the figure as
  // typed. A fail reads red and a pass reads blue, the same as on the report card,
  // and marks already recorded are coloured as soon as the sheet opens.
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

<?php elseif ($selectedClass === ''): ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">✏️</div>
  <div class="empty-state-text">Choose a class, a year and a marking period above to enter or see its marks.</div>
</div></div>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
