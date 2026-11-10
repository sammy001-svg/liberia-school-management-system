<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a>
  <span>/</span><span>Import Grades</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Import Grades</div>
    <div class="page-header-sub">
      For marks already recorded on paper. Choose where they belong, paste one student per line,
      and check what it matched before anything is saved. Marks already in the system are never changed.
    </div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/grades/enter" class="btn btn-outline">Enter marks one by one instead</a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger" style="margin-bottom:16px;"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="<?= $cfg['url'] ?>/school/grades/import">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title">1 · Where these marks belong</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Class *</label>
          <select name="class_id" class="form-control" required onchange="this.form.submit()">
            <option value="">— Select Class —</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= (string)$selectedClass === (string)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Academic Year *</label>
          <select name="year_id" class="form-control" required>
            <?php foreach ($years as $y): ?>
              <option value="<?= $y['id'] ?>" <?= (string)$selectedYear === (string)$y['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($y['name']) ?><?= (int)$y['is_current'] === 1 ? ' (current)' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Marking Period *</label>
          <select name="period" class="form-control" required>
            <option value="">— Select Marking Period —</option>
            <?php foreach ($periods as $slot => $label): ?>
              <option value="<?= $slot ?>" <?= $selectedPeriod === $slot ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Subject *</label>
          <select name="course_id" class="form-control" required>
            <option value="">— Select Subject —</option>
            <?php foreach ($subjects as $s): ?>
              <option value="<?= $s['id'] ?>" <?= (string)$selectedSubject === (string)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($subjects)): ?>
            <div class="form-hint">Choose a class first — its subjects will appear here.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title">2 · The marks</div></div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">One student per line — name, then the total for this subject</label>
        <textarea name="data" class="form-control" rows="12" style="font-family:ui-monospace,Consolas,monospace;font-size:13px;"
                  placeholder="Miracle T. Gboerreh-boe, 85&#10;Darlington S. Siafa Jr, 79&#10;Cyrus K. Kerkulah, 75"><?= htmlspecialchars($raw ?? '') ?></textarea>
        <div class="form-hint">
          A comma, a tab or just a space between the name and the mark — all read the same.
          Middle initials and Jr/Sr are ignored when matching, so names copied from a paper sheet still find the student.
        </div>
      </div>
      <button type="submit" class="btn btn-secondary">Check these marks</button>
    </div>
  </div>

  <?php if (is_array($rows)): ?>
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">3 · What this will do</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:3px;">Nothing has been saved yet.</div></div>
      <?php if (!empty($summary['ready'])): ?>
        <button type="submit" name="confirm" value="1" class="btn btn-primary">
          Add <?= (int)$summary['ready'] ?> mark<?= $summary['ready'] === 1 ? '' : 's' ?>
        </button>
      <?php endif; ?>
    </div>
    <div class="card-body" style="padding-bottom:0;">
      <div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
        <div class="stat-card" style="--card-color:var(--success);">
          <div class="stat-label">Will be added</div><div class="stat-value"><?= (int)$summary['ready'] ?></div></div>
        <div class="stat-card" style="--card-color:var(--info);">
          <div class="stat-label">Already recorded</div><div class="stat-value"><?= (int)$summary['already'] ?></div>
          <div class="stat-sub">left untouched</div></div>
        <div class="stat-card" style="--card-color:var(--warning);">
          <div class="stat-label">No such student</div><div class="stat-value"><?= (int)$summary['no_student'] + (int)$summary['ambiguous'] ?></div></div>
        <div class="stat-card" style="--card-color:var(--danger);">
          <div class="stat-label">Could not be read</div><div class="stat-value"><?= (int)$summary['unreadable'] + (int)$summary['bad_mark'] ?></div></div>
      </div>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th style="width:44px;"></th><th>Line</th><th>Matched student</th><th style="width:80px;">Mark</th><th>What will happen</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $i => $r): ?>
          <?php
            [$icon, $cls, $what] = [
              'ready'      => ['✓', 'badge-success', 'will be added'],
              'already'    => ['=', 'badge-info',    'already recorded — left as it is'],
              'no_student' => ['?', 'badge-warning', 'no student of this name in the class'],
              'ambiguous'  => ['?', 'badge-warning', 'more than one student of this name'],
              'bad_mark'   => ['!', 'badge-danger',  'the mark must be between 0 and 100'],
              'unreadable' => ['!', 'badge-danger',  'no mark found at the end of the line'],
            ][$r['status']];
          ?>
          <tr>
            <td><span class="badge <?= $cls ?>"><?= $icon ?></span></td>
            <td style="font-family:ui-monospace,Consolas,monospace;font-size:12px;"><?= htmlspecialchars($r['raw']) ?></td>
            <td><?= !empty($r['student_id']) ? htmlspecialchars($r['name']) : '<span class="text-muted">—</span>' ?></td>
            <td class="fw-700"><?= isset($r['mark']) ? number_format((float)$r['mark'], 0) : '—' ?></td>
            <td style="font-size:12.5px;color:var(--text-light);"><?= htmlspecialchars($what) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($summary['ready'])): ?>
    <div class="card-body" style="border-top:1px solid var(--border);">
      <button type="submit" name="confirm" value="1" class="btn btn-primary">
        Add <?= (int)$summary['ready'] ?> mark<?= $summary['ready'] === 1 ? '' : 's' ?> to this period
      </button>
      <span style="font-size:12px;color:var(--text-muted);margin-left:10px;">
        Only the <?= (int)$summary['ready'] ?> ticked above are written. Everything else is left exactly as it is.
      </span>
    </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</form>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
