<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Classes</div><a href="<?= $cfg['url'] ?>/school/classes/create" class="btn btn-primary">+ Add Class</a></div>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Class</th><th>Grade</th><th>Section</th><th>Class Teacher</th><th>Students</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($classes as $c): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['grade_level']) ?></td>
          <td><?= htmlspecialchars($c['section']??'—') ?></td>
          <td><?= htmlspecialchars($c['teacher_name']??'—') ?></td>
          <td><span class="badge badge-info"><?= $c['student_count'] ?> students</span></td>
          <td><a href="<?= $cfg['url'] ?>/school/classes/<?= $c['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($classes)): ?><tr><td colspan="6" class="text-center text-muted" style="padding:32px">No classes. <a href="<?= $cfg['url'] ?>/school/classes/create">Create one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
