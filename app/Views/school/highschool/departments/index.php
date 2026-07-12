<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Departments</div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addDepartmentModal').classList.add('open')">+ Add Department</button>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach($departments as $d): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['code']??'—') ?></td>
                    <td><?= htmlspecialchars($d['head_name'] ?? 'Not Assigned') ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($d['description']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($departments)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted" style="padding:40px;">No departments found. <a href="javascript:void(0)" onclick="document.getElementById('addDepartmentModal').classList.add('open')">Add one</a></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal-overlay" id="addDepartmentModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Department</div>
      <button class="modal-close" onclick="document.getElementById('addDepartmentModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/departments/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Computer Science">
          </div>
          <div class="form-group">
            <label class="form-label">Department Code</label>
            <input type="text" name="code" class="form-control" placeholder="e.g. CS">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Head of Department</label>
          <select name="head_user_id" class="form-control">
            <option value="">— Select Head —</option>
            <?php foreach($staff as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addDepartmentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Department</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
