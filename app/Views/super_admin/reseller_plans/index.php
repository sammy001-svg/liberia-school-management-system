<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div class="page-header-title">Reseller Packages</div>
  <div class="page-header-actions">
    <a href="<?= $cfg['url'] ?>/admin/reseller-plans/create" class="btn btn-primary">Create New Package</a>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0;">
    <table class="table">
      <thead>
        <tr>
          <th>Package Name</th>
          <th>Price</th>
          <th>School Limit</th>
          <th>Status</th>
          <th style="text-align:right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($plans as $p): ?>
        <tr>
          <td style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></td>
          <td>Ksh <?= number_format($p['price'], 2) ?></td>
          <td><strong><?= $p['max_schools'] ?> Schools</strong></td>
          <td>
            <span class="badge" style="background:<?= $p['is_active'] ? '#D1FAE5;color:#065F46;' : '#FEE2E2;color:#991B1B;' ?>">
              <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
            </span>
          </td>
          <td style="text-align:right;">
            <a href="<?= $cfg['url'] ?>/admin/reseller-plans/<?= $p['id'] ?>/edit" class="btn btn-secondary btn-sm">Edit</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
