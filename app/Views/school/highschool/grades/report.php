<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></a><span>/</span><span>Grade Report</span></div>
<div class="page-header"><div class="page-header-title">Grade Report — <?= htmlspecialchars($student['name']) ?></div></div>
<div class="card">
  <div class="table-wrapper"><table>
    <thead><tr><th>Subject</th><th>Exam</th><th>Score</th><th>Total</th><th>Percentage</th><th>Grade</th><th>GPA</th></tr></thead>
    <tbody>
      <?php foreach($grades as $g):
        $pct = $g['total_marks'] > 0 ? round($g['marks_obtained']/$g['total_marks']*100,1) : 0;
      ?>
      <tr>
        <td class="fw-600"><?= htmlspecialchars($g['course_name']??'—') ?></td>
        <td><?= htmlspecialchars($g['exam_name']??'—') ?></td>
        <td><?= $g['marks_obtained'] ?></td>
        <td><?= $g['total_marks'] ?></td>
        <td><?= $pct ?>%</td>
        <td><span class="badge badge-<?= $g['grade_letter']==='F'?'danger':($pct>=70?'success':'warning') ?>"><?= $g['grade_letter'] ?></span></td>
        <td><?= $g['gpa_points'] ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($grades)): ?><tr><td colspan="7" class="text-center text-muted" style="padding:32px">No grades recorded yet.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
