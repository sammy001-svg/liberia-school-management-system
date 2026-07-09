<?php
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$secondary = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$currency = $tenant['currency'] ?? 'Ksh';
$balance = $invoice['amount_due'] - $invoice['amount_paid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Invoice <?= htmlspecialchars($invoice['invoice_no']) ?> — <?= htmlspecialchars($invoice['student_name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:8.27in; max-width:100%; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; margin-left:auto; }

  .sheet { width:8.27in; min-height:8in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.5in 0.6in; }

  .letterhead { display:flex; align-items:center; gap:16px; border-bottom:3px solid <?= htmlspecialchars($primary) ?>; padding-bottom:14px; margin-bottom:18px; }
  .letterhead .logo { width:56px; height:56px; border-radius:10px; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:22px; flex-shrink:0; overflow:hidden; }
  .letterhead .logo img { width:100%; height:100%; object-fit:cover; }
  .letterhead h1 { font-size:19px; font-weight:800; color:#111827; }
  .letterhead .sub { font-size:11.5px; color:#6b7280; margin-top:2px; }
  .letterhead .doctitle { margin-left:auto; text-align:right; }
  .letterhead .doctitle .tag { font-size:10px; font-weight:800; letter-spacing:0.08em; color:#fff; background:<?= htmlspecialchars($primary) ?>; padding:5px 12px; border-radius:20px; }
  .letterhead .doctitle .invno { font-size:12px; color:#6b7280; margin-top:6px; font-family:monospace; }

  .studentbar { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 16px; margin-bottom:20px; }
  .studentbar .lbl { font-size:9px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; font-weight:700; }
  .studentbar .val { font-size:12.5px; color:#111827; font-weight:600; margin-top:2px; }

  table.pay { width:100%; border-collapse:collapse; margin-bottom:20px; }
  table.pay th { background:<?= htmlspecialchars($primary) ?>; color:#fff; font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em; padding:9px 10px; text-align:left; }
  table.pay td { font-size:13px; padding:10px 10px; border-bottom:1px solid #e5e7eb; color:#1f2937; }
  table.pay tr:nth-child(even) td { background:#f9fafb; }
  table.pay tfoot td { font-weight:800; border-top:2px solid #111827; background:#f3f4f6; font-size:14px; }

  .status-tag { display:inline-block; padding:4px 12px; border-radius:20px; font-size:11px; font-weight:800; text-transform:uppercase; }
  .status-paid { background:rgba(16,185,129,0.15); color:#059669; }
  .status-partial, .status-unpaid { background:rgba(245,158,11,0.15); color:#B45309; }
  .status-overdue { background:rgba(239,68,68,0.15); color:#DC2626; }
  .status-waived { background:rgba(107,114,128,0.15); color:#4B5563; }

  .signatures { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; margin-top:50px; }
  .signatures div { text-align:center; font-size:11px; color:#374151; }
  .signatures .line { border-top:1px solid #9ca3af; margin-bottom:6px; padding-top:26px; }

  .doc-footer { margin-top:30px; padding-top:10px; border-top:1px solid #e5e7eb; font-size:9.5px; color:#9ca3af; text-align:center; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.4in 0.5in; }
    @page { size: A4; margin: 0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ Print / Save as PDF</button>
</div>

<div class="sheet">

  <div class="letterhead">
    <div class="logo"><?php if(!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php else: ?><?= htmlspecialchars(strtoupper(substr($schoolName,0,1))) ?><?php endif; ?></div>
    <div>
      <h1><?= htmlspecialchars($schoolName) ?></h1>
      <div class="sub"><?= htmlspecialchars(trim(implode(', ', array_filter([$tenant['address'] ?? null, $tenant['city'] ?? null, $tenant['country'] ?? null])))) ?></div>
    </div>
    <div class="doctitle">
      <div class="tag">INVOICE</div>
      <div class="invno"><?= htmlspecialchars($invoice['invoice_no']) ?></div>
    </div>
  </div>

  <div class="studentbar">
    <div><span class="lbl">Student Name</span><span class="val"><?= htmlspecialchars($invoice['student_name']) ?></span></div>
    <div><span class="lbl">Admission No</span><span class="val"><?= htmlspecialchars($invoice['admission_no']) ?></span></div>
    <div><span class="lbl">Class</span><span class="val"><?= htmlspecialchars($invoice['class_name'] ?? '—') ?></span></div>
    <div><span class="lbl">Due Date</span><span class="val"><?= $invoice['due_date'] ? date('d M Y', strtotime($invoice['due_date'])) : '—' ?></span></div>
  </div>

  <table class="pay">
    <thead><tr><th>Description</th><th style="text-align:right;">Amount (<?= htmlspecialchars($currency) ?>)</th></tr></thead>
    <tbody>
      <tr><td><?= htmlspecialchars($invoice['notes'] ?: 'School Fees') ?></td><td style="text-align:right;"><?= number_format($invoice['amount_due'] + $invoice['discount'],2) ?></td></tr>
      <?php if($invoice['discount'] > 0): ?>
      <tr><td>Discount</td><td style="text-align:right;color:#059669;">-<?= number_format($invoice['discount'],2) ?></td></tr>
      <?php endif; ?>
      <tr><td>Amount Paid</td><td style="text-align:right;color:#059669;">-<?= number_format($invoice['amount_paid'],2) ?></td></tr>
    </tbody>
    <tfoot><tr><td>Balance Due</td><td style="text-align:right;"><?= htmlspecialchars($currency) ?><?= number_format($balance,2) ?></td></tr></tfoot>
  </table>

  <div style="margin-bottom:20px;">
    <span class="status-tag status-<?= htmlspecialchars($invoice['status']) ?>"><?= strtoupper($invoice['status']) ?></span>
  </div>

  <?php if(!empty($payments)): ?>
  <table class="pay">
    <thead><tr><th>Payment Date</th><th>Method</th><th>Reference</th><th style="text-align:right;">Amount</th></tr></thead>
    <tbody>
      <?php foreach($payments as $p): ?>
      <tr>
        <td><?= date('d M Y', strtotime($p['paid_at'])) ?></td>
        <td style="text-transform:capitalize;"><?= htmlspecialchars($p['method']) ?></td>
        <td style="font-family:monospace;font-size:11px;"><?= htmlspecialchars($p['reference'] ?: '—') ?></td>
        <td style="text-align:right;"><?= htmlspecialchars($currency) ?><?= number_format($p['amount'],2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <div class="signatures">
    <div><div class="line">Accountant / Bursar</div></div>
    <div><div class="line">Parent / Guardian</div></div>
  </div>

  <div class="doc-footer">Generated on <?= date('F d, Y \a\t H:i') ?> · This is a computer-generated document. Issued <?= date('M d, Y', strtotime($invoice['created_at'])) ?>.</div>

</div>

</body>
</html>
