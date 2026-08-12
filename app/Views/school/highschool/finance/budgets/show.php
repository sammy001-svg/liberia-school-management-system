<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
  $cur = $tenant['currency'] ?? 'Ksh';
  $money = fn($n) => $cur.' '.number_format((float)$n, 2);
  // Percentage of budget consumed/achieved; null when nothing was budgeted.
  $pct = fn($actual, $budget) => (float)$budget > 0 ? round((float)$actual / (float)$budget * 100) : null;
  $typeMeta = [
    'income'  => ['label'=>'Income',  'badge'=>'success', 'icon'=>'📈'],
    'expense' => ['label'=>'Expense', 'badge'=>'danger',  'icon'=>'📉'],
    'other'   => ['label'=>'Other',   'badge'=>'info',    'icon'=>'📦'],
  ];
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
    <button type="button" class="btn btn-primary" onclick="openLineModal('')">+ Add Budget Line</button>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Income</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_income']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">of <?= $money($totals['budget_income']) ?> budgeted</div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Expenditure</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_expense']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">of <?= $money($totals['budget_expense']) ?> budgeted</div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Other</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_other']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">of <?= $money($totals['budget_other']) ?> budgeted</div>
  </div>
  <div class="stat-card" style="--card-color: <?= $totals['actual_net'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
    <div class="stat-label">Net Position</div>
    <div class="stat-value" style="font-size:18px;"><?= $money($totals['actual_net']) ?></div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
      <?= $totals['actual_net'] >= 0 ? 'Surplus' : 'Deficit' ?> · <?= $money($totals['budget_net']) ?> planned
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Budget Lines (<?= count($rows) ?>)</div>
    <button type="button" class="btn btn-sm btn-primary" onclick="openLineModal('')">+ Add Line</button>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Type</th><th>Category</th><th>Budgeted</th><th>Actual</th><th>Variance</th><th>Progress</th><th></th></tr>
      </thead>
      <tbody>
        <?php $lastGroup = null; ?>
        <?php foreach($rows as $l): ?>
          <?php
            $type = $l['line_type'];
            $meta = $typeMeta[$type] ?? $typeMeta['other'];
            $isIncome = $type === 'income';
            $p = $pct($l['actual'], $l['budgeted_amount']);
            $favourable = $l['variance'] >= 0;
            // Outgoing bars turn red past budget; income bars fill green toward target.
            $barColor = $isIncome
                ? ($p !== null && $p >= 100 ? 'var(--success)' : 'var(--info)')
                : ($p !== null && $p > 100 ? 'var(--danger)' : ($p !== null && $p > 85 ? 'var(--warning)' : 'var(--success)'));
          ?>
          <?php if ($lastGroup !== null && $lastGroup !== $type): ?>
          <tr><td colspan="7" style="padding:0;border:0;"><div style="height:1px;background:var(--border);"></div></td></tr>
          <?php endif; ?>
          <?php $lastGroup = $type; ?>
        <tr>
          <td><span class="badge badge-<?= $meta['badge'] ?>"><?= $meta['icon'] ?> <?= $meta['label'] ?></span></td>
          <td>
            <span class="fw-600"><?= htmlspecialchars($l['category']) ?></span>
            <?php if($isIncome && $l['source']==='fees'): ?>
              <span class="badge badge-muted" style="margin-left:6px;">from fee payments</span>
            <?php endif; ?>
            <?php if($l['description']): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($l['description']) ?></div><?php endif; ?>
          </td>
          <td><?= $money($l['budgeted_amount']) ?></td>
          <td class="fw-600"><?= $money($l['actual']) ?></td>
          <td style="color:<?= $favourable ? 'var(--success)' : 'var(--danger)' ?>;font-weight:600;white-space:nowrap;">
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
        <?php if(empty($rows)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <div class="empty-state-text">
              No budget lines yet.
              <a href="javascript:void(0)" onclick="openLineModal('')">Add your first line</a> —
              income, expenditure or any other planned item.
            </div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
      <?php if(!empty($rows)): ?>
      <tfoot>
        <tr style="font-weight:700;border-top:2px solid var(--border);">
          <td colspan="2">Total Income</td>
          <td><?= $money($totals['budget_income']) ?></td>
          <td><?= $money($totals['actual_income']) ?></td>
          <td colspan="3"></td>
        </tr>
        <tr style="font-weight:700;">
          <td colspan="2">Total Outgoings (Expenditure + Other)</td>
          <td><?= $money($totals['budget_outgoing']) ?></td>
          <td><?= $money($totals['actual_outgoing']) ?></td>
          <td colspan="3"></td>
        </tr>
        <tr style="font-weight:800;border-top:2px solid var(--border);">
          <td colspan="2">Net</td>
          <td><?= $money($totals['budget_net']) ?></td>
          <td style="color:<?= $totals['actual_net'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"><?= $money($totals['actual_net']) ?></td>
          <td colspan="3"></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

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
              <option value="other">Other</option>
            </select>
            <div class="form-hint" id="typeHint"></div>
          </div>
          <div class="form-group">
            <label class="form-label">Budgeted Amount *</label>
            <input type="number" step="0.01" min="0" name="budgeted_amount" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category *</label>
          <input type="text" name="category" class="form-control" required list="catList"
                 placeholder="e.g. Salaries, Utilities, Tuition, Donations, Contingency">
          <datalist id="catList">
            <?php foreach(array_unique(array_merge($knownExpenseCats, $knownIncomeCats)) as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <div class="form-hint">Type any category you want — actuals are matched to recorded transactions by this name.</div>
        </div>
        <div class="form-group" id="sourceGroup" style="display:none;">
          <label class="form-label">Where does this income come from?</label>
          <select name="source" class="form-control">
            <option value="other">Recorded under Other Income</option>
            <option value="fees">Student fee payments</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Display Order</label>
          <input type="number" name="sort_order" class="form-control" value="0" min="0">
          <div class="form-hint">Lower numbers appear first within their type.</div>
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
var HINTS = {
  income:  'Actual comes from fee collections or Other Income entries.',
  expense: 'Actual comes from recorded expenses in this category.',
  other:   'Contingency, capital projects or anything else planned. Actual comes from recorded expenses and counts as an outgoing.'
};
function syncLineType(){
  var t = document.getElementById('lineType');
  document.getElementById('sourceGroup').style.display = t.value === 'income' ? '' : 'none';
  document.getElementById('typeHint').textContent = HINTS[t.value] || '';
}
function openLineModal(preset){
  if (preset) { document.getElementById('lineType').value = preset; }
  syncLineType();
  document.getElementById('addLineModal').classList.add('open');
}
document.getElementById('lineType').addEventListener('change', syncLineType);
syncLineType();
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
