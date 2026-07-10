<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/finance">Finance</a><span>/</span><span>Reports</span></div>

<div class="page-header">
  <div>
    <div class="page-header-title">Financial Reports</div>
    <div class="page-header-sub"><?= htmlspecialchars($periodLabel) ?></div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/finance/reports/print?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-outline">🖨️ Print Income Statement</a>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <label style="color:var(--text-muted);font-size:13px">Period:</label>
    <?php foreach(['month'=>'This Month','term'=>'This Period','year'=>'This Academic Year','all'=>'All Time'] as $val=>$lbl): ?>
      <a href="?range=<?= $val ?>" class="btn btn-sm <?= $range===$val?'btn-primary':'btn-outline' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
    <span style="border-left:1px solid var(--border);height:20px;margin:0 4px;"></span>
    <input type="hidden" name="range" value="custom">
    <label style="color:var(--text-muted);font-size:13px">Custom:</label>
    <input type="date" name="from" class="form-control" style="width:150px;" value="<?= $range==='custom' ? htmlspecialchars($from) : '' ?>">
    <span style="color:var(--text-muted);">to</span>
    <input type="date" name="to" class="form-control" style="width:150px;" value="<?= $range==='custom' ? htmlspecialchars($to) : '' ?>">
    <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
  </div>
</form>

<div class="stat-grid" style="grid-template-columns:repeat(5,1fr);">
  <div class="stat-card"><div class="stat-label">Total Billed</div><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($totalBilled,0) ?></div></div>
  <div class="stat-card" style="--card-color: var(--success);"><div class="stat-label">Total Collected</div><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($totalCollected,0) ?></div></div>
  <div class="stat-card" style="--card-color: var(--danger);"><div class="stat-label">Total Expenses</div><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($totalExpenses,0) ?></div></div>
  <div class="stat-card" style="--card-color: <?= $netIncome>=0?'var(--success)':'var(--danger)' ?>;"><div class="stat-label">Net Income</div><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($netIncome,0) ?></div></div>
  <div class="stat-card" style="--card-color: var(--info);"><div class="stat-label">Collection Rate</div><div class="stat-value"><?= $collectionRate ?>%</div></div>
</div>

<div style="display:grid; grid-template-columns: 1.4fr 1fr; gap:24px; margin-bottom:24px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Collections vs Expenses (Last 6 Months)</div></div>
    <div class="card-body"><canvas id="trendChart"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Revenue by Category</div></div>
    <div class="card-body">
      <?php if(empty($revenueByCategory)): ?>
        <div class="empty-state"><div class="empty-state-icon">💰</div><div class="empty-state-text">No invoices in this period.</div></div>
      <?php else: ?>
        <canvas id="revenueChart"></canvas>
      <?php endif; ?>
    </div>
  </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:24px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Expenses by Category</div></div>
    <div class="card-body">
      <?php if(empty($expensesByCategory)): ?>
        <div class="empty-state"><div class="empty-state-icon">🧮</div><div class="empty-state-text">No expenses in this period.</div></div>
      <?php else: ?>
        <canvas id="expenseChart"></canvas>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Payments by Method</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Method</th><th>Count</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach($paymentsByMethod as $p): ?>
          <tr>
            <td style="text-transform:capitalize;" class="fw-600"><?= htmlspecialchars($p['method']) ?></td>
            <td><?= (int)$p['cnt'] ?></td>
            <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($p['total'],2) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($paymentsByMethod)): ?>
          <tr><td colspan="3"><div class="empty-state"><div class="empty-state-icon">💳</div><div class="empty-state-text">No payments in this period.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('trendChart').getContext('2d'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($monthlyTrend, 'label')) ?>,
    datasets: [
      { label: 'Collected', data: <?= json_encode(array_column($monthlyTrend, 'collected')) ?>, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.4 },
      { label: 'Expenses', data: <?= json_encode(array_column($monthlyTrend, 'expenses')) ?>, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.1)', fill: true, tension: 0.4 }
    ]
  },
  options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

<?php if(!empty($revenueByCategory)): ?>
new Chart(document.getElementById('revenueChart').getContext('2d'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_column($revenueByCategory, 'category')) ?>,
    datasets: [{ data: <?= json_encode(array_map('floatval', array_column($revenueByCategory, 'total'))) ?>,
      backgroundColor: ['#10b981','#3b82f6','#f59e0b','#ef4444','#8b5cf6','#14b8a6','#f97316'] }]
  },
  options: { responsive: true }
});
<?php endif; ?>

<?php if(!empty($expensesByCategory)): ?>
new Chart(document.getElementById('expenseChart').getContext('2d'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($expensesByCategory, 'category')) ?>,
    datasets: [{ label: 'Amount', data: <?= json_encode(array_map('floatval', array_column($expensesByCategory, 'total'))) ?>,
      backgroundColor: 'rgba(239,68,68,0.6)', borderColor: 'rgba(239,68,68,1)', borderWidth: 1 }]
  },
  options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
<?php endif; ?>
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
