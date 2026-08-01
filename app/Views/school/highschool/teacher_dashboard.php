<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
  $hour = (int)date('G');
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
  $firstName = explode(' ', trim($teacherName))[0];
?>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= $greeting ?>, <?= htmlspecialchars($firstName) ?></div>
    <div class="page-header-sub"><?= date('l, d F Y') ?> — here's your teaching day at a glance</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= $cfg['url'] ?>/school/attendance" class="btn btn-primary">✓ Mark Attendance</a>
    <a href="<?= $cfg['url'] ?>/school/grades/enter" class="btn btn-secondary">✏️ Enter Grades</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">My Classes</div>
    <div class="stat-value"><?= (int)$stats['classes'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--purple);">
    <div class="stat-label">My Subjects</div>
    <div class="stat-value"><?= (int)$stats['subjects'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">My Students</div>
    <div class="stat-value"><?= (int)$stats['students'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: <?= $stats['toMark'] > 0 ? 'var(--warning)' : 'var(--success)' ?>;">
    <div class="stat-label">Attendance Today</div>
    <div class="stat-value"><?= (int)$stats['marked'] ?>/<?= (int)$stats['classes'] ?></div>
  </div>
</div>

<?php if ($stats['toMark'] > 0 || $stats['toGrade'] > 0): ?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-body" style="display:flex;gap:20px;flex-wrap:wrap;align-items:center;">
    <div style="font-size:22px;">📌</div>
    <div style="flex:1;min-width:220px;">
      <div class="fw-600" style="margin-bottom:4px;">Needs your attention</div>
      <div style="font-size:13px;color:var(--text-light);">
        <?php $bits = []; ?>
        <?php if ($stats['toMark'] > 0)  { $bits[] = $stats['toMark'] . ' class' . ($stats['toMark'] > 1 ? 'es' : '') . ' without attendance marked today'; } ?>
        <?php if ($stats['toGrade'] > 0) { $bits[] = $stats['toGrade'] . ' homework submission' . ($stats['toGrade'] > 1 ? 's' : '') . ' waiting to be graded'; } ?>
        <?= htmlspecialchars(implode(' · ', $bits)) ?>
      </div>
    </div>
    <?php if ($stats['toMark'] > 0): ?>
    <a href="<?= $cfg['url'] ?>/school/attendance" class="btn btn-sm btn-primary">Mark Attendance</a>
    <?php endif; ?>
    <?php if ($stats['toGrade'] > 0): ?>
    <a href="<?= $cfg['url'] ?>/school/homework" class="btn btn-sm btn-outline">Grade Homework</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="profile-layout">
  <div class="profile-stack">

    <div class="card">
      <div class="card-header">
        <div class="card-title">Today's Schedule</div>
        <a href="<?= $cfg['url'] ?>/school/timetable" class="btn btn-sm btn-outline">Full Timetable</a>
      </div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Time</th><th>Class</th><th>Subject</th><th>Room</th></tr></thead>
        <tbody>
          <?php foreach ($todaySchedule as $s): ?>
          <?php $isNow = date('H:i:s') >= $s['start_time'] && date('H:i:s') <= $s['end_time']; ?>
          <tr<?= $isNow ? ' style="background:var(--primary-soft);"' : '' ?>>
            <td class="fw-600">
              <?= date('g:i A', strtotime($s['start_time'])) ?>
              <div style="font-size:11px;color:var(--text-muted);">to <?= date('g:i A', strtotime($s['end_time'])) ?></div>
            </td>
            <td><?= htmlspecialchars($s['class_name'] ?? '—') ?><?php if ($isNow): ?> <span class="badge badge-primary">Now</span><?php endif; ?></td>
            <td><?= htmlspecialchars($s['course_name'] ?? '—') ?></td>
            <td class="text-muted"><?= htmlspecialchars($s['room'] ?: '—') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($todaySchedule)): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">📅</div><div class="empty-state-text">No classes scheduled for you today.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">My Classes</div>
        <a href="<?= $cfg['url'] ?>/school/classes" class="btn btn-sm btn-outline">All Classes</a>
      </div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Class</th><th>Students</th><th>Attendance Today</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($myClasses as $c): ?>
          <tr>
            <td>
              <a href="<?= $cfg['url'] ?>/school/classes/<?= $c['id'] ?>" class="fw-600"><?= htmlspecialchars($c['name']) ?></a>
              <?php if (!empty($c['is_form_class'])): ?> <span class="badge badge-info">Form Class</span><?php endif; ?>
              <?php if ($c['room_number']): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($c['room_number']) ?></div><?php endif; ?>
            </td>
            <td><?= (int)$c['student_count'] ?></td>
            <td>
              <?php if ((int)$c['marked_today'] > 0): ?>
                <span class="badge badge-success">Marked</span>
              <?php else: ?>
                <span class="badge badge-warning">Not marked</span>
              <?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="<?= $cfg['url'] ?>/school/attendance?class_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline">Attendance</a>
                <a href="<?= $cfg['url'] ?>/school/grades/enter?class_id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary">Grades</a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($myClasses)): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">🏫</div><div class="empty-state-text">No classes assigned to you yet. Ask an administrator to assign your subjects.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Homework Awaiting Grading</div>
        <a href="<?= $cfg['url'] ?>/school/homework" class="btn btn-sm btn-outline">All Homework</a>
      </div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Title</th><th>Class</th><th>Due</th><th>Ungraded</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($pendingGrading as $h): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($h['title']) ?></td>
            <td><?= htmlspecialchars($h['class_name'] ?? '—') ?></td>
            <td><?= date('d M Y', strtotime($h['due_date'])) ?></td>
            <td><span class="badge badge-warning"><?= (int)$h['ungraded'] ?></span></td>
            <td><a href="<?= $cfg['url'] ?>/school/homework/<?= $h['id'] ?>/submissions" class="btn btn-sm btn-primary">Grade</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($pendingGrading)): ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text">Nothing waiting to be graded. All caught up.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

  </div>

  <div class="profile-stack">

    <div class="card">
      <div class="card-header"><div class="card-title">My Subjects</div></div>
      <div class="card-body">
        <?php if (empty($mySubjects)): ?>
          <div class="empty-state"><div class="empty-state-icon">📚</div><div class="empty-state-text">No subjects assigned yet.</div></div>
        <?php else: ?>
          <div style="display:flex;flex-wrap:wrap;gap:8px;">
            <?php foreach ($mySubjects as $s): ?>
              <span class="badge badge-primary" style="padding:7px 12px;font-size:12px;">
                <?= htmlspecialchars($s['name']) ?>
                <?php if ((int)$s['class_count'] > 0): ?> · <?= (int)$s['class_count'] ?> class<?= (int)$s['class_count'] > 1 ? 'es' : '' ?><?php endif; ?>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Upcoming Exams</div>
        <a href="<?= $cfg['url'] ?>/school/grades" class="btn btn-sm btn-outline">Grades &amp; Exams</a>
      </div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Exam</th><th>Class</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($upcomingExams as $e): ?>
          <?php $days = (int)floor((strtotime($e['exam_date']) - strtotime(date('Y-m-d'))) / 86400); ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($e['name']) ?></td>
            <td><?= htmlspecialchars($e['class_name'] ?? 'All') ?></td>
            <td>
              <?= date('d M', strtotime($e['exam_date'])) ?>
              <div style="font-size:11px;color:var(--text-muted);">
                <?= $days === 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : "in {$days} days") ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($upcomingExams)): ?>
          <tr><td colspan="3"><div class="empty-state"><div class="empty-state-icon">📝</div><div class="empty-state-text">No upcoming exams.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="card-header">
        <div class="card-title">Announcements</div>
        <a href="<?= $cfg['url'] ?>/school/announcements" class="btn btn-sm btn-outline">View All</a>
      </div>
      <div class="card-body">
        <?php foreach ($announcements as $a): ?>
        <div style="padding:10px 0;border-bottom:1px solid var(--border);">
          <div class="fw-600" style="font-size:13px;">
            <?php if (!empty($a['is_pinned'])): ?>📌 <?php endif; ?><?= htmlspecialchars($a['title']) ?>
          </div>
          <div style="font-size:12px;color:var(--text-light);margin-top:3px;">
            <?= htmlspecialchars(mb_strimwidth($a['body'], 0, 110, '…')) ?>
          </div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
            <?= htmlspecialchars($a['author']) ?> · <?= date('d M Y', strtotime($a['published_at'])) ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($announcements)): ?>
          <div class="empty-state"><div class="empty-state-icon">📢</div><div class="empty-state-text">No announcements.</div></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Quick Links</div></div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:8px;">
          <a href="<?= $cfg['url'] ?>/school/homework" class="btn btn-outline btn-block">📚 Assign Homework</a>
          <a href="<?= $cfg['url'] ?>/school/online-classes" class="btn btn-outline btn-block">🎥 Online Classes</a>
          <a href="<?= $cfg['url'] ?>/school/discipline" class="btn btn-outline btn-block">🛡️ Record Behaviour</a>
          <a href="<?= $cfg['url'] ?>/school/my-leave" class="btn btn-outline btn-block">📅 Apply for Leave</a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
