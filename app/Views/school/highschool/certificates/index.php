<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Certificates</div>
    <div class="page-header-sub">Issue certificates to students, teachers and staff</div>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= $cfg['url'] ?>/school/certificates/types" class="btn btn-outline">Manage Types</a>
    <?php if($tab==='student' && $academicYearId && $stats['pending'] > 0): ?>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('bulkModal').classList.add('open')">+ Generate All Pending (<?= $stats['pending'] ?>)</button>
    <?php endif; ?>
  </div>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px;">
  <a href="<?= $cfg['url'] ?>/school/certificates?tab=student" class="btn btn-sm <?= $tab==='student'?'btn-primary':'btn-outline' ?>">Students</a>
  <a href="<?= $cfg['url'] ?>/school/certificates?tab=staff" class="btn btn-sm <?= $tab==='staff'?'btn-primary':'btn-outline' ?>">Teachers &amp; Staff</a>
</div>

<?php if(empty($certificateTypes)): ?>
<div class="card"><div class="card-body">
  <div class="empty-state">
    <div class="empty-state-icon">🎖️</div>
    <div class="empty-state-text">No certificate types set up for <?= $tab==='student'?'students':'teachers/staff' ?> yet. <a href="<?= $cfg['url'] ?>/school/certificates/types">Add one</a> first.</div>
  </div>
</div></div>
<?php elseif($tab==='student' && empty($academicYears)): ?>
<div class="card"><div class="card-body">
  <div class="empty-state">
    <div class="empty-state-icon">📅</div>
    <div class="empty-state-text">No academic years set up yet. <a href="<?= $cfg['url'] ?>/school/academic-years">Add one</a> first.</div>
  </div>
</div></div>
<?php else: ?>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <?php if($tab==='student'): ?>
      <label style="color:var(--text-muted);font-size:13px">Academic Year:</label>
      <select name="academic_year_id" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
        <?php foreach($academicYears as $y): ?>
          <option value="<?= $y['id'] ?>" <?= (string)$academicYearId===(string)$y['id']?'selected':'' ?>><?= htmlspecialchars($y['name']) ?><?= $y['is_current']?' (Current)':'' ?></option>
        <?php endforeach; ?>
      </select>
      <label style="color:var(--text-muted);font-size:13px">Class:</label>
      <select name="class_id" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
        <option value="">All Classes</option>
        <?php foreach($classes as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (string)$classId===(string)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>
    <label style="color:var(--text-muted);font-size:13px">Type:</label>
    <select name="type_id" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
      <?php foreach($certificateTypes as $t): ?>
        <option value="<?= $t['id'] ?>" <?= (string)$typeId===(string)$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <a href="<?= $cfg['url'] ?>/school/certificates?tab=<?= htmlspecialchars($tab) ?>" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Eligible <?= $tab==='student'?'Students':'People' ?></div><div class="stat-value"><?= (int)$stats['total'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--success);"><div class="stat-label">Issued</div><div class="stat-value"><?= (int)$stats['issued'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--warning);"><div class="stat-label">Pending</div><div class="stat-value"><?= (int)$stats['pending'] ?></div></div>
</div>

<?php if($tab==='student'): ?>
<div class="card">
  <div class="card-header">
    <div class="card-title"><?= htmlspecialchars($selectedYear['name'] ?? '') ?> — Students (<?= count($students) ?>)</div>
    <button type="button" id="issueSelectedBtn" class="btn btn-primary btn-sm" disabled onclick="submitIssueSelected()">Issue to Selected (<span id="selectedCount">0</span>)</button>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th style="width:36px;"><input type="checkbox" id="selectAllStudents"></th><th>Student</th><th>Admission No</th><th>Class</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($students as $s): ?>
        <tr>
          <td><?php if(!$s['certificate_id']): ?><input type="checkbox" class="student-select" value="<?= $s['student_id'] ?>" onchange="updateSelectedCount()"><?php endif; ?></td>
          <td class="fw-600"><?= htmlspecialchars($s['student_name']) ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($s['admission_no']) ?></td>
          <td><?= htmlspecialchars($s['class_name'] ?? '—') ?></td>
          <td>
            <?php if($s['certificate_id']): ?>
              <span class="badge badge-success">Issued</span>
              <div style="font-size:11px;color:var(--text-muted);margin-top:4px;font-family:monospace;"><?= htmlspecialchars($s['certificate_no']) ?></div>
            <?php else: ?>
              <span class="badge badge-muted">Pending</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <?php if($s['certificate_id']): ?>
                <a href="<?= $cfg['url'] ?>/school/certificates/<?= $s['certificate_id'] ?>/print" target="_blank" class="btn btn-sm btn-outline">Print</a>
                <form method="POST" action="<?= $cfg['url'] ?>/school/certificates/<?= $s['certificate_id'] ?>/delete" data-confirm="Revoke this certificate? This cannot be undone." data-confirm-title="Revoke Certificate" data-confirm-label="Revoke">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                </form>
              <?php else: ?>
                <button type="button" class="btn btn-sm btn-primary" onclick='openIssueCertModal(<?= json_encode([
                  "student_id" => $s['student_id'], "name" => $s['student_name'],
                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Issue Certificate</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($students)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🎓</div><div class="empty-state-text">No active or graduated students found for this filter.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bulk Generate Modal -->
<div class="modal-overlay" id="bulkModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Generate All Pending Certificates</div>
      <button class="modal-close" onclick="document.getElementById('bulkModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/certificates/bulk-generate">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="academic_year_id" value="<?= $academicYearId ?>">
      <input type="hidden" name="class_id" value="<?= htmlspecialchars($classId) ?>">
      <input type="hidden" name="certificate_type_id" value="<?= $typeId ?>">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-light);">This will issue a certificate for <?= $stats['pending'] ?> student(s) who don't already have one for <strong><?= htmlspecialchars($selectedYear['name'] ?? '') ?></strong><?= $classId ? ' in the selected class' : '' ?>. Already-issued students are skipped automatically.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate All</button>
      </div>
    </form>
  </div>
