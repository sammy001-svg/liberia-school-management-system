<?php $cur = $r['currency'] ?: ($finSettings['default_currency'] ?? 'LRD'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($pageTitle) ?></title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e9ee; font-family: Arial, Helvetica, sans-serif; color: #111; }
  .toolbar { position: sticky; top: 0; background: #1e1b2e; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; }
  .toolbar button { background: #4cae72; color: #fff; border: 0; border-radius: 6px; padding: 8px 16px; font-weight: 700; cursor: pointer; }
  .sheet { width: 210mm; margin: 12px auto; background: #fff; padding: 10mm; }
  .slip { border: 2px solid #222; padding: 12px 16px 14px; margin-bottom: 8mm; position: relative; }
  .copy { position: absolute; top: 8px; right: 12px; font-size: 10px; letter-spacing: .12em; text-transform: uppercase; color: #777; }
  .head { display: flex; align-items: center; gap: 14px; border-bottom: 1px solid #ccc; padding-bottom: 8px; margin-bottom: 10px; }
  .head img { width: 58px; height: 58px; object-fit: contain; }
  .head .school { flex: 1; text-align: center; }
  .name { font-size: 24px; font-weight: 800; letter-spacing: .04em; color: #5b2d82; text-transform: uppercase; }
  .meta { font-size: 11px; margin-top: 2px; }
  h2 { text-align: center; font-size: 16px; margin: 0 0 10px; }
  .row { display: flex; gap: 8px; font-size: 13px; margin-bottom: 8px; align-items: baseline; }
  .row .fill { flex: 1; border-bottom: 1px solid #333; padding: 0 6px 1px; }
  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 20px; font-size: 13px; }
  .grid b { display: inline-block; min-width: 110px; }
  .sig { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 14px; font-size: 13px; }
  .sig span.l { display: inline-block; width: 60%; border-bottom: 1px solid #333; margin-left: 6px; }
  @media print { body { background: #fff; } .toolbar { display: none; } .sheet { margin: 0; width: auto; padding: 0; } @page { size: A4; margin: 10mm; } }
</style>
</head>
<body>
<div class="toolbar"><span><?= htmlspecialchars($pageTitle) ?></span><button onclick="window.print()">🖨 Print / Save as PDF</button></div>
<div class="sheet">
<?php foreach (['Payer copy', 'School copy'] as $copy): ?>
  <div class="slip">
    <span class="copy"><?= $copy ?></span>
    <div class="head">
      <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php endif; ?>
      <div class="school">
        <div class="name"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
        <div class="meta"><?= htmlspecialchars((string)($tenant['address'] ?? '')) ?></div>
        <div class="meta"><?= !empty($tenant['phone']) ? 'Phone: ' . htmlspecialchars($tenant['phone']) : '' ?><?= !empty($tenant['email']) ? ' · Email: ' . htmlspecialchars($tenant['email']) : '' ?></div>
      </div>
    </div>
    <h2>OFFICIAL RECEIPT</h2>
    <div class="row"><span>Receipt No:</span><span class="fill"><?= (int)$r['id'] ?></span><span>Date:</span><span class="fill"><?= date('M j, Y', strtotime($r['income_date'])) ?></span></div>
    <div class="row"><span>Received From:</span><span class="fill"><?= htmlspecialchars($r['source']) ?><?= $r['admission_no'] ? ' (' . htmlspecialchars($r['admission_no']) . ($r['class_name'] ? ', ' . htmlspecialchars($r['class_name']) : '') . ')' : '' ?></span></div>
    <div class="row"><span>Being Payment For:</span><span class="fill"><?= htmlspecialchars($r['category']) ?><?= $r['description'] ? ' — ' . htmlspecialchars($r['description']) : '' ?></span></div>
    <div class="grid">
      <div><b>Amount Paid:</b> <?= Finance::money($r['amount'], $cur) ?></div>
      <div><b>Method:</b> <?= htmlspecialchars(Finance::PAYMENT_METHODS[$r['method']] ?? (string)$r['method']) ?><?= $r['reference'] ? ' · ' . htmlspecialchars($r['reference']) : '' ?></div>
      <div><b>Balance Owed:</b> <?= (float)$r['balance_owed'] > 0 ? Finance::money($r['balance_owed'], $cur) : 'None' ?></div>
    </div>
    <div class="sig"><div><b>Received By:</b> <?= htmlspecialchars($r['recorded_by_name'] ?? '') ?></div><div><b>Signed By:</b><span class="l">&nbsp;</span></div></div>
  </div>
<?php endforeach; ?>
</div>
</body>
</html>
