<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/staff">Staff</a>
  <span>/</span><span>Edit</span>
</div>
<div class="page-header">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="avatar avatar-lg"><?= strtoupper(substr($staff['name'] ?? '?',0,1)) ?></div>
    <div>
      <div class="page-header-title">Edit Staff</div>
      <div class="page-header-sub"><?= htmlspecialchars($staff['name'] ?? '') ?> · <?= htmlspecialchars($staff['role_name'] ?? '') ?></div>
    </div>
  </div>
</div>
<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/staff/<?= $staff['id'] ?>/update">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Personal Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="">— Select —</option>
            <?php foreach(['male','female','other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($staff['gender']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Position / Title</label>
          <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($staff['position']??'') ?>" placeholder="e.g. Principal, Business Manager">
        </div>
        <div class="form-group">
          <label class="form-label">Staff / Employee No</label>
          <input type="text" name="employee_no" class="form-control" value="<?= htmlspecialchars($staff['employee_no']??'') ?>" placeholder="e.g. CAF0001">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <div class="card-header"><div class="card-title">Salary Details</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Basic Salary *</label>
          <input type="number" name="basic_salary" class="form-control" step="0.01" value="<?= $staff['basic_salary'] ?? '' ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Allowances</label>
          <input type="number" name="allowances" class="form-control" step="0.01" value="<?= $staff['allowances'] ?? 0 ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Deductions</label>
          <input type="number" name="deductions" class="form-control" step="0.01" value="<?= $staff['deductions'] ?? 0 ?>">
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Update Staff</button>
    <a href="<?= $cfg['url'] ?>/school/staff" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
