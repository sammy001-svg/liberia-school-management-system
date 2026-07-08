<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
    <a href="<?= $cfg['url'] ?>/reseller/schools">My Schools</a>
    <span>/</span>
    <span><?= htmlspecialchars($school['name']) ?></span>
</div>

<div class="page-header">
    <div>
        <div class="page-header-title"><?= htmlspecialchars($school['name']) ?></div>
        <div class="page-header-sub"><?= ucfirst(str_replace('_', ' ', $school['institution_type'])) ?> · <?= htmlspecialchars($school['country'] ?? 'Global') ?></div>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="<?= $cfg['url'] ?>/reseller/schools/<?= $school['id'] ?>/edit" class="btn btn-secondary">Edit Details</a>
        <button class="btn btn-primary">Login as Admin</button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Institution Info</div></div>
        <div class="card-body">
            <div style="margin-bottom:15px;">
                <label class="stat-label">Email</label>
                <div class="fw-600"><?= htmlspecialchars($school['email'] ?? '—') ?></div>
            </div>
            <div style="margin-bottom:15px;">
                <label class="stat-label">Phone</label>
                <div class="fw-600"><?= htmlspecialchars($school['phone'] ?? '—') ?></div>
            </div>
            <div style="margin-bottom:15px;">
                <label class="stat-label">Domain</label>
                <div class="fw-600"><?= htmlspecialchars($school['domain'] ?? $school['slug'].'.'.$_SERVER['HTTP_HOST']) ?></div>
            </div>
            <div style="margin-bottom:15px;">
                <label class="stat-label">Status</label>
                <div><span class="badge badge-<?= $school['status']==='active'?'success':'warning' ?>"><?= ucfirst($school['status']) ?></span></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">Subscription Details</div></div>
        <div class="card-body">
            <!-- Simplified for now -->
            <div style="padding:20px;background:rgba(255,255,255,0.03);border-radius:12px;border:1px solid var(--border-color);">
                <div class="fw-600" style="font-size:18px;margin-bottom:5px;">Standard Plan</div>
                <div class="text-muted" style="font-size:13px;margin-bottom:15px;">Expires on Dec 31, 2026</div>
                <div style="display:flex;gap:20px;">
                    <div>
                        <div class="stat-label">Max Students</div>
                        <div class="fw-600">500</div>
                    </div>
                    <div>
                        <div class="stat-label">Max Teachers</div>
                        <div class="fw-600">50</div>
                    </div>
                </div>
            </div>
            <div style="margin-top:20px;">
                <button class="btn btn-sm btn-outline">Renew Subscription</button>
                <button class="btn btn-sm btn-outline">Change Plan</button>
            </div>
        </div>
    </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
