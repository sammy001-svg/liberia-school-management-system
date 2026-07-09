<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/homework">Homework</a><span>/</span><span><?= htmlspecialchars($homework['title']) ?></span></div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($homework['title']) ?></div>
    <div class="page-header-sub">
      <?= htmlspecialchars($homework['class_name'] ?? '—') ?>
      <?= $homework['course_name'] ? ' · '.htmlspecialchars($homework['course_name']) : '' ?>
      · Due <?= date('M d, Y', strtotime($homework['due_date'])) ?> · Max Score <?= number_format($homework['max_score'],0) ?>
    </div>
  </div>
</div>

<?php if(!empty($homework['description'])): ?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-body"><?= nl2br(htmlspecialchars($homework['description'])) ?></div>
</div>
<?php endif; ?>

<?php if(!empty($homework['attachment_path'])): ?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;">
    <span>📎 <?= htmlspecialchars($homework['attachment_name'] ?? 'Attachment') ?></span>
    <a href="<?= htmlspecialchars($homework['attachment_path']) ?>" class="btn btn-sm btn-outline" download>Download</a>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">Submissions (<?= count($roster) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Submitted</th><th>Attachment</th><th>Score</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($roster as $r): ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($r['student_name']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($r['admission_no']) ?></div>
          </td>
          <td>
            <?php if($r['submission_id']): ?>
              <span class="badge badge-success">Submitted</span>
              <div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= date('M d, Y H:i', strtotime($r['submitted_at'])) ?></div>
              <?php if($r['submission_text']): ?><div style="font-size:12px;color:var(--text-light);margin-top:6px;max-width:280px;white-space:pre-wrap;"><?= htmlspecialchars(mb_strimwidth($r['submission_text'],0,200,'…')) ?></div><?php endif; ?>
            <?php else: ?>
              <span class="badge badge-muted">Not submitted</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if(!empty($r['attachment_path'])): ?>
              <a href="<?= htmlspecialchars($r['attachment_path']) ?>" class="btn btn-sm btn-outline" download><?= htmlspecialchars($r['attachment_name'] ?? 'File') ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if($r['score'] !== null): ?>
              <span class="badge badge-info"><?= number_format($r['score'],1) ?> / <?= number_format($homework['max_score'],0) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if($r['submission_id']): ?>
              <button type="button" class="btn btn-sm btn-secondary" onclick='openGradeModal(<?= json_encode([
                "submission_id"=>$r["submission_id"], "student_name"=>$r["student_name"],
                "score"=>$r["score"], "feedback"=>$r["feedback"], "max_score"=>$homework["max_score"],
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= $r['score']!==null ? 'Edit Grade' : 'Grade' ?></button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($roster)): ?>
        <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">👥</div><div class="empty-state-text">No students in this class yet.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Grade Modal -->
<div class="modal-overlay" id="gradeModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="gradeModalTitle">Grade Submission</div>
      <button class="modal-close" onclick="document.getElementById('gradeModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="gradeForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Score (out of <span id="gradeMaxScore"><?= number_format($homework['max_score'],0) ?></span>) *</label>
          <input type="number" name="score" id="gradeScore" class="form-control" step="0.01" required>
        </div>
        <div class="form-group">
          <label class="form-label">Feedback</label>
          <textarea name="feedback" id="gradeFeedback" class="form-control" rows="3" placeholder="Optional comments for the student"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('gradeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Grade</button>
      </div>
    </form>
  </div>
</div>

<script>
function openGradeModal(s) {
  document.getElementById('gradeForm').action = '<?= $cfg['url'] ?>/school/homework/submissions/' + s.submission_id + '/grade';
  document.getElementById('gradeModalTitle').textContent = 'Grade — ' + s.student_name;
  document.getElementById('gradeMaxScore').textContent = s.max_score;
  document.getElementById('gradeScore').value = s.score !== null ? s.score : '';
  document.getElementById('gradeFeedback').value = s.feedback || '';
  document.getElementById('gradeModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
