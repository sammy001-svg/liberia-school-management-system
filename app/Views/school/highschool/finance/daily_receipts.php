<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$reportHeading = 'Daily Receipts Summary — ' . date('l, F j, Y', strtotime($date));
$tot = []; $byMethod = [];
foreach ($fees as $p) { $c = $p['currency'] ?: $def; $tot[$c]['fees'] = ($tot[$c]['fees'] ?? 0) + $p['amount']; $byMethod[$c][$p['method']] = ($byMethod[$c][$p['method']] ?? 0) + $p['amount']; }
foreach ($collections as $i) { $c = $i['currency'] ?: $def; $tot[$c]['col'] = ($tot[$c]['col'] ?? 0) + $i['amount']; $byMethod[$c][$i['method'] ?: 'cash'] = ($byMethod[$c][$i['method'] ?: 'cash'] ?? 0) + $i['amount']; }
?>
<?php require __DIR__ . '/partials/print_head.php'; ?>
<div class="page-header no-print">
  <div>
    <div class="page-header-title">Daily Receipts Summary</div>
    <div class="page-header-sub">Everything received on one day — school fees and extra collections — for the cash-up at close of business.</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;">
    <form method="GET"><input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>" max="<?= date('Y-m-d') ?>" onchange="this.form.submit()"></form>
    <button type="button" class="btn btn-primary" onclick="window.print()">🖨 Print</button>
  </div>
</div>

<div class="stat-grid">
  <?php foreach ($tot ?: [$def => []] as $c => $t): ?>
    <div class="stat-card" style="--card-color:var(--success);"><div class="stat-value" style="font-size:22px;"><?= Finance::money(($t['fees'] ?? 0) + ($t['col'] ?? 0), $c) ?></div><div class="stat-label">Total received · fees <?= Finance::money($t['fees'] ?? 0, $c) ?> · collections <?= Finance::money($t['col'] ?? 0, $c) ?></div></div>
  <?php endforeach; ?>
  <?php foreach ($byMethod as $c => $m): foreach ($m as $k => $v): ?>
    <div class="stat-card" style="--card-color:var(--blue);"><div class="stat-value" style="font-size:20px;"><?= Finance::money($v, $c) ?></div><div class="stat-label"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$k] ?? ucfirst($k)) ?></div></div>
  <?php endforeach; endforeach; ?>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><div class="card-title">School Fees (<?= count($fees) ?>)</div></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>Receipt</th><th>Student</th><th>Class</th><th>Bill</th><th>Method / Ref</th><th>Received By</th><th style="text-align:right;">Amount</th><th class="no-print"></th></tr></thead>
    <tbody>
      <?php foreach ($fees as $p): ?>
        <tr><td>#<?= $p['id'] ?></td><td><b><?= htmlspecialchars($p['student_name']) ?></b> <span style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($p['admission_no']) ?></span></td><td><?= htmlspecialchars($p['class_name'] ?? '') ?></td>
          <td><?= htmlspecialchars($p['label']) ?><?= $p['is_arrears'] ? ' (arrears)' : '' ?></td><td style="font-size:12.5px;"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$p['method']] ?? $p['method']) ?><?= $p['reference'] ? ' · ' . htmlspecialchars($p['reference']) : '' ?></td>
          <td style="font-size:12.5px;"><?= htmlspecialchars($p['received_by_name'] ?? '') ?></td><td style="text-align:right;font-weight:700;"><?= Finance::money($p['amount'], $p['currency'] ?: $def) ?></td>
          <td class="no-print"><a href="<?= $base ?>/payments/<?= $p['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-outline">Receipt</a></td></tr>
      <?php endforeach; ?>
      <?php if (!$fees): ?><tr><td colspan="8" style="color:var(--text-muted);">No fee payments on this day.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Extra Collections (<?= count($collections) ?>)</div></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>Receipt</th><th>Payer</th><th>Category</th><th>Method / Ref</th><th>Received By</th><th style="text-align:right;">Amount</th><th class="no-print"></th></tr></thead>
    <tbody>
      <?php foreach ($collections as $i): ?>
        <tr><td>#<?= $i['id'] ?></td><td><?= htmlspecialchars($i['source'] ?? '') ?></td><td><?= htmlspecialchars($i['category']) ?></td>
          <td style="font-size:12.5px;"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$i['method']] ?? (string)$i['method']) ?><?= $i['reference'] ? ' · ' . htmlspecialchars($i['reference']) : '' ?></td>
          <td style="font-size:12.5px;"><?= htmlspecialchars($i['received_by_name'] ?? '') ?></td><td style="text-align:right;font-weight:700;"><?= Finance::money($i['amount'], $i['currency'] ?: $def) ?></td>
          <td class="no-print"><a href="<?= $base ?>/collections/<?= $i['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-outline">Receipt</a></td></tr>
      <?php endforeach; ?>
      <?php if (!$collections): ?><tr><td colspan="7" style="color:var(--text-muted);">No extra collections on this day.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<div class="print-only" style="margin-top:30px;display:none;"><div style="display:flex;justify-content:space-between;font-size:13px;"><span>Cashier: ______________________</span><span>Checked by: ______________________</span></div></div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
