<?php $cur = $r['currency']; $m = fn($v) => Finance::money($v, $cur); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payslip — <?= htmlspecialchars($r['name']) ?></title>
<style>
  body { margin: 0; background: #e9e9ee; font-family: Arial, Helvetica, sans-serif; color: #111; }
  .toolbar { position: sticky; top: 0; background: #1e1b2e; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; }
  .toolbar button { background: #4cae72; color: #fff; border: 0; border-radius: 6px; padding: 8px 16px; font-weight: 700; cursor: pointer; }
  .sheet { width: 190mm; margin: 12px auto; background: #fff; padding: 12mm; }
  .head { display: flex; gap: 14px; align-items: center; border-bottom: 2px solid #222; padding-bottom: 10px; }
  .head img { width: 60px; height: 60px; object-fit: contain; }
  .name { font-size: 22px; font-weight: 800; color: #5b2d82; text-transform: uppercase; }
  .meta { font-size: 11px; }
  h2 { font-size: 16px; margin: 14px 0 10px; letter-spacing: .08em; }
  .info { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 30px; font-size: 13px; margin-bottom: 14px; }
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  th, td { border: 1px solid #bbb; padding: 7px 10px; }
  th { background: #f1ecf6; text-align: left; }
  td.r, th.r { text-align: right; }
  .net { font-size: 16px; font-weight: 800; background: #eaf6ef; }
  .status { display: inline-block; padding: 2px 10px; border-radius: 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; background: <?= $r['status'] === 'paid' ? '#d8f0e2' : '#fdf0cf' ?>; }
  .sig { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; font-size: 12.5px; }
  .sig div { border-top: 1px solid #333; padding-top: 4px; }
  @media print { body { background: #fff; } .toolbar { display: none; } .sheet { margin: 0; width: auto; padding: 0; } @page { size: A4; margin: 12mm; } }
</style>
</head>
<body>
<div class="toolbar"><span>Payslip — <?= htmlspecialchars($r['name']) ?></span><button onclick="window.print()">🖨 Print / Save as PDF</button></div>
<div class="sheet">
  <div class="head">
    <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php endif; ?>
    <div><div class="name"><?= htmlspecialchars($tenant['name'] ?? '') ?></div><div class="meta"><?= htmlspecialchars((string)($tenant['address'] ?? '')) ?></div><div class="meta"><?= htmlspecialchars((string)($tenant['phone'] ?? '')) ?></div></div>
  </div>
  <h2>PAYSLIP <span class="status"><?= htmlspecialchars($r['status']) ?></span></h2>
  <div class="info">
    <div><b>Employee:</b> <?= htmlspecialchars($r['name']) ?></div>
    <div><b>Pay Period:</b> <?= date('M j, Y', strtotime($r['period_start'])) ?> – <?= date('M j, Y', strtotime($r['period_end'])) ?></div>
    <div><b>Staff ID:</b> <?= htmlspecialchars($r['employee_no'] ?: '—') ?></div>
    <div><b>Payslip No:</b> <?= (int)$r['id'] ?></div>
    <div><b>Position:</b> <?= htmlspecialchars($r['position'] ?: '—') ?></div>
    <div><b>Department:</b> <?= htmlspecialchars($r['department'] ?: '—') ?></div>
    <div><b>Employment:</b> <?= htmlspecialchars($r['employment_type'] ?? '—') ?></div>
    <div><b>Paid By:</b> <?= htmlspecialchars($r['payment_method'] ?? '—') ?><?= $r['payment_method'] === 'Bank' && $r['bank_name'] ? ' · ' . htmlspecialchars($r['bank_name']) . ' ' . htmlspecialchars((string)$r['bank_account']) : '' ?><?= $r['payment_method'] === 'Mobile Money' && $r['momo_number'] ? ' · ' . htmlspecialchars((string)$r['momo_provider']) . ' ' . htmlspecialchars($r['momo_number']) : '' ?></div>
  </div>
  <table>
    <thead><tr><th>Earnings</th><th class="r">Amount</th><th>Deductions</th><th class="r">Amount</th></tr></thead>
    <tbody>
      <?php
        $earn = array_values(array_filter($lines, fn($l) => in_array($l[1], ['base', 'allowance'], true)));
        $ded = array_values(array_filter($lines, fn($l) => in_array($l[1], ['deduction', 'tax'], true)));
        for ($i = 0; $i < max(count($earn), count($ded), 1); $i++): ?>
        <tr>
          <td><?= htmlspecialchars($earn[$i][0] ?? '') ?></td><td class="r"><?= isset($earn[$i]) ? $m($earn[$i][2]) : '' ?></td>
          <td><?= htmlspecialchars($ded[$i][0] ?? '') ?></td><td class="r"><?= isset($ded[$i]) ? $m($ded[$i][2]) : '' ?></td>
        </tr>
      <?php endfor; ?>
      <tr style="font-weight:700;"><td>Gross Pay</td><td class="r"><?= $m($r['gross_pay']) ?></td><td>Total Deductions</td><td class="r"><?= $m((float)$r['deductions'] + (float)$r['tax']) ?></td></tr>
      <tr class="net"><td colspan="3">NET PAY</td><td class="r"><?= $m($r['net_pay']) ?></td></tr>
    </tbody>
  </table>
  <div class="sig"><div>Prepared / Approved By</div><div>Employee Signature</div></div>
</div>
</body>
</html>
