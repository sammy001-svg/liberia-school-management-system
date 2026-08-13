<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/roles">Roles &amp; Permissions</a>
  <span>/</span><span><?= $roleRow ? 'Edit' : 'New' ?></span>
</div>
<div class="page-header">
  <div>
    <div class="page-header-title"><?= $roleRow ? 'Edit Role' : 'New Role' ?></div>
    <div class="page-header-sub">Choose which modules and actions this role can access</div>
  </div>
</div>

<?php if($roleRow && !empty($otherRoles)): ?>
<!-- Copy an existing role's access rather than re-ticking it by hand -->
<div class="card mb-16">
  <div class="card-header"><div class="card-title">Copy Permissions From Another Role</div></div>
  <form method="POST" action="<?= $cfg['url'] ?>/school/roles/<?= $roleRow['id'] ?>/copy-permissions">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="card-body">
      <p style="font-size:12.5px;color:var(--text-light);margin-bottom:14px;">
        Adds everything the chosen role can do to <strong><?= htmlspecialchars($roleRow['name']) ?></strong>.
        Nothing is removed — existing permissions are kept.
      </p>
      <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
        <select name="source_role_id" class="form-control" style="max-width:320px;" required>
          <option value="">— Select a role to copy from —</option>
          <?php foreach($otherRoles as $r): ?>
            <option value="<?= $r['id'] ?>">
              <?= htmlspecialchars($r['name']) ?><?= $r['tenant_id'] ? '' : ' (built-in)' ?>
              — <?= (int)$r['permission_count'] ?> permission<?= (int)$r['permission_count'] === 1 ? '' : 's' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary">Copy Permissions</button>
      </div>
      <div class="form-hint" style="margin-top:10px;">
        Anyone already signed in with this role must sign out and back in before the change reaches them.
      </div>
    </div>
  </form>
</div>
<?php endif; ?>

<form method="POST" action="<?= $cfg['url'] ?>/school/roles/<?= $roleRow ? $roleRow['id'].'/update' : 'store' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

  <div class="card">
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($roleRow['name'] ?? '') ?>" required placeholder="e.g. Librarian, Exam Officer">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($roleRow['description'] ?? '') ?>" placeholder="What is this role for?">
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">Permissions</div></div>
    <div class="card-body">
      <?php if(empty($permissionsByModule)): ?>
        <div class="empty-state">
          <div class="empty-state-icon">🔐</div>
          <div class="empty-state-text">
            No permissions are defined in this database yet, so there is nothing to grant.<br>
            Run <code>sql/add_role_permissions_data.sql</code> to load the permission catalogue,
            then reload this page.
          </div>
        </div>
      <?php endif; ?>
      <?php foreach($permissionsByModule as $module => $perms): ?>
        <div class="modal-section-title" style="text-transform:capitalize;">
          <?= htmlspecialchars(str_replace('_',' ',$module)) ?>
          <a href="javascript:void(0)" data-select-module="<?= htmlspecialchars($module) ?>" style="font-weight:400;font-size:11px;margin-left:10px;">select all</a>
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:16px;margin-bottom:14px;">
          <?php foreach($perms as $p): ?>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;">
              <input type="checkbox" class="perm-checkbox" data-module="<?= htmlspecialchars($module) ?>"
                     name="permissions[<?= htmlspecialchars($module) ?>][]" value="<?= htmlspecialchars($p['action']) ?>"
                     <?= !empty($checked[$module][$p['action']]) ? 'checked' : '' ?>>
              <?= htmlspecialchars(ucfirst($p['action'])) ?>
              <?php if(!empty($p['description'])): ?><span style="color:var(--text-muted);font-size:11px;">— <?= htmlspecialchars($p['description']) ?></span><?php endif; ?>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><?= $roleRow ? 'Save Changes' : 'Create Role' ?></button>
    <a href="<?= $cfg['url'] ?>/school/roles" class="btn btn-secondary">Cancel</a>
  </div>
</form>

<script>
document.querySelectorAll('[data-select-module]').forEach(function(link){
  link.addEventListener('click', function(){
    const module = this.getAttribute('data-select-module');
    const boxes = document.querySelectorAll('.perm-checkbox[data-module="' + module + '"]');
    const allChecked = Array.from(boxes).every(b => b.checked);
    boxes.forEach(b => b.checked = !allChecked);
  });
});
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
