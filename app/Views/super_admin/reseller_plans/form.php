<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/admin/reseller-plans">Reseller Packages</a>
  <span>/</span>
  <span><?= $plan ? 'Edit Package' : 'Create Package' ?></span>
</div>

<div class="page-header">
  <div class="page-header-title"><?= $plan ? 'Edit Reseller Package' : 'Create New Package' ?></div>
</div>

<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/admin/reseller-plans/<?= $plan ? $plan['id'].'/update' : 'store' ?>">
  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Package Name *</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($plan['name']??'') ?>" placeholder="e.g. Starter Pack, Growth Pack" required>
      </div>
      <div class="form-group">
        <label class="form-label">Price (Ksh) *</label>
        <input type="number" name="price" class="form-control" step="0.01" value="<?= $plan['price']??0 ?>" required>
        <small style="color:#666;">How much the reseller pays for this package.</small>
      </div>
      <div class="form-group">
        <label class="form-label">Max Schools Allowed *</label>
        <input type="number" name="max_schools" class="form-control" value="<?= $plan['max_schools']??20 ?>" required>
        <small style="color:#666;">Total number of school slots in this package.</small>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($plan['description']??'') ?></textarea>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $plan ? 'Update Package' : 'Create Package' ?></button>
    <a href="<?= $cfg['url'] ?>/admin/reseller-plans" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
