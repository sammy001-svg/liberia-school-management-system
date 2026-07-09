<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Fee Structures</div>
    <div class="page-header-sub">Define fee types used when generating invoices</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addFeeModal').classList.add('open')">+ Add Fee Structure</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Fee Structures</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Class-Specific</div>
    <div class="stat-value"><?= (int)($stats['classSpecific'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">School-Wide</div>
    <div class="stat-value"><?= (int)($stats['schoolWide'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Fee Structures (<?= count($fees) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Amount</th><th>Frequency</th><th>Class</th><th>Description</th></tr></thead>
      <tbody>
        <?php foreach($fees as $f): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($f['name']) ?></td>
          <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($f['amount'],2) ?></td>
          <td><span class="badge badge-muted" style="text-transform:capitalize;"><?= htmlspecialchars($f['frequency']) ?></span></td>
          <td><?= htmlspecialchars($f['class_name'] ?? 'All Classes') ?></td>
          <td class="text-muted" style="font-size:12px"><?= htmlspecialchars($f['description'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($fees)): ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <div class="empty-state-text">No fee structures yet. <a href="javascript:void(0)" onclick="document.getElementById('addFeeModal').classList.add('open')">Add one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Fee Structure Modal -->
<div class="modal-overlay" id="addFeeModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Fee Structure</div>
      <button class="modal-close" onclick="document.getElementById('addFeeModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/fees/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Fee Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Tuition Fee">
          </div>
          <div class="form-group">
            <label class="form-label">Amount (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>) *</label>
            <input type="number" name="amount" class="form-control" step="0.01" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Frequency</label>
            <select name="frequency" class="form-control">
              <option value="once">Once</option>
              <option value="monthly">Monthly</option>
              <option value="termly" selected>Termly</option>
              <option value="yearly">Yearly</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Class (optional)</label>
            <select name="class_id" class="form-control">
              <option value="">All Classes</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Academic Year (optional)</label>
          <select name="academic_year_id" class="form-control">
            <option value="">— Not Assigned —</option>
            <?php foreach($academicYears as $y): ?>
              <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addFeeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Fee Structure</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
