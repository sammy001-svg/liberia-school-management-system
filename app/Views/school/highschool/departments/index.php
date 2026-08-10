<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Departments</div>
    <button type="button" class="btn btn-primary" onclick="openDeptModal()">+ Add Department</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                    <th>Head of Department</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($departments as $d): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['code']??'—') ?></td>
                    <td><?= htmlspecialchars($d['head_name'] ?? 'Not Assigned') ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($d['description'] ?? '') ?></td>
                    <td>
                      <div style="display:flex;gap:6px;">
                        <button type="button" class="btn btn-sm btn-secondary"
                                onclick='openDeptModal(<?= json_encode($d, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
                        <form method="POST" action="<?= $cfg['url'] ?>/school/departments/<?= $d['id'] ?>/delete"
                              data-confirm="Delete the department '<?= htmlspecialchars($d['name']) ?>'? Teachers filed under it keep their records but lose the department."
                              data-confirm-title="Delete Department" data-confirm-label="Delete">
                          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                          <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                      </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($departments)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted" style="padding:40px;">No departments found. <a href="javascript:void(0)" onclick="openDeptModal()">Add one</a></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / edit department -->
<div class="modal-overlay" id="addDepartmentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="deptModalTitle">Add Department</div>
      <button class="modal-close" onclick="document.getElementById('addDepartmentModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="deptForm" action="<?= $cfg['url'] ?>/school/departments/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department Name *</label>
            <input type="text" name="name" id="d_name" class="form-control" required placeholder="e.g. Computer Science">
          </div>
          <div class="form-group">
            <label class="form-label">Department Code</label>
            <input type="text" name="code" id="d_code" class="form-control" placeholder="e.g. CS">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Head of Department</label>
          <select name="head_user_id" id="d_head" class="form-control">
            <option value="">— Select Head —</option>
            <?php foreach($staff as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="d_description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addDepartmentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Department</button>
      </div>
    </form>
  </div>
</div>

<script>
const DEPT_BASE = '<?= $cfg['url'] ?>/school/departments';
function openDeptModal(dept) {
  const form = document.getElementById('deptForm');
  if (dept) {
    document.getElementById('deptModalTitle').textContent = 'Edit Department';
    form.action = DEPT_BASE + '/' + dept.id + '/update';
    document.getElementById('d_name').value = dept.name || '';
    document.getElementById('d_code').value = dept.code || '';
    document.getElementById('d_description').value = dept.description || '';
    document.getElementById('d_head').value = dept.head_user_id || '';
  } else {
    document.getElementById('deptModalTitle').textContent = 'Add Department';
    form.action = DEPT_BASE + '/store';
    form.reset();
  }
  document.getElementById('addDepartmentModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
