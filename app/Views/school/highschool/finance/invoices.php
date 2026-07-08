<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Invoices</div>
    <div class="page-header-sub">Manage student billing and fee invoices</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('generateInvoiceModal').classList.add('open')">+ Generate Invoice</button>
</div>

<form method="GET" style="margin-bottom:20px;display:flex;gap:12px;align-items:center;">
  <label style="color:var(--text-muted);font-size:13px">Status:</label>
  <?php foreach(['' => 'All','unpaid'=>'Unpaid','partial'=>'Partial','paid'=>'Paid','overdue'=>'Overdue'] as $val=>$lbl): ?>
    <a href="?status=<?= $val ?>" class="btn btn-sm <?= $status===$val?'btn-primary':'btn-outline' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</form>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Invoice No</th><th>Student</th><th>Class</th><th>Amount</th><th>Paid</th><th>Due Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($invoices as $inv): ?>
        <tr>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($inv['invoice_no']) ?></td>
          <td class="fw-600"><?= htmlspecialchars($inv['student_name']) ?></td>
          <td><?= htmlspecialchars($inv['class_name']??'—') ?></td>
          <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($inv['amount_due'],2) ?></td>
          <td class="text-success"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($inv['amount_paid'],2) ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= $inv['due_date']?date('M d, Y',strtotime($inv['due_date'])):'—' ?></td>
          <td><span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='overdue'?'danger':'warning') ?>"><?= ucfirst($inv['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($invoices)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:40px">No invoices found. <a href="javascript:void(0)" onclick="document.getElementById('generateInvoiceModal').classList.add('open')">Generate one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

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

<script>function loadAmount(sel){const opt=sel.options[sel.selectedIndex];const amt=opt.dataset.amount;if(amt) document.getElementById('amountInput').value=amt;}</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
