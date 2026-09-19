<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$statusBadge = ['active' => 'badge-success', 'pending' => 'badge-warning', 'cancelled' => 'badge-muted', 'rejected' => 'badge-danger'];
$qs = http_build_query(array_filter($filters, 'strlen'));
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Expenses</div>
    <div class="page-header-sub">Money the school pays out. Cancel an expense instead of deleting it — cancelled entries stay on record but don’t count.</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php if ($finSettings['expense_approval'] === '1' || $pendingCount): ?>
      <a href="<?= $base ?>/expense-approvals" class="btn <?= $pendingCount ? 'btn-warning' : 'btn-outline' ?>">✔ Expense Approvals<?= $pendingCount ? " ({$pendingCount})" : '' ?></a>
    <?php endif; ?>
    <a href="<?= $base ?>/expenses/export?<?= $qs ?>" class="btn btn-outline">⬇ Export CSV</a>
    <button type="button" class="btn btn-primary" onclick="openExpense()">＋ Add Expense</button>
  </div>
</div>

<div class="stat-grid">
  <?php foreach ($sums ?: [['cur' => $def, 't' => 0, 'n' => 0]] as $s): ?>
    <div class="stat-card" style="--card-color:var(--danger);"><div class="stat-value" style="font-size:22px;"><?= Finance::money($s['t'], $s['cur']) ?></div><div class="stat-label"><?= (int)$s['n'] ?> active expense(s) <?= $qs ? 'matching filters' : 'in total' ?></div></div>
  <?php endforeach; ?>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:16px;">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" class="form-control" style="max-width:200px;" placeholder="Payee, description, ref" value="<?= htmlspecialchars($filters['q']) ?>">
    <select name="category" class="form-control" style="max-width:200px;"><option value="">All Categories</option><?php foreach ($categories as $c): ?><option <?= $filters['category'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?></select>
    <select name="method" class="form-control" style="max-width:150px;"><option value="">All Methods</option><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>" <?= $filters['method'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <select name="status" class="form-control" style="max-width:150px;"><option value="">All Statuses</option><?php foreach (['active' => 'Active', 'pending' => 'Pending', 'cancelled' => 'Cancelled', 'rejected' => 'Rejected'] as $k => $l): ?><option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <input type="date" name="from" class="form-control" style="max-width:150px;" value="<?= htmlspecialchars($filters['from']) ?>" title="From">
    <span style="color:var(--text-muted);font-size:12px;">to</span>
    <input type="date" name="to" class="form-control" style="max-width:150px;" value="<?= htmlspecialchars($filters['to']) ?>" title="To">
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $base ?>/expenses" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">All Expenses (<?= $total ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>ID</th><th>Date</th><th>Payee</th><th>Category</th><th>Description</th><th>Method / Ref</th><th style="text-align:right;">Amount</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $e): ?>
          <tr style="<?= in_array($e['status'], ['cancelled', 'rejected'], true) ? 'opacity:.55;' : '' ?>">
            <td style="font-size:12px;">#<?= $e['id'] ?></td>
            <td style="font-size:12.5px;white-space:nowrap;"><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
            <td class="fw-600"><?= htmlspecialchars($e['payee'] ?: '—') ?></td>
            <td><span class="badge badge-muted"><?= htmlspecialchars($e['category']) ?></span></td>
            <td style="max-width:260px;"><?= htmlspecialchars($e['description'] ?: '—') ?>
              <?php if ($e['cancel_reason']): ?><div style="font-size:11px;color:var(--danger);"><?= htmlspecialchars($e['cancel_reason']) ?></div><?php endif; ?></td>
            <td style="font-size:12.5px;"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$e['method']] ?? ucfirst((string)$e['method'])) ?><?= $e['reference'] ? '<br><span style="color:var(--text-muted)">' . htmlspecialchars($e['reference']) . '</span>' : '' ?></td>
            <td style="text-align:right;font-weight:700;white-space:nowrap;"><?= Finance::money($e['amount'], $e['currency'] ?: $def) ?></td>
            <td><span class="badge <?= $statusBadge[$e['status']] ?? 'badge-muted' ?>"><?= ucfirst($e['status']) ?></span></td>
            <td>
              <?php if (in_array($e['status'], ['active', 'pending'], true)): ?>
                <div style="display:flex;gap:6px;">
                  <button type="button" class="btn btn-sm btn-secondary" onclick='openExpense(<?= json_encode($e, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                  <button type="button" class="btn btn-sm btn-danger" onclick="cancelExpense(<?= $e['id'] ?>)">Cancel</button>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">🧾</div><div class="empty-state-text">No expenses match.</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<div class="modal-overlay" id="expenseModal">
  <div class="modal modal-lg">
    <div class="modal-header"><div class="modal-title" id="expTitle">Add New Expense</div><button type="button" class="modal-close" onclick="document.getElementById('expenseModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="expForm" action="<?= $base ?>/expenses/store">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="payee_user_id" id="x_payee_user_id">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Payee *</label>
            <input type="text" name="payee" id="x_payee" class="form-control" list="payeeList" maxlength="150" required placeholder="Staff member, supplier or person">
            <datalist id="payeeList">
              <?php foreach ($staff as $s): ?><option value="<?= htmlspecialchars($s['name']) ?>" data-id="<?= $s['id'] ?>"><?= $s['code'] ? htmlspecialchars($s['code']) : 'Staff' ?></option><?php endforeach; ?>
              <?php foreach ($pastPayees as $pp): ?><option value="<?= htmlspecialchars($pp) ?>"></option><?php endforeach; ?>
            </datalist></div>
          <div class="form-group"><label class="form-label">Payment Date *</label><input type="date" name="expense_date" id="x_expense_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Category *</label>
            <input type="text" name="category" id="x_category" class="form-control" list="catList" maxlength="80" required placeholder="Choose or type a new one">
            <datalist id="catList"><?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?></datalist></div>
          <div class="form-group"><label class="form-label">Payment Method *</label>
            <select name="method" id="x_method" class="form-control"><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Amount *</label>
            <div style="display:flex;gap:6px;"><select name="currency" id="x_currency" class="form-control" style="max-width:90px;"><?php foreach ($currencies as $c): ?><option><?= $c ?></option><?php endforeach; ?></select>
              <input type="number" name="amount" id="x_amount" class="form-control" min="0.01" step="0.01" required></div></div>
          <div class="form-group"><label class="form-label">Payment Reference</label><input type="text" name="reference" id="x_reference" class="form-control" maxlength="150" placeholder="Cheque / slip / transaction no."></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Academic Year</label>
            <select name="academic_year_id" id="x_academic_year_id" class="form-control"><option value="">By payment date</option><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Description *</label><input type="text" name="description" id="x_description" class="form-control" maxlength="255" required></div>
        </div>
        <?php if ($finSettings['expense_approval'] === '1' && !$canApprove): ?><div class="alert alert-info" style="font-size:12.5px;">Expenses you record wait for an approver before they count.</div><?php endif; ?>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('expenseModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Save Expense</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="cancelModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Cancel Expense</div><button type="button" class="modal-close" onclick="document.getElementById('cancelModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="cancelForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body"><div class="form-group"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="2" required placeholder="e.g. Entered twice"></textarea></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('cancelModal').classList.remove('open')">Keep</button><button type="submit" class="btn btn-danger">Cancel Expense</button></div>
    </form>
  </div>
</div>

<script>
const EXP_BASE = '<?= $base ?>/expenses';
document.getElementById('x_payee').addEventListener('input', function () {
  const o = [...document.querySelectorAll('#payeeList option')].find(o => o.value === this.value && o.dataset.id);
  document.getElementById('x_payee_user_id').value = o ? o.dataset.id : '';
});
function openExpense(e) {
  const f = document.getElementById('expForm');
  f.reset();
  document.getElementById('x_payee_user_id').value = '';
  if (e) {
    document.getElementById('expTitle').textContent = 'Edit Expense #' + e.id;
    f.action = EXP_BASE + '/' + e.id + '/update';
    ['payee', 'expense_date', 'category', 'method', 'amount', 'reference', 'description', 'academic_year_id'].forEach(k => { const el = document.getElementById('x_' + k); if (el) el.value = e[k] || ''; });
    document.getElementById('x_currency').value = e.currency || '<?= $def ?>';
    document.getElementById('x_payee_user_id').value = e.payee_user_id || '';
  } else {
    document.getElementById('expTitle').textContent = 'Add New Expense';
    f.action = EXP_BASE + '/store';
    document.getElementById('x_expense_date').value = '<?= date('Y-m-d') ?>';
  }
  document.getElementById('expenseModal').classList.add('open');
}
function cancelExpense(id) {
  document.getElementById('cancelForm').action = EXP_BASE + '/' + id + '/cancel';
  document.getElementById('cancelModal').classList.add('open');
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
