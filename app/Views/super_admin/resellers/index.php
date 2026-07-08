<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Resellers</div>
    <div class="page-header-sub">Manage your reseller network</div>
  </div>
  <a href="<?= $cfg['url'] ?>/admin/resellers/create" class="btn btn-primary">+ Add Reseller</a>
</div>

<!-- SEARCH -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email…" class="form-control" style="max-width:320px;">
    <button type="submit" class="btn btn-secondary">Search</button>
    <?php if($search): ?><a href="<?= $cfg['url'] ?>/admin/resellers" class="btn btn-outline">Clear</a><?php endif; ?>
  </div>
</form>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Reseller</th><th>Domain</th><th>Schools</th><th>Commission</th><th>Status</th><th>Created</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($resellers as $r): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar" style="background:linear-gradient(135deg,<?= htmlspecialchars($r['primary_color']??'#10B981') ?>,<?= htmlspecialchars($r['secondary_color']??'#059669') ?>)">
                <?= strtoupper(substr($r['name'],0,1)) ?>
              </div>
              <div>
                <div class="fw-600"><?= htmlspecialchars($r['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($r['email']) ?></div>
              </div>
            </div>
          </td>
          <td><?= htmlspecialchars($r['domain'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= $r['school_count'] ?> schools</span></td>
          <td><?= $r['commission_rate'] ?>%</td>
          <td><span class="badge badge-<?= $r['status']==='active'?'success':($r['status']==='suspended'?'danger':'warning') ?>"><?= ucfirst($r['status']) ?></span></td>
          <td style="color:var(--text-muted);font-size:12px"><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/admin/resellers/<?= $r['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/admin/resellers/<?= $r['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/admin/resellers/<?= $r['id'] ?>/delete" onsubmit="return confirm('Delete this reseller?')">
                <button class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($resellers)): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">No resellers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
