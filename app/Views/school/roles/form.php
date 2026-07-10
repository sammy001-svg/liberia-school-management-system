<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/roles">Roles &amp; Permissions</a>
  <span>/</span><span><?= $role ? 'Edit' : 'New' ?></span>
</div>
<div class="page-header">
  <div>
    <div class="page-header-title"><?= $role ? 'Edit Role' : 'New Role' ?></div>
    <div class="page-header-sub">Choose which modules and actions this role can access</div>
  </div>
</div>

<form method="POST" action="<?= $cfg['url'] ?>/school/roles/<?= $role ? $role['id'].'/update' : 'store' ?>">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

  <div class="card">
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Role Name *</label>
          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($role['name'] ?? '') ?>" required placeholder="e.g. Librarian, Exam Officer">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($role['description'] ?? '') ?>" placeholder="What is this role for?">
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">Permissions</div></div>
    <div class="card-body">
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
    <button type="submit" class="btn btn-primary"><?= $role ? 'Save Changes' : 'Create Role' ?></button>
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
