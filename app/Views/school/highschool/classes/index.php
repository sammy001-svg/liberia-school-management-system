<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Classes</div>
    <div class="page-header-sub">Manage classes, sections and teacher assignments</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addClassModal').classList.add('open')">+ Add Class</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Classes</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Enrolled Students</div>
    <div class="stat-value"><?= (int)($stats['enrolled'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Total Capacity</div>
    <div class="stat-value"><?= (int)($stats['totalCapacity'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Without a Class Teacher</div>
    <div class="stat-value"><?= (int)($stats['unassigned'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Classes (<?= count($classes) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Class</th><th>Grade</th><th>Section</th><th>Room</th><th>Class Teacher</th><th>Enrolment</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($classes as $c): ?>
        <?php $fillPct = $c['capacity'] > 0 ? min(100, round($c['student_count'] / $c['capacity'] * 100)) : 0; ?>
        <tr>
          <td><a href="<?= $cfg['url'] ?>/school/classes/<?= $c['id'] ?>" class="fw-600"><?= htmlspecialchars($c['name']) ?></a></td>
          <td><?= htmlspecialchars($c['grade_level']) ?></td>
          <td><?= htmlspecialchars($c['section']??'—') ?></td>
          <td><?= htmlspecialchars($c['room_number']??'—') ?></td>
          <td><?= htmlspecialchars($c['teacher_name']??'—') ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="progress-track" style="width:70px;"><div class="progress-fill" style="width:<?= $fillPct ?>%;--card-color:<?= $fillPct>=90?'var(--danger)':($fillPct>=70?'var(--warning)':'var(--success)') ?>;"></div></div>
              <span style="font-size:12px;color:var(--text-muted);white-space:nowrap;"><?= $c['student_count'] ?>/<?= $c['capacity'] ?></span>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/classes/<?= $c['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/school/classes/<?= $c['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($classes)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🏫</div>
            <div class="empty-state-text">No classes yet. <a href="javascript:void(0)" onclick="document.getElementById('addClassModal').classList.add('open')">Create one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Class Modal -->
<div class="modal-overlay" id="addClassModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add New Class</div>
      <button class="modal-close" onclick="document.getElementById('addClassModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/classes/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Class Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Grade 7A">
          </div>
          <div class="form-group">
            <label class="form-label">Grade Level *</label>
            <input type="text" name="grade_level" class="form-control" required placeholder="e.g. Grade 7">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Section</label>
            <input type="text" name="section" class="form-control" placeholder="e.g. A">
          </div>
          <div class="form-group">
            <label class="form-label">Room Number</label>
            <input type="text" name="room_number" class="form-control" placeholder="e.g. Block B - Room 12">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Optional notes about this class"></textarea>
        </div>

        <div class="modal-section-title">Assignment</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class Teacher</label>
            <select name="teacher_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($teachers as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($academicYears as $y): ?>
                <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Capacity</label>
          <input type="number" name="capacity" class="form-control" value="40">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addClassModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Class</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
