<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">My Schools</div><a href="<?= $cfg['url'] ?>/reseller/schools/create" class="btn btn-primary">+ Add School</a></div>
<div class="card">
  <div class="table-wrapper"><table>
    <thead><tr><th>School</th><th>Type</th><th>Plan</th><th>Country</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach($schools as $s): ?>
      <tr>
        <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
        <td><span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$s['institution_type'])) ?></span></td>
        <td><?= htmlspecialchars($s['plan_name']??'—') ?></td>
        <td><?= htmlspecialchars($s['country']??'—') ?></td>
        <td><span class="badge badge-<?= $s['status']==='active'?'success':'warning' ?>"><?= ucfirst($s['status']) ?></span></td>
        <td><a href="<?= $cfg['url'] ?>/reseller/schools/<?= $s['id'] ?>" class="btn btn-sm btn-outline">View</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($schools)): ?><tr><td colspan="6" class="text-center text-muted" style="padding:40px">No schools yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
