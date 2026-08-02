<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a>
  <span>/</span><span>Marking Periods</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Marking Periods</div>
    <div class="page-header-sub">
      Each class needs six marking periods and two semester exams per year — these are the columns on the report card.
      Semester and yearly averages are worked out automatically.
    </div>
  </div>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:18px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <label class="form-label" style="margin:0;">Academic Year</label>
    <select name="year_id" class="form-control" style="max-width:240px;" onchange="this.form.submit()">
      <?php foreach($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$y['id']===(string)$selectedYearId?'selected':'' ?>><?= htmlspecialchars($y['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if(empty($years)): ?>
      <span style="color:var(--text-muted);font-size:13px;">No academic years yet — create one under Settings first.</span>
    <?php endif; ?>
  </div>
</form>

<?php if(!empty($years)): ?>
<div class="card">
  <div class="card-header">
    <div class="card-title">Classes</div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/grades/marking-periods/setup"
          data-confirm="Create the eight report-card slots for every class that is missing them?"
          data-confirm-title="Set Up All Classes" data-confirm-label="Set Up">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($selectedYearId) ?>">
      <button type="submit" class="btn btn-sm btn-primary">Set Up All Classes</button>
    </form>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Class</th><th>Report Style</th><th>Students</th><th>Subjects</th><th>Report Card Slots</th><th>Released</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($classes as $c): $slots = (int)$c['slots']; $pub = (int)$c['published_slots']; ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
          <td>
            <?php if(($c['report_style'] ?? 'numeric')==='letter'): ?>
              <span class="badge badge-purple">Letters (E/S/I/N/C)</span>
            <?php else: ?>
              <span class="badge badge-info">Numbers</span>
            <?php endif; ?>
          </td>
          <td><?= (int)$c['student_count'] ?></td>
          <td>
            <?php if((int)$c['subject_count'] === 0): ?>
              <span style="color:var(--danger);font-size:12px;">None — add subjects first</span>
            <?php else: ?><?= (int)$c['subject_count'] ?><?php endif; ?>
          </td>
          <td>
            <?php if($slots >= 8): ?>
              <span class="badge badge-success">Ready — 8 of 8</span>
            <?php elseif($slots > 0): ?>
              <span class="badge badge-warning"><?= $slots ?> of 8</span>
            <?php else: ?>
              <span class="badge badge-danger">Not set up</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($slots > 0 && $pub >= $slots): ?>
              <span class="badge badge-success">Visible to parents</span>
            <?php elseif($pub > 0): ?>
              <span class="badge badge-warning">Partly released</span>
            <?php else: ?>
              <span class="badge badge-secondary">Not released</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <?php if($slots < 8): ?>
              <form method="POST" action="<?= $cfg['url'] ?>/school/grades/marking-periods/setup">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($selectedYearId) ?>">
                <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                <button type="submit" class="btn btn-sm btn-secondary">Set Up</button>
              </form>
            <?php else: ?>
              <a href="<?= $cfg['url'] ?>/school/grades/enter?class_id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">Enter Grades</a>
              <a href="<?= $cfg['url'] ?>/school/grades/report-cards/class/<?= $c['id'] ?>?year_id=<?= htmlspecialchars($selectedYearId) ?>"
                 target="_blank" class="btn btn-sm btn-secondary">Print Cards</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/grades/report-cards/publish"
                    data-confirm="<?= $pub >= $slots
                        ? 'Withdraw this class&#39;s report cards? Parents and students will no longer see them.'
                        : 'Release this class&#39;s report cards to parents and students?' ?>"
                    data-confirm-title="<?= $pub >= $slots ? 'Withdraw Report Cards' : 'Release Report Cards' ?>"
                    data-confirm-label="<?= $pub >= $slots ? 'Withdraw' : 'Release' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($selectedYearId) ?>">
                <input type="hidden" name="class_id" value="<?= $c['id'] ?>">
                <input type="hidden" name="status" value="<?= $pub >= $slots ? 'draft' : 'published' ?>">
                <button type="submit" class="btn btn-sm <?= $pub >= $slots ? 'btn-secondary' : 'btn-primary' ?>">
                  <?= $pub >= $slots ? 'Withdraw' : 'Release' ?>
                </button>
              </form>
            <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($classes)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🏫</div>
            <div class="empty-state-text">No classes yet. Add them on the <a href="<?= $cfg['url'] ?>/school/classes">Classes</a> page.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-16">
  <div class="card-header"><div class="card-title">Report Card Columns</div></div>
  <div class="card-body">
    <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">
      Teachers enter marks for the eight slots below. <strong>Sem. Ave.</strong> is
      <em>(average of the three periods + the semester exam) &divide; 2</em>, and
      <strong>Yearly Ave.</strong> is the mean of the two semester averages — both are computed, never typed.
    </p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
      <?php foreach($slotNames as $slot => $name): ?>
        <span class="badge badge-info"><?= htmlspecialchars($name) ?></span>
      <?php endforeach; ?>
      <span class="badge badge-teal">Sem. Ave. — computed</span>
      <span class="badge badge-teal">Yearly Ave. — computed</span>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
