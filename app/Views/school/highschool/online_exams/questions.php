<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/online-exams">Online Exams</a><span>/</span><span><?= htmlspecialchars($exam['title']) ?></span></div>

<div class="page-header">
  <div>
    <div class="page-header-title">Questions — <?= htmlspecialchars($exam['title']) ?></div>
    <div class="page-header-sub"><?= htmlspecialchars($exam['class_name'] ?? '—') ?> · <?= count($questions) ?> question(s) · <?= number_format(array_sum(array_column($questions,'marks')),0) ?> total marks</div>
  </div>
  <div style="display:flex;gap:10px;">
    <a href="<?= $cfg['url'] ?>/school/online-exams" class="btn btn-outline">&larr; Back to Exams</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addQuestionModal').classList.add('open')">+ Add Question</button>
  </div>
</div>

<?php if($exam['status']==='draft'): ?>
<div class="alert alert-info" style="margin-bottom:20px;">This exam is still a <strong>draft</strong> — students won't see it until you publish it from the Online Exams list.</div>
<?php else: ?>
<div class="alert alert-success" style="margin-bottom:20px;">This exam is <strong>published</strong> and visible to students in the scheduled window.</div>
<?php endif; ?>

<div class="card">
  <div class="card-body" style="padding:0;">
    <?php foreach($questions as $i => $q): ?>
    <div style="padding:18px 22px;<?= $i>0 ? 'border-top:1px solid var(--border);' : '' ?>">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
        <div style="flex:1;">
          <div style="font-size:11px;color:var(--text-muted);font-weight:700;text-transform:uppercase;">Question <?= $i+1 ?> · <?= number_format($q['marks'],1) ?> mark<?= $q['marks']!=1?'s':'' ?></div>
          <div class="fw-600" style="margin-top:4px;"><?= htmlspecialchars($q['question_text']) ?></div>
          <div style="margin-top:10px;display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:13px;">
            <?php foreach(['a'=>$q['option_a'],'b'=>$q['option_b'],'c'=>$q['option_c'],'d'=>$q['option_d']] as $key=>$opt): ?>
              <?php if($opt !== null && $opt !== ''): ?>
              <div style="padding:6px 10px;border-radius:6px;<?= $q['correct_option']===$key ? 'background:rgba(16,185,129,0.12);color:var(--success);font-weight:600;' : 'color:var(--text-light);' ?>">
                <?= strtoupper($key) ?>. <?= htmlspecialchars($opt) ?> <?= $q['correct_option']===$key ? '✓' : '' ?>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <form method="POST" action="<?= $cfg['url'] ?>/school/online-exams/<?= $exam['id'] ?>/questions/<?= $q['id'] ?>/delete" data-confirm="Remove this question?" data-confirm-title="Remove Question" data-confirm-label="Remove">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <button type="submit" class="btn btn-sm btn-danger">Del</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if(empty($questions)): ?>
    <div class="empty-state" style="padding:40px;">
      <div class="empty-state-icon">❓</div>
      <div class="empty-state-text">No questions yet. <a href="javascript:void(0)" onclick="document.getElementById('addQuestionModal').classList.add('open')">Add the first one</a></div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Add Question Modal -->
<div class="modal-overlay" id="addQuestionModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Question</div>
      <button class="modal-close" onclick="document.getElementById('addQuestionModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/online-exams/<?= $exam['id'] ?>/questions/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Question *</label>
          <textarea name="question_text" class="form-control" rows="2" required placeholder="e.g. What is the boiling point of water at sea level?"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Option A *</label>
            <input type="text" name="option_a" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Option B *</label>
            <input type="text" name="option_b" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Option C</label>
            <input type="text" name="option_c" class="form-control" placeholder="Leave blank for True/False questions">
          </div>
          <div class="form-group">
            <label class="form-label">Option D</label>
            <input type="text" name="option_d" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Correct Answer *</label>
            <select name="correct_option" class="form-control" required>
              <option value="a">Option A</option>
              <option value="b">Option B</option>
              <option value="c">Option C</option>
              <option value="d">Option D</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Marks</label>
            <input type="number" name="marks" class="form-control" step="0.5" value="1">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addQuestionModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Question</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
