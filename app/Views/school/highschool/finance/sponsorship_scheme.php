<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$reportHeading = $scheme['name'] . ' — ' . ($year['name'] ?? '');
require ROOT_DIR . '/app/Views/school/highschool/finance/partials/print_head.php';
$methods = Finance::PAYMENT_METHODS;
?>
<div class="page-header">
  <div>
    <div class="breadcrumb no-print"><a href="<?= $base ?>/sponsorships">Sponsorships</a><span>/</span><span><?= htmlspecialchars($scheme['name']) ?></span></div>
    <div class="page-header-title"><?= htmlspecialchars($scheme['name']) ?></div>
    <div class="page-header-sub">
      <?= $scheme['contact_person'] ? htmlspecialchars($scheme['contact_person']) : 'No contact person' ?>
      <?= $scheme['phone'] ? ' · ' . htmlspecialchars($scheme['phone']) : '' ?><?= $scheme['email'] ? ' · ' . htmlspecialchars($scheme['email']) : '' ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;" class="no-print">
    <form method="GET"><select name="year" class="form-control" onchange="this.form.submit()"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year && $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></form>
    <button type="button" class="btn btn-secondary" onclick="window.print()">🖨 Print</button>
  </div>
</div>

<div class="stat-grid">
  <?php foreach ($totals ?: [$def => ['cover' => 0, 'paid' => 0, 'owing' => 0]] as $cur => $t): ?>
    <div class="stat-card"><div class="stat-label">Covers<?= count($totals) > 1 ? " ({$cur})" : '' ?></div><div class="stat-value" style="font-size:22px;"><?= Finance::money($t['cover'], $cur) ?></div><div class="stat-sub"><?= count($rows) ?> child(ren) this year</div></div>
    <div class="stat-card" style="--card-color:var(--success);"><div class="stat-label">Paid in by the sponsor</div><div class="stat-value" style="font-size:22px;"><?= Finance::money($t['paid'], $cur) ?></div></div>
    <div class="stat-card" style="--card-color:var(--danger);"><div class="stat-label">Still to pay</div><div class="stat-value" style="font-size:22px;"><?= Finance::money($t['owing'], $cur) ?></div></div>
  <?php endforeach; ?>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><div class="card-title">Children on this scheme</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Covers</th><th style="text-align:right;">Billed for the year</th><th style="text-align:right;">Cover value</th><th style="text-align:right;">Sponsor paid</th><th style="text-align:right;">Sponsor owes</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr style="<?= $r['status'] === 'ended' ? 'opacity:.55;' : '' ?>">
            <td class="fw-600"><a href="<?= $base ?>/fees-payment?student=<?= (int)$r['student_id'] ?>&year=<?= (int)$r['academic_year_id'] ?>"><?= htmlspecialchars($r['student_name']) ?></a>
              <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?><?= $r['reference'] ? ' · ref ' . htmlspecialchars($r['reference']) : '' ?></div></td>
            <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars(Finance::coverLabel($r, $def)) ?></td>
            <?php foreach (['billed', 'cover', 'paid', 'owing'] as $k): ?>
              <td style="text-align:right;white-space:nowrap;">
                <?php foreach ($r['totals'] as $cur => $v): ?><div style="<?= $k === 'owing' && $v[$k] > 0.005 ? 'color:var(--danger);font-weight:600;' : '' ?>"><?= Finance::money($v[$k], $cur) ?></div><?php endforeach; ?>
                <?php if (!$r['totals']): ?>—<?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td><span class="badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= ucfirst($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:26px;">No children on this scheme for <?= htmlspecialchars($year['name'] ?? 'this year') ?>.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Money received from this scheme (<?= count($payments) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Date</th><th>Receipt no.</th><th>Student</th><th>For</th><th>Method</th><th style="text-align:right;">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr>
            <td><?= date('M j, Y', strtotime($p['pay_date'])) ?></td>
            <td><a href="<?= $base ?>/payments/<?= (int)$p['id'] ?>/receipt" target="_blank"><?= (int)$p['id'] ?></a></td>
            <td><?= htmlspecialchars($p['student_name']) ?> <span style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($p['admission_no']) ?></span></td>
            <td><?= htmlspecialchars($p['label']) ?><?= $p['reference'] ? ' <span style="font-size:11px;color:var(--text-muted);">· ' . htmlspecialchars($p['reference']) . '</span>' : '' ?></td>
            <td><?= htmlspecialchars($methods[$p['method']] ?? ucfirst((string)$p['method'])) ?></td>
            <td style="text-align:right;" class="fw-700"><?= Finance::money($p['amount'], $p['cur'] ?: $def) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:26px;">Nothing received from this scheme yet. Record it on Fees Payment and choose this scheme under "Received from".</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
