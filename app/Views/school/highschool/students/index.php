<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Students</div>
    <div class="page-header-sub">Manage student enrolment and profiles</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.add('open')">Bulk Upload</button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('admitModal').classList.add('open')">+ Admit Student</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Students</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Active</div>
    <div class="stat-value"><?= (int)($stats['active'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Male</div>
    <div class="stat-value"><?= (int)($stats['male'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: #EC4899;">
    <div class="stat-label">Female</div>
    <div class="stat-value"><?= (int)($stats['female'] ?? 0) ?></div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or admission no…" class="form-control" style="max-width:280px;">
    <select name="class_id" class="form-control" style="max-width:200px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/students" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Students (<?= $total ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Admission No</th><th>Class</th><th>Phone</th><th>Gender</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($students as $s): ?>
        <tr>
          <td>
            <a href="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>" style="display:flex;align-items:center;gap:10px;color:inherit;">
              <div class="avatar"><?= strtoupper(substr($s['name'],0,1)) ?></div>
              <div>
                <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></div>
              </div>
            </a>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($s['admission_no']) ?></td>
          <td><?= htmlspecialchars($s['class_name']??'—') ?></td>
          <td><?= htmlspecialchars($s['phone']??'—') ?></td>
          <td><?= ucfirst($s['gender']??'—') ?></td>
          <td><span class="badge badge-<?= $s['status']==='active'?'success':($s['status']==='graduated'?'info':'danger') ?>"><?= ucfirst($s['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>/delete" data-confirm="Remove <?= htmlspecialchars($s['name']) ?>? This cannot be undone." data-confirm-title="Remove Student" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($students)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🎓</div>
            <div class="empty-state-text">No students found. <a href="javascript:void(0)" onclick="document.getElementById('admitModal').classList.add('open')">Admit the first student</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<!-- Admission Modal -->
<div class="modal-overlay" id="admitModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Admit New Student</div>
      <button class="modal-close" onclick="document.getElementById('admitModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/students/store">
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
            <label class="form-label">Blood Group</label>
            <select name="blood_group" class="form-control">
              <option value="">— Select —</option>
              <?php foreach(['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                <option value="<?= $bg ?>"><?= $bg ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <textarea name="address" class="form-control" rows="2"></textarea>
        </div>

        <div class="modal-section-title">Guardian &amp; Emergency Contact</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Guardian Name</label>
            <input type="text" name="guardian_name" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Relationship</label>
            <select name="guardian_relationship" class="form-control">
              <option value="">— Select —</option>
              <option value="Father">Father</option>
              <option value="Mother">Mother</option>
              <option value="Guardian">Guardian</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Guardian Phone</label>
            <input type="text" name="guardian_phone" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Emergency Contact Phone</label>
            <input type="text" name="emergency_contact_phone" class="form-control">
          </div>
        </div>

        <div class="modal-section-title">Admission Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign to Class</label>
            <select name="class_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Previous School</label>
            <input type="text" name="previous_school" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Admission Date</label>
            <input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Login Password</label>
            <input type="password" name="password" class="form-control" placeholder="Default: Student@123">
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('admitModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Admit Student</button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal-overlay" id="bulkUploadModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Bulk Upload Students</div>
      <button class="modal-close" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/students/bulk-upload" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
          Upload a CSV file to admit multiple students at once.
          <a href="<?= $cfg['url'] ?>/school/students/bulk-template">Download the CSV template</a> to see the expected columns.
        </p>
        <div class="form-group">
          <label class="form-label">CSV File *</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <div class="form-hint">New students get the default password <code>Student@123</code>. Rows with missing name/email or duplicate emails are skipped and reported.</div>
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
