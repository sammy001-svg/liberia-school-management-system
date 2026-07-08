<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Parents</div>
    <div class="page-header-sub">Manage parent/guardian accounts and student links</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addParentModal').classList.add('open')">+ Add Parent</button>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Parents (<?= $total ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Parent</th><th>Phone</th><th>Occupation</th></tr></thead>
      <tbody>
        <?php foreach($parents as $p): ?>
        <tr>
          <td><div class="fw-600"><?= htmlspecialchars($p['name']) ?></div><div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($p['email']) ?></div></td>
          <td><?= htmlspecialchars($p['phone']??'—') ?></td>
          <td><?= htmlspecialchars($p['occupation']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($parents)): ?><tr><td colspan="3" class="text-center text-muted" style="padding:32px">No parents registered yet. <a href="javascript:void(0)" onclick="document.getElementById('addParentModal').classList.add('open')">Add first parent</a></td></tr><?php endif; ?>
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
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" required>
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
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addParentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Parent</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
