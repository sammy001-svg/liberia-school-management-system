<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Invoices</div>
    <div class="page-header-sub">Manage student billing and fee invoices</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('generateInvoiceModal').classList.add('open')">+ Generate Invoice</button>
</div>

<?php if(!empty($filteredStudent)): ?>
<div class="alert alert-info" style="display:flex;justify-content:space-between;align-items:center;">
  <span>Showing invoices for <strong><?= htmlspecialchars($filteredStudent['name']) ?></strong> only.</span>
  <a href="<?= $cfg['url'] ?>/school/finance/invoices<?= $status ? '?status='.$status : '' ?>" class="btn btn-sm btn-outline">Clear Filter</a>
</div>
<?php endif; ?>

<form method="GET" style="margin-bottom:20px;display:flex;gap:12px;align-items:center;">
  <?php if($studentId): ?><input type="hidden" name="student_id" value="<?= htmlspecialchars($studentId) ?>"><?php endif; ?>
  <label style="color:var(--text-muted);font-size:13px">Status:</label>
  <?php foreach(['' => 'All','unpaid'=>'Unpaid','partial'=>'Partial','paid'=>'Paid','overdue'=>'Overdue'] as $val=>$lbl): ?>
    <a href="?status=<?= $val ?><?= $studentId ? '&student_id='.$studentId : '' ?>" class="btn btn-sm <?= $status===$val?'btn-primary':'btn-outline' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</form>
<div class="card">
  <div class="card-header"><div class="card-title">Invoices (<?= $total ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Invoice No</th><th>Student</th><th>Class</th><th>Amount</th><th>Paid</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($invoices as $inv): ?>
        <?php $balance = $inv['amount_due'] - $inv['amount_paid']; ?>
        <tr>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($inv['invoice_no']) ?></td>
          <td class="fw-600"><?= htmlspecialchars($inv['student_name']) ?></td>
          <td><?= htmlspecialchars($inv['class_name']??'—') ?></td>
          <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($inv['amount_due'],2) ?></td>
          <td class="text-success"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($inv['amount_paid'],2) ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= $inv['due_date']?date('M d, Y',strtotime($inv['due_date'])):'—' ?></td>
          <td><span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='overdue'?'danger':'warning') ?>"><?= ucfirst($inv['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/finance/invoices/<?= $inv['id'] ?>/print" target="_blank" class="btn btn-sm btn-outline">Print</a>
              <?php if($inv['status']!=='paid' && $inv['status']!=='waived'): ?>
                <button type="button" class="btn btn-sm btn-primary" onclick="openPaymentModal(<?= $inv['id'] ?>,'<?= htmlspecialchars(addslashes($inv['invoice_no'])) ?>',<?= number_format($balance,2,'.','') ?>)">Record Payment</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($invoices)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🧾</div>
            <div class="empty-state-text">No invoices found. <a href="javascript:void(0)" onclick="document.getElementById('generateInvoiceModal').classList.add('open')">Generate one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<!-- Generate Invoice Modal -->
<div class="modal-overlay" id="generateInvoiceModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Generate Invoice</div>
      <button class="modal-close" onclick="document.getElementById('generateInvoiceModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/invoices/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Student &amp; Fee</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Student *</label>
            <select name="student_id" class="form-control" required>
              <option value="">— Select Student —</option>
              <?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Fee Type</label>
            <select name="fee_structure_id" class="form-control" onchange="loadAmount(this)">
              <option value="">— Manual Amount —</option>
              <?php foreach($feeStructs as $f): ?><option value="<?= $f['id'] ?>" data-amount="<?= $f['amount'] ?>"><?= htmlspecialchars($f['name']) ?> — <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($f['amount'],2) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-section-title">Amount &amp; Due Date</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount Due (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>) *</label>
            <input type="number" name="amount_due" id="amountInput" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Discount (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>)</label>
            <input type="number" name="discount" class="form-control" step="0.01" value="0">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Due Date</label>
          <input type="date" name="due_date" class="form-control">
        </div>

        <div class="modal-section-title">Notes</div>
        <div class="form-group">
          <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes for this invoice"></textarea>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('generateInvoiceModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate Invoice</button>
      </div>
    </form>
  </div>
</div>

<!-- Record Payment Modal -->
<div class="modal-overlay" id="recordPaymentModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Record Payment</div>
      <button class="modal-close" onclick="document.getElementById('recordPaymentModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/payments/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="invoice_id" id="paymentInvoiceId">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Invoice</label>
          <input type="text" id="paymentInvoiceLabel" class="form-control" disabled>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>) *</label>
            <input type="number" name="amount" id="paymentAmountInput" class="form-control" step="0.01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Method</label>
            <select name="method" class="form-control">
              <option value="cash">Cash</option>
              <option value="mpesa">M-Pesa</option>
              <option value="bank">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="online">Online</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reference No.</label>
          <input type="text" name="reference" class="form-control" placeholder="Transaction / receipt reference">
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('recordPaymentModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Payment</button>
      </div>
    </form>
  </div>
</div>

<script>
function loadAmount(sel){const opt=sel.options[sel.selectedIndex];const amt=opt.dataset.amount;if(amt) document.getElementById('amountInput').value=amt;}
function openPaymentModal(invoiceId, invoiceNo, balance){
  document.getElementById('paymentInvoiceId').value = invoiceId;
  document.getElementById('paymentInvoiceLabel').value = invoiceNo + ' — Balance: <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>' + balance.toFixed(2);
  document.getElementById('paymentAmountInput').value = balance;
  document.getElementById('recordPaymentModal').classList.add('open');
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
