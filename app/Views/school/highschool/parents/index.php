<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Parents</div><a href="<?= $cfg['url'] ?>/school/parents/create" class="btn btn-primary">+ Add Parent</a></div>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Parent</th><th>Phone</th><th>Occupation</th></tr></thead>
      <tbody>
        <?php foreach($parents as $p): ?>
        <tr>
          <td><div class="fw-600"><?= htmlspecialchars($p['name']) ?></div><div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($p['email']) ?></div></td>
          <td><?= htmlspecialchars($p['phone']??'—') ?></td>
          <td><?= htmlspecialchars($p['occupation']??'—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($parents)): ?><tr><td colspan="3" class="text-center text-muted" style="padding:32px">No parents registered yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
