<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Timetable</div><a href="<?= $cfg['url'] ?>/school/timetable/create" class="btn btn-primary">+ Add Entry</a></div>
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load</button>
  </div>
</form>
<?php $days = ['monday','tuesday','wednesday','thursday','friday']; ?>
<?php if (!empty($timetable)): ?>
<?php foreach($days as $day): ?>
<?php if (!empty($timetable[$day])): ?>
<div class="card mb-16">
  <div class="card-header"><div class="card-title"><?= ucfirst($day) ?></div></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>Time</th><th>Subject</th><th>Teacher</th><th>Room</th></tr></thead>
    <tbody>
      <?php foreach($timetable[$day] as $slot): ?>
      <tr>
        <td style="font-family:monospace;font-size:12px"><?= substr($slot['start_time'],0,5) ?> – <?= substr($slot['end_time'],0,5) ?></td>
        <td class="fw-600"><?= htmlspecialchars($slot['course_name']??'—') ?></td>
        <td><?= htmlspecialchars($slot['teacher_name']??'—') ?></td>
        <td><?= htmlspecialchars($slot['room']??'—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php endforeach; ?>
<?php else: ?><div class="card"><div class="card-body text-center text-muted">Select a class to view timetable.</div></div><?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
