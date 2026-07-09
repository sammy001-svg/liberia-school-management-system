<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Finance Overview</div>
    <div class="page-header-sub">School-wide billing and collection summary</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/finance/invoices/create" class="btn btn-primary">+ Create Invoice</a>
</div>
<div class="stat-grid" style="grid-template-columns:repeat(5,1fr);">
  <div class="stat-card" style="--card-color:var(--primary)"><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['total_due'],2) ?></div><div class="stat-label">Total Billed</div></div>
  <div class="stat-card" style="--card-color:var(--success)"><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['total_paid'],2) ?></div><div class="stat-label">Total Collected</div></div>
  <div class="stat-card" style="--card-color:var(--danger)"><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['total_expenses'],2) ?></div><div class="stat-label">Total Expenses</div></div>
  <?php $net = $stats['total_paid'] - $stats['total_expenses']; ?>
  <div class="stat-card" style="--card-color:<?= $net>=0?'var(--success)':'var(--danger)' ?>"><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($net,2) ?></div><div class="stat-label">Net Income</div></div>
  <div class="stat-card" style="--card-color:var(--warning)"><div class="stat-value"><?= $stats['unpaid'] ?></div><div class="stat-label">Unpaid Invoices</div></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card-body">
    <?php $collectionRate = $stats['total_due'] > 0 ? min(100, round($stats['total_paid'] / $stats['total_due'] * 100)) : 0; ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <div class="fw-600" style="font-size:13px;">Collection Rate</div>
      <div class="fw-600" style="font-size:13px;"><?= $collectionRate ?>%</div>
    </div>
    <div class="progress-track"><div class="progress-fill" style="width:<?= $collectionRate ?>%;--card-color:<?= $collectionRate>=75?'var(--success)':($collectionRate>=40?'var(--warning)':'var(--danger)') ?>;"></div></div>
  </div>
</div>

<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
  <a href="<?= $cfg['url'] ?>/school/finance/invoices" class="btn btn-secondary">🧾 All Invoices</a>
  <a href="<?= $cfg['url'] ?>/school/finance/payments" class="btn btn-secondary">💳 All Payments</a>
  <a href="<?= $cfg['url'] ?>/school/finance/collection" class="btn btn-secondary">📥 Manage Collection</a>
  <a href="<?= $cfg['url'] ?>/school/finance/expenses" class="btn btn-secondary">🧮 Expenses</a>
  <a href="<?= $cfg['url'] ?>/school/finance/fees" class="btn btn-secondary">📋 Fee Structures</a>
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
          <td><span class="badge badge-info"><?= ucfirst($p['method']) ?></span></td>
          <td style="font-size:12px"><?= htmlspecialchars($p['reference']??'—') ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y', strtotime($p['paid_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($recentPayments)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">💳</div>
            <div class="empty-state-text">No payments recorded yet.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
