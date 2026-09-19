<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$payBadge = ['active' => ['badge-success', 'Paid'], 'pending' => ['badge-warning', 'Awaiting approval'], 'cancelled' => ['badge-muted', 'Cancelled'], 'rejected' => ['badge-danger', 'Rejected']];
$billBadge = ['paid' => ['badge-success', 'Paid'], 'partial' => ['badge-warning', 'Part paid'], 'unpaid' => ['badge-danger', 'Unpaid'], 'overdue' => ['badge-danger', 'Overdue'], 'waived' => ['badge-muted', 'Written off']];
$justPaid = (int)($_GET['receipt'] ?? 0);
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Fees Payment</div>
    <div class="page-header-sub">Choose a student, then record money received against one of their bills.</div>
  </div>
  <a href="<?= $base ?>/enrollment?year=<?= $year['id'] ?? '' ?>" class="btn btn-outline">← Enrollment</a>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:16px;" id="pickForm">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin:0;flex:1;min-width:260px;"><label class="form-label">Student</label>
      <input type="text" class="form-control" list="payStudents" id="payPick" autocomplete="off" placeholder="Type a name or admission no."
             value="<?= $student ? htmlspecialchars($student['admission_no'] . ' — ' . $student['name']) : '' ?>">
      <datalist id="payStudents"><?php foreach ($students as $s): ?><option value="<?= htmlspecialchars($s['admission_no'] . ' — ' . $s['name']) ?>" data-id="<?= $s['id'] ?>"></option><?php endforeach; ?></datalist>
      <input type="hidden" name="student" id="payStudentId" value="<?= $student['id'] ?? '' ?>">
    </div>
    <div class="form-group" style="margin:0;min-width:150px;"><label class="form-label">Academic Year</label>
      <select name="year" class="form-control"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year && $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-primary">Fetch Record</button>
  </div>
</form>

