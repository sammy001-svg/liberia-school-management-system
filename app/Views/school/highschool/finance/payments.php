<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Payments</div>
    <a href="<?= $cfg['url'] ?>/school/finance/invoices" class="btn btn-primary">Make Payment</a>
</div>

<div class="card">
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
                <tr>
                    <td colspan="7" class="text-center text-muted" style="padding:40px;">No payments recorded yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
