<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $pbase = $cfg['url'] . '/school/payroll'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">My Payroll</div>
    <div class="page-header-sub">Your salary details and payslips.</div>
  </div>
</div>
<?php if (!$profile): ?>
  <div class="card"><div class="empty-state"><div class="empty-state-icon">💼</div><div class="empty-state-text">Your payroll details haven’t been set up yet. Please contact the business office.</div></div></div>
<?php else: ?>
  <div class="stat-grid">
    <div class="stat-card" style="--card-color:var(--success);"><div class="stat-value" style="font-size:22px;"><?= Finance::money($profile['base_salary'], $profile['currency']) ?></div><div class="stat-label">Base salary · <?= htmlspecialchars($profile['pay_frequency']) ?></div></div>
    <div class="stat-card" style="--card-color:var(--blue);"><div class="stat-value" style="font-size:20px;"><?= htmlspecialchars($profile['employment_type']) ?></div><div class="stat-label"><?= htmlspecialchars($profile['employment_period']) ?><?= $profile['department'] ? ' · ' . htmlspecialchars($profile['department']) : '' ?></div></div>
    <div class="stat-card" style="--card-color:var(--purple);"><div class="stat-value" style="font-size:20px;"><?= htmlspecialchars($profile['payment_method']) ?></div><div class="stat-label"><?= htmlspecialchars(trim(($profile['momo_provider'] ?? '') . ' ' . ($profile['momo_number'] ?? '') . ' ' . ($profile['bank_name'] ?? ''))) ?: 'Payment method' ?></div></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">My Payslips</div></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Pay Period</th><th style="text-align:right;">Gross</th><th style="text-align:right;">Deductions</th><th style="text-align:right;">Net Pay</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($records as $r): ?>
          <tr><td><?= date('M j', strtotime($r['period_start'])) ?> – <?= date('M j, Y', strtotime($r['period_end'])) ?></td>
            <td style="text-align:right;"><?= Finance::money($r['gross_pay'], $r['currency']) ?></td><td style="text-align:right;"><?= Finance::money((float)$r['deductions'] + (float)$r['tax'], $r['currency']) ?></td>
            <td style="text-align:right;font-weight:700;"><?= Finance::money($r['net_pay'], $r['currency']) ?></td>
            <td><span class="badge <?= $r['status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>"><?= $r['status'] === 'paid' ? 'Paid' : 'Approved' ?></span></td>
            <td><a href="<?= $pbase ?>/payslips/<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline">View Payslip</a></td></tr>
        <?php endforeach; ?>
        <?php if (!$records): ?><tr><td colspan="6" style="color:var(--text-muted);">No payslips yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
