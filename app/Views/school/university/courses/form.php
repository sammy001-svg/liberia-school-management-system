<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Add Course Unit</div></div>
<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/courses/store">
  <div class="card"><div class="card-body">
    <div class="form-group">
      <label class="form-label">Course Title *</label>
      <input type="text" name="name" class="form-control" required placeholder="e.g. Object Oriented Programming">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Course Code</label>
        <input type="text" name="code" class="form-control" placeholder="e.g. CSC201">
      </div>
      <div class="form-group">
        <label class="form-label">Credit Hours (Units)</label>
        <input type="number" name="credit_hours" class="form-control" value="3">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Program (Optional)</label>
      <select name="program_id" class="form-control">
        <option value="">— General / Cross-cutting —</option>
        <?php foreach($programs as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Target Semester</label>
      <input type="number" name="semester_no" class="form-control" value="1">
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Add Course</button>
    <a href="<?= $cfg['url'] ?>/school/courses" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
