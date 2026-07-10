<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/roles">Roles &amp; Permissions</a>
  <span>/</span><span>Assign Roles</span>
</div>
<div class="page-header">
  <div>
    <div class="page-header-title">Assign Roles to Users</div>
    <div class="page-header-sub">School Admin, Accountant, Staff and custom-role accounts. Teacher, Student and Parent accounts keep their own onboarding flow and aren't reassigned here.</div>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Current Role</th><th>Change Role</th></tr></thead>
      <tbody>
        <?php foreach($users as $u): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($u['name']) ?></td>
          <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($u['role_name']) ?></span></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/roles/assign-user" style="display:flex;gap:8px;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <select name="role_id" class="form-control" style="max-width:220px;">
                <?php foreach($assignableRoles as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= $r['id']==$u['role_id']?'selected':'' ?>><?= htmlspecialchars($r['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-secondary">Save</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($users)): ?>
        <tr><td colspan="4">
          <div class="empty-state">
            <div class="empty-state-icon">🧑‍💼</div>
            <div class="empty-state-text">No assignable accounts found. Staff accounts are created from the <a href="<?= $cfg['url'] ?>/school/staff">Staff</a> page.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
