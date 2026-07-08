<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Attendance Report</div></div>
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control" style="max-width:160px;">
    <input type="date" name="to"   value="<?= htmlspecialchars($to) ?>"   class="form-control" style="max-width:160px;">
    <button type="submit" class="btn btn-secondary">Generate</button>
  </div>
</form>
<?php if (!empty($report)): ?>
<div class="card">
  <div class="table-wrapper"><table>
    <thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th></tr></thead>
    <tbody>
      <?php
      $byStudent = [];
      foreach($report as $r) { $byStudent[$r['name']][$r['status']] = $r['cnt']; }
      foreach($byStudent as $name => $counts): ?>
      <tr>
        <td class="fw-600"><?= htmlspecialchars($name) ?></td>
        <td class="text-success"><?= $counts['present']??0 ?></td>
        <td class="text-danger"><?= $counts['absent']??0 ?></td>
        <td class="text-warning"><?= $counts['late']??0 ?></td>
        <td class="text-muted"><?= $counts['excused']??0 ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php else: ?>
<div class="card"><div class="card-body text-center text-muted">Select class and date range to generate report.</div></div>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
