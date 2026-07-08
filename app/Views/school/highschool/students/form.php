<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/students">Students</a>
  <span>/</span><span><?= $student ? 'Edit Student' : 'Admit Student' ?></span>
</div>
<div class="page-header">
  <div class="page-header-title"><?= $student ? 'Edit Student Profile' : 'Admit New Student' ?></div>
</div>
<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/students/<?= $student ? $student['id'].'/update' : 'store' ?>">
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
      <?php if($student): ?>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <?php foreach(['active','graduated','withdrawn','suspended'] as $s): ?>
            <option value="<?= $s ?>" <?= ($student['status']??'active')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php else: ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Admission Date</label>
          <input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Login Password</label>
          <input type="password" name="password" class="form-control" placeholder="Default: Student@123">
          <div class="form-hint">Leave blank to use default password</div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $student ? 'Update Student' : 'Admit Student' ?></button>
    <a href="<?= $cfg['url'] ?>/school/students" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
