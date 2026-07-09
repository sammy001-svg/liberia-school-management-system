<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Buses</div>
    <div class="page-header-sub">Manage the school bus fleet</div>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= $cfg['url'] ?>/school/transport/drivers" class="btn btn-outline">Drivers</a>
    <a href="<?= $cfg['url'] ?>/school/transport/routes" class="btn btn-outline">Routes</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addBusModal').classList.add('open')">+ Add Bus</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Total Buses</div><div class="stat-value"><?= (int)$stats['total'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--success);"><div class="stat-label">Active</div><div class="stat-value"><?= (int)$stats['active'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--warning);"><div class="stat-label">In Maintenance</div><div class="stat-value"><?= (int)$stats['maintenance'] ?></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Buses (<?= count($buses) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Bus No.</th><th>Plate No.</th><th>Model</th><th>Capacity</th><th>Routes</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($buses as $b): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($b['bus_number']) ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($b['plate_number'] ?? '—') ?></td>
          <td><?= htmlspecialchars($b['model'] ?? '—') ?></td>
          <td><?= (int)$b['capacity'] ?> seats</td>
          <td><?= (int)$b['route_count'] ?></td>
          <td><span class="badge badge-<?= $b['status']==='active'?'success':($b['status']==='maintenance'?'warning':'muted') ?>"><?= ucfirst($b['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openEditBusModal(<?= json_encode($b, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/transport/buses/<?= $b['id'] ?>/delete" data-confirm="Remove bus '<?= htmlspecialchars(addslashes($b['bus_number'])) ?>'? This cannot be undone." data-confirm-title="Remove Bus" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($buses)): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">🚌</div><div class="empty-state-text">No buses added yet. <a href="javascript:void(0)" onclick="document.getElementById('addBusModal').classList.add('open')">Add the first one</a></div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Bus Modal -->
<div class="modal-overlay" id="addBusModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Bus</div>
      <button class="modal-close" onclick="document.getElementById('addBusModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/transport/buses/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bus Number *</label>
            <input type="text" name="bus_number" class="form-control" required placeholder="e.g. BUS-01">
          </div>
          <div class="form-group">
            <label class="form-label">Plate Number</label>
            <input type="text" name="plate_number" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" placeholder="e.g. Toyota Coaster">
          </div>
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" class="form-control" value="40">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="active">Active</option>
            <option value="maintenance">In Maintenance</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addBusModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Bus</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Bus Modal -->
<div class="modal-overlay" id="editBusModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Edit Bus</div>
      <button class="modal-close" onclick="document.getElementById('editBusModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editBusForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bus Number *</label>
            <input type="text" name="bus_number" id="editBusNumber" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Plate Number</label>
            <input type="text" name="plate_number" id="editBusPlate" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Model</label>
            <input type="text" name="model" id="editBusModel" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input type="number" name="capacity" id="editBusCapacity" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="editBusStatus" class="form-control">
            <option value="active">Active</option>
            <option value="maintenance">In Maintenance</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editBusModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditBusModal(b) {
  document.getElementById('editBusForm').action = '<?= $cfg['url'] ?>/school/transport/buses/' + b.id + '/update';
  document.getElementById('editBusNumber').value = b.bus_number || '';
  document.getElementById('editBusPlate').value = b.plate_number || '';
  document.getElementById('editBusModel').value = b.model || '';
  document.getElementById('editBusCapacity').value = b.capacity || 40;
  document.getElementById('editBusStatus').value = b.status || 'active';
  document.getElementById('editBusModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
