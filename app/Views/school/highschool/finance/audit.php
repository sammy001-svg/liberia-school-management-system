<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$def = $finSettings['default_currency'];
$reportHeading = 'Payment Records Audit — ' . date('M j, Y', strtotime($filters['from'])) . ' to ' . date('M j, Y', strtotime($filters['to']));
$badge = ['active' => 'badge-success', 'pending' => 'badge-warning', 'cancelled' => 'badge-muted', 'rejected' => 'badge-danger'];
?>
<?php require __DIR__ . '/partials/print_head.php'; ?>
<div class="page-header no-print">
  <div>
    <div class="page-header-title">Payment Records Audit</div>
    <div class="page-header-sub">Reconcile fee payments: totals by method and by the staff member who took them, with every cancelled or rejected payment shown and who did it.</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="window.print()">🖨 Print</button>
</div>
<?php if ($finSettings['payment_audit'] !== '1'): ?><div class="alert alert-info no-print">The audit view is switched off in Finance Settings; it still works here for administrators.</div><?php endif; ?>

<form method="GET" class="card no-print" style="padding:14px 18px;margin-bottom:16px;">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="date" name="from" class="form-control" style="max-width:160px;" value="<?= htmlspecialchars($filters['from']) ?>">
    <span style="color:var(--text-muted);font-size:12px;">to</span>
    <input type="date" name="to" class="form-control" style="max-width:160px;" value="<?= htmlspecialchars($filters['to']) ?>">
    <select name="method" class="form-control" style="max-width:160px;"><option value="">All Methods</option><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>" <?= $filters['method'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
    <select name="user" class="form-control" style="max-width:190px;"><option value="">All Staff</option><?php foreach ($users as $u): ?><option value="<?= $u['id'] ?>" <?= $filters['user'] == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option><?php endforeach; ?></select>
    <button type="submit" class="btn btn-secondary">Filter</button>
  </div>
</form>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;" class="aud-grid">
  <?php foreach (['By Method' => $byMethod, 'By Staff Member' => $byUser] as $title => $data): ?>
    <div class="card"><div class="card-header"><div class="card-title"><?= $title ?></div></div>
      <div class="table-wrapper"><table><tbody>
        <?php foreach ($data as $cur => $items): arsort($items); foreach ($items as $k => $v): ?>
          <tr><td><?= htmlspecialchars($title === 'By Method' ? (Finance::PAYMENT_METHODS[$k] ?? ucfirst($k)) : $k) ?></td><td style="text-align:right;font-weight:700;"><?= Finance::money($v, $cur) ?></td></tr>
        <?php endforeach; ?><tr style="font-weight:800;border-top:2px solid var(--border);"><td>Total</td><td style="text-align:right;"><?= Finance::money(array_sum($items), $cur) ?></td></tr><?php endforeach; ?>
        <?php if (!$data): ?><tr><td style="color:var(--text-muted);">No active payments in this period.</td></tr><?php endif; ?>
      </tbody></table></div></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Payment Records (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>ID</th><th>Date</th><th>Student</th><th>Bill</th><th>Method / Ref</th><th>Taken By</th><th style="text-align:right;">Amount</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $p): ?>
        <tr style="<?= $p['status'] !== 'active' ? 'opacity:.6;' : '' ?>">
          <td>#<?= $p['id'] ?></td><td style="font-size:12.5px;"><?= date('M d, Y', strtotime($p['payment_date'] ?: $p['paid_at'])) ?></td>
          <td><?= htmlspecialchars($p['student_name']) ?> <span style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($p['admission_no']) ?></span></td>
          <td><?= htmlspecialchars($p['label']) ?></td>
          <td style="font-size:12.5px;"><?= htmlspecialchars(Finance::PAYMENT_METHODS[$p['method']] ?? $p['method']) ?><?= $p['reference'] ? ' · ' . htmlspecialchars($p['reference']) : '' ?></td>
          <td style="font-size:12.5px;"><?= htmlspecialchars($p['received_by_name'] ?? '—') ?><?= $p['approved_by_name'] ? '<br><span style="color:var(--text-muted)">Approved: ' . htmlspecialchars($p['approved_by_name']) . '</span>' : '' ?></td>
          <td style="text-align:right;font-weight:700;"><?= Finance::money($p['amount'], $p['currency'] ?: $def) ?></td>
          <td><span class="badge <?= $badge[$p['status']] ?? 'badge-muted' ?>"><?= ucfirst($p['status']) ?></span>
            <?php if ($p['status'] === 'cancelled' || $p['status'] === 'rejected'): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($p['cancelled_by_name'] ?? '') ?><?= $p['cancel_reason'] ? ': ' . htmlspecialchars($p['cancel_reason']) : '' ?></div><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="8" style="color:var(--text-muted);">No payments in this period.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<style>@media (max-width: 800px) { .aud-grid { grid-template-columns: 1fr !important; } }</style>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
