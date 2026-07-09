<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Bus Routes</div>
    <div class="page-header-sub">Set up routes, assign a bus/driver, and set the monthly fee</div>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= $cfg['url'] ?>/school/transport/buses" class="btn btn-outline">Buses</a>
    <a href="<?= $cfg['url'] ?>/school/transport/drivers" class="btn btn-outline">Drivers</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addRouteModal').classList.add('open')">+ Add Route</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Total Routes</div><div class="stat-value"><?= (int)$stats['total'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--info);"><div class="stat-label">Students Riding</div><div class="stat-value"><?= (int)$stats['students'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--success);"><div class="stat-label">Monthly Revenue Potential</div><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['revenue'],0) ?></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Routes (<?= count($routes) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Route</th><th>Bus</th><th>Driver</th><th>Schedule</th><th>Monthly Fee</th><th>Students</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($routes as $r): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['bus_number'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['driver_name'] ?? '—') ?></td>
          <td style="font-size:12px;color:var(--text-muted)">
            <?= $r['departure_time'] ? date('h:i A', strtotime($r['departure_time'])) : '—' ?>
            <?= $r['return_time'] ? ' / '.date('h:i A', strtotime($r['return_time'])) : '' ?>
          </td>
          <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($r['monthly_fee'],2) ?></td>
          <td><span class="badge badge-info"><?= (int)$r['student_count'] ?></span></td>
          <td><span class="badge badge-<?= $r['status']==='active'?'success':'muted' ?>"><?= ucfirst($r['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= $cfg['url'] ?>/school/transport/routes/<?= $r['id'] ?>/students" class="btn btn-sm btn-secondary">Students</a>
              <button type="button" class="btn btn-sm btn-outline" onclick='openEditRouteModal(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/transport/routes/<?= $r['id'] ?>/delete" data-confirm="Remove route '<?= htmlspecialchars(addslashes($r['name'])) ?>'? This cannot be undone." data-confirm-title="Remove Route" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($routes)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">🗺️</div><div class="empty-state-text">No routes created yet. <a href="javascript:void(0)" onclick="document.getElementById('addRouteModal').classList.add('open')">Add the first one</a></div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Route Modal -->
<div class="modal-overlay" id="addRouteModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Bus Route</div>
      <button class="modal-close" onclick="document.getElementById('addRouteModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/transport/routes/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Route Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Sinkor - Congo Town">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bus</label>
            <select name="bus_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($buses as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['bus_number']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Driver</label>
            <select name="driver_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($drivers as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Stops</label>
          <textarea name="stops" class="form-control" rows="2" placeholder="e.g. Sinkor, Tubman Blvd, Congo Town (comma or line separated)"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Departure Time</label>
            <input type="time" name="departure_time" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Return Time</label>
            <input type="time" name="return_time" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Monthly Fee *</label>
            <input type="number" name="monthly_fee" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addRouteModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Route</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Route Modal -->
<div class="modal-overlay" id="editRouteModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Edit Route</div>
      <button class="modal-close" onclick="document.getElementById('editRouteModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editRouteForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Route Name *</label>
          <input type="text" name="name" id="editRouteName" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bus</label>
            <select name="bus_id" id="editRouteBus" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($buses as $b): ?><option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['bus_number']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Driver</label>
            <select name="driver_id" id="editRouteDriver" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($drivers as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Stops</label>
          <textarea name="stops" id="editRouteStops" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Departure Time</label>
            <input type="time" name="departure_time" id="editRouteDeparture" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Return Time</label>
            <input type="time" name="return_time" id="editRouteReturn" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Monthly Fee *</label>
            <input type="number" name="monthly_fee" id="editRouteFee" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" id="editRouteStatus" class="form-control">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editRouteModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditRouteModal(r) {
  document.getElementById('editRouteForm').action = '<?= $cfg['url'] ?>/school/transport/routes/' + r.id + '/update';
  document.getElementById('editRouteName').value = r.name || '';
  document.getElementById('editRouteBus').value = r.bus_id || '';
  document.getElementById('editRouteDriver').value = r.driver_id || '';
  document.getElementById('editRouteStops').value = r.stops || '';
  document.getElementById('editRouteDeparture').value = r.departure_time || '';
  document.getElementById('editRouteReturn').value = r.return_time || '';
  document.getElementById('editRouteFee').value = r.monthly_fee || 0;
  document.getElementById('editRouteStatus').value = r.status || 'active';
  document.getElementById('editRouteModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
