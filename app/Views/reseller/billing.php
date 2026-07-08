<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Billing & Subscriptions</div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(300px, 1fr));gap:20px;margin-bottom:30px;">
    <div class="card">
        <div class="card-body">
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value">$<?= number_format(array_sum(array_column($subscriptions, 'amount_paid')), 2) ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="stat-label">Active Subscriptions</div>
            <div class="stat-value"><?= count(array_filter($subscriptions, fn($s) => $s['status'] === 'active')) ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Subscription History</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>School</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($subscriptions as $sub): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($sub['school_name']) ?></td>
                    <td><?= htmlspecialchars($sub['plan_name']) ?></td>
                    <td>$<?= number_format($sub['amount_paid'], 2) ?></td>
                    <td><?= date('M d, Y', strtotime($sub['start_date'])) ?></td>
                    <td><?= date('M d, Y', strtotime($sub['end_date'])) ?></td>
                    <td>
                        <span class="badge badge-<?= $sub['status'] === 'active' ? 'success' : 'warning' ?>">
                            <?= ucfirst($sub['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($subscriptions)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted" style="padding:40px;">No billing records found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
