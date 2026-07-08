<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Enroll Student to Course</div></div>
<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/enrollments/store">
  <div class="card"><div class="card-body">
    <div class="form-group">
      <label class="form-label">Student *</label>
      <select name="student_id" class="form-control" required>
        <option value="">— Select Student —</option>
        <?php foreach($students as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Course Unit *</label>
      <select name="course_id" class="form-control" required>
        <option value="">— Select Course —</option>
        <?php foreach($courses as $c): ?>
          <option value="<?= $c['id'] ?>">[<?= $c['code'] ?>] <?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Semester</label>
      <input type="number" name="semester" class="form-control" value="1" min="1" max="12">
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Enroll Now</button>
    <a href="<?= $cfg['url'] ?>/school/enrollments" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