<?php if ($student && $year): ?>
  <?php if ($justPaid): ?>
    <div class="alert alert-success" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
      <span>Payment saved.</span>
      <a href="<?= $base ?>/payments/<?= $justPaid ?>/receipt" target="_blank" class="btn btn-sm btn-success">🖨 Print Receipt</a>
    </div>
  <?php endif; ?>
  <?php if (!empty($arrears)): ?>
    <div class="alert alert-warning">
      This student still owes <strong><?= implode(' + ', array_map(fn($c, $a) => Finance::money($a, $c), array_keys($arrears), $arrears)) ?></strong> from previous years.
      <a href="<?= $base ?>/arrears-collection?student=<?= $student['id'] ?>">Collect arrears →</a>
    </div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
      <div class="avatar avatar-xl" style="flex-shrink:0;"><?= htmlspecialchars(mb_strtoupper(mb_substr($student['name'], 0, 1))) ?></div>
      <div style="flex:1;min-width:240px;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px 20px;font-size:13px;">
        <div><div style="color:var(--text-muted);font-size:11.5px;">Student</div><div class="fw-600" style="font-size:15px;"><?= htmlspecialchars($student['name']) ?></div></div>
        <div><div style="color:var(--text-muted);font-size:11.5px;">Admission No.</div><div class="fw-600"><?= htmlspecialchars($student['admission_no']) ?></div></div>
        <div><div style="color:var(--text-muted);font-size:11.5px;">Class (<?= htmlspecialchars($year['name']) ?>)</div><div class="fw-600"><?= htmlspecialchars($enrollment['class_name'] ?? ($student['class_name'] ?? '—')) ?></div></div>
        <div><div style="color:var(--text-muted);font-size:11.5px;">Student Type</div><div class="fw-600"><?= htmlspecialchars($enrollment['type_name'] ?? '—') ?></div></div>
        <div><div style="color:var(--text-muted);font-size:11.5px;">New / Old</div><div class="fw-600"><?= $enrollment ? ($enrollment['category'] === 'old' ? 'Old Student' : 'New Student') : '—' ?></div></div>
      </div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>" class="btn btn-sm btn-outline">Profile</a>
        <a href="<?= $base ?>/statements?mode=student&student=<?= $student['id'] ?>&year=<?= $year['id'] ?>" class="btn btn-sm btn-outline">Statement</a>
      </div>
    </div>
  </div>

  <?php if (!$enrollment && !$bills): ?>
    <div class="alert alert-info"><?= htmlspecialchars($student['name']) ?> is not enrolled for <?= htmlspecialchars($year['name']) ?>, so there are no bills yet. <a href="<?= $base ?>/enrollment?year=<?= $year['id'] ?>">Enroll them first →</a></div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:minmax(280px,360px) 1fr;gap:16px;align-items:start;" class="fp-grid">
    <div class="card">
      <div class="card-header"><div class="card-title">Record Payment</div></div>
      <div class="card-body">
        <?php $open = array_values(array_filter($bills, fn($b) => $b['status'] !== 'waived' && (float)$b['balance'] > 0.005)); ?>
        <?php if (!$open): ?>
          <div class="empty-state" style="padding:20px;"><div class="empty-state-icon">✅</div><div class="empty-state-text">Nothing is owed on this year’s bills.</div></div>
        <?php else: ?>
        <form method="POST" action="<?= $base ?>/fees-payment/store" enctype="multipart/form-data" id="payForm">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
          <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?>">
          <div class="form-group"><label class="form-label">Payment For *</label>
            <select name="invoice_id" id="billSelect" class="form-control" required>
              <option value="">Select bill</option>
              <?php foreach ($open as $b): ?>
                <option value="<?= $b['id'] ?>" data-cur="<?= htmlspecialchars($b['currency'] ?: $def) ?>" data-total="<?= (float)$b['amount_due'] - (float)$b['discount'] ?>" data-paid="<?= (float)$b['amount_paid'] ?>" data-bal="<?= (float)$b['balance'] ?>"><?= htmlspecialchars($b['label']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint" id="billHint"></div>
          </div>
          <div class="form-group"><label class="form-label">Amount *</label>
            <div style="display:flex;gap:6px;align-items:center;"><span id="curTag" class="badge badge-muted" style="font-size:12px;"><?= $def ?></span>
              <input type="number" name="amount" id="payAmount" class="form-control" min="0.01" step="0.01" required></div>
            <a href="javascript:void(0)" id="payFull" style="font-size:12px;">Pay the full balance</a>
          </div>
          <div class="form-group"><label class="form-label">Payment Method *</label>
            <select name="method" id="payMethod" class="form-control"><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="form-group" id="refWrap" style="display:none;"><label class="form-label" id="refLabel">Reference *</label><input type="text" name="reference" class="form-control" maxlength="150"></div>
          <div class="form-group"><label class="form-label">Payment Date *</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
          <div class="form-group"><label class="form-label">Comment</label><textarea name="comment" class="form-control" rows="2"></textarea></div>
          <div class="form-group"><label class="form-label">Receipt / Proof (optional)</label><input type="file" name="receipt_file" class="form-control" accept="image/*,.pdf"><div class="form-hint">Bank slip or mobile money screenshot — image or PDF, up to 5MB.</div></div>
          <?php if ($finSettings['payment_approval'] === '1' && !$canApprove): ?><div class="alert alert-info" style="font-size:12.5px;">Payments you record wait for an approver before they count.</div><?php endif; ?>
          <button type="submit" class="btn btn-primary btn-block">Submit Payment</button>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title">Bills — <?= htmlspecialchars($year['name']) ?></div></div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Bill</th><th>Pay By</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Paid</th><th style="text-align:right;">Balance</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($bills as $b): [$bc, $bl] = $billBadge[$b['status']] ?? ['badge-muted', $b['status']]; $cur = $b['currency'] ?: $def; ?>
                <tr>
                  <td class="fw-600"><?= htmlspecialchars($b['label']) ?></td>
                  <td style="font-size:12px;color:var(--text-muted);"><?= $b['due_date'] ? date('M d, Y', strtotime($b['due_date'])) : '—' ?></td>
                  <td style="text-align:right;"><?= Finance::money((float)$b['amount_due'] - (float)$b['discount'], $cur) ?></td>
                  <td style="text-align:right;"><?= Finance::money($b['amount_paid'], $cur) ?></td>
                  <td style="text-align:right;<?= $b['balance'] > 0.005 && $b['status'] !== 'waived' ? 'color:var(--danger);font-weight:600;' : '' ?>"><?= $b['status'] === 'waived' ? '—' : Finance::money(max(0, $b['balance']), $cur) ?></td>
                  <td><span class="badge <?= $bc ?>"><?= $bl ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$bills): ?><tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:20px;">No bills for this year.</td></tr><?php endif; ?>
            </tbody>
            <?php if (!empty($totals)): ?>
              <tfoot>
                <?php foreach ($totals as $cur => $t): ?>
                  <tr style="font-weight:700;"><td colspan="2">Total (<?= $cur ?>)</td><td style="text-align:right;"><?= Finance::money($t['billed'], $cur) ?></td><td style="text-align:right;"><?= Finance::money($t['paid'], $cur) ?></td><td style="text-align:right;color:<?= $t['balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><?= Finance::money($t['balance'], $cur) ?></td><td></td></tr>
                <?php endforeach; ?>
              </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
          <div class="card-title">Payment History</div>
          <?php if (array_filter($payments, fn($p) => $p['status'] === 'active')): ?>
            <a href="<?= $base ?>/fees-payment/receipts?student=<?= $student['id'] ?>&year=<?= $year['id'] ?>" target="_blank" class="btn btn-sm btn-success">🖨 All Receipts</a>
          <?php endif; ?>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>ID</th><th>Date</th><th>Bill</th><th>Method</th><th style="text-align:right;">Amount</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($payments as $p): [$pc, $pl] = $payBadge[$p['status']] ?? ['badge-muted', $p['status']]; ?>
                <tr style="<?= in_array($p['status'], ['cancelled', 'rejected'], true) ? 'opacity:.55;' : '' ?>">
                  <td style="font-size:12px;">#<?= $p['id'] ?></td>
                  <td style="font-size:12.5px;"><?= date('M d, Y', strtotime($p['payment_date'] ?: $p['paid_at'])) ?></td>
                  <td><?= htmlspecialchars($p['label']) ?><?= $p['is_arrears'] ? ' <span class="badge badge-info">Arrears</span>' : '' ?>
                    <div style="font-size:11px;color:var(--text-muted);">by <?= htmlspecialchars($p['received_by_name'] ?? '—') ?><?= $p['reference'] ? ' · Ref ' . htmlspecialchars($p['reference']) : '' ?><?= $p['cancel_reason'] ? ' · ' . htmlspecialchars($p['cancel_reason']) : '' ?></div></td>
                  <td style="font-size:12.5px;"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$p['method']] ?? ucfirst((string)$p['method'])) ?></td>
                  <td style="text-align:right;font-weight:600;"><?= Finance::money($p['amount'], $p['currency'] ?: $def) ?></td>
                  <td><span class="badge <?= $pc ?>"><?= $pl ?></span></td>
                  <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                      <?php if ($p['status'] === 'active'): ?>
                        <a href="<?= $base ?>/payments/<?= $p['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-outline">Receipt</a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="cancelPayment(<?= $p['id'] ?>, '<?= htmlspecialchars(Finance::money($p['amount'], $p['currency'] ?: $def), ENT_QUOTES) ?>')">Cancel</button>
                      <?php endif; ?>
                      <?php if (!empty($p['receipt_file'])): ?><a href="<?= htmlspecialchars($p['receipt_file']) ?>" target="_blank" class="btn btn-sm btn-outline" title="Uploaded proof">📎</a><?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$payments): ?><tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px;">No payments yet.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="cancelModal">
    <div class="modal">
      <div class="modal-header"><div class="modal-title">Cancel Payment</div><button type="button" class="modal-close" onclick="document.getElementById('cancelModal').classList.remove('open')">&times;</button></div>
      <form method="POST" id="cancelForm">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="back" value="/school/finance/fees-payment?student=<?= $student['id'] ?>&year=<?= $year['id'] ?>">
        <div class="modal-body">
          <p style="font-size:13px;margin-bottom:12px;" id="cancelText"></p>
          <div class="form-group"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="2" required placeholder="e.g. Recorded against the wrong student"></textarea></div>
          <div class="form-hint">The payment stays on record as cancelled; the amount becomes owed again.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('cancelModal').classList.remove('open')">Keep Payment</button><button type="submit" class="btn btn-danger">Cancel Payment</button></div>
      </form>
    </div>
  </div>
