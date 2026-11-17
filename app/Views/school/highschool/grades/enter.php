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

  <?php if (empty($recordedCount) && !empty($marksElsewhere)): ?>
  <div class="card" style="padding:14px 18px;margin-bottom:16px;border-left:3px solid var(--info);">
    <div class="fw-600" style="margin-bottom:6px;">These students do have marks recorded — under these periods:</div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Period</th><th>Class it belongs to</th><th>Academic year</th><th>Marks</th></tr></thead>
        <tbody>
          <?php foreach ($marksElsewhere as $r): ?>
          <tr>
            <td class="fw-600">
              <?= htmlspecialchars($r['exam_name']) ?><?php if (!empty($r['report_column'])): ?>
                <span class="badge badge-info" style="margin-left:6px;"><?= htmlspecialchars($slotLabels[$r['report_column']] ?? $r['report_column']) ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($r['class_name']) ?></td>
            <td><?= htmlspecialchars($r['year_name']) ?></td>
            <td><?= (int)$r['marks'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="font-size:12.5px;color:var(--text-muted);margin-top:8px;">
      Choose that class, year and period above to see and edit them. Nothing has been lost.
    </div>
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
    <?php
      /*
       * A sheet posts one form field per cell, and PHP throws away everything past
       * max_input_vars (1000 by default) without raising a word — so a big class
       * used to half-save and still say "Grades saved".
       *
       * The script below packs the whole grid into grades_json, one field however
       * many students there are. sheet_cells is what the server counts against if
       * that script never ran, so a truncated sheet is refused rather than lost.
       */
      $sheetCells = count($students) * count($courses);
    ?>
    <input type="hidden" name="sheet_cells"  value="<?= (int)$sheetCells ?>">
    <input type="hidden" name="grades_json"  id="grades_json"  value="">
    <input type="hidden" name="grades_count" id="grades_count" value="">
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

    // Send the grid as one field instead of one per cell. A class of any size
    // then posts a handful of fields, so PHP's max_input_vars can never reach in
    // and drop the bottom of the sheet on the way to the server.
    var form = document.querySelector('form[action$="/school/grades/store"]');
    if (!form) { return; }
    form.addEventListener('submit', function () {
      var grid = {}, n = 0, stripped = [];
      document.querySelectorAll('.mark-input').forEach(function (input) {
        var m = /^grades\[(\d+)\]\[(\d+)\]$/.exec(input.name || '');
        if (!m) { return; }
        var v = (input.value || '').trim();
        if (v !== '') {
          if (!grid[m[1]]) { grid[m[1]] = {}; }
          grid[m[1]][m[2]] = v;
          n++;
        }
        // Keep the individual cells out of the POST now their values are packed.
        input.setAttribute('data-name', input.name);
        input.removeAttribute('name');
        stripped.push(input);
      });
      document.getElementById('grades_json').value = JSON.stringify(grid);
      document.getElementById('grades_count').value = String(n);
      // If the browser stays on the page after all, put the sheet back as it was.
      setTimeout(function () {
        stripped.forEach(function (i) { i.setAttribute('name', i.getAttribute('data-name')); });
      }, 4000);
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
