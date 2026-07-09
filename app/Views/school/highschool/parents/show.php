<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/parents">Parents</a><span>/</span><span><?= htmlspecialchars($parent['name']) ?></span></div>

<div class="card profile-hero">
  <div class="profile-hero-body">
    <div class="avatar avatar-xl"><?= strtoupper(substr($parent['name'],0,1)) ?></div>
    <div class="profile-hero-info">
      <div class="profile-hero-name"><?= htmlspecialchars($parent['name']) ?></div>
      <div class="profile-hero-meta">
        <span class="meta-chip">✉️ <?= htmlspecialchars($parent['email']) ?></span>
        <?php if($parent['occupation']): ?><span class="meta-chip">💼 <?= htmlspecialchars($parent['occupation']) ?></span><?php endif; ?>
        <span class="badge badge-info"><?= count($children) ?> Linked Child<?= count($children)===1?'':'ren' ?></span>
      </div>
    </div>
    <div class="profile-hero-actions">
      <a href="<?= $cfg['url'] ?>/school/parents/<?= $parent['id'] ?>/edit" class="btn btn-secondary">Edit Profile</a>
    </div>
  </div>
</div>

<div class="profile-layout">
  <div class="profile-stack">

    <div class="card">
      <div class="card-header"><div class="card-title">Contact Info</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">✉️</div>
            <div><div class="detail-label">Email</div><div class="detail-value"><?= htmlspecialchars($parent['email']) ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">📞</div>
            <div><div class="detail-label">Phone</div><div class="detail-value"><?= htmlspecialchars($parent['phone'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🚨</div>
            <div><div class="detail-label">Emergency Contact</div><div class="detail-value"><?= htmlspecialchars($parent['emergency_contact_phone'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🏠</div>
            <div><div class="detail-label">Address</div><div class="detail-value"><?= htmlspecialchars($parent['address'] ?? '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Professional Info</div></div>
      <div class="card-body">
        <div class="detail-list">
          <div class="detail-item">
            <div class="detail-icon">💼</div>
            <div><div class="detail-label">Occupation</div><div class="detail-value"><?= htmlspecialchars($parent['occupation'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🏢</div>
            <div><div class="detail-label">Workplace</div><div class="detail-value"><?= htmlspecialchars($parent['workplace'] ?? '—') ?></div></div>
          </div>
          <div class="detail-item">
            <div class="detail-icon">🪪</div>
            <div><div class="detail-label">National ID</div><div class="detail-value"><?= htmlspecialchars($parent['national_id'] ?? '—') ?></div></div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="profile-stack">

    <div class="card">
      <div class="card-header">
        <div class="card-title">Linked Children (<?= count($children) ?>)</div>
        <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('linkChildModal').classList.add('open')">+ Link Child</button>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Student</th><th>Admission No</th><th>Class</th><th>Relationship</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach($children as $c): ?>
            <tr>
              <td><a href="<?= $cfg['url'] ?>/school/students/<?= $c['id'] ?>" class="fw-600"><?= htmlspecialchars($c['name']) ?></a></td>
              <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($c['admission_no']) ?></td>
              <td><?= htmlspecialchars($c['class_name'] ?? '—') ?></td>
              <td><?= htmlspecialchars(ucfirst($c['relationship'] ?? 'parent')) ?></td>
              <td><span class="badge badge-<?= $c['status']==='active'?'success':'warning' ?>"><?= ucfirst($c['status']) ?></span></td>
              <td>
                <form method="POST" action="<?= $cfg['url'] ?>/school/parents/<?= $parent['id'] ?>/children/<?= $c['id'] ?>/unlink" data-confirm="Unlink <?= htmlspecialchars($c['name']) ?> from <?= htmlspecialchars($parent['name']) ?>?" data-confirm-label="Unlink">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <button type="submit" class="btn btn-sm btn-outline">Unlink</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($children)): ?>
            <tr><td colspan="6">
              <div class="empty-state">
                <div class="empty-state-icon">👨‍👩‍👧</div>
                <div class="empty-state-text">No children linked yet. <a href="javascript:void(0)" onclick="document.getElementById('linkChildModal').classList.add('open')">Link one</a></div>
              </div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Link Child Modal -->
<div class="modal-overlay" id="linkChildModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Link Child to <?= htmlspecialchars($parent['name']) ?></div>
      <button class="modal-close" onclick="document.getElementById('linkChildModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/parents/<?= $parent['id'] ?>/children/link">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Student *</label>
          <select name="student_id" class="form-control" required>
            <option value="">— Select Student —</option>
            <?php foreach($availableStudents as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if(empty($availableStudents)): ?><div class="form-hint">All active students are already linked to this parent.</div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Relationship</label>
          <select name="relationship" class="form-control">
            <?php foreach(['parent','mother','father','guardian'] as $r): ?>
              <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('linkChildModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Link Child</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
