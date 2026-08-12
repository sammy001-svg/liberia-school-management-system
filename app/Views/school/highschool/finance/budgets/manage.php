<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'Ksh'; $money = fn($n) => $cur.' '.number_format((float)$n, 2); ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Manage Budgets</div>
    <div class="page-header-sub">Plan income and expenditure, and track actual performance against it</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= $cfg['url'] ?>/school/finance/budgets" class="btn btn-outline">← Budget Report</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addBudgetModal').classList.add('open')">+ New Budget</button>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Budgets (<?= count($budgets) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Budget</th><th>Period</th><th>Budgeted Income</th><th>Budgeted Expense</th><th>Net</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach($budgets as $b): ?>
        <?php $net = (float)$b['budget_income'] - (float)$b['budget_expense']; ?>
        <tr>
          <td>
            <a href="<?= $cfg['url'] ?>/school/finance/budgets/<?= $b['id'] ?>" class="fw-600"><?= htmlspecialchars($b['name']) ?></a>
            <?php if($b['year_name']): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($b['year_name']) ?></div><?php endif; ?>
          </td>
          <td style="font-size:12px;">
            <?= date('d M Y', strtotime($b['period_start'])) ?>
            <div style="color:var(--text-muted);">to <?= date('d M Y', strtotime($b['period_end'])) ?></div>
          </td>
          <td><?= $money($b['budget_income']) ?></td>
          <td><?= $money($b['budget_expense']) ?></td>
          <td class="fw-600" style="color:<?= $net >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"><?= $money($net) ?></td>
          <td><span class="badge badge-<?= $b['status']==='active'?'success':($b['status']==='closed'?'muted':'warning') ?>"><?= ucfirst($b['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/finance/budgets/<?= $b['id'] ?>" class="btn btn-sm btn-outline">Open</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/finance/budgets/<?= $b['id'] ?>/delete"
                    data-confirm="Delete <?= htmlspecialchars($b['name']) ?> and all its lines? This cannot be undone."
                    data-confirm-title="Delete Budget" data-confirm-label="Delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($budgets)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <div class="empty-state-text">No budgets yet. <a href="javascript:void(0)" onclick="document.getElementById('addBudgetModal').classList.add('open')">Create your first budget</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addBudgetModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">New Budget</div>
      <button class="modal-close" onclick="document.getElementById('addBudgetModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/budgets/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Budget Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. 2026 Annual Budget, Term 1 Budget">
        </div>
        <div class="form-group">
          <label class="form-label">Academic Year</label>
          <select name="academic_year_id" class="form-control" id="budgetYear">
            <option value="">— Not linked —</option>
            <?php foreach($years as $y): ?>
              <option value="<?= $y['id'] ?>" data-start="<?= htmlspecialchars($y['start_date'] ?? '') ?>" data-end="<?= htmlspecialchars($y['end_date'] ?? '') ?>">
                <?= htmlspecialchars($y['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-hint">Choosing a year fills the period dates below.</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Period Start *</label>
            <input type="date" name="period_start" id="budgetStart" class="form-control" required value="<?= date('Y-01-01') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Period End *</label>
            <input type="date" name="period_end" id="budgetEnd" class="form-control" required value="<?= date('Y-12-31') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="draft">Draft</option>
            <option value="active">Active</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addBudgetModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Budget</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('budgetYear').addEventListener('change', function(){
  var o = this.options[this.selectedIndex];
  if (o.dataset.start) document.getElementById('budgetStart').value = o.dataset.start;
  if (o.dataset.end)   document.getElementById('budgetEnd').value   = o.dataset.end;
});
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
