<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Academic Years &amp; Terms</div>
    <div class="page-header-sub">Used by Classes, Timetable, Fee Structures and Exams to scope records to a period</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTermModal').classList.add('open')">+ Add Term</button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addYearModal').classList.add('open')">+ Add Academic Year</button>
  </div>
</div>

<div class="card mb-16">
  <div class="card-header"><div class="card-title">Academic Years (<?= count($years) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($years as $y): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($y['name']) ?></td>
          <td><?= date('M d, Y', strtotime($y['start_date'])) ?></td>
          <td><?= date('M d, Y', strtotime($y['end_date'])) ?></td>
          <td><?php if($y['is_current']): ?><span class="badge badge-success">CURRENT</span><?php else: ?><span class="badge badge-muted">—</span><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($years)): ?><tr><td colspan="4" class="text-center text-muted" style="padding:32px">No academic years yet. <a href="javascript:void(0)" onclick="document.getElementById('addYearModal').classList.add('open')">Add one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Terms (<?= count($terms) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Term</th><th>Academic Year</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($terms as $t): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($t['name']) ?></td>
          <td><?= htmlspecialchars($t['year_name']) ?></td>
          <td><?= date('M d, Y', strtotime($t['start_date'])) ?></td>
          <td><?= date('M d, Y', strtotime($t['end_date'])) ?></td>
          <td><?php if($t['is_current']): ?><span class="badge badge-success">CURRENT</span><?php else: ?><span class="badge badge-muted">—</span><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($terms)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:32px">No terms yet. <a href="javascript:void(0)" onclick="document.getElementById('addTermModal').classList.add('open')">Add one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Academic Year Modal -->
<div class="modal-overlay" id="addYearModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Academic Year</div>
      <button class="modal-close" onclick="document.getElementById('addYearModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/academic-years/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. 2026/2027">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date *</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date *</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="is_current" value="1"> <span class="form-label" style="margin:0">Set as current academic year</span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addYearModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Academic Year</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Term Modal -->
<div class="modal-overlay" id="addTermModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Term</div>
      <button class="modal-close" onclick="document.getElementById('addTermModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/terms/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Academic Year *</label>
          <select name="academic_year_id" class="form-control" required>
            <option value="">— Select Academic Year —</option>
            <?php foreach($years as $y): ?>
              <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if(empty($years)): ?><div class="form-hint">Add an academic year first.</div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Term Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Term 1">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date *</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date *</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="is_current" value="1"> <span class="form-label" style="margin:0">Set as current term</span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTermModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Term</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
