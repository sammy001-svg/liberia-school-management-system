<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Homework</div>
    <div class="page-header-sub">Assign homework to classes and track submissions</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addHomeworkModal').classList.add('open')">+ Assign Homework</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Assignments</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Upcoming / Due</div>
    <div class="stat-value"><?= (int)($stats['upcoming'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Past Due</div>
    <div class="stat-value"><?= (int)($stats['overdue'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Homework (<?= count($homework) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Title</th><th>Class</th><th>Subject</th><th>Due Date</th><th>Submissions</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($homework as $h): ?>
        <?php $isOverdue = strtotime($h['due_date']) < strtotime(date('Y-m-d')); ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($h['title']) ?></div>
            <?php if($h['teacher_name']): ?><div style="font-size:11px;color:var(--text-muted)">by <?= htmlspecialchars($h['teacher_name']) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($h['class_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($h['course_name'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $isOverdue ? 'danger' : 'info' ?>"><?= date('M d, Y', strtotime($h['due_date'])) ?></span></td>
          <td>
            <span class="badge badge-<?= $h['graded_count']>0 && $h['graded_count']==$h['submission_count'] ? 'success' : 'muted' ?>">
              <?= (int)$h['submission_count'] ?> / <?= (int)$h['student_count'] ?> submitted
            </span>
            <?php if($h['graded_count']>0): ?><div style="font-size:11px;color:var(--text-muted);margin-top:4px;"><?= (int)$h['graded_count'] ?> graded</div><?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/homework/<?= $h['id'] ?>/submissions" class="btn btn-sm btn-secondary">View</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/homework/<?= $h['id'] ?>/delete" data-confirm="Remove '<?= htmlspecialchars(addslashes($h['title'])) ?>'? This cannot be undone." data-confirm-title="Remove Homework" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($homework)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <div class="empty-state-text">No homework assigned yet. <a href="javascript:void(0)" onclick="document.getElementById('addHomeworkModal').classList.add('open')">Assign the first one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Homework Modal -->
<div class="modal-overlay" id="addHomeworkModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Assign Homework</div>
      <button class="modal-close" onclick="document.getElementById('addHomeworkModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/homework/store" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. Chapter 4 Exercises">
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
              <option value="">— Any / Not subject-specific —</option>
              <?php foreach($courses as $co): ?><option value="<?= $co['id'] ?>" data-class="<?= $co['class_id'] ?>"><?= htmlspecialchars($co['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description / Instructions</label>
          <textarea name="description" class="form-control" rows="4" placeholder="What should students do?"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Due Date *</label>
            <input type="date" name="due_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+1 week')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Max Score</label>
            <input type="number" name="max_score" class="form-control" step="0.01" value="100">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Attachment (optional)</label>
          <input type="file" name="attachment" class="form-control">
          <div class="form-hint">PDF, Word, Excel, PowerPoint, images or ZIP — up to 10MB.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addHomeworkModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Assign Homework</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