</div>

<!-- Issue Certificate Modal -->
<div class="modal-overlay" id="issueCertModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="issueCertModalTitle">Issue Certificate</div>
      <button class="modal-close" onclick="document.getElementById('issueCertModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/certificates/generate">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="student_id" id="issueCertStudentId">
      <input type="hidden" name="academic_year_id" value="<?= $academicYearId ?>">
      <input type="hidden" name="certificate_type_id" value="<?= $typeId ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Placement / Rank</label>
          <input type="text" name="placement" class="form-control" placeholder="e.g. 1st, 2nd Runner-up (leave blank if not applicable)">
        </div>
        <div class="form-group">
          <label class="form-label">Title Override</label>
          <input type="text" name="title" class="form-control" placeholder="Leave blank to use the certificate type's default title">
        </div>
        <div class="form-group">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control" rows="2" placeholder="Optional note printed on the certificate"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('issueCertModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Issue Certificate</button>
      </div>
    </form>
  </div>
</div>

<!-- Issue to Selected Students (hidden form, populated by JS before submit) -->
<form method="POST" action="<?= $cfg['url'] ?>/school/certificates/issue-selected" id="issueSelectedForm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="academic_year_id" value="<?= $academicYearId ?>">
  <input type="hidden" name="certificate_type_id" value="<?= $typeId ?>">
</form>

