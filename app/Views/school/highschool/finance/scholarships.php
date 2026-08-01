<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'Ksh'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Scholarships &amp; Discounts</div>
    <div class="page-header-sub">Awards, bursaries and concessions applied to student accounts</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addScholarshipModal').classList.add('open')">+ Add Award</button>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Awards (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Award</th><th>Value</th><th>Academic Year</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td>
            <a href="<?= $cfg['url'] ?>/school/students/<?= $r['student_id'] ?>" class="fw-600"><?= htmlspecialchars($r['student_name']) ?></a>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?></div>
          </td>
          <td>
            <?= htmlspecialchars($r['name']) ?>
            <?php if($r['notes']): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['notes']) ?></div><?php endif; ?>
          </td>
          <td>
            <?php if($r['award_type'] === 'percentage'): ?>
              <span class="badge badge-info"><?= rtrim(rtrim(number_format((float)$r['award_value'], 2), '0'), '.') ?>%</span>
            <?php else: ?>
              <span class="badge badge-primary"><?= $cur ?> <?= number_format((float)$r['award_value'], 2) ?></span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['year_name'] ?? 'All years') ?></td>
          <td><span class="badge badge-<?= $r['status']==='active'?'success':'muted' ?>"><?= ucfirst($r['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <?php if($r['status']==='active'): ?>
              <form method="POST" action="<?= $cfg['url'] ?>/school/finance/scholarships/<?= $r['id'] ?>/apply"
                    data-confirm="Credit this award to <?= htmlspecialchars($r['student_name']) ?>'s account now?"
                    data-confirm-title="Apply Award" data-confirm-label="Apply">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
              </form>
              <form method="POST" action="<?= $cfg['url'] ?>/school/finance/scholarships/<?= $r['id'] ?>/end"
                    data-confirm="End this award? It can no longer be applied." data-confirm-title="End Award" data-confirm-label="End">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-outline">End</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🎓</div><div class="empty-state-text">No scholarships or discounts recorded yet.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addScholarshipModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Scholarship or Discount</div>
      <button class="modal-close" onclick="document.getElementById('addScholarshipModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/scholarships/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Student *</label>
          <select name="student_id" class="form-control" required>
            <option value="">— Select Student —</option>
            <?php foreach($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['admission_no']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Award Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Sibling Discount, Staff Child, Merit Bursary">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type *</label>
            <select name="award_type" class="form-control" required>
              <option value="percentage">Percentage of balance</option>
              <option value="fixed">Fixed amount</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Value *</label>
            <input type="number" step="0.01" min="0" name="award_value" class="form-control" required placeholder="e.g. 25">
            <div class="form-hint">A percentage (25 = 25%) or a fixed <?= htmlspecialchars($cur) ?> amount.</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Academic Year</label>
          <select name="academic_year_id" class="form-control">
            <option value="">— All years —</option>
            <?php foreach($years as $y): ?>
              <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addScholarshipModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Award</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
