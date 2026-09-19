<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$tabs = ['pending' => 'Pending Requests (' . (int)($counts['pending'] ?? 0) . ')', 'reviewed' => 'Reviewed Requests', 'audit' => 'Override Audit Log', 'writeoffs' => 'Balance Write-offs'];
?>
<style>.tabs-line{display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:16px;flex-wrap:wrap}.tabs-line a{padding:10px 16px;font-size:13px;font-weight:600;color:var(--text-muted);border-bottom:2px solid transparent}.tabs-line a.active{color:var(--primary);border-bottom-color:var(--primary)}</style>
<div class="page-header">
  <div>
    <div class="page-header-title">Arrears Override Approvals</div>
    <div class="page-header-sub">Approving registers the student for the requested year even though prior-year balances are outstanding. The debt is <strong>not</strong> cleared — it stays on the student’s record and every approval is logged. To write a balance off, use “Clear balance & register” or Clear balance on the Arrears page.</div>
  </div>
  <a href="<?= $base ?>/arrears-collection" class="btn btn-outline">Arrears Collection</a>
</div>

<div class="tabs-line"><?php foreach ($tabs as $k => $l): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= $l ?></a><?php endforeach; ?></div>

<div class="card">
  <div class="table-wrapper">
    <?php if ($tab === 'writeoffs'): ?>
      <table>
        <thead><tr><th>Date</th><th>Student</th><th>Year</th><th>Bill</th><th style="text-align:right;">Amount</th><th>Reason</th><th>By</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $w): ?>
            <tr><td style="font-size:12.5px;"><?= date('M d, Y', strtotime($w['created_at'])) ?></td><td><span class="fw-600"><?= htmlspecialchars($w['student_name']) ?></span><br><span style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($w['admission_no']) ?></span></td>
              <td><?= htmlspecialchars($w['year_name'] ?? '—') ?></td><td><?= htmlspecialchars($w['label'] ?? '—') ?></td><td style="text-align:right;font-weight:700;"><?= Finance::money($w['amount'], $w['currency']) ?></td>
              <td><?= htmlspecialchars($w['reason']) ?></td><td style="font-size:12.5px;"><?= htmlspecialchars($w['by_name'] ?? '—') ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="7"><div class="empty-state"><div class="empty-state-text">No balances have been written off.</div></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    <?php else: ?>
      <table>
        <thead><tr><th>Requested</th><th>Student</th><th>For</th><th>Outstanding</th><th>Reason</th><?= $tab === 'pending' ? '<th>Actions</th>' : '<th>Decision</th>' ?></tr></thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td style="font-size:12.5px;"><?= date('M d, Y', strtotime($r['requested_at'])) ?><br><span style="color:var(--text-muted);"><?= htmlspecialchars($r['requested_by_name'] ?? '') ?></span></td>
              <td><span class="fw-600"><?= htmlspecialchars($r['student_name']) ?></span><br><span style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?></span></td>
              <td style="font-size:12.5px;"><?= htmlspecialchars($r['year_name'] ?? '') ?><br><?= htmlspecialchars($r['class_name'] ?? '') ?> · <?= htmlspecialchars($r['type_name'] ?? '') ?> · <?= $r['category'] === 'old' ? 'Old' : 'New' ?></td>
              <td style="color:var(--danger);font-weight:700;"><?= htmlspecialchars($r['outstanding'] ?? '') ?><br><a href="<?= $base ?>/arrears-collection?student=<?= $r['student_id'] ?>" style="font-size:11.5px;font-weight:400;">View bills</a></td>
              <td style="font-size:12.5px;max-width:220px;"><?= htmlspecialchars($r['reason'] ?? '—') ?></td>
              <?php if ($tab === 'pending'): ?>
                <td>
                  <?php if ($canApprove): ?>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;">
                    <button type="button" class="btn btn-sm btn-success" onclick="decide(<?= $r['id'] ?>,'approve')">Approve</button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="decide(<?= $r['id'] ?>,'clear')">Clear balance &amp; register</button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="decide(<?= $r['id'] ?>,'reject')">Reject</button>
                  </div>
                  <?php else: ?><span style="font-size:12px;color:var(--text-muted);">Waiting for an approver</span><?php endif; ?>
                </td>
              <?php else: ?>
                <td style="font-size:12.5px;"><span class="badge <?= $r['status'] === 'approved' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($r['status']) ?></span><br><?= htmlspecialchars($r['reviewed_by_name'] ?? '') ?> · <?= $r['reviewed_at'] ? date('M d, Y', strtotime($r['reviewed_at'])) : '' ?><?= $r['review_note'] ? '<br><span style="color:var(--text-muted)">' . htmlspecialchars($r['review_note']) . '</span>' : '' ?></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text"><?= $tab === 'pending' ? 'No arrears override requests are waiting for approval.' : 'Nothing here yet.' ?></div></div></td></tr><?php endif; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>

<div class="modal-overlay" id="decideModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="decideTitle"></div><button type="button" class="modal-close" onclick="document.getElementById('decideModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="decideForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="decision" id="decideValue">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;" id="decideText"></p>
        <div class="form-group"><label class="form-label" id="decideLabel">Note</label><textarea name="note" id="decideNote" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('decideModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Confirm</button></div>
    </form>
  </div>
</div>
<script>
function decide(id, d) {
  const t = { approve: ['Approve Override', 'The student is registered; the prior-year debt stays on their record.', 'Note (optional)', false],
              clear: ['Clear Balance & Register', 'All prior-year balances are written off (logged under Balance Write-offs) and the student is registered.', 'Reason *', true],
              reject: ['Reject Request', 'The student stays unregistered until the arrears are paid.', 'Note (optional)', false] }[d];
  document.getElementById('decideForm').action = '<?= $base ?>/arrears-overrides/' + id + '/decide';
  document.getElementById('decideValue').value = d;
  document.getElementById('decideTitle').textContent = t[0];
  document.getElementById('decideText').textContent = t[1];
  document.getElementById('decideLabel').textContent = t[2];
  document.getElementById('decideNote').required = t[3];
  document.getElementById('decideModal').classList.add('open');
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