<script>
function openIssueCertModal(s) {
  document.getElementById('issueCertStudentId').value = s.student_id;
  document.getElementById('issueCertModalTitle').textContent = 'Issue Certificate — ' + s.name;
  document.getElementById('issueCertModal').classList.add('open');
}
function updateSelectedCount() {
  var checked = document.querySelectorAll('.student-select:checked');
  document.getElementById('selectedCount').textContent = checked.length;
  document.getElementById('issueSelectedBtn').disabled = checked.length === 0;
  var boxes = document.querySelectorAll('.student-select');
  document.getElementById('selectAllStudents').checked = boxes.length > 0 && checked.length === boxes.length;
}
document.getElementById('selectAllStudents').addEventListener('change', function(){
  document.querySelectorAll('.student-select').forEach(function(cb){ cb.checked = this.checked; }.bind(this));
  updateSelectedCount();
});
function submitIssueSelected() {
  var form = document.getElementById('issueSelectedForm');
  document.querySelectorAll('.student-select:checked').forEach(function(cb){
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'student_ids[]';
    input.value = cb.value;
    form.appendChild(input);
  });
  form.submit();
}
</script>

<?php else: /* --- STAFF TAB --- */ ?>
<div class="card">
  <div class="card-header">
    <div class="card-title">Teachers &amp; Staff (<?= count($staff) ?>)</div>
    <button type="button" id="issueStaffSelectedBtn" class="btn btn-primary btn-sm" disabled onclick="submitIssueStaffSelected()">Issue to Selected (<span id="selectedStaffCount">0</span>)</button>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th style="width:36px;"><input type="checkbox" id="selectAllStaff"></th><th>Name</th><th>Role</th><th>Employee No</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($staff as $s): ?>
        <tr>
          <td><?php if(!$s['certificate_id']): ?><input type="checkbox" class="staff-select" value="<?= $s['user_id'] ?>" onchange="updateSelectedStaffCount()"><?php endif; ?></td>
          <td class="fw-600"><?= htmlspecialchars($s['staff_name']) ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($s['role_name']) ?></span></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($s['employee_no'] ?? '—') ?></td>
          <td>
            <?php if($s['certificate_id']): ?>
              <span class="badge badge-success">Issued</span>
              <div style="font-size:11px;color:var(--text-muted);margin-top:4px;font-family:monospace;"><?= htmlspecialchars($s['certificate_no']) ?></div>
            <?php else: ?>
              <span class="badge badge-muted">Pending</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if($s['certificate_id']): ?>
              <div style="display:flex;gap:6px;">
                <a href="<?= $cfg['url'] ?>/school/certificates/<?= $s['certificate_id'] ?>/print" target="_blank" class="btn btn-sm btn-outline">Print</a>
                <form method="POST" action="<?= $cfg['url'] ?>/school/certificates/<?= $s['certificate_id'] ?>/delete" data-confirm="Revoke this certificate? This cannot be undone." data-confirm-title="Revoke Certificate" data-confirm-label="Revoke">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Revoke</button>
                </form>
              </div>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($staff)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🧑‍🏫</div><div class="empty-state-text">No teachers or staff found.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Issue to Selected Staff (hidden form, populated by JS before submit) -->
<form method="POST" action="<?= $cfg['url'] ?>/school/certificates/issue-staff-selected" id="issueStaffSelectedForm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="certificate_type_id" value="<?= $typeId ?>">
</form>

<script>
function updateSelectedStaffCount() {
  var checked = document.querySelectorAll('.staff-select:checked');
  document.getElementById('selectedStaffCount').textContent = checked.length;
  document.getElementById('issueStaffSelectedBtn').disabled = checked.length === 0;
  var boxes = document.querySelectorAll('.staff-select');
  document.getElementById('selectAllStaff').checked = boxes.length > 0 && checked.length === boxes.length;
}
document.getElementById('selectAllStaff').addEventListener('change', function(){
  document.querySelectorAll('.staff-select').forEach(function(cb){ cb.checked = this.checked; }.bind(this));
  updateSelectedStaffCount();
});
function submitIssueStaffSelected() {
  var form = document.getElementById('issueStaffSelectedForm');
  document.querySelectorAll('.staff-select:checked').forEach(function(cb){
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'user_ids[]';
    input.value = cb.value;
    form.appendChild(input);
  });
  form.submit();
}
</script>
<?php endif; ?>

<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
