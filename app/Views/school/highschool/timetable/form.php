<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Add Timetable Entry</div></div>
<div style="max-width:640px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/timetable/store">
  <div class="card"><div class="card-body">
    <div class="form-row">
      <div class="form-group"><label class="form-label">Class *</label><select name="class_id" class="form-control" required><option value="">— Select —</option><?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">Day *</label><select name="day_of_week" class="form-control" required><?php foreach(['monday','tuesday','wednesday','thursday','friday','saturday'] as $d): ?><option value="<?= $d ?>"><?= ucfirst($d) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Start Time *</label><input type="time" name="start_time" class="form-control" required></div>
      <div class="form-group"><label class="form-label">End Time *</label><input type="time" name="end_time" class="form-control" required></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Subject / Course</label><select name="course_id" class="form-control"><option value="">— Select —</option><?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label class="form-label">Teacher</label><select name="teacher_id" class="form-control"><option value="">— Select —</option><?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
    </div>
    <div class="form-group"><label class="form-label">Room / Venue</label><input type="text" name="room" class="form-control" placeholder="e.g. Room 101"></div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn btn-primary">Add Entry</button><a href="<?= $cfg['url'] ?>/school/timetable" class="btn btn-secondary">Cancel</a></div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
