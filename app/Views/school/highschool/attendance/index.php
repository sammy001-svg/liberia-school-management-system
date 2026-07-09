<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Attendance</div>
    <div class="page-header-sub">Mark daily attendance by class</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/attendance/report" class="btn btn-outline">📊 View Report</a>
</div>
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" class="form-control" style="max-width:180px;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load</button>
  </div>
</form>
<?php if (!empty($students)): ?>
<form method="POST" action="<?= $cfg['url'] ?>/school/attendance/mark">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="class_id" value="<?= htmlspecialchars($classId) ?>">
  <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Attendance for <?= date('F j, Y', strtotime($date)) ?> (<?= count($students) ?> students)</div>
      <div style="display:flex;gap:8px;">
        <button type="button" class="btn btn-sm btn-outline" onclick="markAll('present')">Mark All Present</button>
        <button type="submit" class="btn btn-sm btn-primary">Save Attendance</button>
      </div>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($students as $s): ?>
          <?php $cur = $records[$s['id']] ?? 'present'; ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
            <td>
              <div class="status-pills">
                <?php foreach(['present'=>'Present','absent'=>'Absent','late'=>'Late','excused'=>'Excused'] as $st=>$lbl): ?>
                <label class="status-pill status-pill-<?= $st ?>">
                  <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $st ?>" <?= $cur===$st?'checked':'' ?>>
                  <span><?= $lbl ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>
<script>
function markAll(status){
  document.querySelectorAll('.status-pill-' + status + ' input').forEach(function(input){ input.checked = true; });
}
</script>
<?php else: ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">📋</div>
  <div class="empty-state-text">Select a class and date above to mark attendance.</div>
</div></div>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
