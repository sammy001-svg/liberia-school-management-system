<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Teachers</div>
    <div class="page-header-sub">Manage teaching staff and assignments</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.add('open')">Bulk Upload</button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addTeacherModal').classList.add('open')">+ Add Teacher</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Teachers</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Full-Time</div>
    <div class="stat-value"><?= (int)($stats['full_time'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Part-Time / Contract</div>
    <div class="stat-value"><?= (int)($stats['part_time'] ?? 0) + (int)($stats['contract'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Departments Covered</div>
    <div class="stat-value"><?= (int)($stats['departments'] ?? 0) ?></div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or employee no…" class="form-control" style="max-width:280px;">
    <select name="class_id" class="form-control" style="max-width:200px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $cl): ?>
        <option value="<?= $cl['id'] ?>" <?= $classId==$cl['id']?'selected':'' ?>><?= htmlspecialchars($cl['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/teachers" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Teachers (<?= $total ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Teacher</th><th>Employee No</th><th>Department</th><th>Class</th><th>Subjects Taught</th><th>Phone</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($teachers as $t): ?>
        <tr>
          <td>
            <a href="<?= $cfg['url'] ?>/school/teachers/<?= $t['id'] ?>" style="display:flex;align-items:center;gap:10px;color:inherit;">
              <div class="avatar"><?= strtoupper(substr($t['name'],0,1)) ?></div>
              <div>
                <div class="fw-600"><?= htmlspecialchars($t['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($t['email']) ?></div>
              </div>
            </a>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($t['employee_no']) ?></td>
          <td><?= htmlspecialchars($t['department_name']??'—') ?></td>
          <td><?= htmlspecialchars($t['class_name']??'—') ?></td>
          <td><?= htmlspecialchars($t['subjects_taught'] ?: 'Not assigned') ?></td>
          <td><?= htmlspecialchars($t['phone']??'—') ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/teachers/<?= $t['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/school/teachers/<?= $t['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/<?= $t['id'] ?>/delete" data-confirm="Remove <?= htmlspecialchars($t['name']) ?>? This cannot be undone." data-confirm-title="Remove Teacher" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($teachers)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🧑‍🏫</div>
            <div class="empty-state-text">No teachers found. <a href="javascript:void(0)" onclick="document.getElementById('addTeacherModal').classList.add('open')">Add the first teacher</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<!-- Add Teacher Modal -->
<div class="modal-overlay" id="addTeacherModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add New Teacher</div>
      <button class="modal-close" onclick="document.getElementById('addTeacherModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Personal Information</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" class="form-control">
              <option value="">— Select —</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="dob" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">National ID / Passport No.</label>
            <input type="text" name="national_id" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <textarea name="address" class="form-control" rows="2"></textarea>
        </div>

        <div class="modal-section-title">Professional Information</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Qualification</label>
            <input type="text" name="qualification" class="form-control" placeholder="e.g. B.Ed, MSc">
          </div>
          <div class="form-group">
            <label class="form-label">Area of Expertise</label>
            <input type="text" name="specialization" class="form-control" placeholder="e.g. Mathematics">
            <div class="form-hint">A descriptive note only &mdash; assign actual subjects from the teacher's profile page after creating the account.</div>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Assign to Class</label>
            <select name="class_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Employment Type</label>
            <select name="employment_type" class="form-control">
              <option value="full_time">Full-Time</option>
              <option value="part_time">Part-Time</option>
              <option value="contract">Contract</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Join Date</label>
            <input type="date" name="joined_at" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>

        <div class="modal-section-title">Account</div>
        <div class="form-group">
          <label class="form-label">Login Password</label>
          <input type="password" name="password" class="form-control" placeholder="Default: Teacher@123">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTeacherModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Teacher</button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal-overlay" id="bulkUploadModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Bulk Upload Teachers</div>
      <button class="modal-close" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/bulk-upload" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
          Upload a CSV file to add multiple teachers at once.
          <a href="<?= $cfg['url'] ?>/school/teachers/bulk-template">Download the CSV template</a> to see the expected columns.
        </p>
        <div class="form-group">
          <label class="form-label">CSV File *</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <div class="form-hint">New teachers get the default password <code>Teacher@123</code>. Rows with missing name/email or duplicate emails are skipped and reported.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
