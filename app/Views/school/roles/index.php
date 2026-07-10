<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Roles &amp; Permissions</div>
    <div class="page-header-sub">Create custom roles and control which modules each one can access</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/school/roles/users" class="btn btn-secondary">Assign Roles to Users</a>
    <a href="<?= $cfg['url'] ?>/school/roles/create" class="btn btn-primary">+ New Role</a>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">System Roles</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Description</th><th>Users at this school</th><th></th></tr></thead>
      <tbody>
        <?php foreach($systemRoles as $r): ?>
        <tr>
          <td><span class="badge badge-info"><?= htmlspecialchars($r['name']) ?></span></td>
          <td><?= htmlspecialchars($r['description'] ?? '—') ?></td>
          <td><?= (int)$r['user_count'] ?></td>
          <td><span style="font-size:11px;color:var(--text-muted);">Built-in — shared across all schools</span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-16">
  <div class="card-header"><div class="card-title">Custom Roles (<?= count($customRoles) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Description</th><th>Permissions</th><th>Users</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($customRoles as $r): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['description'] ?: '—') ?></td>
          <td><?= (int)$r['permission_count'] ?></td>
          <td><?= (int)$r['user_count'] ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/roles/<?= $r['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/roles/<?= $r['id'] ?>/delete" data-confirm="Delete the role '<?= htmlspecialchars($r['name']) ?>'? This cannot be undone." data-confirm-title="Delete Role" data-confirm-label="Delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($customRoles)): ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <div class="empty-state-icon">🛡️</div>
            <div class="empty-state-text">No custom roles yet. <a href="<?= $cfg['url'] ?>/school/roles/create">Create your first role</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
