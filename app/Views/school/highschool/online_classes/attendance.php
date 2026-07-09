<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/online-classes">Online Classes</a><span>/</span><span><?= htmlspecialchars($onlineClass['title']) ?></span></div>

<div class="page-header">
  <div>
    <div class="page-header-title">Attendance — <?= htmlspecialchars($onlineClass['title']) ?></div>
    <div class="page-header-sub">
      <?= htmlspecialchars($onlineClass['class_name'] ?? '—') ?> · <?= date('M d, Y', strtotime($onlineClass['scheduled_date'])) ?> · <?= date('h:i A', strtotime($onlineClass['start_time'])) ?>
    </div>
  </div>
  <a href="<?= htmlspecialchars($onlineClass['meeting_link']) ?>" target="_blank" class="btn btn-outline">Join Meeting</a>
</div>

<div class="alert alert-info" style="margin-bottom:20px;">Marking attendance here saves directly to this class's regular attendance register — it feeds into attendance rate reports and report cards.</div>

<form method="POST" action="<?= $cfg['url'] ?>/school/online-classes/<?= $onlineClass['id'] ?>/attendance/mark">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">Roster (<?= count($roster) ?>)</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student</th><th>Admission No</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($roster as $r): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($r['student_name']) ?></td>
            <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['admission_no']) ?></td>
            <td>
              <div style="display:flex;gap:14px;">
                <?php foreach(['present'=>'Present','late'=>'Late','excused'=>'Excused','absent'=>'Absent'] as $val=>$label): ?>
                <label style="display:flex;align-items:center;gap:5px;font-size:13px;font-weight:500;cursor:pointer;">
                  <input type="radio" name="status[<?= $r['student_id'] ?>]" value="<?= $val ?>" <?= ($r['status']??'present')===$val ? 'checked' : '' ?>>
                  <?= $label ?>
                </label>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($roster)): ?>
          <tr><td colspan="3"><div class="empty-state"><div class="empty-state-icon">👥</div><div class="empty-state-text">No students in this class.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div style="margin-top:20px;">
    <button type="submit" class="btn btn-primary">Save Attendance</button>
  </div>
</form>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
