<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div><div class="page-header-title">Reseller Dashboard</div><div class="page-header-sub"><?= date('l, F j, Y') ?></div></div>
</div>
<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--primary)"><div class="stat-value"><?= $stats['schools'] ?></div><div class="stat-label">My Schools</div></div>
  <div class="stat-card" style="--card-color:var(--accent)"><div class="stat-value"><?= number_format($stats['students']) ?></div><div class="stat-label">Total Students</div></div>
  <div class="stat-card" style="--card-color:var(--success)"><div class="stat-value">$<?= number_format($stats['revenue'],2) ?></div><div class="stat-label">Total Revenue</div></div>
</div>
<div class="card">
  <div class="card-header"><div class="card-title">My Schools</div><a href="<?= $cfg['url'] ?>/reseller/schools/create" class="btn btn-sm btn-primary">+ Add School</a></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>School</th><th>Type</th><th>Plan</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($schools as $s): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
          <td><span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$s['institution_type'])) ?></span></td>
          <td><?= htmlspecialchars($s['plan_name']??'—') ?></td>
          <td><span class="badge badge-<?= $s['status']==='active'?'success':'warning' ?>"><?= ucfirst($s['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($schools)): ?><tr><td colspan="4" class="text-center text-muted" style="padding:32px">No schools yet. <a href="<?= $cfg['url'] ?>/reseller/schools/create">Add one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
