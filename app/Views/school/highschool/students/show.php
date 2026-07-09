<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/students">Students</a><span>/</span><span><?= htmlspecialchars($student['name']) ?></span></div>

<div class="card profile-hero">
  <div class="profile-hero-body">
    <div class="avatar avatar-xl"><?= strtoupper(substr($student['name'],0,1)) ?></div>
    <div class="profile-hero-info">
      <div class="profile-hero-name"><?= htmlspecialchars($student['name']) ?></div>
      <div class="profile-hero-meta">
        <span class="meta-chip">🎓 <?= htmlspecialchars($student['admission_no']) ?></span>
        <span class="meta-chip">🏫 <?= htmlspecialchars($student['class_name'] ?? 'No Class') ?></span>
        <?php if($student['gender']): ?><span class="meta-chip"><?= $student['gender']==='female'?'♀':'♂' ?> <?= ucfirst($student['gender']) ?></span><?php endif; ?>
        <?php if(!empty($student['admission_type'])): ?><span class="meta-chip"><?= $student['admission_type']==='new'?'🆕':'🔁' ?> <?= ucfirst($student['admission_type']) ?> Student</span><?php endif; ?>
        <span class="badge badge-<?= $student['status']==='active'?'success':($student['status']==='graduated'?'info':'danger') ?>"><?= ucfirst($student['status']) ?></span>
      </div>
    </div>
    <div class="profile-hero-actions">
      <a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>/id-card" target="_blank" class="btn btn-outline">🪪 ID Card</a>
      <a href="<?= $cfg['url'] ?>/school/grades/report-card/<?= $student['id'] ?>" target="_blank" class="btn btn-outline">📄 Report Card</a>
      <a href="<?= $cfg['url'] ?>/school/certificates" class="btn btn-outline">🎓 Certificates</a>
      <a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>/edit" class="btn btn-secondary">Edit Profile</a>
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
            <div class="mini-stat-value"><?= $attendanceRate !== null ? $attendanceRate.'%' : '—' ?></div>
            <div class="mini-stat-label">Attendance</div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-value"><?= $avgGrade !== null ? $avgGrade.'%' : '—' ?></div>
            <div class="mini-stat-label">Avg Grade</div>
          </div>
          <div class="mini-stat">
            <div class="mini-stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($outstandingFees,0) ?></div>
            <div class="mini-stat-label">Fees Due</div>
          </div>
        </div>
        <?php if($attendanceRate !== null): ?>
        <div style="margin-top:16px;">
          <div class="progress-track"><div class="progress-fill" style="width:<?= $attendanceRate ?>%;--card-color:<?= $attendanceRate>=75?'var(--success)':($attendanceRate>=50?'var(--warning)':'var(--danger)') ?>;"></div></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Contact &amp; Personal Info</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">✉️</div>
            <div><div class="detail-label">Email</div><div class="detail-value"><?= htmlspecialchars($student['email'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📞</div>
            <div><div class="detail-label">Phone</div><div class="detail-value"><?= htmlspecialchars($student['phone'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🎂</div>
            <div><div class="detail-label">Date of Birth</div><div class="detail-value"><?= $student['date_of_birth'] ? date('d M Y', strtotime($student['date_of_birth'])) : '—' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🩸</div>
            <div><div class="detail-label">Blood Group</div><div class="detail-value"><?= htmlspecialchars($student['blood_group'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📅</div>
            <div><div class="detail-label">Admission Date</div><div class="detail-value"><?= $student['admission_date'] ? date('d M Y', strtotime($student['admission_date'])) : '—' ?></div></div>
          </div>
          <?php if(!empty($student['county']) || !empty($student['country'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">County / Country</div><div class="detail-value"><?= htmlspecialchars(trim(implode(', ', array_filter([$student['county'] ?? null, $student['country'] ?? null])))) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if(!empty($student['religion'])): ?>
          <div class="detail-item">
            <div class="detail-icon">🙏</div>
            <div><div class="detail-label">Religion</div><div class="detail-value"><?= htmlspecialchars($student['religion']) ?></div></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Guardian &amp; Emergency Contact</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">👪</div>
            <div><div class="detail-label">Guardian</div><div class="detail-value"><?= htmlspecialchars($student['guardian_name'] ?? '—') ?><?= $student['guardian_relationship'] ? ' ('.htmlspecialchars($student['guardian_relationship']).')' : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📱</div>
            <div><div class="detail-label">Contact Number 1</div><div class="detail-value"><?= htmlspecialchars($student['guardian_phone'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🚨</div>
            <div><div class="detail-label">Contact Number 2</div><div class="detail-value"><?= htmlspecialchars($student['emergency_contact_phone'] ?? '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <?php if(!empty($student['previous_school']) || !empty($student['previous_class']) || !empty($student['reason_for_leaving'])): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Previous School</div></div>
      <div class="card-body">
        <div class="detail-list">
          <?php if(!empty($student['previous_school'])): ?>
          <div class="detail-item">
            <div class="detail-icon">🏛️</div>
            <div><div class="detail-label">Name</div><div class="detail-value"><?= htmlspecialchars($student['previous_school']) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if(!empty($student['previous_school_address'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">Address</div><div class="detail-value"><?= htmlspecialchars($student['previous_school_address']) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if(!empty($student['previous_class'])): ?>
          <div class="detail-item">
            <div class="detail-icon">🎒</div>
            <div><div class="detail-label">Previous Class</div><div class="detail-value"><?= htmlspecialchars($student['previous_class']) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if(!empty($student['reason_for_leaving'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📝</div>
            <div><div class="detail-label">Reason for Leaving</div><div class="detail-value"><?= htmlspecialchars($student['reason_for_leaving']) ?></div></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="profile-stack">

    <?php if(!empty($rankings)): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Rankings</div><a href="<?= $cfg['url'] ?>/school/grades/rankings" class="btn btn-sm btn-outline">All Rankings</a></div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Period</th><th>Score</th><th>Rank</th></tr></thead>
        <tbody>
          <?php foreach($rankings as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['period']) ?></td>
            <td><span class="badge badge-<?= $r['score']>=70?'success':($r['score']>=50?'warning':'danger') ?>"><?= number_format($r['score'],1) ?>%</span></td>
            <td><?= $r['rank_position']!==null ? '#'.$r['rank_position'].($r['group_size']?' of '.$r['group_size']:'') : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><div class="card-title">Recent Grades</div><a href="<?= $cfg['url'] ?>/school/grades/report/<?= $student['id'] ?>" class="btn btn-sm btn-outline">Full Report</a></div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Subject</th><th>Score</th><th>Grade</th></tr></thead>
        <tbody>
          <?php foreach($grades as $g): ?>
          <tr><td><?= htmlspecialchars($g['course_name']??'—') ?></td><td><?= $g['marks_obtained'] ?>%</td><td><span class="badge badge-<?= $g['grade_letter']==='F'?'danger':'success' ?>"><?= $g['grade_letter'] ?></span></td></tr>
          <?php endforeach; ?>
          <?php if(empty($grades)): ?>
          <tr><td colspan="3"><div class="empty-state"><div class="empty-state-icon">📝</div><div class="empty-state-text">No grades recorded yet.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Attendance History</div></div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Date</th><th>Status</th><th>Remarks</th></tr></thead>
        <tbody>
          <?php foreach($attendance as $a): ?>
          <tr>
            <td><?= date('d M Y', strtotime($a['date'])) ?></td>
            <td><span class="badge badge-<?= $a['status']==='present'?'success':($a['status']==='late'?'warning':($a['status']==='excused'?'info':'danger')) ?>"><?= ucfirst($a['status']) ?></span></td>
            <td class="text-muted"><?= htmlspecialchars($a['remarks'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($attendance)): ?>
          <tr><td colspan="3"><div class="empty-state"><div class="empty-state-icon">📆</div><div class="empty-state-text">No attendance records yet.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Invoices</div><a href="<?= $cfg['url'] ?>/school/finance/invoices/create" class="btn btn-sm btn-primary">+ Invoice</a></div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Invoice</th><th>Amount</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach($invoices as $inv): ?>
          <tr>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($inv['invoice_no']) ?></td>
            <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($inv['amount_due'],2) ?></td>
            <td><span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='overdue'?'danger':($inv['status']==='waived'?'muted':'warning')) ?>"><?= ucfirst($inv['status']) ?></span></td>
            <td><a href="<?= $cfg['url'] ?>/school/finance/invoices/<?= $inv['id'] ?>/print" target="_blank" class="btn btn-sm btn-outline">Print</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($invoices)): ?>
          <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">🧾</div><div class="empty-state-text">No invoices raised yet.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table></div>
    </div>

  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
