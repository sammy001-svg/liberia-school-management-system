<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
  $cur = $tenant['currency'] ?? 'Ksh';
  $money = fn($n) => $cur.' '.number_format((float)$n, 2);
?>

<div class="page-header">
  <div>
    <div class="page-header-title">Budget</div>
    <div class="page-header-sub">
      <?php if($budget): ?>
        <?= htmlspecialchars($budget['name']) ?> ·
        <?= date('d M Y', strtotime($budget['period_start'])) ?> — <?= date('d M Y', strtotime($budget['period_end'])) ?>
      <?php else: ?>
        Plan income and expenditure, and track actual performance against it
      <?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <?php if(count($budgets) > 1): ?>
    <form method="GET" style="display:flex;gap:8px;align-items:center;">
      <select name="budget_id" class="form-control" style="width:auto;padding:8px 12px;" onchange="this.form.submit()">
        <?php foreach($budgets as $b): ?>
          <option value="<?= $b['id'] ?>" <?= $budget && $budget['id']==$b['id']?'selected':'' ?>>
            <?= htmlspecialchars($b['name']) ?><?= $b['status']==='active' ? ' (active)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
    <a href="<?= $cfg['url'] ?>/school/finance/budgets/manage" class="btn btn-outline">⚙ Manage Budgets</a>
  </div>
</div>

<?php if(!$budget): ?>
  <div class="card"><div class="empty-state">
    <div class="empty-state-icon">📊</div>
    <div class="empty-state-text">
      No budgets yet. <a href="<?= $cfg['url'] ?>/school/finance/budgets/manage">Create your first budget</a>
      to start tracking income and expenditure.
    </div>
  </div></div>
<?php else: ?>

<!-- Section buttons -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:22px;">
  <a href="<?= $cfg['url'] ?>/school/finance/budgets/<?= $budget['id'] ?>?type=income" class="btn btn-primary btn-lg">📈 Income</a>
  <a href="<?= $cfg['url'] ?>/school/finance/budgets/<?= $budget['id'] ?>?type=expense" class="btn btn-primary btn-lg">📉 Expenses</a>
  <a href="<?= $cfg['url'] ?>/school/finance/budgets/<?= $budget['id'] ?>?type=other" class="btn btn-primary btn-lg">📦 Other Budgets</a>
  <a href="<?= $cfg['url'] ?>/school/finance/budgets/<?= $budget['id'] ?>" class="btn btn-outline btn-lg">All Lines</a>
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

<?php
  $incomeRows  = array_values(array_filter($rows, fn($r) => $r['line_type'] === 'income'));
  $outgoRows   = array_values(array_filter($rows, fn($r) => $r['line_type'] !== 'income'));
  $chartData = [
    'incomeLabels'  => array_map(fn($r) => $r['category'], $incomeRows),
    'incomeBudget'  => array_map(fn($r) => round((float)$r['budgeted_amount'], 2), $incomeRows),
    'incomeActual'  => array_map(fn($r) => round((float)$r['actual'], 2), $incomeRows),
    'expenseLabels' => array_map(fn($r) => $r['category'], $outgoRows),
    'expenseBudget' => array_map(fn($r) => round((float)$r['budgeted_amount'], 2), $outgoRows),
    'expenseActual' => array_map(fn($r) => round((float)$r['actual'], 2), $outgoRows),
    'compare' => [
      'budgetIncome'  => round($totals['budget_income'], 2),
      'actualIncome'  => round($totals['actual_income'], 2),
      'budgetOutgoing'=> round($totals['budget_outgoing'], 2),
      'actualOutgoing'=> round($totals['actual_outgoing'], 2),
    ],
    'currency' => $cur,
  ];
?>

<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><div class="card-title">📈 Income — Budget vs Actual</div></div>
  <div class="card-body">
    <?php if(empty($incomeRows)): ?>
      <div class="empty-state"><div class="empty-state-icon">📈</div><div class="empty-state-text">No income lines yet.</div></div>
    <?php else: ?><div style="height:280px;"><canvas id="incomeChart"></canvas></div><?php endif; ?>
  </div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><div class="card-title">📉 Expenses — Budget vs Actual</div></div>
  <div class="card-body">
    <?php if(empty($outgoRows)): ?>
      <div class="empty-state"><div class="empty-state-icon">📉</div><div class="empty-state-text">No expense lines yet.</div></div>
    <?php else: ?><div style="height:280px;"><canvas id="expenseChart"></canvas></div><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">⚖️ Income vs Expenses</div></div>
  <div class="card-body"><div style="height:280px;"><canvas id="compareChart"></canvas></div></div>
</div>

<script>
(function(){
  var D = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  var css   = getComputedStyle(document.documentElement);
  var text  = (css.getPropertyValue('--text-muted') || '#94a3b8').trim();
  var grid  = (css.getPropertyValue('--border') || 'rgba(148,163,184,0.2)').trim();
  var GREEN = '#10B981', RED = '#EF4444', BLUE = '#3B82F6', AMBER = '#F59E0B';

  function money(v){ return D.currency + ' ' + Number(v).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); }

  var base = {
    responsive: true, maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: text, boxWidth: 12, font: { size: 11 } } },
      tooltip: { callbacks: { label: function(c){ return c.dataset.label + ': ' + money(c.parsed.y); } } }
    },
    scales: {
      x: { ticks: { color: text, font: { size: 10 } }, grid: { color: grid } },
      y: { beginAtZero: true, ticks: { color: text, font: { size: 10 } }, grid: { color: grid } }
    }
  };

  function bars(id, labels, budget, actual, actualColor){
    var el = document.getElementById(id);
    if (!el) return;
    new Chart(el.getContext('2d'), {
      type: 'bar',
      data: { labels: labels, datasets: [
        { label: 'Budgeted', data: budget, backgroundColor: BLUE, borderRadius: 4 },
        { label: 'Actual',   data: actual, backgroundColor: actualColor, borderRadius: 4 }
      ]},
      options: base
    });
  }

  bars('incomeChart',  D.incomeLabels,  D.incomeBudget,  D.incomeActual,  GREEN);
  bars('expenseChart', D.expenseLabels, D.expenseBudget, D.expenseActual, RED);

  var cmp = document.getElementById('compareChart');
  if (cmp) {
    new Chart(cmp.getContext('2d'), {
      type: 'bar',
      data: {
        labels: ['Income', 'Expenses'],
        datasets: [
          { label: 'Budgeted', data: [D.compare.budgetIncome, D.compare.budgetOutgoing], backgroundColor: BLUE,  borderRadius: 4 },
          { label: 'Actual',   data: [D.compare.actualIncome, D.compare.actualOutgoing], backgroundColor: AMBER, borderRadius: 4 }
        ]
      },
      options: base
    });
  }
})();
</script>

<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
