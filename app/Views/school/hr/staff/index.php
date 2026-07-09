<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Staff</div>
    <div class="page-header-sub">Non-teaching staff and accountants, with salary details used by Payroll</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addStaffModal').classList.add('open')">+ Add Staff</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Staff</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Monthly Payroll Cost</div>
    <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['monthlyCost'] ?? 0,0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Missing Salary Info</div>
    <div class="stat-value"><?= (int)($stats['noSalary'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Staff (<?= count($staff) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Staff No</th><th>Name</th><th>Role / Position</th><th>Phone</th><th>Basic Salary</th><th>Allowances</th><th>Deductions</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($staff as $s): ?>
        <?php $isTeacher = !empty($s['teacher_id']); ?>
        <tr>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($s['staff_no'] ?? '—') ?></td>
          <td><div style="display:flex;align-items:center;gap:10px;"><div class="avatar"><?= strtoupper(substr($s['name'],0,1)) ?></div><div><div class="fw-600"><?= htmlspecialchars($s['name']) ?></div><div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($s['email'] ?? '—') ?></div></div></div></td>
          <td>
            <span class="badge badge-info"><?= htmlspecialchars($s['role_name']) ?></span>
            <?php if(!empty($s['position'])): ?><div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= htmlspecialchars($s['position']) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($s['phone']??'—') ?></td>
          <td><?= $s['basic_salary']!==null ? number_format($s['basic_salary'],2) : '—' ?></td>
          <td class="text-success"><?= $s['allowances']!==null ? '+'.number_format($s['allowances'],2) : '—' ?></td>
          <td class="text-danger"><?= $s['deductions']!==null ? '-'.number_format($s['deductions'],2) : '—' ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openEditSalaryModal(<?= json_encode([
                "id" => $s['id'], "name" => $s['name'], "email" => $s['email'], "phone" => $s['phone'],
                "gender" => $s['gender'], "employee_no" => $s['employee_no'], "position" => $s['position'],
                "basic_salary" => $s['basic_salary'], "allowances" => $s['allowances'], "deductions" => $s['deductions'],
                "effective_from" => $s['effective_from'],
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <?php if(!$isTeacher): ?>
                <form method="POST" action="<?= $cfg['url'] ?>/school/staff/<?= $s['id'] ?>/delete" data-confirm="Remove <?= htmlspecialchars($s['name']) ?>? This cannot be undone." data-confirm-title="Remove Staff" data-confirm-label="Remove">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Del</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($staff)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🧑‍💼</div>
            <div class="empty-state-text">No staff accounts yet. <a href="javascript:void(0)" onclick="document.getElementById('addStaffModal').classList.add('open')">Add the first staff member</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Staff Modal -->
<div class="modal-overlay" id="addStaffModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Staff Member</div>
      <button class="modal-close" onclick="document.getElementById('addStaffModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/staff/store">
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
            <label class="form-label">Role *</label>
            <select name="role" class="form-control" required>
              <option value="Staff">Staff</option>
              <option value="Accountant">Accountant</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Position / Title</label>
            <input type="text" name="position" class="form-control" placeholder="e.g. Principal, Business Manager">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Staff / Employee No</label>
          <input type="text" name="employee_no" class="form-control" placeholder="e.g. CAF0001">
        </div>

        <div class="modal-section-title">Salary Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Basic Salary *</label>
            <input type="number" name="basic_salary" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Effective From</label>
            <input type="date" name="effective_from" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Allowances</label>
            <input type="number" name="allowances" class="form-control" step="0.01" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Deductions</label>
            <input type="number" name="deductions" class="form-control" step="0.01" value="0">
          </div>
        </div>

        <div class="modal-section-title">Account</div>
        <div class="form-group">
          <label class="form-label">Login Password</label>
          <input type="password" name="password" class="form-control" placeholder="Default: Staff@123">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addStaffModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Staff</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Staff / Salary Modal -->
<div class="modal-overlay" id="editStaffModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="editStaffModalTitle">Edit Staff</div>
      <button class="modal-close" onclick="document.getElementById('editStaffModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editStaffForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="name" id="editStaffName">
      <input type="hidden" name="email" id="editStaffEmail">
      <input type="hidden" name="phone" id="editStaffPhone">
      <input type="hidden" name="gender" id="editStaffGender">
      <input type="hidden" name="employee_no" id="editStaffEmployeeNo">
      <input type="hidden" name="position" id="editStaffPosition">
      <div class="modal-body">
        <div class="modal-section-title">Salary Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Basic Salary *</label>
            <input type="number" name="basic_salary" id="editStaffBasicSalary" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Effective From</label>
            <input type="date" name="effective_from" id="editStaffEffectiveFrom" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Allowances</label>
            <input type="number" name="allowances" id="editStaffAllowances" class="form-control" step="0.01" value="0">
          </div>
          <div class="form-group">
            <label class="form-label">Deductions</label>
            <input type="number" name="deductions" id="editStaffDeductions" class="form-control" step="0.01" value="0">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editStaffModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Salary</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditSalaryModal(s) {
  document.getElementById('editStaffForm').action = '<?= $cfg['url'] ?>/school/staff/' + s.id + '/update';
  document.getElementById('editStaffModalTitle').textContent = 'Edit Salary — ' + s.name;
  document.getElementById('editStaffName').value = s.name || '';
  document.getElementById('editStaffEmail').value = s.email || '';
  document.getElementById('editStaffPhone').value = s.phone || '';
  document.getElementById('editStaffGender').value = s.gender || '';
  document.getElementById('editStaffEmployeeNo').value = s.employee_no || '';
  document.getElementById('editStaffPosition').value = s.position || '';
  document.getElementById('editStaffBasicSalary').value = s.basic_salary || '';
  document.getElementById('editStaffAllowances').value = s.allowances || 0;
  document.getElementById('editStaffDeductions').value = s.deductions || 0;
  document.getElementById('editStaffEffectiveFrom').value = s.effective_from || '<?= date('Y-m-d') ?>';
  document.getElementById('editStaffModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
