<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/finance'; $def = $finSettings['default_currency']; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Expense Approvals</div>
    <div class="page-header-sub">Expenses recorded while approval is switched on wait here and don’t count until approved.</div>
  </div>
  <a href="<?= $base ?>/expenses" class="btn btn-outline">← Expenses</a>
</div>
<?php if ($finSettings['expense_approval'] !== '1'): ?><div class="alert alert-info">Expense approval is switched off, so new expenses count immediately. Turn it on in Finance Settings.</div><?php endif; ?>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>ID</th><th>Date</th><th>Payee</th><th>Category</th><th>Description</th><th style="text-align:right;">Amount</th><th>Recorded By</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $e): ?>
          <tr>
            <td>#<?= $e['id'] ?></td>
            <td style="font-size:12.5px;"><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
            <td class="fw-600"><?= htmlspecialchars($e['payee']) ?></td>
            <td><?= htmlspecialchars($e['category']) ?></td>
            <td><?= htmlspecialchars($e['description']) ?></td>
            <td style="text-align:right;font-weight:700;"><?= Finance::money($e['amount'], $e['currency'] ?: $def) ?></td>
            <td style="font-size:12.5px;"><?= htmlspecialchars($e['recorded_by_name'] ?? '—') ?></td>
            <td>
              <?php if ($canApprove): ?>
              <div style="display:flex;gap:6px;">
                <form method="POST" action="<?= $base ?>/expenses/<?= $e['id'] ?>/decide"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="decision" value="approve"><button class="btn btn-sm btn-success">Approve</button></form>
                <form method="POST" action="<?= $base ?>/expenses/<?= $e['id'] ?>/decide" onsubmit="const r=prompt('Reason for rejecting?');if(!r)return false;this.reason.value=r;">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="decision" value="reject"><input type="hidden" name="reason"><button class="btn btn-sm btn-danger">Reject</button></form>
              </div>
              <?php else: ?><span style="font-size:12px;color:var(--text-muted);">Waiting for an approver</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">✅</div><div class="empty-state-text">No expense approval requests at this time.</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
