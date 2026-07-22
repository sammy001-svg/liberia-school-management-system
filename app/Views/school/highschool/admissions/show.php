<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/admissions">Online Applications</a><span>/</span><span><?= htmlspecialchars($application['reference_no']) ?></span></div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars(trim($application['first_name'].' '.$application['last_name'])) ?></div>
    <div class="page-header-sub">Reference: <?= htmlspecialchars($application['reference_no']) ?> · Submitted <?= date('d M Y', strtotime($application['created_at'])) ?></div>
  </div>
  <span class="badge badge-<?= $application['status']==='approved'?'success':($application['status']==='rejected'?'danger':'warning') ?>" style="font-size:13px;padding:6px 14px;"><?= ucfirst($application['status']) ?></span>
</div>

<div class="profile-layout">
  <div class="profile-stack">

    <div class="card">
      <div class="card-header"><div class="card-title">Student Information</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🎓</div>
            <div><div class="detail-label">Full Name</div><div class="detail-value"><?= htmlspecialchars(trim($application['first_name'].' '.($application['middle_name']?:'').' '.$application['last_name'])) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon"><?= $application['gender']==='female'?'♀':'♂' ?></div>
            <div><div class="detail-label">Gender</div><div class="detail-value"><?= $application['gender'] ? ucfirst($application['gender']) : '—' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🎂</div>
            <div><div class="detail-label">Date of Birth</div><div class="detail-value"><?= $application['date_of_birth'] ? date('d M Y', strtotime($application['date_of_birth'])) : '—' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🏫</div>
            <div><div class="detail-label">Desired Class</div><div class="detail-value"><?= htmlspecialchars($application['desired_class_name'] ?? 'Not sure') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Guardian Information</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">👪</div>
            <div><div class="detail-label">Guardian</div><div class="detail-value"><?= htmlspecialchars($application['guardian_name']) ?><?= $application['guardian_relationship'] ? ' ('.htmlspecialchars($application['guardian_relationship']).')' : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📱</div>
            <div><div class="detail-label">Phone</div><div class="detail-value"><?= htmlspecialchars($application['guardian_phone']) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">✉️</div>
            <div><div class="detail-label">Email</div><div class="detail-value"><?= htmlspecialchars($application['guardian_email'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">Address</div><div class="detail-value"><?= htmlspecialchars($application['address'] ?? '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <?php if(!empty($application['previous_school']) || !empty($application['previous_class']) || !empty($application['notes'])): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Previous School &amp; Notes</div></div>
      <div class="card-body">
        <div class="detail-list">
          <?php if(!empty($application['previous_school'])): ?>
          <div class="detail-item">
            <div class="detail-icon">🏛️</div>
            <div><div class="detail-label">Previous School</div><div class="detail-value"><?= htmlspecialchars($application['previous_school']) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if(!empty($application['previous_class'])): ?>
          <div class="detail-item">
            <div class="detail-icon">🎒</div>
            <div><div class="detail-label">Previous Class</div><div class="detail-value"><?= htmlspecialchars($application['previous_class']) ?></div></div>
          </div>
          <?php endif; ?>
          <?php if(!empty($application['notes'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📝</div>
            <div><div class="detail-label">Notes</div><div class="detail-value"><?= nl2br(htmlspecialchars($application['notes'])) ?></div></div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="profile-stack">
    <?php if($application['status'] === 'pending'): ?>

    <div class="card">
      <div class="card-header"><div class="card-title">Approve &amp; Enrol</div></div>
      <form method="POST" action="<?= $cfg['url'] ?>/school/admissions/<?= $application['id'] ?>/approve">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="card-body">
          <p style="font-size:13px;color:var(--text-light);margin-bottom:16px;">Approving creates a student record and login PIN immediately.</p>
          <div class="form-group">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $application['desired_class_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Admission Date</label>
            <input type="date" name="admission_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--border);">
          <button type="submit" class="btn btn-primary btn-block">✓ Approve &amp; Enrol</button>
        </div>
      </form>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Reject</div></div>
      <form method="POST" action="<?= $cfg['url'] ?>/school/admissions/<?= $application['id'] ?>/reject" data-confirm="Reject this application?" data-confirm-title="Reject Application" data-confirm-label="Reject">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <div class="card-body">
          <div class="form-group">
            <label class="form-label">Reason (optional)</label>
            <textarea name="review_notes" class="form-control" rows="3" placeholder="Shared internally, not sent to the applicant"></textarea>
          </div>
        </div>
        <div class="modal-footer" style="border-top:1px solid var(--border);">
          <button type="submit" class="btn btn-danger btn-block">✕ Reject Application</button>
        </div>
      </form>
    </div>

    <?php else: ?>

    <div class="card">
      <div class="card-header"><div class="card-title">Review Outcome</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon"><?= $application['status']==='approved'?'✅':'❌' ?></div>
            <div><div class="detail-label">Status</div><div class="detail-value"><?= ucfirst($application['status']) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">👤</div>
            <div><div class="detail-label">Reviewed By</div><div class="detail-value"><?= htmlspecialchars($application['reviewed_by_name'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📅</div>
            <div><div class="detail-label">Reviewed On</div><div class="detail-value"><?= $application['reviewed_at'] ? date('d M Y H:i', strtotime($application['reviewed_at'])) : '—' ?></div></div>
          </div>
          <?php if(!empty($application['review_notes'])): ?>
          <div class="detail-item">
            <div class="detail-icon">📝</div>
            <div><div class="detail-label">Notes</div><div class="detail-value"><?= htmlspecialchars($application['review_notes']) ?></div></div>
          </div>
          <?php endif; ?>
        </div>
        <?php if($application['status']==='approved' && !empty($application['student_id'])): ?>
        <a href="<?= $cfg['url'] ?>/school/students/<?= $application['student_id'] ?>" class="btn btn-primary btn-block" style="margin-top:16px;">View Student Profile</a>
        <?php endif; ?>
      </div>
    </div>

    <?php endif; ?>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
