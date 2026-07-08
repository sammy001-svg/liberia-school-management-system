<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Add Department</div></div>
<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/departments/store">
  <div class="card"><div class="card-body">
    <div class="form-group">
      <label class="form-label">Department Name *</label>
      <input type="text" name="name" class="form-control" required placeholder="e.g. Computer Science">
    </div>
    <div class="form-group">
      <label class="form-label">Head of Department</label>
      <select name="head_user_id" class="form-control">
        <option value="">— Select Head —</option>
        <?php foreach($staff as $s): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea name="description" class="form-control" rows="3"></textarea>
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Save Department</button>
    <a href="<?= $cfg['url'] ?>/school/departments" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
