<?php
/**
 * One marking period's report card — for a single student, or for a whole class
 * as one printable run.
 *
 * Self-contained styling like the other print views, so the app's theming can
 * never leak into something that goes home to a parent.
 */
$cfg        = require ROOT_DIR . '/config/app.php';
$primary    = $tenant['primary_color'] ?? '#10B981';
$secondary  = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$logo       = !empty($tenant['logo']) ? $cfg['url'] . '/uploads/logos/' . $tenant['logo'] : null;
$issued     = date('d M Y');
$mark = function (?float $v) use ($passMark) {
    if ($v === null) { return ['', '']; }
    return [number_format($v, 0), $v < $passMark ? 'fail' : 'pass'];
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px;
         display:flex; flex-direction:column; align-items:center; }

  .toolbar { display:flex; align-items:center; gap:10px; margin-bottom:18px; width:8.27in; max-width:100%; flex-wrap:wrap; }
  .toolbar select { padding:8px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:13px; background:#fff; }
  .toolbar button, .toolbar a.btn { padding:9px 18px; border-radius:8px; border:none; font-weight:600; font-size:13px;
         cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; text-decoration:none; display:inline-block; }
  .toolbar a.btn.alt { background:#fff; color:#111827; border:1px solid #d1d5db; }
  .toolbar .spacer { flex:1; }

  .sheet { width:8.27in; min-height:11.69in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18);
           padding:0.5in 0.6in; margin-bottom:22px; page-break-after:always; }
  .sheet:last-of-type { page-break-after:auto; }

  .letterhead { display:flex; align-items:center; gap:16px; border-bottom:3px solid <?= htmlspecialchars($primary) ?>;
                padding-bottom:14px; margin-bottom:18px; }
  .letterhead .logo { width:56px; height:56px; border-radius:10px; flex-shrink:0; overflow:hidden; display:flex;
                      align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:22px;
                      background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); }
  .letterhead .logo img { width:100%; height:100%; object-fit:cover; }
  .letterhead h1 { font-size:19px; font-weight:800; color:#111827; }
  .letterhead .sub { font-size:11.5px; color:#6b7280; margin-top:2px; }
  .letterhead .doctitle { margin-left:auto; text-align:right; }
  .letterhead .doctitle .tag { font-size:10px; font-weight:800; letter-spacing:0.08em; color:#fff;
                               background:<?= htmlspecialchars($primary) ?>; padding:5px 12px; border-radius:20px; }
  .letterhead .doctitle .issued { font-size:10.5px; color:#6b7280; margin-top:6px; }

  .studentbar { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; background:#f9fafb;
                border:1px solid #e5e7eb; border-radius:10px; padding:12px 16px; margin-bottom:18px; }
  .studentbar .lbl { display:block; font-size:9px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; font-weight:700; }
  .studentbar .val { display:block; font-size:12.5px; color:#111827; font-weight:600; margin-top:2px; }

  table.marks { width:100%; border-collapse:collapse; margin-bottom:16px; }
  table.marks th { text-align:left; font-size:10px; text-transform:uppercase; letter-spacing:0.05em; color:#6b7280;
                   border-bottom:2px solid #e5e7eb; padding:8px 10px; }
  table.marks td { font-size:12.5px; padding:8px 10px; border-bottom:1px solid #f3f4f6; color:#111827; }
  table.marks td.num { text-align:center; width:110px; font-weight:700; }
  table.marks td.grade { text-align:center; width:80px; font-weight:700; }
  .pass { color:#1d4ed8; }
  .fail { color:#dc2626; }
  .blank { color:#9ca3af; font-weight:400; }

  .summary { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:18px; }
  .summary .box { border:1px solid #e5e7eb; border-radius:10px; padding:10px 14px; }
  .summary .box .lbl { font-size:9px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; font-weight:700; }
  .summary .box .val { font-size:17px; font-weight:800; color:#111827; margin-top:3px; }

  .key { font-size:10.5px; color:#6b7280; border-top:1px solid #e5e7eb; padding-top:10px; margin-bottom:16px; }
  .key b.pass { color:#1d4ed8; } .key b.fail { color:#dc2626; }

  .signatures { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-top:26px; }
  .signatures div { border-top:1px solid #9ca3af; padding-top:6px; font-size:10.5px; color:#6b7280; text-align:center; }

  .empty { padding:40px; text-align:center; color:#6b7280; font-size:13px; }

  @media print {
    /* The colours carry meaning on this card, so they must survive printing. */
    * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    body { background:#fff; padding:0; display:block; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.4in 0.5in; margin:0; }
    @page { size:A4 portrait; margin:0.25in; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <?php if (!empty($years)): ?>
    <select name="year_id" onchange="this.form.submit()">
      <?php foreach ($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$yearId === (string)$y['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($y['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
    <select name="period" onchange="this.form.submit()">
      <?php foreach ($periods as $slot => $label): ?>
        <option value="<?= $slot ?>" <?= $period === $slot ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <div class="spacer"></div>
  <?php if (!empty($oneStudent) && !empty($classId)): ?>
    <a class="btn alt"
       href="<?= $cfg['url'] ?>/school/grades/period-reports/class/<?= (int)$classId ?>?year_id=<?= htmlspecialchars((string)$yearId) ?>&period=<?= htmlspecialchars($period) ?>">
      Print all for this class
    </a>
  <?php endif; ?>
  <button onclick="window.print()"><?= !empty($oneStudent) ? 'Print / Download' : 'Print / Download All (' . count($cards) . ')' ?></button>
</div>

<?php if (empty($cards)): ?>
  <div class="sheet"><div class="empty">There are no students to report on here.</div></div>
<?php endif; ?>

<?php foreach ($cards as $card): $st = $card['student']; ?>
<div class="sheet">
  <div class="letterhead">
    <div class="logo">
      <?php if ($logo): ?><img src="<?= htmlspecialchars($logo) ?>" alt=""><?php else: ?><?= htmlspecialchars(mb_substr($schoolName, 0, 1)) ?><?php endif; ?>
    </div>
    <div>
      <h1><?= htmlspecialchars($schoolName) ?></h1>
      <div class="sub"><?= htmlspecialchars($tenant['address'] ?? '') ?></div>
    </div>
    <div class="doctitle">
      <span class="tag">PERIOD REPORT</span>
      <div class="issued"><?= htmlspecialchars($periodName) ?> · <?= htmlspecialchars($yearName) ?></div>
      <div class="issued">Issued <?= $issued ?></div>
    </div>
  </div>

  <div class="studentbar">
    <div><span class="lbl">Student</span><span class="val"><?= htmlspecialchars($st['name']) ?></span></div>
    <div><span class="lbl">Admission No</span><span class="val"><?= htmlspecialchars($st['admission_no'] ?? '—') ?></span></div>
    <div><span class="lbl">Class</span><span class="val"><?= htmlspecialchars($st['class_name'] ?? '—') ?></span></div>
    <div><span class="lbl">Marking Period</span><span class="val"><?= htmlspecialchars($periodName) ?></span></div>
  </div>

  <table class="marks">
    <thead><tr><th>Subject</th><th style="text-align:center;">Mark</th><th style="text-align:center;">Grade</th></tr></thead>
    <tbody>
      <?php foreach ($card['rows'] as $r): [$txt, $cls] = $mark($r['mark']); ?>
      <tr>
        <td><?= htmlspecialchars($r['subject']) ?></td>
        <td class="num <?= $cls ?>"><?= $txt !== '' ? $txt : '<span class="blank">—</span>' ?></td>
        <td class="grade <?= $cls ?>"><?= $r['mark'] !== null ? Controller::celdiLetter($r['mark']) : '<span class="blank">—</span>' ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($card['rows'])): ?>
      <tr><td colspan="3" class="empty">This class has no subjects assigned yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="summary">
    <div class="box"><div class="lbl">Period Average</div>
      <div class="val <?= $card['average'] === null ? '' : ($card['average'] < $passMark ? 'fail' : 'pass') ?>">
        <?= $card['average'] !== null ? number_format($card['average'], 1) . '%' : '—' ?>
      </div></div>
    <div class="box"><div class="lbl">Position in Class</div>
      <div class="val"><?= $card['position'] ? $card['position'] . ' / ' . $card['of'] : '—' ?></div></div>
    <div class="box"><div class="lbl">Days Absent</div>
      <div class="val"><?= $card['absent'] !== null ? (int)$card['absent'] : '—' ?></div></div>
    <div class="box"><div class="lbl">Subjects Marked</div>
      <div class="val"><?= count(array_filter($card['rows'], fn($r) => $r['mark'] !== null)) ?> / <?= count($card['rows']) ?></div></div>
  </div>

  <div class="key">
    <b class="pass">Blue</b> = pass (<?= (int)$passMark ?> and above) &nbsp;·&nbsp;
    <b class="fail">Red</b> = fail (<?= (int)$passMark - 1 ?> and below)
  </div>

  <div class="signatures">
    <div>Class Teacher</div>
    <div>Principal</div>
    <div>Parent / Guardian</div>
  </div>
</div>
<?php endforeach; ?>

</body>
</html>
