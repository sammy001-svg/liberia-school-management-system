<?php
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$cur = $tenant['currency'] ?? 'Ksh';
$money = fn($n) => $cur . ' ' . number_format((float)$n, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Statement of Account — <?= htmlspecialchars($student['name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:8.27in; max-width:100%; }
  .toolbar a { font-size:13px; color:#374151; text-decoration:none; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; margin-left:auto; }

  .sheet { width:8.27in; min-height:11.69in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.5in 0.6in; }

  .letterhead { display:flex; align-items:center; gap:16px; border-bottom:3px solid <?= htmlspecialchars($primary) ?>; padding-bottom:14px; margin-bottom:18px; }
  .letterhead .logo { width:54px; height:54px; border-radius:10px; background:<?= htmlspecialchars($primary) ?>; display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:21px; overflow:hidden; flex-shrink:0; }
  .letterhead .logo img { width:100%; height:100%; object-fit:cover; }
  .letterhead h1 { font-size:18px; font-weight:800; color:#111827; }
  .letterhead .sub { font-size:11px; color:#6b7280; margin-top:2px; }
  .letterhead .doctitle { margin-left:auto; text-align:right; }
  .letterhead .tag { font-size:10px; font-weight:800; letter-spacing:0.08em; color:#fff; background:<?= htmlspecialchars($primary) ?>; padding:5px 12px; border-radius:20px; }
  .letterhead .period { font-size:10.5px; color:#6b7280; margin-top:6px; }

  .studentbar { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 16px; margin-bottom:18px; }
  .studentbar .lbl { display:block; font-size:9px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; font-weight:700; }
  .studentbar .val { display:block; font-size:12.5px; color:#111827; font-weight:600; margin-top:2px; }

  table.ledger { width:100%; border-collapse:collapse; margin-bottom:18px; }
  table.ledger th { background:<?= htmlspecialchars($primary) ?>; color:#fff; font-size:10px; text-transform:uppercase; letter-spacing:0.04em; padding:8px 9px; text-align:left; }
  table.ledger td { font-size:11.5px; padding:7px 9px; border-bottom:1px solid #e5e7eb; color:#1f2937; }
  table.ledger tr:nth-child(even) td { background:#fafafa; }
  table.ledger td.num, table.ledger th.num { text-align:right; white-space:nowrap; }
  table.ledger tr.opening td { background:#f3f4f6; font-weight:700; }
  table.ledger tfoot td { font-weight:800; border-top:2px solid #111827; background:#f3f4f6; font-size:12.5px; }
  .credit { color:#047857; }
  .charge { color:#111827; }

  .balance-box { border:2px solid <?= htmlspecialchars($primary) ?>; border-radius:10px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
  .balance-box .lbl { font-size:11px; text-transform:uppercase; letter-spacing:0.08em; color:#6b7280; font-weight:700; }
  .balance-box .val { font-size:22px; font-weight:800; }
  .owing { color:#B91C1C; }
  .settled { color:#047857; }

  .note { font-size:10px; color:#6b7280; line-height:1.7; border-top:1px solid #e5e7eb; padding-top:12px; }
  .empty { text-align:center; padding:40px; color:#9ca3af; font-size:12.5px; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.4in 0.5in; }
    @page { size:A4; margin:0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <a href="<?= $cfg['url'] ?>/school/finance/accounts">&larr; Back to student accounts</a>
  <button onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">

  <div class="letterhead">
    <div class="logo">
      <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="">
      <?php else: ?><?= strtoupper(substr($schoolName, 0, 1)) ?><?php endif; ?>
    </div>
    <div>
      <h1><?= htmlspecialchars($schoolName) ?></h1>
      <div class="sub"><?= htmlspecialchars(trim(implode(' · ', array_filter([$tenant['address'] ?? null, $tenant['phone'] ?? null])))) ?></div>
    </div>
    <div class="doctitle">
      <span class="tag">STATEMENT OF ACCOUNT</span>
      <div class="period">
        <?php if ($from || $to): ?>
          <?= $from ? date('d M Y', strtotime($from)) : 'Start' ?> — <?= $to ? date('d M Y', strtotime($to)) : date('d M Y') ?>
        <?php else: ?>All transactions to <?= date('d M Y') ?><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="studentbar">
    <div><span class="lbl">Student</span><span class="val"><?= htmlspecialchars($student['name']) ?></span></div>
    <div><span class="lbl">Admission No.</span><span class="val"><?= htmlspecialchars($student['admission_no']) ?></span></div>
    <div><span class="lbl">Class</span><span class="val"><?= htmlspecialchars($student['class_name'] ?? '—') ?></span></div>
    <div><span class="lbl">Issued</span><span class="val"><?= date('d M Y') ?></span></div>
  </div>

  <?php if (empty($entries) && abs($opening) < 0.009): ?>
    <div class="empty">No financial transactions recorded for this student.</div>
  <?php else: ?>
  <table class="ledger">
    <thead>
      <tr><th>Date</th><th>Description</th><th>Ref</th><th class="num">Charges</th><th class="num">Credits</th><th class="num">Balance</th></tr>
    </thead>
    <tbody>
      <?php if ($from): ?>
      <tr class="opening">
        <td><?= date('d M Y', strtotime($from)) ?></td>
        <td colspan="4">Balance brought forward</td>
        <td class="num"><?= $money($opening) ?></td>
      </tr>
      <?php endif; ?>
      <?php foreach ($entries as $e): ?>
      <?php $amt = (float)$e['amount']; ?>
      <tr>
        <td><?= date('d M Y', strtotime($e['entry_date'])) ?></td>
        <td><?= htmlspecialchars($e['description']) ?></td>
        <td style="font-size:10px;color:#6b7280;"><?= htmlspecialchars($e['reference'] ?? '—') ?></td>
        <td class="num charge"><?= $amt > 0 ? $money($amt) : '' ?></td>
        <td class="num credit"><?= $amt < 0 ? $money(abs($amt)) : '' ?></td>
        <td class="num"><?= $money($e['balance']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="5">Closing Balance</td><td class="num"><?= $money($closing) ?></td></tr>
    </tfoot>
  </table>
  <?php endif; ?>

  <div class="balance-box">
    <div>
      <div class="lbl"><?= $closing > 0.009 ? 'Amount Due' : ($closing < -0.009 ? 'Credit on Account' : 'Account Settled') ?></div>
      <div style="font-size:10.5px;color:#6b7280;margin-top:3px;">
        <?= $closing > 0.009 ? 'Please settle at the school bursary.' : ($closing < -0.009 ? 'This amount is held in credit toward future fees.' : 'No outstanding balance.') ?>
      </div>
    </div>
    <div class="val <?= $closing > 0.009 ? 'owing' : 'settled' ?>"><?= $money(abs($closing)) ?></div>
  </div>

  <div class="note">
    This statement reflects all charges and payments recorded against this student's account as at
    <?= date('d M Y') ?>. Charges increase the balance; payments, discounts and scholarships reduce it.
    If any entry appears incorrect, please contact the school bursary quoting the reference shown.
  </div>

</div>
</body>
</html>
