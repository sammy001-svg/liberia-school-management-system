<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div class="page-header-title">Subscription Plans</div>
  <div class="page-header-actions">
    <a href="<?= $cfg['url'] ?>/admin/plans/create" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:18px;height:18px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
      Create New Plan
    </a>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0;">
    <table class="table">
      <thead>
        <tr>
          <th>Plan Name</th>
          <th>Owner</th>
          <th>Monthly Price</th>
          <th>Yearly Price</th>
          <th>Limits</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($plans as $p): ?>
        <tr>
          <td>
            <div style="font-weight:600;color:var(--text-dark);"><?= htmlspecialchars($p['name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($p['description'] ?? '') ?></div>
          </td>
          <td>
            <span class="badge" style="background:<?= $p['billing_owner'] === 'platform' ? '#E0E7FF;color:#4338CA;' : '#FEF3C7;color:#92400E;' ?>">
              <?= ucfirst($p['billing_owner']) ?>
            </span>
          </td>
          <td>Ksh <?= number_format($p['price_monthly'], 2) ?></td>
          <td>Ksh <?= number_format($p['price_yearly'], 2) ?></td>
          <td>
            <div style="font-size:12px;">
              Students: <strong><?= $p['max_students'] ?></strong><br>
              Teachers: <strong><?= $p['max_teachers'] ?></strong>
            </div>
          </td>
          <td>
            <span class="badge" style="background:<?= $p['is_active'] ? '#D1FAE5;color:#065F46;' : '#FEE2E2;color:#991B1B;' ?>">
              <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td style="text-align:right;">
            <a href="<?= $cfg['url'] ?>/admin/plans/<?= $p['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($plans)): ?>
        <tr>
          <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No plans found. Create your first plan to get started.</td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
