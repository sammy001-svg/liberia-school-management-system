<?php
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$secondary = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$currency = $tenant['currency'] ?? 'Ksh';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Income Statement — <?= htmlspecialchars($periodLabel) ?> — <?= htmlspecialchars($schoolName) ?></title>
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
  .letterhead .doctitle .meta { font-size:12px; color:#6b7280; margin-top:6px; }

  .section-title { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:0.05em; color:<?= htmlspecialchars($primary) ?>; margin:20px 0 8px; }

  table.stmt { width:100%; border-collapse:collapse; margin-bottom:6px; }
  table.stmt td { font-size:13px; padding:7px 4px; border-bottom:1px solid #f0f0f0; color:#1f2937; }
  table.stmt td:last-child { text-align:right; font-family:monospace; }
  table.stmt tr.total td { font-weight:800; border-top:2px solid #111827; border-bottom:none; padding-top:10px; }

  .net-box { display:flex; justify-content:space-between; align-items:center; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; margin:18px 0; }
  .net-box .label { font-size:13px; font-weight:700; color:#374151; }
  .net-box .value { font-size:20px; font-weight:800; }

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
      <div class="tag">INCOME STATEMENT</div>
      <div class="meta"><?= htmlspecialchars($periodLabel) ?></div>
    </div>
  </div>

  <div class="section-title">Revenue</div>
  <table class="stmt">
    <?php foreach($revenueByCategory as $r): ?>
    <tr><td><?= htmlspecialchars($r['category']) ?></td><td><?= htmlspecialchars($currency) ?><?= number_format($r['total'],2) ?></td></tr>
    <?php endforeach; ?>
    <?php if(empty($revenueByCategory)): ?><tr><td colspan="2" style="color:#9ca3af;">No invoices in this period.</td></tr><?php endif; ?>
    <tr class="total"><td>Total Billed</td><td><?= htmlspecialchars($currency) ?><?= number_format($totalBilled,2) ?></td></tr>
    <tr class="total"><td>Total Collected</td><td><?= htmlspecialchars($currency) ?><?= number_format($totalCollected,2) ?></td></tr>
  </table>

  <div class="section-title">Expenses</div>
  <table class="stmt">
    <?php foreach($expensesByCategory as $e): ?>
    <tr><td><?= htmlspecialchars($e['category']) ?></td><td><?= htmlspecialchars($currency) ?><?= number_format($e['total'],2) ?></td></tr>
    <?php endforeach; ?>
    <?php if(empty($expensesByCategory)): ?><tr><td colspan="2" style="color:#9ca3af;">No expenses in this period.</td></tr><?php endif; ?>
    <tr class="total"><td>Total Expenses</td><td><?= htmlspecialchars($currency) ?><?= number_format($totalExpenses,2) ?></td></tr>
  </table>

  <div class="net-box">
    <div class="label">NET INCOME (Collected − Expenses)</div>
    <div class="value" style="color:<?= $netIncome>=0?'#059669':'#DC2626' ?>;"><?= htmlspecialchars($currency) ?><?= number_format($netIncome,2) ?></div>
  </div>

  <div class="section-title">Payments by Method</div>
  <table class="stmt">
    <?php foreach($paymentsByMethod as $p): ?>
    <tr><td style="text-transform:capitalize;"><?= htmlspecialchars($p['method']) ?> (<?= (int)$p['cnt'] ?>)</td><td><?= htmlspecialchars($currency) ?><?= number_format($p['total'],2) ?></td></tr>
    <?php endforeach; ?>
    <?php if(empty($paymentsByMethod)): ?><tr><td colspan="2" style="color:#9ca3af;">No payments in this period.</td></tr><?php endif; ?>
  </table>

  <div class="signatures">
    <div><div class="line">Prepared By (Accountant)</div></div>
    <div><div class="line">Approved By (Proprietor / Principal)</div></div>
  </div>

  <div class="doc-footer">Generated on <?= date('F d, Y \a\t H:i') ?> · This is a computer-generated financial statement.</div>

</div>

</body>
</html>
