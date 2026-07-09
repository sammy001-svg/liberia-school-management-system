<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Manage Collection</div>
    <div class="page-header-sub">Students with outstanding balances, sorted by amount owed</div>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Total Outstanding</div>
    <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['totalOutstanding'] ?? 0,2) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Students Owing</div>
    <div class="stat-value"><?= (int)($stats['studentsOwing'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Past Due Date</div>
    <div class="stat-value"><?= (int)($stats['overdue'] ?? 0) ?></div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <label style="color:var(--text-muted);font-size:13px">Class:</label>
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/finance/collection" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">Outstanding Balances (<?= count($balances) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Guardian Contact</th><th>Invoices</th><th>Balance</th><th>Oldest Due</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($balances as $b): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar"><?= strtoupper(substr($b['name'],0,1)) ?></div>
              <div class="fw-600"><?= htmlspecialchars($b['name']) ?></div>
            </div>
          </td>
          <td><?= htmlspecialchars($b['class_name'] ?? '—') ?></td>
          <td style="font-size:12px;"><?= htmlspecialchars($b['guardian_phone'] ?? $b['phone'] ?? '—') ?></td>
          <td><span class="badge badge-muted"><?= $b['invoice_count'] ?></span></td>
          <td class="text-danger fw-700"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($b['balance'],2) ?></td>
          <td style="font-size:12px;color:var(--text-muted)"><?= $b['oldest_due_date'] ? date('M d, Y', strtotime($b['oldest_due_date'])) : '—' ?></td>
          <td>
            <?php if($b['days_overdue'] !== null && $b['days_overdue'] > 0): ?>
              <span class="badge badge-danger"><?= (int)$b['days_overdue'] ?> days overdue</span>
            <?php else: ?>
              <span class="badge badge-warning">Not yet due</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/finance/invoices?student_id=<?= $b['student_id'] ?>" class="btn btn-sm btn-primary">View Invoices</a>
              <a href="<?= $cfg['url'] ?>/school/students/<?= $b['student_id'] ?>" class="btn btn-sm btn-outline">Profile</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($balances)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🎉</div>
            <div class="empty-state-text">No outstanding balances<?= $classId ? ' for this class' : '' ?>. Every invoice is fully paid.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
