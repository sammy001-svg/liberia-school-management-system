<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$base = $cfg['url'] . '/school/finance';
$reportHeading = 'Profit & Loss Statement — ' . $reportTitle;
$g = fn($k, $d = '') => htmlspecialchars((string)($_GET[$k] ?? $d));
$types = ['year' => 'Academic Year', 'range' => 'Date Range', 'compare' => 'Compare Periods', 'daily' => 'Daily Report', 'daily_class' => 'Daily by Class'];
?>
<?php require __DIR__ . '/partials/print_head.php'; ?>
<div class="page-header no-print">
  <div>
    <div class="page-header-title">Profit &amp; Loss Statement</div>
    <div class="page-header-sub">Income (school fees by bill, arrears collections, extra collections) against expenses by category. Cancelled and unapproved entries are excluded.</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="window.print()">🖨 Print / PDF</button>
</div>

<form method="GET" class="card no-print" style="padding:14px 18px;margin-bottom:16px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin:0;"><label class="form-label">Report Type</label>
      <select name="type" id="plType" class="form-control"><?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $type === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    <div class="form-group pl-f" data-for="year" style="margin:0;"><label class="form-label">Academic Year</label>
      <select name="year" class="form-control"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= ($_GET['year'] ?? ($y['is_current'] ? $y['id'] : '')) == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group pl-f" data-for="range" style="margin:0;"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="<?= $g('from', date('Y-m-01')) ?>"></div>
    <div class="form-group pl-f" data-for="range" style="margin:0;"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="<?= $g('to', date('Y-m-d')) ?>"></div>
    <?php foreach ([1, 2] as $n): ?>
      <div class="form-group pl-f" data-for="compare" style="margin:0;"><label class="form-label">Period <?= $n ?> — year</label>
        <select name="year<?= $n ?>" class="form-control"><option value="">Or use dates →</option><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= ($_GET["year{$n}"] ?? '') == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group pl-f" data-for="compare" style="margin:0;"><label class="form-label">From</label><input type="date" name="from<?= $n ?>" class="form-control" value="<?= $g("from{$n}") ?>"></div>
      <div class="form-group pl-f" data-for="compare" style="margin:0;"><label class="form-label">To</label><input type="date" name="to<?= $n ?>" class="form-control" value="<?= $g("to{$n}") ?>"></div>
    <?php endforeach; ?>
    <div class="form-group pl-f" data-for="daily daily_class" style="margin:0;"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="<?= $g('date', date('Y-m-d')) ?>"></div>
    <div class="form-group pl-f" data-for="daily_class" style="margin:0;"><label class="form-label">Class</label>
      <select name="class" class="form-control"><option value="">Select Class</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-secondary">Generate Report</button>
  </div>
</form>

<?php if (!$columns): ?>
  <div class="alert alert-info">Choose the two periods to compare.</div>
<?php endif; ?>
<?php if ($type === 'daily_class'): ?>
  <div class="alert alert-info no-print">Daily by Class shows school-fee payments received from that class’s students on the day. Extra collections and expenses aren’t tied to a class, so they are left out.</div>
<?php endif; ?>

<?php foreach ($plCurrencies as $cur):
  $cols = array_map(fn($d) => $d[$cur] ?? ['fees' => [], 'arrears' => 0, 'collections' => [], 'expenses' => [], 'income' => 0, 'expense' => 0], $columns);
  $feeRows = []; $colRows = []; $expRows = []; $hasArrears = false;
  foreach ($cols as $c) { $feeRows += array_fill_keys(array_keys($c['fees']), 1); $colRows += array_fill_keys(array_keys($c['collections']), 1); $expRows += array_fill_keys(array_keys($c['expenses']), 1); $hasArrears = $hasArrears || $c['arrears'] > 0; }
  ksort($colRows); ksort($expRows);
  $cell = fn($v) => '<td style="text-align:right;">' . Finance::money($v, $cur) . '</td>';
?>
<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><div class="card-title"><?= htmlspecialchars($reportTitle) ?><?= count($plCurrencies) > 1 ? ' · ' . $cur : '' ?></div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Summary</th><?php foreach (array_keys($columns) as $h): ?><th style="text-align:right;"><?= htmlspecialchars($h) ?> (<?= $cur ?>)</th><?php endforeach; ?></tr></thead>
      <tbody>
        <tr><td>Total Income</td><?php foreach ($cols as $c) echo $cell($c['income']); ?></tr>
        <tr><td>Total Expenses</td><?php foreach ($cols as $c) echo $cell($c['expense']); ?></tr>
        <tr style="font-weight:800;"><td>Net Profit (Loss)</td><?php foreach ($cols as $c): $n = $c['income'] - $c['expense']; ?><td style="text-align:right;color:<?= $n < 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= Finance::money($n, $cur) ?></td><?php endforeach; ?></tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><div class="card-title">Income</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Description</th><?php foreach (array_keys($columns) as $h): ?><th style="text-align:right;"><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php if ($feeRows): ?><tr><td colspan="<?= count($columns) + 1 ?>" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);">School Fees</td></tr><?php endif; ?>
        <?php foreach (array_keys($feeRows) as $r): ?><tr><td><?= htmlspecialchars($r) ?></td><?php foreach ($cols as $c) echo $cell($c['fees'][$r] ?? 0); ?></tr><?php endforeach; ?>
        <?php if ($hasArrears): ?><tr><td>Arrears Collections</td><?php foreach ($cols as $c) echo $cell($c['arrears']); ?></tr><?php endif; ?>
        <?php if ($colRows): ?><tr><td colspan="<?= count($columns) + 1 ?>" style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-muted);">Extra Collections</td></tr><?php endif; ?>
        <?php foreach (array_keys($colRows) as $r): ?><tr><td><?= htmlspecialchars($r) ?></td><?php foreach ($cols as $c) echo $cell($c['collections'][$r] ?? 0); ?></tr><?php endforeach; ?>
        <?php if (!$feeRows && !$colRows && !$hasArrears): ?><tr><td colspan="<?= count($columns) + 1 ?>" style="color:var(--text-muted);">No income in this period.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr style="font-weight:800;"><td>Total</td><?php foreach ($cols as $c) echo $cell($c['income']); ?></tr></tfoot>
    </table>
  </div>
</div>

<?php if ($type !== 'daily_class'): ?>
<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><div class="card-title">Expenses</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Description</th><?php foreach (array_keys($columns) as $h): ?><th style="text-align:right;"><?= htmlspecialchars($h) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php foreach (array_keys($expRows) as $r): ?><tr><td><?= htmlspecialchars($r) ?></td><?php foreach ($cols as $c) echo $cell($c['expenses'][$r] ?? 0); ?></tr><?php endforeach; ?>
        <?php if (!$expRows): ?><tr><td colspan="<?= count($columns) + 1 ?>" style="color:var(--text-muted);">No expenses in this period.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><tr style="font-weight:800;"><td>Total</td><?php foreach ($cols as $c) echo $cell($c['expense']); ?></tr></tfoot>
    </table>
  </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<script>
(function () {
  const t = document.getElementById('plType');
  const sync = () => document.querySelectorAll('.pl-f').forEach(el => { el.style.display = el.dataset.for.split(' ').includes(t.value) ? '' : 'none'; });
  t.addEventListener('change', sync); sync();
})();
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
