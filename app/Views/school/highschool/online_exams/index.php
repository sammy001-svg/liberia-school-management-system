<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Online Exams</div>
    <div class="page-header-sub">Auto-graded multiple choice exams for students to take in their portal</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addExamModal').classList.add('open')">+ Create Exam</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Exams</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Published</div>
    <div class="stat-value"><?= (int)($stats['published'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Draft</div>
    <div class="stat-value"><?= (int)($stats['draft'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Exams (<?= count($exams) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Title</th><th>Class</th><th>Window</th><th>Questions</th><th>Attempts</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($exams as $e): ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($e['title']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($e['course_name'] ?? 'General') ?> · <?= $e['duration_minutes'] ?> min</div>
          </td>
          <td><?= htmlspecialchars($e['class_name'] ?? '—') ?></td>
          <td style="font-size:12px;">
            <?= date('M d, H:i', strtotime($e['starts_at'])) ?><br>
            <span style="color:var(--text-muted)">to <?= date('M d, H:i', strtotime($e['ends_at'])) ?></span>
          </td>
          <td><?= (int)$e['question_count'] ?></td>
          <td><?= (int)$e['attempt_count'] ?> / <?= (int)$e['student_count'] ?></td>
          <td><span class="badge badge-<?= $e['status']==='published'?'success':'muted' ?>"><?= ucfirst($e['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= $cfg['url'] ?>/school/online-exams/<?= $e['id'] ?>/questions" class="btn btn-sm btn-secondary">Questions</a>
              <a href="<?= $cfg['url'] ?>/school/online-exams/<?= $e['id'] ?>/results" class="btn btn-sm btn-outline">Results</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/online-exams/<?= $e['id'] ?>/publish">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-<?= $e['status']==='published'?'outline':'primary' ?>"><?= $e['status']==='published' ? 'Unpublish' : 'Publish' ?></button>
              </form>
              <form method="POST" action="<?= $cfg['url'] ?>/school/online-exams/<?= $e['id'] ?>/delete" data-confirm="Remove '<?= htmlspecialchars(addslashes($e['title'])) ?>'? This cannot be undone." data-confirm-title="Remove Exam" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($exams)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <div class="empty-state-text">No online exams yet. <a href="javascript:void(0)" onclick="document.getElementById('addExamModal').classList.add('open')">Create the first one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Create Exam Modal -->
<div class="modal-overlay" id="addExamModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Create Online Exam</div>
      <button class="modal-close" onclick="document.getElementById('addExamModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/online-exams/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. Mid-Term Science Quiz">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class *</label>
            <select name="class_id" class="form-control" required>
              <option value="">— Select Class —</option>
              <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Subject</label>
            <select name="course_id" class="form-control">
              <option value="">— Any —</option>
              <?php foreach($courses as $co): ?><option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Duration (minutes) *</label>
          <input type="number" name="duration_minutes" class="form-control" required value="30">
          <div class="form-hint">Once a student starts, they have this many minutes (or until the window closes, whichever comes first).</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Opens At *</label>
            <input type="datetime-local" name="starts_at" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Closes At *</label>
            <input type="datetime-local" name="ends_at" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addExamModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create &amp; Add Questions</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
