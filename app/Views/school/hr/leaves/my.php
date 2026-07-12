<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">My Leave</div>
    <div class="page-header-sub">Submit and track your own leave requests</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('applyLeaveModal').classList.add('open')">+ Apply for Leave</button>
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
    <div class="card-header"><div class="card-title">My Applications (<?= count($leaves) ?>)</div></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($leaves as $l): ?>
                <tr>
                    <td style="text-transform:capitalize;"><?= htmlspecialchars($l['leave_type']) ?></td>
                    <td style="font-size:12px;"><?= date('M d', strtotime($l['start_date'])) ?> – <?= date('M d, Y', strtotime($l['end_date'])) ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($l['reason'] ?: '—') ?></td>
                    <td>
                        <?php $badge = $l['status'] === 'approved' ? 'badge-success' : ($l['status'] === 'rejected' ? 'badge-danger' : 'badge-warning'); ?>
                        <span class="badge <?= $badge ?>"><?= strtoupper($l['status']) ?></span>
                    </td>
                    <td style="font-size:12px;color:var(--text-muted);"><?= date('M d, Y', strtotime($l['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($leaves)): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="empty-state-icon">🗓️</div>
                        <div class="empty-state-text">No leave requests yet. <a href="javascript:void(0)" onclick="document.getElementById('applyLeaveModal').classList.add('open')">Apply for leave</a></div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Apply for Leave Modal -->
<div class="modal-overlay" id="applyLeaveModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Apply for Leave</div>
      <button class="modal-close" onclick="document.getElementById('applyLeaveModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/my-leave/apply">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Leave Type *</label>
          <select name="leave_type" class="form-control" required>
            <option value="annual">Annual</option>
            <option value="sick">Sick</option>
            <option value="maternity">Maternity</option>
            <option value="paternity">Paternity</option>
            <option value="unpaid">Unpaid</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Date *</label>
            <input type="date" name="start_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date *</label>
            <input type="date" name="end_date" class="form-control" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason</label>
          <textarea name="reason" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('applyLeaveModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Request</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
