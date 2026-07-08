<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Grades & Exams</div><a href="<?= $cfg['url'] ?>/school/grades/enter" class="btn btn-primary">Enter Grades</a></div>
<div class="card">
  <div class="table-wrapper"><table>
    <thead><tr><th>Exam</th><th>Class</th><th>Date</th><th>Total Marks</th><th>Pass Marks</th></tr></thead>
    <tbody>
      <?php foreach($exams as $e): ?>
      <tr><td class="fw-600"><?= htmlspecialchars($e['name']) ?></td><td><?= htmlspecialchars($e['class_name']??'—') ?></td><td><?= $e['exam_date']?date('M d, Y',strtotime($e['exam_date'])):'—' ?></td><td><?= $e['total_marks'] ?></td><td><?= $e['pass_marks'] ?></td></tr>
      <?php endforeach; ?>
      <?php if(empty($exams)): ?><tr><td colspan="5" class="text-center text-muted" style="padding:32px">No exams created yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
