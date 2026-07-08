<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Create New Program</div></div>
<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/programs/store">
  <div class="card"><div class="card-body">
    <div class="form-group">
      <label class="form-label">Program Name *</label>
      <input type="text" name="name" class="form-control" required placeholder="e.g. BSc Software Engineering">
    </div>
    <div class="form-group">
      <label class="form-label">Program Code</label>
      <input type="text" name="code" class="form-control" placeholder="e.g. BSE001">
    </div>
    <div class="form-group">
      <label class="form-label">Department *</label>
      <select name="department_id" class="form-control" required>
        <?php foreach($departments as $d): ?>
          <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Duration (Years)</label>
        <input type="number" name="duration_years" class="form-control" value="4">
      </div>
      <div class="form-group">
        <label class="form-label">Degree Type</label>
        <select name="degree_type" class="form-control">
          <option value="bachelor">Bachelor</option>
          <option value="master">Master</option>
          <option value="phd">PhD</option>
          <option value="diploma">Diploma</option>
          <option value="certificate">Certificate</option>
        </select>
      </div>
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Create Program</button>
    <a href="<?= $cfg['url'] ?>/school/programs" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
