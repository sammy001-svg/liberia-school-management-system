<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/online-exams">Online Exams</a><span>/</span><span><?= htmlspecialchars($exam['title']) ?></span></div>

<div class="page-header">
  <div>
    <div class="page-header-title">Results — <?= htmlspecialchars($exam['title']) ?></div>
    <div class="page-header-sub"><?= htmlspecialchars($exam['class_name'] ?? '—') ?></div>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Class Size</div>
    <div class="stat-value"><?= (int)$stats['total'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Attempted</div>
    <div class="stat-value"><?= (int)$stats['attempted'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Average Score</div>
    <div class="stat-value"><?= $stats['avgScore'] !== null ? $stats['avgScore'].'%' : '—' ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Student Results (<?= count($roster) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Admission No</th><th>Status</th><th>Score</th><th>Submitted</th></tr></thead>
      <tbody>
        <?php foreach($roster as $r): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($r['student_name']) ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['admission_no']) ?></td>
          <td>
            <?php if($r['attempt_status']==='submitted'): ?><span class="badge badge-success">Submitted</span>
            <?php elseif($r['attempt_status']==='in_progress'): ?><span class="badge badge-warning">In Progress</span>
            <?php else: ?><span class="badge badge-muted">Not Attempted</span><?php endif; ?>
          </td>
          <td>
            <?php if($r['score'] !== null): ?>
              <?php $pct = $r['total_marks']>0 ? round($r['score']/$r['total_marks']*100,1) : 0; ?>
              <span class="badge badge-<?= $pct>=70?'success':($pct>=50?'warning':'danger') ?>"><?= number_format($r['score'],1) ?> / <?= number_format($r['total_marks'],1) ?> (<?= $pct ?>%)</span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="text-muted"><?= $r['submitted_at'] ? date('M d, Y H:i', strtotime($r['submitted_at'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($roster)): ?>
        <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">👥</div><div class="empty-state-text">No students in this class yet.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
