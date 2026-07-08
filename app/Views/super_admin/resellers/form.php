<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/admin/resellers">Resellers</a>
  <span>/</span>
  <span><?= $reseller ? 'Edit Reseller' : 'Add Reseller' ?></span>
</div>

<div class="page-header">
  <div class="page-header-title"><?= $reseller ? 'Edit Reseller' : 'Add New Reseller' ?></div>
</div>

<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/admin/resellers/<?= $reseller ? $reseller['id'].'/update' : 'store' ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Reseller Details</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Company Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($reseller['name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($reseller['email']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($reseller['phone']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Custom Domain</label>
          <input type="text" name="domain" class="form-control" placeholder="app.resellerdomain.com" value="<?= htmlspecialchars($reseller['domain']??'') ?>">
          <div class="form-hint">Leave blank to use the default platform domain</div>
        </div>
      </div>
      <?php if (!$reseller): ?>
      <div class="form-group">
        <label class="form-label">Owner Password *</label>
        <input type="password" name="password" class="form-control" placeholder="Set password for reseller owner" required>
        <small style="color:#666;">This password will be used for the first login.</small>
      </div>
      <?php endif; ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Commission Rate (%)</label>
          <input type="number" name="commission_rate" class="form-control" step="0.01" min="0" max="100" value="<?= $reseller['commission_rate']??0 ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Reseller Package *</label>
          <select name="reseller_plan_id" class="form-control" required>
            <option value="">-- Select Package --</option>
            <?php foreach($resellerPlans as $rp): ?>
              <option value="<?= $rp['id'] ?>" <?= ($reseller['reseller_plan_id']??'') == $rp['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($rp['name']) ?> (Max <?= $rp['max_schools'] ?> Schools)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <?php foreach(['pending','active','suspended'] as $s): ?>
              <option value="<?= $s ?>" <?= ($reseller['status']??'pending')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">White-Label Branding</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Primary Color</label>
          <input type="color" name="primary_color" class="form-control" style="height:42px;padding:4px;" value="<?= $reseller['primary_color']??'#10B981' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Secondary Color</label>
          <input type="color" name="secondary_color" class="form-control" style="height:42px;padding:4px;" value="<?= $reseller['secondary_color']??'#059669' ?>">
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $reseller ? 'Update Reseller' : 'Create Reseller' ?></button>
    <a href="<?= $cfg['url'] ?>/admin/resellers" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
