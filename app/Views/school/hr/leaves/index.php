<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Leave Applications</div>
    <div class="page-header-sub">Review and action staff leave requests</div>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Pending</div>
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

<div class="card">
    <div class="card-header"><div class="card-title">All Applications (<?= count($leaves) ?>)</div></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($leaves as $l): ?>
                <tr>
                    <td><div style="display:flex;align-items:center;gap:10px;"><div class="avatar"><?= strtoupper(substr($l['staff_name'],0,1)) ?></div><div class="fw-600"><?= htmlspecialchars($l['staff_name']) ?></div></div></td>
                    <td style="text-transform:capitalize;"><?= htmlspecialchars($l['leave_type']) ?></td>
                    <td style="font-size:12px;"><?= date('M d', strtotime($l['start_date'])) ?> – <?= date('M d, Y', strtotime($l['end_date'])) ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($l['reason'] ?: '—') ?></td>
                    <td>
                        <?php
                        $badge = $l['status'] === 'approved' ? 'badge-success' : ($l['status'] === 'rejected' ? 'badge-danger' : 'badge-warning');
                        ?>
                        <span class="badge <?= $badge ?>"><?= strtoupper($l['status']) ?></span>
                    </td>
                    <td>
                        <?php if($l['status'] === 'pending'): ?>
                        <form method="POST" action="<?= $cfg['url'] ?>/school/hr/leaves/approve" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button name="status" value="approved" class="btn btn-sm btn-success">Approve</button>
                            <button name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                        <?php else: ?>
                            <span class="text-muted" style="font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($leaves)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon">🗓️</div>
                        <div class="empty-state-text">No leave applications found.</div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
