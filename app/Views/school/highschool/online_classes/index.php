<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Online Classes</div>
    <div class="page-header-sub">Schedule virtual sessions and track attendance</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addClassModal').classList.add('open')">+ Schedule Class</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Sessions</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Upcoming</div>
    <div class="stat-value"><?= (int)($stats['upcoming'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Completed</div>
    <div class="stat-value"><?= (int)($stats['completed'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Sessions (<?= count($onlineClasses) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Title</th><th>Class</th><th>Subject</th><th>Date / Time</th><th>Status</th><th>Attendance</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($onlineClasses as $c): ?>
        <tr>
          <td>
            <div class="fw-600"><?= htmlspecialchars($c['title']) ?></div>
            <?php if($c['teacher_name']): ?><div style="font-size:11px;color:var(--text-muted)">by <?= htmlspecialchars($c['teacher_name']) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($c['class_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($c['course_name'] ?? '—') ?></td>
          <td>
            <?= date('M d, Y', strtotime($c['scheduled_date'])) ?><br>
            <span style="font-size:11px;color:var(--text-muted)"><?= date('h:i A', strtotime($c['start_time'])) ?> · <?= $c['duration_minutes'] ?>min</span>
          </td>
          <td><span class="badge badge-<?= $c['status']==='completed'?'success':($c['status']==='cancelled'?'danger':'info') ?>"><?= ucfirst($c['status']) ?></span></td>
          <td><?= (int)$c['marked_count'] ?> / <?= (int)$c['student_count'] ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= htmlspecialchars($c['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-primary">Join</a>
              <a href="<?= $cfg['url'] ?>/school/online-classes/<?= $c['id'] ?>/attendance" class="btn btn-sm btn-secondary">Attendance</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/online-classes/<?= $c['id'] ?>/delete" data-confirm="Remove '<?= htmlspecialchars(addslashes($c['title'])) ?>'? This cannot be undone." data-confirm-title="Remove Class" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($onlineClasses)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🎥</div>
            <div class="empty-state-text">No online classes scheduled yet. <a href="javascript:void(0)" onclick="document.getElementById('addClassModal').classList.add('open')">Schedule the first one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Schedule Class Modal -->
<div class="modal-overlay" id="addClassModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Schedule Online Class</div>
      <button class="modal-close" onclick="document.getElementById('addClassModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/online-classes/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. Algebra Review Session">
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
          <label class="form-label">Meeting Link *</label>
          <input type="url" name="meeting_link" class="form-control" required placeholder="https://meet.google.com/... or Zoom link">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Platform</label>
            <select name="platform" class="form-control">
              <option value="">— Select —</option>
              <option value="Zoom">Zoom</option>
              <option value="Google Meet">Google Meet</option>
              <option value="Microsoft Teams">Microsoft Teams</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Duration (minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" value="60">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="scheduled_date" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Start Time *</label>
            <input type="time" name="start_time" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addClassModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Schedule</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
