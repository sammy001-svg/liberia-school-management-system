<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/teachers">Teachers</a>
  <span>/</span><span><?= htmlspecialchars($teacher['name']) ?></span>
</div>

<div class="card profile-hero">
  <div class="profile-hero-body">
    <div class="avatar avatar-xl"><?= strtoupper(substr($teacher['name'],0,1)) ?></div>
    <div class="profile-hero-info">
      <div class="profile-hero-name"><?= htmlspecialchars($teacher['name']) ?></div>
      <div class="profile-hero-meta">
        <span class="meta-chip">🪪 <?= htmlspecialchars($teacher['employee_no']) ?></span>
        <?php if($teacher['department_name']): ?><span class="meta-chip">🏢 <?= htmlspecialchars($teacher['department_name']) ?></span><?php endif; ?>
        <?php if($teacher['class_name']): ?><span class="meta-chip">🏫 <?= htmlspecialchars($teacher['class_name']) ?> (Homeroom)</span><?php endif; ?>
        <span class="badge badge-info"><?= ucfirst(str_replace('_',' ',$teacher['employment_type'] ?? 'full_time')) ?></span>
      </div>
    </div>
    <div class="profile-hero-actions">
      <a href="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/id-card" target="_blank" class="btn btn-outline">🪪 ID Card</a>
      <a href="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/edit" class="btn btn-secondary">Edit</a>
    </div>
  </div>
</div>

<div class="profile-layout">
  <div class="profile-stack">

    <div class="card">
      <div class="card-header"><div class="card-title">Overview</div></div>
      <div class="card-body">
        <div class="mini-stat-grid">
          <div class="mini-stat">
            <div class="mini-stat-value"><?= count($assignedCourses) ?></div>
            <div class="mini-stat-label">Courses</div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-value"><?= $homeroomCount ?></div>
            <div class="mini-stat-label">Homeroom</div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-value"><?= $yearsOfService !== null ? $yearsOfService : '—' ?></div>
            <div class="mini-stat-label">Yrs Service</div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Contact Info</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">✉️</div>
            <div><div class="detail-label">Email</div><div class="detail-value"><?= htmlspecialchars($teacher['email']) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📞</div>
            <div><div class="detail-label">Phone</div><div class="detail-value"><?= htmlspecialchars($teacher['phone'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon"><?= $teacher['gender']==='female'?'♀':'♂' ?></div>
            <div><div class="detail-label">Gender</div><div class="detail-value"><?= ucfirst($teacher['gender'] ?? '—') ?></div></div>
          </div>
          <?php if(!empty($teacher['joined_at'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📅</div>
            <div><div class="detail-label">Joined</div><div class="detail-value"><?= date('d M Y', strtotime($teacher['joined_at'])) ?></div></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Professional Info</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🎓</div>
            <div><div class="detail-label">Qualification</div><div class="detail-value"><?= htmlspecialchars($teacher['qualification'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📚</div>
            <div><div class="detail-label">Specialization</div><div class="detail-value"><?= htmlspecialchars($teacher['specialization'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🏢</div>
            <div><div class="detail-label">Department</div><div class="detail-value"><?= htmlspecialchars($teacher['department_name'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">💼</div>
            <div><div class="detail-label">Employment Type</div><div class="detail-value"><?= ucfirst(str_replace('_',' ', $teacher['employment_type'] ?? '—')) ?></div></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="profile-stack">

    <div class="card">
      <div class="card-header">
        <div class="card-title">Courses Taught (<?= count($assignedCourses) ?>)</div>
        <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('assignCourseModal').classList.add('open')">+ Assign Course</button>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Course</th><th>Code</th><th>Credit Hours</th><th></th></tr></thead>
          <tbody>
            <?php foreach($assignedCourses as $c): ?>
            <tr>
              <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
              <td><?= htmlspecialchars($c['code']??'—') ?></td>
              <td><?= $c['credit_hours'] ?></td>
              <td>
                <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/courses/<?= $c['id'] ?>/remove" data-confirm="Unassign <?= htmlspecialchars($c['name']) ?> from <?= htmlspecialchars($teacher['name']) ?>?" data-confirm-label="Unassign">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <button type="submit" class="btn btn-sm btn-outline">Remove</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($assignedCourses)): ?>
            <tr><td colspan="4">
              <div class="empty-state">
                <div class="empty-state-icon">📖</div>
                <div class="empty-state-text">No courses assigned yet. <a href="javascript:void(0)" onclick="document.getElementById('assignCourseModal').classList.add('open')">Assign one</a></div>
              </div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Assign Course Modal -->
<div class="modal-overlay" id="assignCourseModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Assign Course to <?= htmlspecialchars($teacher['name']) ?></div>
      <button class="modal-close" onclick="document.getElementById('assignCourseModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/courses/assign">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Course / Subject *</label>
          <select name="course_id" class="form-control" required>
            <option value="">— Select Course —</option>
            <?php foreach($availableCourses as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if(empty($availableCourses)): ?><div class="form-hint">All courses are already assigned to this teacher.</div><?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('assignCourseModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
