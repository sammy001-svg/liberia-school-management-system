<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$colors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#14B8A6', '#6366F1'];
$tools = [
    ['Enrollment', '/enrollment', 'Enroll students & see who has paid'], ['Fees Payment', '/fees-payment', 'Take a payment against a bill'],
    ['Billing Setup', '/billing', 'What each class pays, by installment'], ['Expenses', '/expenses', 'Money paid out'],
    ['Extra Collections', '/collections', 'Uniforms, cafeteria, books…'], ['Arrears', '/arrears-collection', 'Collect prior-year balances'],
    ['Profit & Loss', '/profit-loss', 'Income vs expenses, any period'], ['Statements', '/statements', 'Class & student statements'],
    ['Daily Receipts', '/daily-receipts', 'Everything received on a day'], ['Payment Audit', '/audit', 'Reconcile payments by method & cashier'],
    ['Budget', '/budgets', 'Budget vs actual'], ['Finance Settings', '/settings', 'Currencies, approvals, categories'],
];
?>
<style>
.fin-grid { display:grid; gap:18px; margin-bottom:18px; }
.fin-3 { grid-template-columns: repeat(3, 1fr); }
@media (max-width:1100px) { .fin-3 { grid-template-columns: 1fr 1fr; } }
@media (max-width:720px) { .fin-3 { grid-template-columns: 1fr; } }
.fig { padding-bottom:8px; border-bottom:3px solid var(--fig); margin-bottom:12px; }
.fig .l { font-size:11.5px; color:var(--text-muted); }
.fig .v { font-size:17px; font-weight:800; color:var(--text); }
.bd-row { display:grid; grid-template-columns:12px 1fr auto 56px; gap:10px; align-items:center; padding:8px 0; border-bottom:1px solid var(--border); font-size:12.5px; }
.bd-dot { width:10px; height:10px; border-radius:50%; }
.tool { display:block; padding:14px; border:1px solid var(--border); border-radius:10px; background:var(--surface-1); transition:.2s; }
.tool:hover { border-color:var(--primary); transform:translateY(-2px); }
.tool b { display:block; color:var(--text); font-size:13.5px; }
.tool span { font-size:11.5px; color:var(--text-muted); }
</style>

<div class="page-header">
  <div>
    <div class="page-header-title">Manage Financial Records &amp; Reports</div>
    <div class="page-header-sub">The school’s money for <?= htmlspecialchars($label) ?> — fees, arrears, extra collections and expenses.</div>
  </div>
  <form method="GET" style="display:flex;gap:8px;align-items:center;">
    <select name="year" class="form-control" onchange="this.form.submit()"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year && $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select>
  </form>
</div>

<?php if (array_sum($pending)): ?>
  <div class="alert alert-warning" style="display:flex;gap:16px;flex-wrap:wrap;">
    <strong>Waiting for approval:</strong>
    <?php if ($pending['payments']): ?><a href="<?= $base ?>/payment-approvals"><?= $pending['payments'] ?> payment(s)</a><?php endif; ?>
    <?php if ($pending['expenses']): ?><a href="<?= $base ?>/expense-approvals"><?= $pending['expenses'] ?> expense(s)</a><?php endif; ?>
    <?php if ($pending['overrides']): ?><a href="<?= $base ?>/arrears-overrides"><?= $pending['overrides'] ?> arrears override(s)</a><?php endif; ?>
  </div>
<?php endif; ?>

<?php foreach ($pl as $cur => $p): $collections = array_sum($p['collections']); $fees = array_sum($p['fees']); ?>
<div class="fin-grid fin-3">
  <div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;"><div class="card-title">Finances<?= count($pl) > 1 ? " ({$cur})" : '' ?></div><a href="<?= $base ?>/fees-payment" class="btn btn-sm btn-success">Go to Fees Payment</a></div>
    <div class="fig" style="--fig:var(--success);"><div class="l"><?= htmlspecialchars($label) ?> Total Income</div><div class="v"><?= Finance::money($p['income'], $cur) ?></div></div>
    <div class="fig" style="--fig:var(--danger);"><div class="l"><?= htmlspecialchars($label) ?> Expenses</div><div class="v"><?= Finance::money($p['expense'], $cur) ?></div></div>
    <div class="fig" style="--fig:#3B82F6;"><div class="l"><?= htmlspecialchars($label) ?> Current Balance</div><div class="v" style="<?= $p['income'] - $p['expense'] < 0 ? 'color:var(--danger)' : '' ?>"><?= Finance::money($p['income'] - $p['expense'], $cur) ?></div></div>
    <div style="font-size:11.5px;color:var(--text-muted);">School fees <?= Finance::money($fees, $cur) ?> · Arrears <?= Finance::money($p['arrears'], $cur) ?> · Extra collections <?= Finance::money($collections, $cur) ?></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;"><div class="card-title">Arrears Collections</div><a href="<?= $base ?>/arrears-collection" class="btn btn-sm btn-success">Process Arrears</a></div>
    <div class="fig" style="--fig:var(--success);"><div class="l"><?= htmlspecialchars($label) ?> Arrears Collected</div><div class="v"><?= Finance::money($p['arrears'], $cur) ?></div></div>
    <div class="fig" style="--fig:var(--danger);"><div class="l">Remaining Arrears (earlier years)</div><div class="v"><?= Finance::money($remaining[$cur] ?? 0, $cur) ?></div></div>
    <div class="fig" style="--fig:var(--warning);"><div class="l">Still Owed on <?= htmlspecialchars($label) ?> Bills</div><div class="v"><?= Finance::money($owedThisYear[$cur] ?? 0, $cur) ?></div></div>
    <p style="font-size:11.5px;color:var(--text-muted);margin:0;">Each arrears payment settles the year it was owed for, but the collected amount is reported here, in the year it was received.</p>
  </div></div>
  <div class="card"><div class="card-body">
    <div class="card-title" style="margin-bottom:10px;">Income vs Expenses by Month<?= $cur !== $def ? '' : '' ?></div>
    <?php if ($cur === $def): ?><div style="position:relative;height:250px;"><canvas id="monthChart"></canvas></div>
    <?php else: ?><p style="font-size:12.5px;color:var(--text-muted);">Monthly chart shows <?= $def ?> only.</p><?php endif; ?>
  </div></div>
