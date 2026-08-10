<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
  $cur = $tenant['currency'] ?? 'Ksh';
  $money = fn($n) => $cur.' '.number_format((float)$n, 2);
  // Percentage of budget consumed/achieved; null when nothing was budgeted.
  $pct = fn($actual, $budget) => (float)$budget > 0 ? round((float)$actual / (float)$budget * 100) : null;
?>

<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/finance/budgets">Budgets</a><span>/</span><span><?= htmlspecialchars($budget['name']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($budget['name']) ?></div>
    <div class="page-header-sub">
      <?= date('d M Y', strtotime($budget['period_start'])) ?> — <?= date('d M Y', strtotime($budget['period_end'])) ?>
      <?php if($budget['year_name']): ?> · <?= htmlspecialchars($budget['year_name']) ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/budgets/<?= $budget['id'] ?>/status" style="display:flex;gap:8px;align-items:center;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <select name="status" class="form-control" style="width:auto;padding:8px 12px;">
        <?php foreach(['draft'=>'Draft','active'=>'Active','closed'=>'Closed'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $budget['status']===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary">Update</button>
    </form>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addLineModal').classList.add('open')">+ Add Line</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Income — Actual vs Budget</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_income']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">of <?= $money($totals['budget_income']) ?> budgeted</div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Expenses — Actual vs Budget</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_expense']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">of <?= $money($totals['budget_expense']) ?> budgeted</div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Budgeted Net</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['budget_net']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: <?= $totals['actual_net'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
    <div class="stat-label">Actual Net</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_net']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
      <?= $totals['actual_net'] >= 0 ? 'Surplus' : 'Deficit' ?> for this period
    </div>
  </div>
</div>

<?php
// Shared renderer for the two sections — identical shape, opposite sense of "favourable".
$renderSection = function(string $title, string $icon, array $lines, string $type) use ($cfg, $money, $pct, $csrf_token) {
    $isIncome = $type === 'income';
?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-header">
    <div class="card-title"><?= $icon ?> <?= $title ?></div>
    <div style="font-size:12px;color:var(--text-muted);">
      <?= $isIncome ? 'Favourable when actual exceeds budget' : 'Favourable when actual is under budget' ?>
    </div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Category</th><th>Budgeted</th><th>Actual</th><th>Variance</th><th>Progress</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach($lines as $l): ?>
        <?php
          $p = $pct($l['actual'], $l['budgeted_amount']);
          $favourable = $l['variance'] >= 0;
          // An expense bar turns red past 100%; an income bar turns green as it fills.
          $barColor = $isIncome
              ? ($p !== null && $p >= 100 ? 'var(--success)' : 'var(--info)')
              : ($p !== null && $p > 100 ? 'var(--danger)' : ($p !== null && $p > 85 ? 'var(--warning)' : 'var(--success)'));
        ?>
        <tr>
          <td>
            <span class="fw-600"><?= htmlspecialchars($l['category']) ?></span>
            <?php if($isIncome && $l['source']==='fees'): ?>
              <span class="badge badge-info" style="margin-left:6px;">from fee payments</span>
            <?php endif; ?>
            <?php if($l['description']): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($l['description']) ?></div><?php endif; ?>
          </td>
          <td><?= $money($l['budgeted_amount']) ?></td>
          <td class="fw-600"><?= $money($l['actual']) ?></td>
          <td style="color:<?= $favourable ? 'var(--success)' : 'var(--danger)' ?>;font-weight:600;">
            <?= ($l['variance'] >= 0 ? '+' : '−') ?><?= $money(abs($l['variance'])) ?>
          </td>
          <td style="min-width:120px;">
            <?php if($p !== null): ?>
              <div style="display:flex;align-items:center;gap:8px;">
                <div class="progress-track" style="width:70px;">
                  <div class="progress-fill" style="width:<?= min(100, max(0, $p)) ?>%;--card-color:<?= $barColor ?>;"></div>
                </div>
                <span style="font-size:11px;color:var(--text-muted);"><?= $p ?>%</span>
              </div>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/finance/budget-lines/<?= $l['id'] ?>/delete"
                  data-confirm="Remove the &quot;<?= htmlspecialchars($l['category']) ?>&quot; line?"
                  data-confirm-title="Remove Line" data-confirm-label="Remove">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($lines)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon"><?= $icon ?></div>
            <div class="empty-state-text">No <?= $type ?> lines yet.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php }; ?>

<?php $renderSection('Income', '📈', $income, 'income'); ?>
<?php $renderSection('Expenditure', '📉', $expense, 'expense'); ?>

<div class="modal-overlay" id="addLineModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Budget Line</div>
      <button class="modal-close" onclick="document.getElementById('addLineModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/budgets/<?= $budget['id'] ?>/lines/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type *</label>
            <select name="line_type" id="lineType" class="form-control" required>
              <option value="income">Income</option>
              <option value="expense" selected>Expense</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Budgeted Amount *</label>
            <input type="number" step="0.01" min="0" name="budgeted_amount" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category *</label>
          <input type="text" name="category" class="form-control" required list="catList"
                 placeholder="e.g. Salaries, Utilities, Tuition, Donations">
          <datalist id="catList">
            <?php foreach(array_unique(array_merge($knownExpenseCats, $knownIncomeCats)) as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <div class="form-hint">Actuals are matched to recorded transactions by this category name.</div>
        </div>
        <div class="form-group" id="sourceGroup" style="display:none;">
          <label class="form-label">Where does this income come from?</label>
          <select name="source" class="form-control">
            <option value="other">Recorded under Other Income</option>
            <option value="fees">Student fee payments</option>
          </select>
          <div class="form-hint">Choose fee payments for tuition lines so the actual is drawn from collections.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addLineModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Line</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  var t = document.getElementById('lineType');
  var g = document.getElementById('sourceGroup');
  function sync(){ g.style.display = t.value === 'income' ? '' : 'none'; }
  t.addEventListener('change', sync); sync();
})();
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