<?php elseif (!$year): ?>
  <div class="alert alert-warning">Create an academic year first.</div>
<?php endif; ?>

<style>@media (max-width: 900px) { .fp-grid { grid-template-columns: 1fr !important; } }</style>
<script>
(function () {
  const pick = document.getElementById('payPick');
  const opts = [...document.querySelectorAll('#payStudents option')];
  pick && pick.addEventListener('input', () => {
    const o = opts.find(o => o.value === pick.value);
    document.getElementById('payStudentId').value = o ? o.dataset.id : '';
    if (o) document.getElementById('pickForm').submit();
  });
  const sel = document.getElementById('billSelect');
  if (!sel) return;
  const fmt = (n, c) => (c === 'USD' ? 'US$ ' : 'L$ ') + Number(n).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  sel.addEventListener('change', () => {
    const o = sel.selectedOptions[0];
    if (!o || !o.value) { document.getElementById('billHint').textContent = ''; return; }
    document.getElementById('curTag').textContent = o.dataset.cur;
    document.getElementById('payAmount').max = o.dataset.bal;
    document.getElementById('billHint').textContent = 'Total ' + fmt(o.dataset.total, o.dataset.cur) + ' · Paid ' + fmt(o.dataset.paid, o.dataset.cur) + ' · Balance ' + fmt(o.dataset.bal, o.dataset.cur);
  });
  document.getElementById('payFull').addEventListener('click', () => {
    const o = sel.selectedOptions[0];
    if (o && o.value) document.getElementById('payAmount').value = Number(o.dataset.bal).toFixed(2);
  });
  const method = document.getElementById('payMethod');
  const labels = { cheque: 'Cheque Number *', bank: 'Bank Slip Number *', pos: 'Transaction Number *', mobile: 'Transaction Number *' };
  method.addEventListener('change', () => {
    const l = labels[method.value];
    document.getElementById('refWrap').style.display = l ? '' : 'none';
    if (l) document.getElementById('refLabel').textContent = l;
  });
})();
function cancelPayment(id, amount) {
  document.getElementById('cancelForm').action = '<?= $base ?>/payments/' + id + '/cancel';
  document.getElementById('cancelText').textContent = 'Cancel payment #' + id + ' of ' + amount + '?';
  document.getElementById('cancelModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
