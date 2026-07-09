<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/attendance">Attendance</a>
  <span>/</span><span>Report</span>
</div>
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
<?php if ($stats !== null): ?>
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Records</div>
    <div class="stat-value"><?= (int)$stats['total'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">School Days Covered</div>
    <div class="stat-value"><?= (int)$stats['days'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Class Attendance Rate</div>
    <div class="stat-value"><?= $stats['rate'] !== null ? $stats['rate'].'%' : '—' ?></div>
  </div>
</div>
<?php endif; ?>
<?php if (!empty($report)): ?>
<div class="card">
  <div class="table-wrapper"><table>
    <thead><tr><th>Student</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Attendance Rate</th></tr></thead>
    <tbody>
      <?php
      $byStudent = [];
      foreach($report as $r) { $byStudent[$r['name']][$r['status']] = $r['cnt']; }
      foreach($byStudent as $name => $counts):
        $present = $counts['present'] ?? 0; $absent = $counts['absent'] ?? 0; $late = $counts['late'] ?? 0; $excused = $counts['excused'] ?? 0;
        $studentTotal = $present + $absent + $late + $excused;
        $rate = $studentTotal > 0 ? round($present / $studentTotal * 100) : 0;
      ?>
      <tr>
        <td class="fw-600"><?= htmlspecialchars($name) ?></td>
        <td class="text-success"><?= $present ?></td>
        <td class="text-danger"><?= $absent ?></td>
        <td class="text-warning"><?= $late ?></td>
        <td class="text-muted"><?= $excused ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:8px;">
            <div class="progress-track" style="width:80px;"><div class="progress-fill" style="width:<?= $rate ?>%;--card-color:<?= $rate>=75?'var(--success)':($rate>=50?'var(--warning)':'var(--danger)') ?>;"></div></div>
            <span style="font-size:12px;color:var(--text-muted);"><?= $rate ?>%</span>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php else: ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">📊</div>
  <div class="empty-state-text">Select a class and date range above to generate a report.</div>
</div></div>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
