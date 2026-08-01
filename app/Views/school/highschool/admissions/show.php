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
        <?php if(!empty($application['student_photo']) || !empty($application['parent_photo'])): ?>
        <div style="display:flex;gap:16px;margin-bottom:18px;flex-wrap:wrap;">
          <?php foreach([['student_photo','Student'],['parent_photo','Parent']] as [$field,$label]): ?>
            <?php if(!empty($application[$field])): ?>
            <div style="text-align:center;">
              <a href="<?= htmlspecialchars($application[$field]) ?>" target="_blank">
                <img src="<?= htmlspecialchars($application[$field]) ?>" alt="<?= $label ?> photo"
                     style="width:96px;height:96px;object-fit:cover;border-radius:10px;border:1px solid var(--border);display:block;">
              </a>
              <div style="font-size:11px;color:var(--text-muted);margin-top:6px;"><?= $label ?>'s Photo</div>
            </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🎓</div>
            <div><div class="detail-label">Full Name</div><div class="detail-value"><?= htmlspecialchars(trim($application['first_name'].' '.($application['middle_name']?:'').' '.$application['last_name'])) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🎂</div>
            <div><div class="detail-label">Date of Birth</div><div class="detail-value"><?= $application['date_of_birth'] ? date('d M Y', strtotime($application['date_of_birth'])) : '—' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🏫</div>
            <div><div class="detail-label">Class Promoted To</div><div class="detail-value"><?= htmlspecialchars($application['desired_class_name'] ?? 'Not specified') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Parents</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">👩</div>
            <div><div class="detail-label">Mother</div><div class="detail-value"><?= htmlspecialchars($application['mother_name'] ?: '—') ?><?= $application['mother_phone'] ? ' · '.htmlspecialchars($application['mother_phone']) : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">👨</div>
            <div><div class="detail-label">Father</div><div class="detail-value"><?= htmlspecialchars($application['father_name'] ?: '—') ?><?= $application['father_phone'] ? ' · '.htmlspecialchars($application['father_phone']) : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">Home Address</div><div class="detail-value"><?= htmlspecialchars($application['address'] ?: '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Previous School Information</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🏛️</div>
            <div><div class="detail-label">Last School Attended</div><div class="detail-value"><?= htmlspecialchars($application['previous_school'] ?: '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">School Address</div><div class="detail-value"><?= htmlspecialchars($application['previous_school_address'] ?: '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🎓</div>
            <div><div class="detail-label">Principal</div><div class="detail-value"><?= htmlspecialchars($application['principal_name'] ?: '—') ?><?= $application['principal_phone'] ? ' · '.htmlspecialchars($application['principal_phone']) : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🤝</div>
            <div><div class="detail-label">Sponsor</div><div class="detail-value"><?= htmlspecialchars($application['sponsor_name'] ?: '—') ?><?= $application['sponsor_phone'] ? ' · '.htmlspecialchars($application['sponsor_phone']) : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🎒</div>
            <div><div class="detail-label">Last Class</div><div class="detail-value"><?= htmlspecialchars($application['last_class'] ?: '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Emergency Contact</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🚨</div>
            <div><div class="detail-label">Name</div><div class="detail-value"><?= htmlspecialchars($application['emergency_name'] ?: '—') ?><?= $application['emergency_relationship'] ? ' ('.htmlspecialchars($application['emergency_relationship']).')' : '' ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📱</div>
            <div><div class="detail-label">Phone</div><div class="detail-value"><?= htmlspecialchars($application['emergency_phone'] ?: '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">✉️</div>
            <div><div class="detail-label">Email</div><div class="detail-value"><?= htmlspecialchars($application['emergency_email'] ?: '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📍</div>
            <div><div class="detail-label">Address</div><div class="detail-value"><?= htmlspecialchars($application['emergency_address'] ?: '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <?php if(!empty($application['medical_conditions']) || !empty($application['allergies'])): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Medical Information</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">🩺</div>
            <div><div class="detail-label">Medical Condition</div><div class="detail-value"><?= nl2br(htmlspecialchars($application['medical_conditions'] ?: '—')) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">⚠️</div>
            <div><div class="detail-label">Food / Medicine Allergies</div><div class="detail-value"><?= nl2br(htmlspecialchars($application['allergies'] ?: '—')) ?></div></div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if(!empty($pickupPersons)): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Authorized to Pick Up</div></div>
      <div class="table-wrapper"><table>
        <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Address</th></tr></thead>
        <tbody>
          <?php foreach($pickupPersons as $p): ?>
          <tr>
            <td><?= (int)$p['sort_order'] ?></td>
            <td class="fw-600"><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['phone'] ?? '—') ?></td>
            <td><?= htmlspecialchars($p['address'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
    <?php endif; ?>

    <?php if(!empty($application['notes'])): ?>
    <div class="card">
      <div class="card-header"><div class="card-title">Additional Notes</div></div>
      <div class="card-body"><?= nl2br(htmlspecialchars($application['notes'])) ?></div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><div class="card-title">Attached Documents (<?= count($documents) ?>)</div></div>
      <?php if(empty($documents)): ?>
      <div class="card-body">
        <div class="empty-state">
          <div class="empty-state-icon">📎</div>
          <div class="empty-state-text">No documents were attached to this application.</div>
        </div>
      </div>
      <?php else: ?>
      <div class="table-wrapper"><table>
        <thead><tr><th>Type</th><th>File</th><th></th></tr></thead>
        <tbody>
          <?php foreach($documents as $doc): ?>
          <tr>
            <td><span class="badge badge-info"><?= htmlspecialchars($doc['document_type']) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($doc['file_name'] ?? '—') ?></td>
            <td><a href="<?= htmlspecialchars($doc['file_url']) ?>" target="_blank" class="btn btn-sm btn-outline">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
      <?php if($application['status'] === 'pending'): ?>
      <div class="card-body" style="padding-top:12px;">
        <p style="font-size:12px;color:var(--text-muted);margin:0;">These files are copied to the student's profile automatically on approval.</p>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

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
