<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/parents">Parents</a>
  <span>/</span><a href="<?= $cfg['url'] ?>/school/parents/<?= $parent['id'] ?>"><?= htmlspecialchars($parent['name'] ?? '') ?></a>
  <span>/</span><span>Edit</span>
</div>
<div class="page-header">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="avatar avatar-lg"><?= strtoupper(substr($parent['name'] ?? '?',0,1)) ?></div>
    <div>
      <div class="page-header-title">Edit Parent</div>
      <div class="page-header-sub"><?= htmlspecialchars($parent['name'] ?? '') ?></div>
    </div>
  </div>
</div>
<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/parents/<?= $parent['id'] ?>/update">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Personal Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($parent['name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($parent['email']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Login Username *</label>
          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($parent['username']??'') ?>" required>
          <div class="form-hint">Used to sign in when Parent Login Method is set to Username + Password.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($parent['phone']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="">— Select —</option>
            <?php foreach(['male','female','other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($parent['gender']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">TSM / Parent ID</label>
          <input type="text" name="employee_no" class="form-control" value="<?= htmlspecialchars($parent['employee_no']??'') ?>" placeholder="e.g. P2841">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="dob" class="form-control" value="<?= $parent['date_of_birth']??'' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">National ID / Passport No.</label>
          <input type="text" name="national_id" class="form-control" value="<?= htmlspecialchars($parent['national_id']??'') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Home Address</label>
        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($parent['address']??'') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <div class="card-header"><div class="card-title">Professional Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Occupation</label>
          <input type="text" name="occupation" class="form-control" value="<?= htmlspecialchars($parent['occupation']??'') ?>" placeholder="e.g. Trader, Nurse, Engineer">
        </div>
        <div class="form-group">
          <label class="form-label">Employer / Workplace</label>
          <input type="text" name="workplace" class="form-control" value="<?= htmlspecialchars($parent['workplace']??'') ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Emergency Contact Phone</label>
        <input type="text" name="emergency_contact_phone" class="form-control" value="<?= htmlspecialchars($parent['emergency_contact_phone']??'') ?>">
      </div>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Update Parent</button>
    <a href="<?= $cfg['url'] ?>/school/parents/<?= $parent['id'] ?>" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
