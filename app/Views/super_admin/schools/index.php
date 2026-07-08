<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Schools</div>
    <div class="page-header-sub">All registered institutions</div>
  </div>
  <a href="<?= $cfg['url'] ?>/admin/schools/create" class="btn btn-primary">+ Add School</a>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search school…" class="form-control" style="max-width:260px;">
    <select name="type" class="form-control" style="max-width:200px;">
      <option value="">All Types</option>
      <option value="high_school" <?= $type==='high_school'?'selected':'' ?>>High School</option>
      <option value="university"  <?= $type==='university'?'selected':'' ?>>University</option>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/admin/schools" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>School</th><th>Type</th><th>Reseller</th><th>Plan</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($schools as $s): ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($s['country']??'') ?></div>
          </td>
          <td><span class="badge badge-<?= $s['institution_type']==='university'?'primary':'info' ?>"><?= ucfirst(str_replace('_',' ',$s['institution_type'])) ?></span></td>
          <td><?= htmlspecialchars($s['reseller_name']??'Direct') ?></td>
          <td><?= htmlspecialchars($s['plan_name']??'—') ?></td>
          <td><span class="badge badge-<?= $s['status']==='active'?'success':($s['status']==='suspended'?'danger':'warning') ?>"><?= ucfirst($s['status']) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= date('M d, Y',strtotime($s['created_at'])) ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/admin/schools/<?= $s['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/admin/schools/<?= $s['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/admin/schools/<?= $s['id'] ?>/delete" onsubmit="return confirm('Delete this school?')">
                <button class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($schools)): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">No schools found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
