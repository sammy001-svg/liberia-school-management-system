<?php
// Official payment receipt(s): each payment prints a student copy and a school copy.
$def = $finSettings['default_currency'] ?? 'LRD';
$methods = Finance::PAYMENT_METHODS;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($pageTitle) ?></title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e9ee; font-family: Arial, Helvetica, sans-serif; color: #111; }
  .toolbar { position: sticky; top: 0; background: #1e1b2e; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; z-index: 2; }
  .toolbar button { background: #4cae72; color: #fff; border: 0; border-radius: 6px; padding: 8px 16px; font-weight: 700; cursor: pointer; }
  .sheet { width: 210mm; margin: 12px auto; background: #fff; padding: 10mm; }
  .slip { border: 2px solid #222; padding: 12px 16px 14px; margin-bottom: 8mm; page-break-inside: avoid; position: relative; }
  .slip .copy { position: absolute; top: 8px; right: 12px; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: #777; }
  .head { display: flex; align-items: center; gap: 14px; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 10px; }
  .head img { width: 58px; height: 58px; object-fit: contain; }
  .head .school { flex: 1; text-align: center; }
  .head .name { font-size: 24px; font-weight: 800; letter-spacing: .04em; color: #5b2d82; text-transform: uppercase; }
  .head .meta { font-size: 11px; color: #333; margin-top: 2px; }
  .title-row { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; margin-bottom: 10px; font-size: 12.5px; }
  .title-row h2 { margin: 0; font-size: 16px; letter-spacing: .03em; }
  .title-row .r { text-align: right; }
  .line { display: flex; gap: 8px; font-size: 13px; margin-bottom: 8px; align-items: baseline; }
  .line .fill { flex: 1; border-bottom: 1px solid #333; padding: 0 6px 1px; text-align: center; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; font-size: 13px; margin-top: 6px; }
  .grid b { display: inline-block; min-width: 118px; }
  .methods { font-size: 12.5px; line-height: 1.9; }
  .sig { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 14px; font-size: 13px; }
  .sig span.l { display: inline-block; width: 60%; border-bottom: 1px solid #333; margin-left: 6px; }
  .note { font-size: 10.5px; color: #555; margin-top: 8px; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { margin: 0; width: auto; padding: 0; }
    @page { size: A4; margin: 10mm; }
  }
</style>
</head>
<body>
<div class="toolbar"><span><?= htmlspecialchars($pageTitle) ?> · <?= count($rows) ?> payment(s)</span><button onclick="window.print()">🖨 Print / Save as PDF</button></div>
<div class="sheet">
<?php foreach ($rows as $r):
    $cur = $r['currency'] ?: $def;
    $due = (float)$r['amount_due'] - (float)$r['discount'];
    $balance = max(0, $due - (float)$r['paid_to_date']);
    $date = $r['payment_date'] ?: substr((string)$r['paid_at'], 0, 10);
    foreach (['Student copy', 'School copy'] as $copy): ?>
  <div class="slip">
    <span class="copy"><?= $copy ?></span>
    <div class="head">
      <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php endif; ?>
      <div class="school">
        <div class="name"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
        <div class="meta"><?= htmlspecialchars(trim((string)($tenant['address'] ?? ''))) ?></div>
        <div class="meta"><?= !empty($tenant['phone']) ? 'Phone: ' . htmlspecialchars($tenant['phone']) : '' ?><?= !empty($tenant['email']) ? ' &nbsp;·&nbsp; Email: ' . htmlspecialchars($tenant['email']) : '' ?></div>
      </div>
      <?php if (!empty($tenant['logo'])): ?><span style="width:58px;"></span><?php endif; ?>
    </div>
    <div class="title-row">
      <div>Adm. No: <strong><?= htmlspecialchars($r['admission_no']) ?></strong></div>
      <h2>OFFICIAL PAYMENT RECEIPT</h2>
      <div class="r">Date: <?= date('M j, Y', strtotime($date)) ?><br>Receipt No: <strong><?= (int)$r['id'] ?></strong></div>
    </div>
    <div class="line"><span>Student Name:</span><span class="fill"><?= htmlspecialchars($r['student_name']) ?></span><span>Class:</span><span class="fill" style="flex:.45;"><?= htmlspecialchars($r['class_name'] ?? '') ?></span></div>
    <div class="line"><span>Payment Purpose:</span><span class="fill"><?= htmlspecialchars($r['label']) ?><?= $r['is_arrears'] ? ' (Arrears)' : '' ?></span></div>
    <div class="grid">
      <div>
        <div><b>Amount Due:</b> <?= Finance::money($due, $cur) ?></div>
        <div><b>Amount Paid:</b> <?= Finance::money($r['amount'], $cur) ?></div>
        <div><b>Balance Due:</b> <?= Finance::money($balance, $cur) ?></div>
      </div>
      <div class="methods"><b>Payment Method:</b>
        <?php foreach ($methods as $k => $l): ?><span style="margin-right:10px;white-space:nowrap;">[<?= $r['method'] === $k ? ' X ' : '&nbsp;&nbsp;&nbsp;' ?>] <?= $l ?></span><?php endforeach; ?>
        <?php if ($r['reference']): ?><div style="font-size:12px;">Reference: <?= htmlspecialchars($r['reference']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="sig">
      <div><b>Processed By:</b> <?= htmlspecialchars($r['received_by_name'] ?? '') ?></div>
      <div><b>Signed By:</b><span class="l">&nbsp;</span></div>
    </div>
    <?php if ($r['notes']): ?><div class="note">Note: <?= htmlspecialchars($r['notes']) ?></div><?php endif; ?>
  </div>
<?php endforeach; endforeach; ?>
</div>
</body>
</html>
