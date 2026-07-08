<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Add School</div></div>
<div style="max-width:640px;">
<form method="POST" action="<?= $cfg['url'] ?>/reseller/schools/store">
  <div class="card"><div class="card-body">
    <div class="form-row">
      <div class="form-group"><label class="form-label">School Name *</label><input type="text" name="name" class="form-control" required></div>
      <div class="form-group"><label class="form-label">Institution Type *</label>
        <select name="institution_type" class="form-control" required>
          <option value="high_school">High School</option>
          <option value="university">University</option>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
      <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Country</label><input type="text" name="country" class="form-control"></div>
      <div class="form-group"><label class="form-label">Plan</label>
        <select name="plan_id" class="form-control">
          <option value="">— No Plan —</option>
          <?php foreach($plans as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn btn-primary">Create School</button><a href="<?= $cfg['url'] ?>/reseller/schools" class="btn btn-secondary">Cancel</a></div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
