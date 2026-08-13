<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Academic Years &amp; Periods</div>
    <div class="page-header-sub">Used by Classes, Timetable, Fee Structures and Exams to scope records to a period</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTermModal').classList.add('open')">+ Add Period</button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addYearModal').classList.add('open')">+ Add Academic Year</button>
  </div>
</div>

<div class="card mb-16">
  <div class="card-header"><div class="card-title">Academic Years (<?= count($years) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach($years as $y): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($y['name']) ?></td>
          <td><?= date('M d, Y', strtotime($y['start_date'])) ?></td>
          <td><?= date('M d, Y', strtotime($y['end_date'])) ?></td>
          <td><?php if($y['is_current']): ?><span class="badge badge-success">CURRENT</span><?php else: ?><span class="badge badge-muted">—</span><?php endif; ?></td>
          <td>
            <div style="display:flex;gap:6px;justify-content:flex-end;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openEditYear(<?= json_encode([
                "id" => $y['id'], "name" => $y['name'],
                "start_date" => $y['start_date'], "end_date" => $y['end_date'],
                "is_current" => (int)$y['is_current'],
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/academic-years/<?= $y['id'] ?>/delete"
                    data-confirm="Delete the academic year &quot;<?= htmlspecialchars($y['name']) ?>&quot;? This is only possible if nothing is linked to it yet."
                    data-confirm-title="Delete Academic Year" data-confirm-label="Delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($years)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:32px">No academic years yet. <a href="javascript:void(0)" onclick="document.getElementById('addYearModal').classList.add('open')">Add one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Periods (<?= count($terms) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Period</th><th>Academic Year</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
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
        <?php if(empty($terms)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:32px">No periods yet. <a href="javascript:void(0)" onclick="document.getElementById('addTermModal').classList.add('open')">Add one</a></td></tr><?php endif; ?>
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
      <div class="modal-title">Add Period</div>
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
          <label class="form-label">Period Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Period 1">
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
            <input type="checkbox" name="is_current" value="1"> <span class="form-label" style="margin:0">Set as current period</span>
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTermModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Period</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Academic Year Modal -->
<div class="modal-overlay" id="editYearModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Academic Year</div>
      <button class="modal-close" onclick="document.getElementById('editYearModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editYearForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Year Name *</label>
          <input type="text" name="name" id="editYearName" class="form-control" required placeholder="e.g. 2025-2026">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date *</label>
            <input type="date" name="start_date" id="editYearStart" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date *</label>
            <input type="date" name="end_date" id="editYearEnd" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="is_current" id="editYearCurrent" value="1">
            <span class="form-label" style="margin:0">Set as current academic year</span>
          </label>
          <div class="form-hint">Marking this current removes the marker from any other year.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editYearModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditYear(y) {
  document.getElementById('editYearForm').action = '<?= $cfg['url'] ?>/school/academic-years/' + y.id + '/update';
  document.getElementById('editYearName').value  = y.name || '';
  document.getElementById('editYearStart').value = y.start_date || '';
  document.getElementById('editYearEnd').value   = y.end_date || '';
  document.getElementById('editYearCurrent').checked = !!Number(y.is_current);
  document.getElementById('editYearModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
