<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div class="page-header-title">Finance Overview</div>
  <a href="<?= $cfg['url'] ?>/school/finance/invoices/create" class="btn btn-primary">+ Create Invoice</a>
</div>
<div class="stat-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card" style="--card-color:var(--primary)"><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['total_due'],2) ?></div><div class="stat-label">Total Billed</div></div>
  <div class="stat-card" style="--card-color:var(--success)"><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['total_paid'],2) ?></div><div class="stat-label">Total Collected</div></div>
  <div class="stat-card" style="--card-color:var(--danger)"><div class="stat-value"><?= $stats['unpaid'] ?></div><div class="stat-label">Unpaid Invoices</div></div>
  <div class="stat-card" style="--card-color:var(--success)"><div class="stat-value"><?= $stats['paid'] ?></div><div class="stat-label">Paid Invoices</div></div>
</div>
<div style="display:flex;gap:12px;margin-bottom:20px;">
  <a href="<?= $cfg['url'] ?>/school/finance/invoices" class="btn btn-secondary">All Invoices</a>
  <a href="<?= $cfg['url'] ?>/school/finance/payments" class="btn btn-secondary">All Payments</a>
</div>
<div class="card">
  <div class="card-header"><div class="card-title">Recent Payments</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Reference</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach($recentPayments as $p): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($p['student_name']) ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($p['invoice_no']) ?></td>
          <td class="text-success fw-600"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($p['amount'],2) ?></td>
          <td><?= ucfirst($p['method']) ?></td>
          <td style="font-size:12px"><?= htmlspecialchars($p['reference']??'—') ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($p['paid_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($recentPayments)): ?><tr><td colspan="6" class="text-center text-muted" style="padding:32px">No payments yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
