<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Online Applications</div>
    <div class="page-header-sub">Review admission applications submitted through the public application form</div>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Pending Review</div>
    <div class="stat-value"><?= (int)($stats['pending'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Approved</div>
    <div class="stat-value"><?= (int)($stats['approved'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Rejected</div>
    <div class="stat-value"><?= (int)($stats['rejected'] ?? 0) ?></div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <select name="status" class="form-control" style="max-width:200px;">
      <?php foreach(['pending'=>'Pending Review','approved'=>'Approved','rejected'=>'Rejected','' =>'All Statuses'] as $val=>$label): ?>
        <option value="<?= $val ?>" <?= $status===$val?'selected':'' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">Applications (<?= count($applications) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Reference</th><th>Applicant</th><th>Desired Class</th><th>Guardian</th><th>Phone</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach($applications as $a): ?>
        <tr>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($a['reference_no']) ?></td>
          <td class="fw-600"><?= htmlspecialchars(trim($a['first_name'].' '.$a['last_name'])) ?></td>
          <td><?= htmlspecialchars($a['desired_class_name'] ?? 'Not sure') ?></td>
          <td><?= htmlspecialchars($a['guardian_name']) ?></td>
          <td><?= htmlspecialchars($a['guardian_phone']) ?></td>
          <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
          <td><span class="badge badge-<?= $a['status']==='approved'?'success':($a['status']==='rejected'?'danger':'warning') ?>"><?= ucfirst($a['status']) ?></span></td>
          <td><a href="<?= $cfg['url'] ?>/school/admissions/<?= $a['id'] ?>" class="btn btn-sm btn-outline"><?= $a['status']==='pending'?'Review':'View' ?></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($applications)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">📝</div>
            <div class="empty-state-text">No applications found.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
