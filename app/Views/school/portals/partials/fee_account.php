<?php
/**
 * A student's fee account as a family sees it. Expects:
 *   $acct        Finance::studentAccount() result
 *   $yearUrl     page URL the year picker submits to (may already carry ?child=)
 *   $receiptUrl  prefix for one receipt, e.g. /parent/receipts  → /parent/receipts/{id}
 *   $allReceiptsUrl  URL printing every receipt for the shown year (year id appended)
 */
$badges = ['paid' => 'badge-success', 'partial' => 'badge-warning', 'unpaid' => 'badge-muted', 'overdue' => 'badge-danger', 'waived' => 'badge-info'];
$methods = Finance::PAYMENT_METHODS;
$year = $acct['year'];
$sep = str_contains($yearUrl, '?') ? '&' : '?';
$confirmed = array_filter($acct['payments'], fn($p) => $p['status'] === 'active');
?>
<style>
.fa-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.fa-enrol { display:flex; gap:8px; flex-wrap:wrap; font-size:12.5px; color:var(--text-muted); }
.fa-enrol b { color:var(--text); }
.fa-totals { display:grid; grid-template-columns:repeat(4, 1fr); gap:14px; margin-bottom:18px; }
@media (max-width:900px) { .fa-totals { grid-template-columns:1fr 1fr; } }
.fa-tot { padding:14px 16px; border:1px solid var(--border); border-radius:12px; background:var(--surface-1); border-top:3px solid var(--fig); }
.fa-tot .l { font-size:11.5px; color:var(--text-muted); }
.fa-tot .v { font-size:18px; font-weight:800; color:var(--text); margin-top:2px; white-space:nowrap; }
.fa-tot .s { font-size:11px; color:var(--text-muted); margin-top:2px; }
.fa-progress { height:8px; border-radius:6px; background:var(--surface-1); border:1px solid var(--border); overflow:hidden; margin:-6px 0 18px; }
.fa-progress span { display:block; height:100%; background:var(--success); }
.fa-table td, .fa-table th { white-space:nowrap; }
.fa-table td.wrap { white-space:normal; min-width:160px; }
.fa-cards { display:none; }
@media (max-width:720px) {
  .fa-table-wrap { display:none; }
  .fa-cards { display:block; }
  .fa-card { padding:12px 14px; border-bottom:1px solid var(--border); font-size:12.5px; }
  .fa-card .top { display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:6px; }
  .fa-card .top b { font-size:13.5px; }
  .fa-card .row { display:flex; justify-content:space-between; color:var(--text-muted); padding:1px 0; }
  .fa-card .row span:last-child { color:var(--text); font-weight:600; }
}
</style>

