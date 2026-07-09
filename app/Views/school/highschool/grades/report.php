<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>"><?= htmlspecialchars($student['name']) ?></a><span>/</span><span>Grade Report</span></div>
<div class="page-header"><div class="page-header-title">Grade Report — <?= htmlspecialchars($student['name']) ?></div></div>

<?php if(!empty($grades)):
  $totalObtained = 0; $totalPossible = 0; $gpaSum = 0; $failCount = 0;
  foreach($grades as $g) {
    $totalObtained += $g['marks_obtained']; $totalPossible += $g['total_marks']; $gpaSum += $g['gpa_points'];
    if ($g['grade_letter'] === 'F') $failCount++;
  }
  $avgPct = $totalPossible > 0 ? round($totalObtained/$totalPossible*100,1) : 0;
  $avgGpa = count($grades) > 0 ? round($gpaSum/count($grades),2) : 0;
?>
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Subjects Graded</div>
    <div class="stat-value"><?= count($grades) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Overall Average</div>
    <div class="stat-value"><?= $avgPct ?>%</div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Average GPA</div>
    <div class="stat-value"><?= $avgGpa ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Subjects Failed</div>
    <div class="stat-value"><?= $failCount ?></div>
  </div>
</div>
<?php endif; ?>

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
      <?php if(empty($grades)): ?>
      <tr><td colspan="7">
        <div class="empty-state">
          <div class="empty-state-icon">📊</div>
          <div class="empty-state-text">No grades recorded yet.</div>
        </div>
      </td></tr>
      <?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
