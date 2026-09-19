<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/finance'; $def = $finSettings['default_currency']; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Payment Approvals</div>
    <div class="page-header-sub">Payments recorded while approval is switched on wait here. They don’t count toward income or reduce what a student owes until approved.</div>
  </div>
  <a href="<?= $base ?>/settings" class="btn btn-outline">Finance Settings</a>
</div>

<?php if ($finSettings['payment_approval'] !== '1'): ?>
  <div class="alert alert-info">Payment approval is switched off, so new payments count immediately. Turn it on in Finance Settings.</div>
<?php endif; ?>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>ID</th><th>Date</th><th>Student</th><th>Bill</th><th>Method / Ref</th><th style="text-align:right;">Amount</th><th>Recorded By</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $p): ?>
          <tr>
            <td>#<?= $p['id'] ?></td>
            <td style="font-size:12.5px;"><?= date('M d, Y', strtotime($p['payment_date'] ?: $p['paid_at'])) ?></td>
            <td><div class="fw-600"><?= htmlspecialchars($p['student_name']) ?></div><div style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($p['admission_no']) ?> · <?= htmlspecialchars($p['class_name'] ?? '') ?></div></td>
            <td><?= htmlspecialchars($p['label']) ?></td>
            <td style="font-size:12.5px;"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$p['method']] ?? $p['method']) ?><?= $p['reference'] ? '<br><span style="color:var(--text-muted)">' . htmlspecialchars($p['reference']) . '</span>' : '' ?>
              <?php if ($p['receipt_file']): ?><br><a href="<?= htmlspecialchars($p['receipt_file']) ?>" target="_blank">📎 Proof</a><?php endif; ?></td>
            <td style="text-align:right;font-weight:700;"><?= Finance::money($p['amount'], $p['currency'] ?: $def) ?></td>
            <td style="font-size:12.5px;"><?= htmlspecialchars($p['received_by_name'] ?? '—') ?></td>
            <td>
              <?php if ($canApprove): ?>
              <div style="display:flex;gap:6px;">
                <form method="POST" action="<?= $base ?>/payments/<?= $p['id'] ?>/approve"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><button class="btn btn-sm btn-success">Approve</button></form>
                <form method="POST" action="<?= $base ?>/payments/<?= $p['id'] ?>/reject" onsubmit="const r=prompt('Reason for rejecting this payment?');if(!r)return false;this.reason.value=r;">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="reason"><button class="btn btn-sm btn-danger">Reject</button></form>
              </div>
              <?php else: ?><span style="font-size:12px;color:var(--text-muted);">Waiting for an approver</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text">No payments are waiting for approval.</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