<div class="fa-head">
  <div class="fa-enrol">
    <?php if ($acct['enrollment']): ?>
      <span>Class: <b><?= htmlspecialchars($acct['enrollment']['class_name'] ?? '—') ?></b></span> ·
      <span><b><?= $acct['enrollment']['category'] === 'new' ? 'New' : 'Returning' ?></b> student</span>
      <?php if (!empty($acct['enrollment']['type_name'])): ?> · <span>Type: <b><?= htmlspecialchars($acct['enrollment']['type_name']) ?></b></span><?php endif; ?>
      <?php if ($acct['enrollment']['status'] === 'withdrawn'): ?> · <span class="badge badge-muted">Withdrawn</span><?php endif; ?>
    <?php elseif ($year): ?>
      <span>Not yet enrolled for <?= htmlspecialchars($year['name']) ?>.</span>
    <?php endif; ?>
  </div>
  <?php if (count($acct['years']) > 1): ?>
    <form method="GET" action="<?= htmlspecialchars(strtok($yearUrl, '?')) ?>" style="display:flex;gap:8px;align-items:center;">
      <?php parse_str((string)parse_url($yearUrl, PHP_URL_QUERY), $keep); foreach ($keep as $k => $v): ?><input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>"><?php endforeach; ?>
      <select name="year" class="form-control" onchange="this.form.submit()" aria-label="School year">
        <?php foreach ($acct['years'] as $y): ?><option value="<?= $y['id'] ?>" <?= (int)$y['id'] === (int)$year['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
</div>

<?php foreach ($acct['arrears'] as $cur => $amt): ?>
  <div class="alert alert-danger">
    <strong><?= Finance::money($amt, $cur) ?></strong> is still owed from earlier school years. Please settle it with the finance office.
  </div>
<?php endforeach; ?>

<?php if (!$acct['bills']): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">
    No school fees have been billed<?= $year ? ' for ' . htmlspecialchars($year['name']) : '' ?> yet.
  </div></div>
<?php else: ?>

<?php foreach ($acct['totals'] as $cur => $t): $net = $t['billed'] - $t['discount']; ?>
  <div class="fa-totals">
    <div class="fa-tot" style="--fig:#3B82F6;"><div class="l">Total fees<?= count($acct['totals']) > 1 ? " ({$cur})" : '' ?></div><div class="v"><?= Finance::money($net, $cur) ?></div>
      <?php if ($t['discount'] > 0.005): ?><div class="s">after <?= Finance::money($t['discount'], $cur) ?> discount</div><?php endif; ?></div>
    <div class="fa-tot" style="--fig:var(--success);"><div class="l">Paid</div><div class="v"><?= Finance::money($t['paid'], $cur) ?></div>
      <?php if ($t['pending'] > 0.005): ?><div class="s">+ <?= Finance::money($t['pending'], $cur) ?> awaiting confirmation</div><?php endif; ?></div>
    <div class="fa-tot" style="--fig:var(--warning);"><div class="l">Balance</div><div class="v"><?= Finance::money($t['balance'], $cur) ?></div></div>
    <div class="fa-tot" style="--fig:var(--danger);"><div class="l">Overdue now</div><div class="v" style="<?= $t['overdue'] > 0.005 ? 'color:var(--danger)' : '' ?>"><?= Finance::money($t['overdue'], $cur) ?></div></div>
  </div>
  <?php $pct = $net > 0 ? min(100, $t['paid'] / $net * 100) : 100; ?>
  <div class="fa-progress" title="<?= round($pct) ?>% paid"><span style="width:<?= round($pct, 1) ?>%"></span></div>
<?php endforeach; ?>

<div class="card" style="margin-bottom:18px;">
  <div class="card-header"><div class="card-title">Bills &amp; installments</div></div>
  <div class="table-wrapper fa-table-wrap">
    <table class="fa-table">
      <thead><tr><th>Bill</th><th>Due by</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Paid</th><th style="text-align:right;">Balance</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($acct['bills'] as $b): ?>
        <tr>
          <td class="wrap fw-600"><?= htmlspecialchars($b['label']) ?></td>
          <td><?= $b['due_date'] ? date('M j, Y', strtotime($b['due_date'])) : '—' ?></td>
          <td style="text-align:right;"><?= Finance::money((float)$b['amount_due'] - (float)$b['discount'], $b['cur']) ?></td>
          <td style="text-align:right;"><?= Finance::money($b['amount_paid'], $b['cur']) ?></td>
          <td style="text-align:right;" class="fw-700"><?= Finance::money($b['balance'], $b['cur']) ?></td>
          <td><span class="badge <?= $badges[$b['display_status']] ?>"><?= ucfirst($b['display_status']) ?></span>
            <?php if ((float)$b['pending_amt'] > 0.005): ?><span class="badge badge-info" title="Recorded by the school, waiting to be confirmed">Pending</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="fa-cards">
    <?php foreach ($acct['bills'] as $b): ?>
      <div class="fa-card">
        <div class="top"><b><?= htmlspecialchars($b['label']) ?></b><span class="badge <?= $badges[$b['display_status']] ?>"><?= ucfirst($b['display_status']) ?></span></div>
        <div class="row"><span>Due by</span><span><?= $b['due_date'] ? date('M j, Y', strtotime($b['due_date'])) : '—' ?></span></div>
        <div class="row"><span>Amount</span><span><?= Finance::money((float)$b['amount_due'] - (float)$b['discount'], $b['cur']) ?></span></div>
        <div class="row"><span>Paid</span><span><?= Finance::money($b['amount_paid'], $b['cur']) ?></span></div>
        <div class="row"><span>Balance</span><span><?= Finance::money($b['balance'], $b['cur']) ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
    <div class="card-title">Payment history</div>
    <?php if ($confirmed && $year): ?><a class="btn btn-sm btn-secondary" target="_blank" rel="noopener" href="<?= htmlspecialchars($allReceiptsUrl . $year['id']) ?>">Print all receipts</a><?php endif; ?>
  </div>
  <?php if (!$acct['payments']): ?>
    <div class="card-body" style="text-align:center;padding:30px;color:var(--text-muted);">No payments recorded<?= $year ? ' for ' . htmlspecialchars($year['name']) : '' ?>.</div>
  <?php else: ?>
  <div class="table-wrapper fa-table-wrap">
    <table class="fa-table">
      <thead><tr><th>Date</th><th>Receipt no.</th><th>For</th><th>Method</th><th style="text-align:right;">Amount</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($acct['payments'] as $p): ?>
        <tr>
          <td><?= date('M j, Y', strtotime($p['pay_date'])) ?></td>
          <td><?= $p['status'] === 'active' ? (int)$p['id'] : '—' ?></td>
          <td class="wrap"><?= htmlspecialchars($p['label']) ?><?= $p['is_arrears'] ? ' <span class="badge badge-orange">Arrears</span>' : '' ?></td>
          <td><?= htmlspecialchars($methods[$p['method']] ?? ucfirst((string)$p['method'])) ?></td>
          <td style="text-align:right;" class="fw-700"><?= Finance::money($p['amount'], $p['cur']) ?></td>
          <td><?php if ($p['status'] === 'active'): ?><a class="btn btn-sm btn-secondary" target="_blank" rel="noopener" href="<?= htmlspecialchars($receiptUrl . '/' . (int)$p['id']) ?>">Receipt</a>
              <?php else: ?><span class="badge badge-info" title="The school recorded this payment and is confirming it">Awaiting confirmation</span><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="fa-cards">
    <?php foreach ($acct['payments'] as $p): ?>
      <div class="fa-card">
        <div class="top"><b><?= Finance::money($p['amount'], $p['cur']) ?></b>
          <?php if ($p['status'] === 'active'): ?><a class="btn btn-sm btn-secondary" target="_blank" rel="noopener" href="<?= htmlspecialchars($receiptUrl . '/' . (int)$p['id']) ?>">Receipt</a>
          <?php else: ?><span class="badge badge-info">Awaiting confirmation</span><?php endif; ?></div>
        <div class="row"><span>For</span><span><?= htmlspecialchars($p['label']) ?></span></div>
        <div class="row"><span>Date</span><span><?= date('M j, Y', strtotime($p['pay_date'])) ?></span></div>
        <div class="row"><span>Method</span><span><?= htmlspecialchars($methods[$p['method']] ?? ucfirst((string)$p['method'])) ?></span></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
