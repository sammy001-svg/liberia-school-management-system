<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div>
        <div class="page-header-title">Payments</div>
        <div class="page-header-sub">All payments recorded against student invoices</div>
    </div>
    <a href="<?= $cfg['url'] ?>/school/finance/invoices?status=unpaid" class="btn btn-primary">+ Record a Payment</a>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Payments</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Total Collected</div>
    <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['totalAmount'] ?? 0,2) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Recorded Today</div>
    <div class="stat-value"><?= (int)($stats['today'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">All Payments (<?= count($payments) ?>)</div></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Ref No</th>
                    <th>Invoice</th>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payments as $p): ?>
                <tr>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($p['reference'] ?? '—') ?></td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($p['invoice_no'] ?? '—') ?></td>
                    <td class="fw-600"><?= htmlspecialchars($p['student_name'] ?? '—') ?></td>
                    <td class="text-success fw-600"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($p['amount'], 2) ?></td>
                    <td><span class="badge badge-info"><?= strtoupper($p['method'] ?? 'Manual') ?></span></td>
                    <td style="font-size:12px;"><?= date('M d, Y', strtotime($p['paid_at'])) ?></td>
                    <td><span class="badge badge-success">Completed</span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($payments)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon">💳</div>
                        <div class="empty-state-text">No payments recorded yet. <a href="<?= $cfg['url'] ?>/school/finance/invoices?status=unpaid">Record one</a></div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
