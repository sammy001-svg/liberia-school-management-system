<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">My Children</div>
    <p class="text-muted">Academic overview for your children enrolled at this institution.</p>
</div>

<?php if (!empty($hasArrears)): ?>
<div class="alert alert-warning" style="margin-bottom:20px;">
    You have an overdue balance of <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($overdueTotal, 2) ?> — child details are restricted until it's paid.
    <a href="<?= $cfg['url'] ?>/parent/finance">View &amp; Pay Invoices &rarr;</a>
</div>
<?php endif; ?>

<div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));">
    <?php foreach($children as $c): ?>
    <div class="card">
        <div class="card-body" style="display:flex; align-items:center; gap:20px;">
            <div class="avatar avatar-lg"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
            <div style="flex:1;">
                <div class="fw-700" style="font-size:18px;"><?= htmlspecialchars($c['name']) ?></div>
                <div class="text-muted"><?= htmlspecialchars($c['class_name'] ?? 'Not Assigned') ?></div>
                <div style="margin-top:12px;">
                    <a href="<?= $cfg['url'] ?>/parent/student/<?= $c['id'] ?>" class="btn btn-sm btn-primary">View Full Profile</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($children)): ?>
    <div class="card" style="grid-column: 1 / -1; padding:60px; text-align:center;">
        <div class="text-muted">No children records linked to your account. Please contact the school office.</div>
    </div>
    <?php endif; ?>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
