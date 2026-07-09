<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Expenses</div>
    <div class="page-header-sub">Track school operational expenditure</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addExpenseModal').classList.add('open')">+ Record Expense</button>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Total Expenses</div>
    <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['total'] ?? 0,2) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">This Month</div>
    <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['thisMonth'] ?? 0,2) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Records</div>
    <div class="stat-value"><?= (int)($stats['count'] ?? 0) ?></div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <label style="color:var(--text-muted);font-size:13px">Category:</label>
    <select name="category" class="form-control" style="max-width:200px;">
      <option value="">All Categories</option>
      <?php foreach($categories as $c): ?>
        <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category===$c['category']?'selected':'' ?>><?= htmlspecialchars($c['category']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/finance/expenses" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">All Expenses (<?= $total ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Payee</th><th>Method</th><th>Amount</th><th>Recorded By</th><th></th></tr></thead>
      <tbody>
        <?php foreach($expenses as $e): ?>
        <tr>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
          <td><span class="badge badge-muted"><?= htmlspecialchars($e['category']) ?></span></td>
          <td><?= htmlspecialchars($e['description'] ?: '—') ?></td>
          <td><?= htmlspecialchars($e['payee'] ?: '—') ?></td>
          <td><span class="badge badge-info"><?= strtoupper($e['method']) ?></span></td>
          <td class="text-danger fw-600"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($e['amount'],2) ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($e['recorded_by_name'] ?? '—') ?></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/finance/expenses/<?= $e['id'] ?>/delete" data-confirm="Delete this expense record?" data-confirm-label="Delete">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-outline">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($expenses)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🧾</div>
            <div class="empty-state-text">No expenses recorded yet. <a href="javascript:void(0)" onclick="document.getElementById('addExpenseModal').classList.add('open')">Record the first one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<!-- Record Expense Modal -->
<div class="modal-overlay" id="addExpenseModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Record Expense</div>
      <button class="modal-close" onclick="document.getElementById('addExpenseModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/expenses/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Expense Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category *</label>
            <input type="text" name="category" class="form-control" list="categoryOptions" required placeholder="e.g. Utilities">
            <datalist id="categoryOptions">
              <?php foreach(['Utilities','Salaries','Maintenance','Supplies','Transport','Marketing','Rent','Equipment','Other'] as $preset): ?>
                <option value="<?= $preset ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="form-group">
            <label class="form-label">Amount (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>) *</label>
            <input type="number" name="amount" class="form-control" step="0.01" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Expense Date *</label>
            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Payee / Vendor</label>
            <input type="text" name="payee" class="form-control" placeholder="Who was paid">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Optional notes about this expense"></textarea>
        </div>

        <div class="modal-section-title">Payment</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Method</label>
            <select name="method" class="form-control">
              <option value="cash">Cash</option>
              <option value="mpesa">M-Pesa</option>
              <option value="bank">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="online">Online</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Reference No.</label>
            <input type="text" name="reference" class="form-control" placeholder="Receipt / transaction reference">
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addExpenseModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Expense</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
