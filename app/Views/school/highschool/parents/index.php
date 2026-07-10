<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Parents</div>
    <div class="page-header-sub">Manage parent/guardian accounts and student links</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.add('open')">Bulk Upload</button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addParentModal').classList.add('open')">+ Add Parent</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Parents</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Linked to a Student</div>
    <div class="stat-value"><?= (int)($stats['linked'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Not Linked</div>
    <div class="stat-value"><?= (int)($stats['unlinked'] ?? 0) ?></div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or email…" class="form-control" style="max-width:280px;">
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/parents" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Parents (<?= $total ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>TSM ID</th><th>Parent</th><th>Username</th><th>Phone</th><th>Occupation</th><th>Linked Children</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($parents as $p): ?>
        <tr>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($p['employee_no'] ?? '—') ?></td>
          <td>
            <a href="<?= $cfg['url'] ?>/school/parents/<?= $p['id'] ?>" style="display:flex;align-items:center;gap:10px;color:inherit;">
              <div class="avatar"><?= strtoupper(substr($p['name'],0,1)) ?></div>
              <div>
                <div class="fw-600"><?= htmlspecialchars($p['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($p['email'] ?? '—') ?></div>
              </div>
            </a>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($p['username'] ?? '—') ?></td>
          <td><?= htmlspecialchars($p['phone']??'—') ?></td>
          <td><?= htmlspecialchars($p['occupation']??'—') ?></td>
          <td>
            <?php if($p['children_count'] > 0): ?>
              <span class="badge badge-info" title="<?= htmlspecialchars($p['children_names']) ?>"><?= $p['children_count'] ?> child<?= $p['children_count']>1?'ren':'' ?></span>
            <?php else: ?>
              <span class="badge badge-muted">None</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-<?= $p['status']==='active'?'success':'danger' ?>"><?= ucfirst($p['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/parents/<?= $p['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/school/parents/<?= $p['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/parents/<?= $p['id'] ?>/delete" data-confirm="Remove <?= htmlspecialchars($p['name']) ?>? This cannot be undone." data-confirm-title="Remove Parent" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($parents)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">👪</div>
            <div class="empty-state-text">No parents registered yet. <a href="javascript:void(0)" onclick="document.getElementById('addParentModal').classList.add('open')">Add the first parent</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<!-- Add Parent Modal -->
<div class="modal-overlay" id="addParentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add New Parent</div>
      <button class="modal-close" onclick="document.getElementById('addParentModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/parents/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Personal Information</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone *</label>
            <input type="text" name="phone" class="form-control" required>
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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">TSM / Parent ID</label>
            <input type="text" name="employee_no" class="form-control" placeholder="e.g. P2841">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Home Address</label>
          <textarea name="address" class="form-control" rows="2"></textarea>
        </div>

        <div class="modal-section-title">Professional Information</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Occupation</label>
            <input type="text" name="occupation" class="form-control" placeholder="e.g. Trader, Nurse, Engineer">
          </div>
          <div class="form-group">
            <label class="form-label">Employer / Workplace</label>
            <input type="text" name="workplace" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Emergency Contact Phone</label>
          <input type="text" name="emergency_contact_phone" class="form-control">
        </div>

        <div class="modal-section-title">Linked Student</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Link to Student</label>
            <select name="student_id" class="form-control">
              <option value="">— Select Student —</option>
              <?php foreach($students as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Relationship</label>
            <select name="relationship" class="form-control">
              <?php foreach(['parent','mother','father','guardian'] as $r): ?>
                <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-section-title">Account</div>
        <div class="form-group">
          <label class="form-label">Login Password</label>
          <input type="password" name="password" class="form-control" placeholder="Default: Parent@123">
          <div class="form-hint">A login username is generated automatically from the parent's name — you can change it afterward from the parent's profile.</div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addParentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Parent</button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal-overlay" id="bulkUploadModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Bulk Upload Parents</div>
      <button class="modal-close" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/parents/bulk-upload" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
          Upload a CSV file to add multiple parent accounts at once.
          <a href="<?= $cfg['url'] ?>/school/parents/bulk-template">Download the CSV template</a> to see the expected columns.
        </p>
        <div class="form-group">
          <label class="form-label">CSV File *</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <div class="form-hint">New parents get the default password <code>Parent@123</code> and a login username generated automatically from their name. Link to a student via their admission number; rows with missing name/email or duplicate emails are skipped and reported.</div>
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
