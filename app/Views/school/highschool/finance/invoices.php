<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Invoices</div><a href="<?= $cfg['url'] ?>/school/finance/invoices/create" class="btn btn-primary">+ Create Invoice</a></div>
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
        <?php if(empty($invoices)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:40px">No invoices found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
