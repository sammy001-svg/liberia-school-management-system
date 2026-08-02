<?php
/**
 * Printable store receipt — sized for an 80mm till roll, which is what a school
 * shop counter actually prints on. Self-contained styling like the other print
 * views so the app theme can't bleed into a customer-facing document.
 */
$cfg = require ROOT_DIR . '/config/app.php';
$cur = $tenant['currency'] ?? 'L$';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$balance = (float)$sale['total'] - (float)$sale['amount_paid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Receipt <?= htmlspecialchars($sale['sale_no']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:24px;
         display:flex; flex-direction:column; align-items:center; gap:16px; }
  .toolbar { display:flex; gap:10px; width:80mm; }
  .toolbar button, .toolbar a {
      flex:1; padding:9px 14px; border-radius:8px; border:1px solid #d1d5db; background:#fff;
      color:#111827; font-size:13px; text-decoration:none; text-align:center; cursor:pointer; }
  .toolbar button { background:#4c1d95; color:#fff; border-color:#4c1d95; font-weight:600; }

  .receipt { width:80mm; background:#fff; padding:8mm 6mm; box-shadow:0 8px 24px rgba(0,0,0,0.18); }
  .head { text-align:center; border-bottom:1px dashed #111; padding-bottom:8px; margin-bottom:8px; }
  .head .name { font-size:15px; font-weight:800; text-transform:uppercase; }
  .head .meta { font-size:10px; color:#374151; line-height:1.4; margin-top:3px; }
  .head .doc { font-size:11px; font-weight:700; margin-top:6px; letter-spacing:0.06em; }

  .kv { display:flex; justify-content:space-between; font-size:11px; margin-bottom:2px; }
  .kv span:first-child { color:#4b5563; }

  table { width:100%; border-collapse:collapse; margin:8px 0; }
  th { font-size:10px; text-transform:uppercase; letter-spacing:0.04em; text-align:left;
       border-bottom:1px solid #111; padding:4px 0; }
  td { font-size:11px; padding:3px 0; vertical-align:top; }
  td.num, th.num { text-align:right; }
  tfoot td { border-top:1px dashed #111; padding-top:5px; font-weight:600; }
  tfoot tr.grand td { border-top:1px solid #111; font-size:13px; font-weight:800; }

  .void-stamp { text-align:center; color:#dc2626; border:2px solid #dc2626; border-radius:6px;
                font-weight:800; letter-spacing:0.1em; padding:5px; margin:8px 0; font-size:13px; }
  .foot { text-align:center; font-size:10px; color:#4b5563; border-top:1px dashed #111;
          padding-top:8px; margin-top:8px; line-height:1.5; }

  @media print {
    body { background:#fff; padding:0; display:block; }
    .toolbar { display:none; }
    .receipt { width:auto; box-shadow:none; padding:0; }
    @page { size:80mm auto; margin:4mm; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <a href="<?= $cfg['url'] ?>/school/store/sales">&larr; Sales</a>
  <a href="<?= $cfg['url'] ?>/school/store/sell">New Sale</a>
  <button onclick="window.print()">Print</button>
</div>

<div class="receipt">
  <div class="head">
    <div class="name"><?= htmlspecialchars($schoolName) ?></div>
    <div class="meta">
      <?php if(!empty($tenant['address'])): ?><?= nl2br(htmlspecialchars($tenant['address'])) ?><br><?php endif; ?>
      <?php if(!empty($tenant['phone'])): ?><?= htmlspecialchars($tenant['phone']) ?><?php endif; ?>
    </div>
    <div class="doc">SCHOOL STORE RECEIPT</div>
  </div>

  <?php if($sale['status'] === 'void'): ?>
    <div class="void-stamp">VOIDED</div>
  <?php endif; ?>

  <div class="kv"><span>Receipt</span><strong><?= htmlspecialchars($sale['sale_no']) ?></strong></div>
  <div class="kv"><span>Date</span><span><?= date('d M Y, H:i', strtotime($sale['created_at'])) ?></span></div>
  <div class="kv"><span>Buyer</span><span><?= htmlspecialchars($sale['student_name'] ?? $sale['buyer_name'] ?? '—') ?></span></div>
  <?php if(!empty($sale['class_name'])): ?>
    <div class="kv"><span>Class</span><span><?= htmlspecialchars($sale['class_name']) ?></span></div>
  <?php endif; ?>
  <?php if(!empty($sale['admission_no'])): ?>
    <div class="kv"><span>Admission No.</span><span><?= htmlspecialchars($sale['admission_no']) ?></span></div>
  <?php endif; ?>
  <?php if(!empty($sale['invoice_no'])): ?>
    <div class="kv"><span>Invoice</span><span><?= htmlspecialchars($sale['invoice_no']) ?></span></div>
  <?php endif; ?>
  <div class="kv"><span>Served by</span><span><?= htmlspecialchars($sale['seller_name'] ?? '—') ?></span></div>

  <table>
    <thead><tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Total</th></tr></thead>
    <tbody>
      <?php foreach($lines as $l): ?>
      <tr>
        <td><?= htmlspecialchars($l['item_name']) ?></td>
        <td class="num"><?= (int)$l['quantity'] ?></td>
        <td class="num"><?= number_format($l['unit_price'],2) ?></td>
        <td class="num"><?= number_format($l['line_total'],2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3">Subtotal</td><td class="num"><?= htmlspecialchars($cur) ?><?= number_format($sale['subtotal'],2) ?></td></tr>
      <?php if((float)$sale['discount'] > 0): ?>
      <tr><td colspan="3">Discount</td><td class="num">-<?= htmlspecialchars($cur) ?><?= number_format($sale['discount'],2) ?></td></tr>
      <?php endif; ?>
      <tr class="grand"><td colspan="3">TOTAL</td><td class="num"><?= htmlspecialchars($cur) ?><?= number_format($sale['total'],2) ?></td></tr>
      <tr><td colspan="3">Paid (<?= htmlspecialchars($sale['payment_method']==='account' ? 'on account' : $sale['payment_method']) ?>)</td>
          <td class="num"><?= htmlspecialchars($cur) ?><?= number_format($sale['amount_paid'],2) ?></td></tr>
      <?php if($balance > 0): ?>
      <tr><td colspan="3">Balance on account</td><td class="num"><?= htmlspecialchars($cur) ?><?= number_format($balance,2) ?></td></tr>
      <?php endif; ?>
    </tfoot>
  </table>

  <div class="foot">
    <?php if($balance > 0): ?>
      This balance has been added to the student's fee account.<br>
    <?php endif; ?>
    Goods once sold are exchangeable only with this receipt.<br>
    Thank you.
  </div>
</div>

</body>
</html>
