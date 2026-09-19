<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$r = $report;
$reportHeading = $mode === 'student'
    ? 'Student Financial Statement' . ($r ? ' — ' . $r['student']['name'] : '')
    : 'Class Statement' . ($r ? ' — ' . $r['class'] . ' (' . ucfirst($r['type']) . ')' : '');
$reportHeading .= $year ? ' · ' . $year['name'] : '';
$statusLbl = ['paid' => ['badge-success', 'Fully Paid'], 'partial' => ['badge-warning', 'Partially Paid'], 'unpaid' => ['badge-danger', 'Unpaid'], 'nobill' => ['badge-muted', 'No Billing']];
$tabs = ['student' => 'Student Statement', 'class' => 'Class Statement'];
?>
<?php require __DIR__ . '/partials/print_head.php'; ?>
<style>.tabs-line{display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:16px}.tabs-line a{padding:10px 16px;font-size:13px;font-weight:600;color:var(--text-muted);border-bottom:2px solid transparent}.tabs-line a.active{color:var(--primary);border-bottom-color:var(--primary)}</style>
<div class="page-header no-print">
  <div>
    <div class="page-header-title">Generate Financial Report</div>
    <div class="page-header-sub">A student’s bills and payments, or a whole class — overview, installments, outstanding balances or payment history.</div>
  </div>
  <?php if ($r): ?><button type="button" class="btn btn-primary" onclick="window.print()">🖨 Print / PDF</button><?php endif; ?>
</div>
<div class="tabs-line no-print"><?php foreach ($tabs as $k => $l): ?><a href="?mode=<?= $k ?>&year=<?= $year['id'] ?? '' ?>" class="<?= $mode === $k ? 'active' : '' ?>"><?= $l ?></a><?php endforeach; ?></div>

