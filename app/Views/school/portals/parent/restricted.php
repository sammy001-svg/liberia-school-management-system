<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title"><?= htmlspecialchars($student['name'] ?? 'Child Profile') ?></div>
    <a href="<?= $cfg['url'] ?>/parent/dashboard" class="btn btn-secondary">Back to My Kids</a>
</div>

<div class="card" style="max-width:560px;margin:0 auto;">
    <div class="card-body" style="text-align:center;padding:48px 32px;">
        <div style="font-size:40px;margin-bottom:12px;">🔒</div>
        <div class="fw-700" style="font-size:18px;margin-bottom:8px;">Access Temporarily Restricted</div>
        <p class="text-muted" style="margin-bottom:20px;">
            There's an outstanding balance on your account. Academic details for your children are restricted until it's cleared.
        </p>
        <div class="stat-card" style="display:inline-block;--card-color: var(--danger);margin-bottom:24px;">
            <div class="stat-label">Amount Overdue</div>
            <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($overdueTotal, 2) ?></div>
        </div>
        <div>
            <a href="<?= $cfg['url'] ?>/parent/finance" class="btn btn-primary btn-lg">View &amp; Pay Invoices</a>
        </div>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
