<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/certificates">Certificates</a>
  <span>/</span><span>Manage Types</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Certificate Types</div>
    <div class="page-header-sub">Create and manage the certificate types available for issuance</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addTypeModal').classList.add('open')">+ Add Type</button>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Types (<?= count($types) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Recipient Category</th><th>Description</th><th>Issued</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($types as $t): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($t['name']) ?></td>
          <td>
            <?php $catLabel = ['student'=>'Students Only','staff'=>'Teachers/Staff Only','any'=>'Any Recipient'][$t['recipient_category']] ?? ucfirst($t['recipient_category']); ?>
            <span class="badge badge-info"><?= htmlspecialchars($catLabel) ?></span>
          </td>
          <td><?= htmlspecialchars($t['description'] ?: '—') ?></td>
          <td><?= (int)$t['usage_count'] ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openEditTypeModal(<?= json_encode([
                "id" => $t['id'], "name" => $t['name'], "recipient_category" => $t['recipient_category'], "description" => $t['description'],
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <?php if((int)$t['usage_count'] === 0): ?>
                <form method="POST" action="<?= $cfg['url'] ?>/school/certificates/types/<?= $t['id'] ?>/delete" data-confirm="Delete the certificate type '<?= htmlspecialchars($t['name']) ?>'?" data-confirm-title="Delete Type" data-confirm-label="Delete">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($types)): ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <div class="empty-state-icon">🎖️</div>
            <div class="empty-state-text">No certificate types yet. <a href="javascript:void(0)" onclick="document.getElementById('addTypeModal').classList.add('open')">Add the first one</a>.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Type Modal -->
<div class="modal-overlay" id="addTypeModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Certificate Type</div>
      <button class="modal-close" onclick="document.getElementById('addTypeModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/certificates/types/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Type Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Certificate of Service">
        </div>
        <div class="form-group">
          <label class="form-label">Who can this be issued to? *</label>
          <select name="recipient_category" class="form-control" required>
            <option value="student">Students Only</option>
            <option value="staff">Teachers / Staff Only</option>
            <option value="any">Any Recipient</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Optional notes about when this type is used"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTypeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Type</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Type Modal -->
<div class="modal-overlay" id="editTypeModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Certificate Type</div>
      <button class="modal-close" onclick="document.getElementById('editTypeModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editTypeForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Type Name *</label>
          <input type="text" name="name" id="editTypeName" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Who can this be issued to? *</label>
          <select name="recipient_category" id="editTypeCategory" class="form-control" required>
            <option value="student">Students Only</option>
            <option value="staff">Teachers / Staff Only</option>
            <option value="any">Any Recipient</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="editTypeDescription" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editTypeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditTypeModal(t) {
  document.getElementById('editTypeForm').action = '<?= $cfg['url'] ?>/school/certificates/types/' + t.id + '/update';
  document.getElementById('editTypeName').value = t.name || '';
  document.getElementById('editTypeCategory').value = t.recipient_category || 'any';
  document.getElementById('editTypeDescription').value = t.description || '';
  document.getElementById('editTypeModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
