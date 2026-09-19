<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$justPaid = (int)($_GET['receipt'] ?? 0);
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Arrears — Prior-Year Balance Collection</div>
    <div class="page-header-sub">Payments here clear the selected previous year’s balance and are reported as <strong>Arrears Collections</strong> income for <?= htmlspecialchars($year['name'] ?? 'the current year') ?>.</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $base ?>/arrears-overrides" class="btn btn-outline">Arrears Overrides</a>
    <a href="<?= $base ?>/arrears" class="btn btn-outline">Aging Report</a>
  </div>
</div>

<?php if (!$year): ?>
  <div class="alert alert-warning">Mark an academic year as current first.</div>
<?php else: ?>
<?php if ($justPaid): ?>
  <div class="alert alert-success" style="display:flex;justify-content:space-between;align-items:center;"><span>Arrears payment saved.</span><a href="<?= $base ?>/payments/<?= $justPaid ?>/receipt" target="_blank" class="btn btn-sm btn-success">🖨 Print Receipt</a></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:minmax(260px,340px) 1fr;gap:16px;align-items:start;" class="ar-grid">
  <div class="card">
    <div class="card-header"><div class="card-title">Students With Arrears (<?= count(array_unique(array_column($owing, 'id'))) ?>)</div></div>
    <div style="max-height:620px;overflow:auto;">
      <?php foreach ($owing as $o): ?>
        <a href="<?= $base ?>/arrears-collection?student=<?= $o['id'] ?>" style="display:flex;justify-content:space-between;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border);<?= $student && $student['id'] == $o['id'] ? 'background:var(--primary-soft);' : '' ?>">
          <span><span class="fw-600" style="color:var(--text);"><?= htmlspecialchars($o['name']) ?></span><br><span style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($o['admission_no']) ?> · <?= htmlspecialchars($o['class_name'] ?? '') ?></span></span>
          <span style="color:var(--danger);font-weight:700;font-size:13px;white-space:nowrap;"><?= Finance::money($o['owed'], $o['cur']) ?></span>
        </a>
      <?php endforeach; ?>
      <?php if (!$owing): ?><div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text">No student owes anything from previous years.</div></div><?php endif; ?>
    </div>
  </div>

  <div>
    <?php if ($student): ?>
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title"><?= htmlspecialchars($student['name']) ?> · <?= htmlspecialchars($student['admission_no']) ?></div></div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Year</th><th>Bill</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Paid</th><th style="text-align:right;">Balance</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($bills as $b): $cur = $b['currency'] ?: $def; ?>
                <tr>
                  <td><?= htmlspecialchars($b['year_name']) ?></td>
                  <td class="fw-600"><?= htmlspecialchars($b['label']) ?></td>
                  <td style="text-align:right;"><?= Finance::money((float)$b['amount_due'] - (float)$b['discount'], $cur) ?></td>
                  <td style="text-align:right;"><?= Finance::money($b['amount_paid'], $cur) ?></td>
                  <td style="text-align:right;color:var(--danger);font-weight:700;"><?= Finance::money($b['balance'], $cur) ?></td>
                  <td><div style="display:flex;gap:6px;">
                    <button type="button" class="btn btn-sm btn-success" onclick='payArrears(<?= json_encode(['id' => $b['id'], 'label' => $b['year_name'] . ' — ' . $b['label'], 'bal' => (float)$b['balance'], 'cur' => $cur], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Collect</button>
                    <?php if ($canApprove): ?><button type="button" class="btn btn-sm btn-outline" onclick="clearBal(<?= $b['id'] ?>)">Clear balance</button><?php endif; ?>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$bills): ?><tr><td colspan="6" style="text-align:center;padding:20px;color:var(--text-muted);">Nothing owed from previous years.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php if (!empty($collected)): ?>
        <div class="card">
          <div class="card-header"><div class="card-title">Arrears Payments Received</div></div>
          <div class="table-wrapper"><table>
            <thead><tr><th>ID</th><th>Date</th><th>Bill</th><th style="text-align:right;">Amount</th><th>Status</th><th></th></tr></thead>
            <tbody><?php foreach ($collected as $p): ?>
              <tr><td>#<?= $p['id'] ?></td><td><?= date('M d, Y', strtotime($p['payment_date'] ?: $p['paid_at'])) ?></td><td><?= htmlspecialchars($p['label']) ?></td>
                <td style="text-align:right;"><?= Finance::money($p['amount'], $p['currency'] ?: $def) ?></td><td><span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= ucfirst($p['status']) ?></span></td>
                <td><?php if ($p['status'] === 'active'): ?><a href="<?= $base ?>/payments/<?= $p['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-outline">Receipt</a><?php endif; ?></td></tr>
            <?php endforeach; ?></tbody>
          </table></div>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="card"><div class="empty-state"><div class="empty-state-icon">👈</div><div class="empty-state-text">Select a student to see and collect their previous-year balances.</div></div></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($student): ?>
<div class="modal-overlay" id="payModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Collect Arrears</div><button type="button" class="modal-close" onclick="document.getElementById('payModal').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/fees-payment/store" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
      <input type="hidden" name="invoice_id" id="arInvoice">
      <input type="hidden" name="back" value="/school/finance/arrears-collection?student=<?= $student['id'] ?>">
      <div class="modal-body">
        <p class="fw-600" id="arLabel" style="margin-bottom:12px;"></p>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Amount *</label><input type="number" name="amount" id="arAmount" class="form-control" min="0.01" step="0.01" required></div>
          <div class="form-group"><label class="form-label">Payment Date *</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Method</label><select name="method" class="form-control"><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control" maxlength="150" placeholder="Required for cheque, bank, POS, mobile"></div>
        </div>
        <div class="form-group"><label class="form-label">Comment</label><input type="text" name="comment" class="form-control" maxlength="255"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('payModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Collect Payment</button></div>
    </form>
  </div>
</div>
<div class="modal-overlay" id="clearModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Clear Balance</div><button type="button" class="modal-close" onclick="document.getElementById('clearModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="clearForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="back" value="/school/finance/arrears-collection?student=<?= $student['id'] ?>">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Writes off what is left on this bill — use it for a wrongly billed line, not for forgiving a debt quietly. It is logged under Balance Write-offs.</p>
        <div class="form-group"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="2" required></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('clearModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-danger">Clear Balance</button></div>
    </form>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

<style>@media (max-width: 900px) { .ar-grid { grid-template-columns: 1fr !important; } }</style>
<script>
function payArrears(b) {
  document.getElementById('arInvoice').value = b.id;
  document.getElementById('arLabel').textContent = b.label;
  const a = document.getElementById('arAmount'); a.max = b.bal; a.value = b.bal.toFixed(2);
  document.getElementById('payModal').classList.add('open');
}
function clearBal(id) {
  document.getElementById('clearForm').action = '<?= $base ?>/invoices/' + id + '/write-off';
  document.getElementById('clearModal').classList.add('open');
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
