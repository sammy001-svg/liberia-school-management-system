<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/students">Students</a>
  <span>/</span><a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>"><?= htmlspecialchars($student['name'] ?? '') ?></a>
  <span>/</span><span>Edit</span>
</div>
<div class="page-header">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="avatar avatar-lg"><?= strtoupper(substr($student['name'] ?? '?',0,1)) ?></div>
    <div>
      <div class="page-header-title">Edit Student Profile</div>
      <div class="page-header-sub"><?= htmlspecialchars($student['name'] ?? '') ?></div>
    </div>
  </div>
</div>
<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>/update">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Personal Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($student['name']??'') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']??'') ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="">— Select —</option>
            <?php foreach(['male','female','other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($student['gender']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="dob" class="form-control" value="<?= $student['date_of_birth']??'' ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Assign to Class</label>
          <select name="class_id" class="form-control">
            <option value="">— Not Assigned —</option>
            <?php foreach($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($student['class_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <?php foreach(['active','graduated','withdrawn','suspended'] as $s): ?>
            <option value="<?= $s ?>" <?= ($student['status']??'active')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Update Student</button>
    <a href="<?= $cfg['url'] ?>/school/students" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
