<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/classes">Classes</a><span>/</span><span><?= htmlspecialchars($class['name']) ?></span></div>

<?php $fillPct = $class['capacity'] > 0 ? min(100, round(count($roster) / $class['capacity'] * 100)) : 0; ?>
<div class="card profile-hero">
  <div class="profile-hero-body">
    <div class="avatar avatar-xl avatar-sq"><?= strtoupper(substr($class['name'],0,2)) ?></div>
    <div class="profile-hero-info">
      <div class="profile-hero-name"><?= htmlspecialchars($class['name']) ?></div>
      <div class="profile-hero-meta">
        <span class="meta-chip">🎓 <?= htmlspecialchars($class['grade_level']) ?></span>
        <?php if($class['section']): ?><span class="meta-chip">Section <?= htmlspecialchars($class['section']) ?></span><?php endif; ?>
        <?php if($class['room_number']): ?><span class="meta-chip">🚪 <?= htmlspecialchars($class['room_number']) ?></span><?php endif; ?>
        <span class="badge badge-<?= $fillPct>=90?'danger':($fillPct>=70?'warning':'success') ?>"><?= count($roster) ?>/<?= $class['capacity'] ?> Enrolled</span>
      </div>
    </div>
    <div class="profile-hero-actions">
      <a href="<?= $cfg['url'] ?>/school/classes/<?= $class['id'] ?>/edit" class="btn btn-secondary">Edit Class</a>
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
            <div class="mini-stat-value"><?= count($roster) ?></div>
            <div class="mini-stat-label">Students</div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-value"><?= count($courses) ?></div>
            <div class="mini-stat-label">Subjects</div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-value"><?= $avgGrade !== null ? $avgGrade.'%' : '—' ?></div>
            <div class="mini-stat-label">Avg Grade</div>
          </div>
        </div>
        <div style="margin-top:16px;">
          <div class="progress-track"><div class="progress-fill" style="width:<?= $fillPct ?>%;--card-color:<?= $fillPct>=90?'var(--danger)':($fillPct>=70?'var(--warning)':'var(--success)') ?>;"></div></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Class Info</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🧑‍🏫</div>
            <div><div class="detail-label">Class Teacher</div><div class="detail-value"><?= htmlspecialchars($class['teacher_name'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🚪</div>
            <div><div class="detail-label">Room</div><div class="detail-value"><?= htmlspecialchars($class['room_number'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📅</div>
            <div><div class="detail-label">Academic Year</div><div class="detail-value"><?= htmlspecialchars($class['academic_year_name'] ?? '—') ?></div></div>
          </div>
          <?php if(!empty($class['description'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📝</div>
            <div><div class="detail-label">Description</div><div class="detail-value"><?= htmlspecialchars($class['description']) ?></div></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php if(!empty($courses)): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Subjects (<?= count($courses) ?>)</div></div>
      <div class="card-body">
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php foreach($courses as $co): ?>
            <span class="meta-chip">📚 <?= htmlspecialchars($co['name']) ?><?= $co['code'] ? ' ('.htmlspecialchars($co['code']).')' : '' ?></span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="profile-stack">

    <div class="card">
      <div class="card-header"><div class="card-title">Class Roster (<?= count($roster) ?>)</div></div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Student</th><th>Admission No</th><th>Gender</th><th>Status</th></tr></thead>
          <tbody>
            <?php foreach($roster as $s): ?>
            <tr>
              <td><a href="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>" class="fw-600"><?= htmlspecialchars($s['name']) ?></a></td>
              <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($s['admission_no']) ?></td>
              <td><?= ucfirst($s['gender'] ?? '—') ?></td>
              <td><span class="badge badge-<?= $s['status']==='active'?'success':($s['status']==='graduated'?'info':'danger') ?>"><?= ucfirst($s['status']) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($roster)): ?>
            <tr><td colspan="4">
              <div class="empty-state">
                <div class="empty-state-icon">🎓</div>
                <div class="empty-state-text">No students assigned to this class yet.</div>
              </div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
