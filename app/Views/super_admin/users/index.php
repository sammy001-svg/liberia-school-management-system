<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Platform Users</div></div>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>School</th><th>Status</th><th>Last Login</th></tr></thead>
      <tbody>
        <?php foreach($users as $u): ?>
        <tr>
          <td><div style="display:flex;align-items:center;gap:8px;"><div class="avatar" style="width:28px;height:28px;font-size:11px"><?= strtoupper(substr($u['name'],0,1)) ?></div><span class="fw-600"><?= htmlspecialchars($u['name']) ?></span></div></td>
          <td style="color:var(--text-muted);font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
          <td><span class="badge badge-primary"><?= htmlspecialchars($u['role_name']) ?></span></td>
          <td><?= htmlspecialchars($u['tenant_name']??'—') ?></td>
          <td><span class="badge badge-<?= $u['status']==='active'?'success':'danger' ?>"><?= ucfirst($u['status']) ?></span></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= $u['last_login']?date('M d, Y',strtotime($u['last_login'])):'Never' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
