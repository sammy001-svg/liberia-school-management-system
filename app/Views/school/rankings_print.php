<?php
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$secondary = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Rankings — <?= htmlspecialchars($period) ?> — <?= htmlspecialchars($schoolName) ?></title>
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

  table.pay { width:100%; border-collapse:collapse; margin-bottom:20px; }
  table.pay th { background:<?= htmlspecialchars($primary) ?>; color:#fff; font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em; padding:9px 10px; text-align:left; }
  table.pay td { font-size:12.5px; padding:8px 10px; border-bottom:1px solid #e5e7eb; color:#1f2937; }
  table.pay tr:nth-child(even) td { background:#f9fafb; }

  .doc-footer { margin-top:30px; padding-top:10px; border-top:1px solid #e5e7eb; font-size:9.5px; color:#9ca3af; text-align:center; }

  @media print {
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.4in 0.5in; }
    table.pay { page-break-inside:auto; }
    table.pay tr { page-break-inside:avoid; }
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
      <div class="tag">RANKINGS</div>
      <div class="meta"><?= htmlspecialchars($period) ?><?= $className ? ' · '.htmlspecialchars($className) : ' · All Classes' ?></div>
    </div>
  </div>

  <table class="pay">
    <thead><tr><th>Rank</th><th>Student</th><th>Admission No</th><th>Class</th><th style="text-align:right;">Score</th><th style="text-align:right;">Group Size</th></tr></thead>
    <tbody>
      <?php foreach($rankings as $r): ?>
      <tr>
        <td><?= $r['rank_position'] !== null ? '#'.$r['rank_position'] : '—' ?></td>
        <td><?= htmlspecialchars($r['student_name']) ?></td>
        <td style="font-family:monospace;font-size:11px;"><?= htmlspecialchars($r['admission_no']) ?></td>
        <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
        <td style="text-align:right;"><?= number_format($r['score'],1) ?>%</td>
        <td style="text-align:right;"><?= htmlspecialchars((string)($r['group_size'] ?? '—')) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($rankings)): ?>
      <tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:20px;">No rankings found for this filter.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="doc-footer">Generated on <?= date('F d, Y \a\t H:i') ?> · This is a computer-generated document.</div>

</div>

</body>
</html>
