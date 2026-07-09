<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Drivers</div>
    <div class="page-header-sub">Manage school bus drivers</div>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= $cfg['url'] ?>/school/transport/buses" class="btn btn-outline">Buses</a>
    <a href="<?= $cfg['url'] ?>/school/transport/routes" class="btn btn-outline">Routes</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addDriverModal').classList.add('open')">+ Add Driver</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Total Drivers</div><div class="stat-value"><?= (int)$stats['total'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--success);"><div class="stat-label">Active</div><div class="stat-value"><?= (int)$stats['active'] ?></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Drivers (<?= count($drivers) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Phone</th><th>License No.</th><th>Routes</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($drivers as $d): ?>
        <tr>
          <td><div style="display:flex;align-items:center;gap:10px;"><div class="avatar"><?= strtoupper(substr($d['name'],0,1)) ?></div><span class="fw-600"><?= htmlspecialchars($d['name']) ?></span></div></td>
          <td><?= htmlspecialchars($d['phone'] ?? '—') ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($d['license_no'] ?? '—') ?></td>
          <td><?= (int)$d['route_count'] ?></td>
          <td><span class="badge badge-<?= $d['status']==='active'?'success':'muted' ?>"><?= ucfirst($d['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openEditDriverModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/transport/drivers/<?= $d['id'] ?>/delete" data-confirm="Remove '<?= htmlspecialchars(addslashes($d['name'])) ?>'? This cannot be undone." data-confirm-title="Remove Driver" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($drivers)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🧑‍✈️</div><div class="empty-state-text">No drivers added yet. <a href="javascript:void(0)" onclick="document.getElementById('addDriverModal').classList.add('open')">Add the first one</a></div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Driver Modal -->
<div class="modal-overlay" id="addDriverModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Driver</div>
      <button class="modal-close" onclick="document.getElementById('addDriverModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/transport/drivers/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">License No.</label>
            <input type="text" name="license_no" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addDriverModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Driver</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Driver Modal -->
<div class="modal-overlay" id="editDriverModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Driver</div>
      <button class="modal-close" onclick="document.getElementById('editDriverModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editDriverForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" id="editDriverName" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" id="editDriverPhone" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">License No.</label>
            <input type="text" name="license_no" id="editDriverLicense" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Address</label>
          <input type="text" name="address" id="editDriverAddress" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="editDriverStatus" class="form-control">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editDriverModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditDriverModal(d) {
  document.getElementById('editDriverForm').action = '<?= $cfg['url'] ?>/school/transport/drivers/' + d.id + '/update';
  document.getElementById('editDriverName').value = d.name || '';
  document.getElementById('editDriverPhone').value = d.phone || '';
  document.getElementById('editDriverLicense').value = d.license_no || '';
  document.getElementById('editDriverAddress').value = d.address || '';
  document.getElementById('editDriverStatus').value = d.status || 'active';
  document.getElementById('editDriverModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