<form method="GET" class="card no-print" style="padding:14px 18px;margin-bottom:16px;">
  <input type="hidden" name="mode" value="<?= $mode ?>">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
    <?php if ($mode === 'student'): ?>
      <div class="form-group" style="margin:0;min-width:260px;"><label class="form-label">Student</label>
        <select name="student" class="form-control" required><option value="">Select Student</option><?php foreach ($students as $s): ?><option value="<?= $s['id'] ?>" <?= ($_GET['student'] ?? '') == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['admission_no'] . ' — ' . $s['name']) ?></option><?php endforeach; ?></select></div>
    <?php else: ?>
      <div class="form-group" style="margin:0;"><label class="form-label">Class</label>
        <select name="class" class="form-control" required><option value="">Select Class</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= ($_GET['class'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Report Type</label>
        <select name="report" class="form-control"><?php foreach (['overview' => 'Overview', 'installments' => 'Payment Installments', 'outstanding' => 'Outstanding Balances', 'history' => 'Payment History'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['report'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
    <?php endif; ?>
    <div class="form-group" style="margin:0;"><label class="form-label">Academic Year</label>
      <select name="year" class="form-control"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year && $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-secondary">Fetch Report</button>
  </div>
  <?php if ($mode === 'class'): ?>
  <details style="margin-top:10px;" <?= array_filter([$_GET['category'] ?? '', $_GET['pay'] ?? '', $_GET['type'] ?? '', $_GET['min'] ?? '', $_GET['max'] ?? '']) ? 'open' : '' ?>>
    <summary style="cursor:pointer;font-size:12.5px;color:var(--primary);">Advanced Filters</summary>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
      <select name="category" class="form-control" style="max-width:170px;"><option value="">All Students</option><option value="new" <?= ($_GET['category'] ?? '') === 'new' ? 'selected' : '' ?>>New Students Only</option><option value="old" <?= ($_GET['category'] ?? '') === 'old' ? 'selected' : '' ?>>Old Students Only</option></select>
      <select name="pay" class="form-control" style="max-width:160px;"><?php foreach (['' => 'Any Payment Status', 'paid' => 'Fully Paid', 'partial' => 'Partially Paid', 'unpaid' => 'Unpaid', 'overdue' => 'Overdue'] as $k => $l): ?><option value="<?= $k ?>" <?= ($_GET['pay'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
      <select name="type" class="form-control" style="max-width:170px;"><option value="">All Types</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= ($_GET['type'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select>
      <input type="number" name="min" class="form-control" style="max-width:130px;" placeholder="Balance min" value="<?= htmlspecialchars($_GET['min'] ?? '') ?>">
      <input type="number" name="max" class="form-control" style="max-width:130px;" placeholder="Balance max" value="<?= htmlspecialchars($_GET['max'] ?? '') ?>">
    </div>
  </details>
  <?php endif; ?>
</form>

<?php if ($r && $mode === 'student'): $e = $r['enrollment']; $tot = []; ?>
  <div class="card" style="margin-bottom:16px;"><div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px 20px;font-size:13px;">
    <div><span style="color:var(--text-muted)">Full Name</span><br><b><?= htmlspecialchars($r['student']['name']) ?></b></div>
    <div><span style="color:var(--text-muted)">Admission No.</span><br><b><?= htmlspecialchars($r['student']['admission_no']) ?></b></div>
    <div><span style="color:var(--text-muted)">Academic Year</span><br><b><?= htmlspecialchars($year['name']) ?></b></div>
    <div><span style="color:var(--text-muted)">Class</span><br><b><?= htmlspecialchars($e['class_name'] ?? '—') ?></b></div>
    <div><span style="color:var(--text-muted)">Student Type</span><br><b><?= htmlspecialchars($e['type_name'] ?? '—') ?></b></div>
    <div><span style="color:var(--text-muted)">New / Old</span><br><b><?= $e ? ($e['category'] === 'old' ? 'Old Student' : 'New Student') : '—' ?></b></div>
  </div></div>
  <?php if ($r['arrears']): ?><div class="alert alert-warning">Also owes from previous years: <?= implode(' + ', array_map(fn($c, $a) => Finance::money($a, $c), array_keys($r['arrears']), $r['arrears'])) ?></div><?php endif; ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title">Student Bill</div></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Description</th><th style="text-align:right;">Billed</th><th style="text-align:right;">Paid</th><th style="text-align:right;">Owed</th></tr></thead>
      <tbody>
        <?php foreach ($r['bills'] as $b): $cur = $b['currency'] ?: $def; $due = (float)$b['amount_due'] - (float)$b['discount']; $owed = $b['status'] === 'waived' ? 0 : max(0, $due - (float)$b['amount_paid']);
          $tot[$cur] ??= [0, 0, 0]; $tot[$cur][0] += $due; $tot[$cur][1] += (float)$b['amount_paid']; $tot[$cur][2] += $owed; ?>
          <tr><td><?= htmlspecialchars($b['label']) ?><?= $b['status'] === 'waived' ? ' <span class="badge badge-muted">Written off</span>' : '' ?></td><td style="text-align:right;"><?= Finance::money($due, $cur) ?></td>
            <td style="text-align:right;"><?= (float)$b['amount_paid'] > 0 ? Finance::money($b['amount_paid'], $cur) : '—' ?></td><td style="text-align:right;<?= $owed > 0 ? 'color:var(--danger);font-weight:600;' : '' ?>"><?= $owed > 0 ? Finance::money($owed, $cur) : '—' ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$r['bills']): ?><tr><td colspan="4" style="color:var(--text-muted);">No bills for this year.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><?php foreach ($tot as $cur => [$a, $p, $o]): ?><tr style="font-weight:800;"><td>Total<?= count($tot) > 1 ? " ({$cur})" : '' ?></td><td style="text-align:right;"><?= Finance::money($a, $cur) ?></td><td style="text-align:right;"><?= Finance::money($p, $cur) ?></td><td style="text-align:right;"><?= Finance::money($o, $cur) ?></td></tr><?php endforeach; ?></tfoot>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Payment History</div></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Date</th><th>Receipt</th><th>Description</th><th>Method</th><th style="text-align:right;">Amount</th></tr></thead>
      <tbody>
        <?php $ptot = []; foreach ($r['payments'] as $p): $cur = $p['currency'] ?: $def; $ptot[$cur] = ($ptot[$cur] ?? 0) + (float)$p['amount']; ?>
          <tr><td><?= date('F j, Y', strtotime($p['payment_date'] ?: $p['paid_at'])) ?></td><td>#<?= $p['id'] ?></td><td><?= htmlspecialchars($p['label']) ?><?= $p['is_arrears'] ? ' (arrears)' : '' ?></td>
            <td><?= htmlspecialchars(Finance::PAYMENT_METHODS[$p['method']] ?? $p['method']) ?></td><td style="text-align:right;"><?= Finance::money($p['amount'], $cur) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$r['payments']): ?><tr><td colspan="5" style="color:var(--text-muted);">No payments yet.</td></tr><?php endif; ?>
      </tbody>
      <tfoot><?php foreach ($ptot as $cur => $t): ?><tr style="font-weight:800;"><td colspan="4">Total</td><td style="text-align:right;"><?= Finance::money($t, $cur) ?></td></tr><?php endforeach; ?></tfoot>
    </table></div>
  </div>
<?php endif; ?>

<?php if ($r && $mode === 'class'): $sum = []; foreach ($r['rows'] as $row) { $c = $row['cur']; $sum[$c] ??= [0, 0, 0]; $sum[$c][0] += $row['billed']; $sum[$c][1] += $row['paid']; $sum[$c][2] += $row['balance']; } ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title"><?= ['overview' => 'Class Overview', 'installments' => 'Payment Installments', 'outstanding' => 'Outstanding Balances', 'history' => 'Payment History'][$r['type']] ?>: <?= htmlspecialchars($r['class']) ?> (<?= htmlspecialchars($year['name']) ?>) · <?= count($r['rows']) ?> of <?= $r['total'] ?> students</div></div>
    <div class="table-wrapper">
      <?php if ($r['type'] === 'history'): ?>
        <table>
          <thead><tr><th>Date</th><th>Receipt</th><th>Student</th><th>Description</th><th>Method</th><th style="text-align:right;">Amount</th></tr></thead>
          <tbody>
            <?php foreach ($r['history'] as $p): ?>
              <tr><td><?= date('M d, Y', strtotime($p['payment_date'] ?: $p['paid_at'])) ?></td><td>#<?= $p['id'] ?></td><td><?= htmlspecialchars($p['student_name']) ?> <span style="color:var(--text-muted);font-size:11.5px;"><?= htmlspecialchars($p['admission_no']) ?></span></td>
                <td><?= htmlspecialchars($p['label']) ?></td><td><?= htmlspecialchars(Finance::PAYMENT_METHODS[$p['method']] ?? $p['method']) ?></td><td style="text-align:right;"><?= Finance::money($p['amount'], $p['currency'] ?: $def) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$r['history']): ?><tr><td colspan="6" style="color:var(--text-muted);">No payments.</td></tr><?php endif; ?>
          </tbody>
        </table>
      <?php elseif ($r['type'] === 'installments'): ?>
        <table>
          <thead><tr><th>Student</th><?php foreach ($r['descriptions'] as $d): ?><th style="text-align:right;font-size:11.5px;"><?= htmlspecialchars($d) ?></th><?php endforeach; ?><th style="text-align:right;">Balance</th></tr></thead>
          <tbody>
            <?php foreach ($r['rows'] as $row): ?>
              <tr><td><b><?= htmlspecialchars($row['name']) ?></b><br><span style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($row['admission_no']) ?></span></td>
                <?php foreach ($r['descriptions'] as $d): $x = $row['per'][$d] ?? null; ?>
                  <td style="text-align:right;font-size:12px;"><?php if (!$x): ?><span style="color:var(--text-muted)">—</span><?php else: ?>Paid <?= Finance::money($x['paid'], $row['cur']) ?><?php if ($x['owed'] > 0): ?><br><span style="color:var(--danger);">Owes <?= Finance::money($x['owed'], $row['cur']) ?></span><?php endif; ?><?php endif; ?></td>
                <?php endforeach; ?>
                <td style="text-align:right;font-weight:700;<?= $row['balance'] > 0 ? 'color:var(--danger);' : '' ?>"><?= Finance::money($row['balance'], $row['cur']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <table>
          <thead><tr><th>Student</th><th>Adm. No.</th><th>Type</th><th>New/Old</th><th style="text-align:right;">Total Billed</th><th style="text-align:right;">Total Paid</th><th style="text-align:right;">Balance</th><th>Payment Status</th></tr></thead>
          <tbody>
            <?php foreach ($r['rows'] as $row): [$bc, $bl] = $statusLbl[$row['status']]; ?>
              <tr><td class="fw-600"><?= htmlspecialchars($row['name']) ?></td><td><?= htmlspecialchars($row['admission_no']) ?></td><td><?= htmlspecialchars($row['type_name'] ?? '—') ?></td><td><?= $row['category'] === 'old' ? 'Old' : 'New' ?></td>
                <td style="text-align:right;"><?= Finance::money($row['billed'], $row['cur']) ?></td><td style="text-align:right;"><?= Finance::money($row['paid'], $row['cur']) ?></td>
                <td style="text-align:right;<?= $row['balance'] > 0 ? 'color:var(--danger);font-weight:700;' : '' ?>"><?= Finance::money($row['balance'], $row['cur']) ?></td><td><span class="badge <?= $bc ?>"><?= $bl ?></span></td></tr>
            <?php endforeach; ?>
            <?php if (!$r['rows']): ?><tr><td colspan="8" style="color:var(--text-muted);">No students match.</td></tr><?php endif; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
  <?php if ($r['type'] !== 'history'): ?>
  <div class="card">
    <div class="card-header"><div class="card-title">Summary Statistics</div></div>
    <div class="table-wrapper"><table>
      <tbody>
        <tr><td>Filtered Students</td><td style="text-align:right;"><?= count($r['rows']) ?> of <?= $r['total'] ?></td></tr>
        <?php foreach ($sum as $cur => [$a, $p, $o]): ?>
          <tr><td>Total Billed (<?= $cur ?>)</td><td style="text-align:right;"><?= Finance::money($a, $cur) ?></td></tr>
          <tr><td>Total Paid (<?= $cur ?>)</td><td style="text-align:right;"><?= Finance::money($p, $cur) ?></td></tr>
          <tr style="font-weight:800;"><td>Outstanding Balance (<?= $cur ?>)</td><td style="text-align:right;color:var(--danger);"><?= Finance::money($o, $cur) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
  <?php endif; ?>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
