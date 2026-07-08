<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Teachers</div><a href="<?= $cfg['url'] ?>/school/teachers/create" class="btn btn-primary">+ Add Teacher</a></div>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Teacher</th><th>Employee No</th><th>Class</th><th>Specialization</th><th>Phone</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($teachers as $t): ?>
        <tr>
          <td><div style="display:flex;align-items:center;gap:10px;"><div class="avatar"><?= strtoupper(substr($t['name'],0,1)) ?></div><div><div class="fw-600"><?= htmlspecialchars($t['name']) ?></div><div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($t['email']) ?></div></div></div></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($t['employee_no']) ?></td>
          <td><?= htmlspecialchars($t['class_name']??'—') ?></td>
          <td><?= htmlspecialchars($t['specialization']??'—') ?></td>
          <td><?= htmlspecialchars($t['phone']??'—') ?></td>
          <td><div style="display:flex;gap:6px;"><a href="<?= $cfg['url'] ?>/school/teachers/<?= $t['id'] ?>" class="btn btn-sm btn-outline">View</a><a href="<?= $cfg['url'] ?>/school/teachers/<?= $t['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a></div></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($teachers)): ?><tr><td colspan="6" class="text-center text-muted" style="padding:40px">No teachers yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
