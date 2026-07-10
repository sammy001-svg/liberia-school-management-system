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
      <thead><tr><th>Name</th><th>Amount</th><th>Frequency</th><th>Class</th><th>Students</th><th>Description</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($fees as $f): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($f['name']) ?></td>
          <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($f['amount'],2) ?></td>
          <td><span class="badge badge-muted" style="text-transform:capitalize;"><?= $f['frequency']==='termly' ? 'Per Period' : htmlspecialchars($f['frequency']) ?></span></td>
          <td><?= htmlspecialchars($f['class_name'] ?? 'All Classes') ?></td>
          <td><span class="badge badge-info"><?= (int)$f['student_count'] ?></span></td>
          <td class="text-muted" style="font-size:12px"><?= htmlspecialchars($f['description'] ?? '—') ?></td>
          <td>
            <button type="button" class="btn btn-sm btn-primary" <?= $f['student_count']==0 ? 'disabled title="No applicable students"' : '' ?> onclick='openGenerateFeeModal(<?= json_encode([
              "id"=>$f["id"], "name"=>$f["name"], "amount"=>$f["amount"], "frequency"=>$f["frequency"],
              "class_name"=>$f["class_name"] ?? "All Classes", "student_count"=>$f["student_count"],
            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Generate Invoices</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($fees)): ?>
        <tr><td colspan="7">
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
              <option value="termly" selected>Per Period</option>
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

<!-- Generate Invoices Modal -->
<div class="modal-overlay" id="generateFeeModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="generateFeeModalTitle">Generate Invoices</div>
      <button class="modal-close" onclick="document.getElementById('generateFeeModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="generateFeeForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;" id="generateFeeSummary"></p>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Billing Period *</label>
            <input type="text" name="period" id="generateFeePeriod" class="form-control" required placeholder="e.g. Period 1 2026">
          </div>
          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
          </div>
        </div>
        <div class="form-hint">One invoice is created per applicable student. Students already billed for this exact period are automatically skipped — safe to re-run.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('generateFeeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate</button>
      </div>
    </form>
  </div>
</div>

<script>
function openGenerateFeeModal(f) {
  document.getElementById('generateFeeForm').action = '<?= $cfg['url'] ?>/school/finance/fees/' + f.id + '/generate';
  document.getElementById('generateFeeModalTitle').textContent = 'Generate Invoices — ' + f.name;
  document.getElementById('generateFeeSummary').textContent = 'This will bill ' + f.student_count + ' student(s) in ' + f.class_name + ' <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>' + Number(f.amount).toFixed(2) + ' each.';
  var periodInput = document.getElementById('generateFeePeriod');
  var now = new Date();
  var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  if (f.frequency === 'once') {
    periodInput.value = 'One-Time';
  } else if (f.frequency === 'monthly') {
    periodInput.value = monthNames[now.getMonth()] + ' ' + now.getFullYear();
  } else if (f.frequency === 'yearly') {
    periodInput.value = '';
    periodInput.placeholder = 'e.g. ' + now.getFullYear() + '/' + (now.getFullYear()+1);
  } else {
    periodInput.value = '';
    periodInput.placeholder = 'e.g. Period 1 ' + now.getFullYear();
  }
  document.getElementById('generateFeeModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
