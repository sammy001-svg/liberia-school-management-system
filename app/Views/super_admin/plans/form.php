<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/admin/plans">Plans</a>
  <span>/</span>
  <span><?= $plan ? 'Edit Plan' : 'Create Plan' ?></span>
</div>

<div class="page-header">
  <div class="page-header-title"><?= $plan ? 'Edit Subscription Plan' : 'Create New Plan' ?></div>
</div>

<div style="max-width:800px;">
<form method="POST" action="<?= $cfg['url'] ?>/admin/plans/<?= $plan ? $plan['id'].'/update' : 'store' ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Basic Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Plan Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($plan['name']??'') ?>" placeholder="e.g. Starter, Pro, Enterprise" required>
        </div>
        <div class="form-group">
          <label class="form-label">Billing Owner *</label>
          <select name="billing_owner" class="form-control" required>
            <option value="platform" <?= ($plan['billing_owner']??'')==='platform'?'selected':'' ?>>Platform (Direct Clients)</option>
            <option value="reseller" <?= ($plan['billing_owner']??'')==='reseller'?'selected':'' ?>>Reseller (For their Schools)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($plan['description']??'') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">Pricing & Limits</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Monthly Price (Ksh)</label>
          <input type="number" name="price_monthly" class="form-control" step="0.01" value="<?= $plan['price_monthly']??0 ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Yearly Price (Ksh)</label>
          <input type="number" name="price_yearly" class="form-control" step="0.01" value="<?= $plan['price_yearly']??0 ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Max Students</label>
          <input type="number" name="max_students" class="form-control" value="<?= $plan['max_students']??500 ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Max Teachers</label>
          <input type="number" name="max_teachers" class="form-control" value="<?= $plan['max_teachers']??50 ?>" required>
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">Feature Limits</div></div>
    <div class="card-body">
      <div style="display:grid;grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:15px;">
        <?php 
          $availableFeatures = [
            'attendance' => 'Attendance Management',
            'exams'      => 'Exams & Grading',
            'finance'    => 'Finance & Invoicing',
            'messaging'  => 'Messaging & Announcements',
            'library'    => 'Library & Materials',
            'hr'         => 'HR & Payroll',
            'inventory'  => 'Inventory Management'
          ];
          foreach($availableFeatures as $key => $label): 
            $checked = in_array($key, $plan['features'] ?? []) ? 'checked' : '';
        ?>
        <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
          <input type="checkbox" name="features[]" value="<?= $key ?>" <?= $checked ?>>
          <span><?= $label ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $plan ? 'Update Plan' : 'Save Plan' ?></button>
    <a href="<?= $cfg['url'] ?>/admin/plans" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
