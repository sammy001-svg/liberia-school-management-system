<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Staff</div>
    <div class="page-header-sub">Non-teaching staff and accountants, with salary details used by Payroll</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addStaffModal').classList.add('open')">+ Add Staff</button>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Staff (<?= count($staff) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Role</th><th>Phone</th><th>Basic Salary</th><th>Allowances</th><th>Deductions</th></tr></thead>
      <tbody>
        <?php foreach($staff as $s): ?>
        <tr>
          <td><div class="fw-600"><?= htmlspecialchars($s['name']) ?></div><div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></div></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($s['role_name']) ?></span></td>
          <td><?= htmlspecialchars($s['phone']??'—') ?></td>
          <td><?= $s['basic_salary']!==null ? number_format($s['basic_salary'],2) : '—' ?></td>
          <td><?= $s['allowances']!==null ? number_format($s['allowances'],2) : '—' ?></td>
          <td><?= $s['deductions']!==null ? number_format($s['deductions'],2) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($staff)): ?><tr><td colspan="6" class="text-center text-muted" style="padding:40px">No staff accounts yet. <a href="javascript:void(0)" onclick="document.getElementById('addStaffModal').classList.add('open')">Add first staff member</a></td></tr><?php endif; ?>
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
        <div class="form-group">
          <label class="form-label">Role *</label>
          <select name="role" class="form-control" required>
            <option value="Staff">Staff</option>
            <option value="Accountant">Accountant</option>
          </select>
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

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
