<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/admin/schools">Schools</a>
  <span>/</span>
  <span><?= $school ? 'Edit' : 'Add New' ?></span>
</div>
<div class="page-header">
  <div class="page-header-title"><?= $school ? 'Edit School' : 'Add New School' ?></div>
</div>
<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/admin/schools/<?= $school ? $school['id'].'/update' : 'store' ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">School Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">School Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($school['name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Institution Type *</label>
          <select name="institution_type" class="form-control" required>
            <option value="high_school" <?= ($school['institution_type']??'')==='high_school'?'selected':'' ?>>High School</option>
            <option value="university"  <?= ($school['institution_type']??'')==='university'?'selected':'' ?>>University</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($school['email']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($school['phone']??'') ?>">
        </div>
      </div>
      <?php if (!$school): ?>
      <div class="form-group">
        <label class="form-label">Admin Password *</label>
        <input type="password" name="password" class="form-control" placeholder="Set password for school admin" required>
        <small style="color:#666;">This password will be used for the first login.</small>
      </div>
      <?php endif; ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Country</label>
          <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($school['country']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <?php foreach(['pending','trial','active','suspended'] as $s): ?>
              <option value="<?= $s ?>" <?= ($school['status']??'pending')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control"><?= htmlspecialchars($school['address']??'') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Assign to Reseller</label>
          <select name="reseller_id" class="form-control">
            <option value="">— Direct Client —</option>
            <?php foreach($resellers as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ($school['reseller_id']??'')==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Subscription Plan</label>
          <select name="plan_id" class="form-control">
            <option value="">— No Plan —</option>
            <?php foreach($plans as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($school['plan_id']??'')==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $school ? 'Update School' : 'Create School' ?></button>
    <a href="<?= $cfg['url'] ?>/admin/schools" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
