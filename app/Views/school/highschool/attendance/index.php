<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div class="page-header-title">Attendance</div>
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
  <input type="hidden" name="class_id" value="<?= htmlspecialchars($classId) ?>">
  <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
  <div class="card">
    <div class="card-header">
      <div class="card-title">Attendance for <?= date('F j, Y', strtotime($date)) ?></div>
      <button type="submit" class="btn btn-primary btn-sm">Save Attendance</button>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th></tr></thead>
        <tbody>
          <?php foreach($students as $s): ?>
          <?php $cur = $records[$s['id']] ?? 'present'; ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
            <?php foreach(['present','absent','late','excused'] as $st): ?>
            <td style="text-align:center">
              <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $st ?>" <?= $cur===$st?'checked':'' ?>>
            </td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>
<?php else: ?>
<div class="card"><div class="card-body text-center text-muted">Select a class and date to mark attendance.</div></div>
<?php endif; ?>
<div style="margin-top:16px;">
  <a href="<?= $cfg['url'] ?>/school/attendance/report" class="btn btn-outline">View Attendance Report</a>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
