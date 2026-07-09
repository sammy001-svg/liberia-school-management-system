<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/transport/routes">Bus Routes</a><span>/</span><span><?= htmlspecialchars($route['name']) ?></span></div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($route['name']) ?></div>
    <div class="page-header-sub">
      <?= htmlspecialchars($route['bus_number'] ?? 'No bus assigned') ?> · <?= htmlspecialchars($route['driver_name'] ?? 'No driver assigned') ?>
      · <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($route['monthly_fee'],2) ?>/month
    </div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('assignModal').classList.add('open')">+ Assign Student</button>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Riders (<?= count($assigned) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Admission No</th><th>Class</th><th>Pickup Stop</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($assigned as $a): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($a['student_name']) ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($a['admission_no']) ?></td>
          <td><?= htmlspecialchars($a['class_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($a['pickup_stop'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $a['status']==='active'?'success':'muted' ?>"><?= ucfirst($a['status']) ?></span></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/transport/routes/<?= $route['id'] ?>/students/<?= $a['student_id'] ?>/unassign" data-confirm="Remove <?= htmlspecialchars(addslashes($a['student_name'])) ?> from this route?" data-confirm-title="Unassign Student" data-confirm-label="Remove">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Remove</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($assigned)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🧒</div><div class="empty-state-text">No students assigned to this route yet.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Assign Student Modal -->
<div class="modal-overlay" id="assignModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Assign Student to Route</div>
      <button class="modal-close" onclick="document.getElementById('assignModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/transport/routes/<?= $route['id'] ?>/students/assign">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Student *</label>
          <select name="student_id" class="form-control" required>
            <option value="">— Select Student —</option>
            <?php foreach($availableStudents as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
          </select>
          <div class="form-hint">Only students not already on a bus route are shown. A student can only be on one route at a time.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Pickup Stop</label>
          <input type="text" name="pickup_stop" class="form-control" placeholder="e.g. Tubman Blvd">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('assignModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