</div>

<div class="fin-grid fin-3">
  <?php foreach ([['Expenses', 'Manage Expenses', '/expenses', $p['expenses'], $p['expense']], ['Extra Collections', 'Manage Collections', '/collections', $p['collections'], $collections]] as $bi => [$title, $btn, $link, $items, $total]): arsort($items); ?>
    <div class="card"><div class="card-body">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;"><div class="card-title"><?= $title ?></div><a href="<?= $base . $link ?>" class="btn btn-sm btn-success"><?= $btn ?></a></div>
      <div style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($label) ?> breakdown</div>
      <div style="font-size:24px;font-weight:800;margin:4px 0 10px;"><?= Finance::money($total, $cur) ?></div>
      <div style="max-height:300px;overflow:auto;">
        <?php $i = 0; foreach ($items as $name => $amt): ?>
          <div class="bd-row"><span class="bd-dot" style="background:<?= $colors[($i++ + $bi * 3) % count($colors)] ?>"></span><span><?= htmlspecialchars($name) ?></span><span class="fw-600"><?= Finance::money($amt, $cur) ?></span><span style="text-align:right;color:var(--text-muted);"><?= $total > 0 ? number_format($amt / $total * 100, 2) : '0.00' ?>%</span></div>
        <?php endforeach; ?>
        <?php if (!$items): ?><div style="font-size:12.5px;color:var(--text-muted);padding:20px 0;text-align:center;">Nothing recorded for this year.</div><?php endif; ?>
      </div>
    </div></div>
  <?php endforeach; ?>
  <div class="card"><div class="card-body">
    <div class="card-title" style="margin-bottom:10px;">School Fees by Bill</div>
    <?php $fi = $p['fees']; arsort($fi); foreach ($fi as $name => $amt): ?>
      <div class="bd-row" style="grid-template-columns:1fr auto;"><span><?= htmlspecialchars($name) ?></span><span class="fw-600"><?= Finance::money($amt, $cur) ?></span></div>
    <?php endforeach; ?>
    <?php if ($p['arrears'] > 0): ?><div class="bd-row" style="grid-template-columns:1fr auto;"><span>Arrears Collections</span><span class="fw-600"><?= Finance::money($p['arrears'], $cur) ?></span></div><?php endif; ?>
    <?php if (!$fi && !$p['arrears']): ?><div style="font-size:12.5px;color:var(--text-muted);padding:20px 0;text-align:center;">No fee payments for this year yet.</div><?php endif; ?>
  </div></div>
</div>
<?php endforeach; ?>

<div class="fin-grid fin-3">
  <div class="card" style="grid-column:span 2;"><div class="card-body">
    <div class="card-title" style="margin-bottom:10px;">Year over Year (<?= $def ?>)</div>
    <div style="position:relative;height:230px;"><canvas id="yoyChart"></canvas></div>
  </div></div>
  <div class="card"><div class="card-body">
    <div class="card-title" style="margin-bottom:12px;">Tools</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <?php foreach ($tools as [$n, $u, $d]): ?><a class="tool" href="<?= $base . $u ?>"><b><?= $n ?></b><span><?= $d ?></span></a><?php endforeach; ?>
    </div>
  </div></div>
</div>

<script>
Chart.defaults.color = '#8892A4';
const grid = 'rgba(136,146,164,0.15)';
const compact = v => Intl.NumberFormat(undefined, { notation: 'compact' }).format(v);
const mc = document.getElementById('monthChart');
if (mc) new Chart(mc, { type: 'bar', data: {
  labels: <?= json_encode(array_map(fn($ym) => date('M y', strtotime($ym . '-01')), array_keys($months))) ?>,
  datasets: [{ label: 'Income', data: <?= json_encode(array_column(array_values($months), 'in')) ?>, backgroundColor: '#10B981', borderRadius: 4 },
             { label: 'Expenses', data: <?= json_encode(array_column(array_values($months), 'out')) ?>, backgroundColor: '#EF4444', borderRadius: 4 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: compact } }, x: { grid: { display: false } } } } });
new Chart(document.getElementById('yoyChart'), { type: 'bar', data: {
  labels: <?= json_encode(array_column($yoy, 'label')) ?>,
  datasets: [{ label: 'Income', data: <?= json_encode(array_column($yoy, 'in')) ?>, backgroundColor: '#10B981', borderRadius: 4 },
             { label: 'Expenses', data: <?= json_encode(array_column($yoy, 'out')) ?>, backgroundColor: '#EF4444', borderRadius: 4 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, grid: { color: grid }, ticks: { callback: compact } }, x: { grid: { display: false } } } } });
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
