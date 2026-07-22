<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/students">Students</a>
  <span>/</span><a href="<?= $cfg['url'] ?>/school/students/returning">Register Returning Student</a>
  <span>/</span><span><?= htmlspecialchars($student['name']) ?></span>
</div>

<div class="page-header">
  <div style="display:flex;align-items:center;gap:14px;">
    <div class="avatar avatar-lg"><?= strtoupper(substr($student['name'],0,1)) ?></div>
    <div>
      <div class="page-header-title">Re-enrol <?= htmlspecialchars($student['name']) ?></div>
      <div class="page-header-sub">Admission No <?= htmlspecialchars($student['admission_no']) ?> — currently <span class="badge badge-<?= $student['status']==='graduated'?'info':'warning' ?>"><?= ucfirst($student['status']) ?></span></div>
    </div>
  </div>
</div>

<div style="max-width:600px;">
  <div class="card">
    <div class="card-header"><div class="card-title">On Record</div></div>
    <div class="card-body">
      <div class="detail-list">
        <div class="detail-item"><div><div class="detail-label">Previous Admission Date</div><div class="detail-value"><?= $student['admission_date'] ? date('d M Y', strtotime($student['admission_date'])) : '—' ?></div></div></div>
        <div class="detail-item"><div><div class="detail-label">Guardian</div><div class="detail-value"><?= htmlspecialchars($student['guardian_name'] ?: '—') ?><?= $student['guardian_phone'] ? ' — '.htmlspecialchars($student['guardian_phone']) : '' ?></div></div></div>
        <?php if(!empty($student['reason_for_leaving'])): ?>
        <div class="detail-item"><div><div class="detail-label">Reason for Leaving</div><div class="detail-value"><?= htmlspecialchars($student['reason_for_leaving']) ?></div></div></div>
        <?php endif; ?>
      </div>
      <div class="form-hint" style="margin-top:12px;">Their admission number, and all prior grades/attendance/fee history, stay linked to this same record — re-enrolling only changes their status back to active.</div>
    </div>
  </div>

  <form method="POST" action="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>/reactivate">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <div class="card mt-16">
      <div class="card-header"><div class="card-title">New Enrolment Details</div></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Assign to Class</label>
            <select name="class_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Admission Date</label>
            <input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>
    <div style="display:flex;gap:12px;margin-top:20px;">
      <button type="submit" class="btn btn-primary">Re-enrol Student</button>
      <a href="<?= $cfg['url'] ?>/school/students/returning?q=<?= urlencode($student['name']) ?>" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
