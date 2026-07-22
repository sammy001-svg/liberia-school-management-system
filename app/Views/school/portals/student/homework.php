<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Homework</div><div class="text-muted">Assignments for your class</div></div>
<?php if (empty($_SESSION['class_id'])): ?>
<div class="card" style="padding:12px 16px;margin-bottom:20px;border-left:3px solid var(--warning);font-size:13px;">
  ⚠️ You have not been assigned to a class yet, so nothing can appear here. Please contact the school office.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body" style="padding:0;">
    <?php foreach($homework as $i => $h): ?>
    <?php
      $isOverdue = strtotime($h['due_date']) < strtotime(date('Y-m-d'));
      $submitted = !empty($h['submission_id']);
    ?>
    <div style="padding:18px 22px;<?= $i>0 ? 'border-top:1px solid var(--border);' : '' ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;">
        <div style="flex:1;min-width:240px;">
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <span class="fw-600"><?= htmlspecialchars($h['title']) ?></span>
            <?php if($h['course_name']): ?><span class="badge badge-muted"><?= htmlspecialchars($h['course_name']) ?></span><?php endif; ?>
            <span class="badge badge-<?= $isOverdue && !$submitted ? 'danger' : 'info' ?>">Due <?= date('M d, Y', strtotime($h['due_date'])) ?></span>
            <?php if($submitted): ?>
              <span class="badge badge-success">Submitted</span>
              <?php if($h['score'] !== null): ?><span class="badge badge-primary"><?= number_format($h['score'],1) ?> / <?= number_format($h['max_score'],0) ?></span><?php endif; ?>
            <?php elseif($isOverdue): ?>
              <span class="badge badge-danger">Overdue</span>
            <?php endif; ?>
          </div>
          <?php if($h['description']): ?><div style="font-size:13px;color:var(--text-light);margin-top:8px;white-space:pre-wrap;"><?= htmlspecialchars($h['description']) ?></div><?php endif; ?>
          <?php if($h['attachment_path']): ?>
            <div style="margin-top:8px;"><a href="<?= htmlspecialchars($h['attachment_path']) ?>" class="btn btn-sm btn-outline" download>📎 <?= htmlspecialchars($h['attachment_name'] ?? 'Attachment') ?></a></div>
          <?php endif; ?>
          <?php if($submitted && $h['feedback']): ?>
            <div style="margin-top:10px;padding:10px 12px;background:var(--surface-alt,#f9fafb);border-radius:8px;font-size:12.5px;"><strong>Teacher feedback:</strong> <?= htmlspecialchars($h['feedback']) ?></div>
          <?php endif; ?>
        </div>
        <button type="button" class="btn btn-sm btn-<?= $submitted ? 'secondary' : 'primary' ?>" onclick='openSubmitModal(<?= json_encode([
          "id"=>$h["id"], "title"=>$h["title"], "text"=>$h["submission_text"], "attachment_name"=>$h["submission_attachment_name"],
        ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= $submitted ? 'Update Submission' : 'Submit' ?></button>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($homework)): ?>
    <div class="empty-state" style="padding:40px;"><div class="empty-state-icon">📚</div><div class="empty-state-text">No homework assigned yet.</div></div>
    <?php endif; ?>
  </div>
</div>

<!-- Submit Modal -->
<div class="modal-overlay" id="submitModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="submitModalTitle">Submit Homework</div>
      <button class="modal-close" onclick="document.getElementById('submitModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="submitForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Your Answer</label>
          <textarea name="submission_text" id="submitText" class="form-control" rows="5" placeholder="Type your answer here..."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Attach File (optional)</label>
          <input type="file" name="attachment" class="form-control">
          <div class="form-hint" id="submitCurrentFile"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('submitModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form>
  </div>
</div>

<script>
function openSubmitModal(h) {
  document.getElementById('submitForm').action = '<?= $cfg['url'] ?>/student/homework/' + h.id + '/submit';
  document.getElementById('submitModalTitle').textContent = (h.attachment_name || h.text ? 'Update Submission — ' : 'Submit — ') + h.title;
  document.getElementById('submitText').value = h.text || '';
  document.getElementById('submitCurrentFile').textContent = h.attachment_name ? 'Currently attached: ' + h.attachment_name : '';
  document.getElementById('submitModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
