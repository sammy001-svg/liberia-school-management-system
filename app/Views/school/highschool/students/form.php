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
      <div class="page-header-sub"><?= htmlspecialchars($student['name'] ?? '') ?> · <?= htmlspecialchars($student['admission_no'] ?? '') ?></div>
    </div>
  </div>
</div>
<?php
// Legacy records created before the name-split migration may have no first_name/last_name yet — derive a starting point from the full name.
$firstName = $student['first_name'] ?? '';
$middleName = $student['middle_name'] ?? '';
$lastName = $student['last_name'] ?? '';
if ($firstName === '' && $lastName === '' && !empty($student['name'])) {
    $parts = preg_split('/\s+/', trim($student['name']));
    $firstName = $parts[0] ?? '';
    $lastName = count($parts) > 1 ? end($parts) : '';
    $middleName = count($parts) > 2 ? implode(' ', array_slice($parts, 1, -1)) : '';
}
?>
<div style="max-width:700px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>/update">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Personal Information</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($firstName) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Middle Name</label>
          <input type="text" name="middle_name" class="form-control" value="<?= htmlspecialchars($middleName) ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($lastName) ?>" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($student['email']??'') ?>" placeholder="Optional">
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($student['phone']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-control">
            <option value="">— Select —</option>
            <?php foreach(['male','female','other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($student['gender']??'')===$g?'selected':'' ?>><?= ucfirst($g) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="dob" class="form-control" value="<?= $student['date_of_birth']??'' ?>">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Home Address</label>
        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($student['address']??'') ?></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">County</label>
          <input type="text" name="county" class="form-control" value="<?= htmlspecialchars($student['county']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Country</label>
          <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($student['country']??'Liberia') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Religion</label>
          <input type="text" name="religion" class="form-control" value="<?= htmlspecialchars($student['religion']??'') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <div class="card-header"><div class="card-title">Guardian &amp; Emergency Contact</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Guardian Name</label>
          <input type="text" name="guardian_name" class="form-control" value="<?= htmlspecialchars($student['guardian_name']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Relationship</label>
          <select name="guardian_relationship" class="form-control">
            <option value="">— Select —</option>
            <?php foreach(['Father','Mother','Guardian','Other'] as $r): ?>
              <option value="<?= $r ?>" <?= ($student['guardian_relationship']??'')===$r?'selected':'' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Contact Number 1 (Guardian Phone)</label>
          <input type="text" name="guardian_phone" class="form-control" value="<?= htmlspecialchars($student['guardian_phone']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Contact Number 2 (Emergency)</label>
          <input type="text" name="emergency_contact_phone" class="form-control" value="<?= htmlspecialchars($student['emergency_contact_phone']??'') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <div class="card-header"><div class="card-title">Previous School</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Previous School Name</label>
          <input type="text" name="previous_school" class="form-control" value="<?= htmlspecialchars($student['previous_school']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Previous Class</label>
          <input type="text" name="previous_class" class="form-control" value="<?= htmlspecialchars($student['previous_class']??'') ?>">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Previous School Address</label>
          <input type="text" name="previous_school_address" class="form-control" value="<?= htmlspecialchars($student['previous_school_address']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Reason for Leaving</label>
          <input type="text" name="reason_for_leaving" class="form-control" value="<?= htmlspecialchars($student['reason_for_leaving']??'') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card" style="margin-top:20px;">
    <div class="card-header"><div class="card-title">Admission Details</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Assign to Class</label>
          <select name="class_id" class="form-control">
            <option value="">— Not Assigned —</option>
            <?php foreach($classes as $c): ?>
              <option value="<?= $c['id'] ?>" <?= ($student['class_id']??'')==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Student Type</label>
          <select name="admission_type" class="form-control">
            <option value="new" <?= ($student['admission_type']??'new')==='new'?'selected':'' ?>>New Student</option>
            <option value="old" <?= ($student['admission_type']??'')==='old'?'selected':'' ?>>Old Student</option>
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
