<?php
/**
 * One marking period's report card — for a single student, or for a whole class
 * as one printable run.
 *
 * Deliberately the same document as the transcript: same sheet, letterhead,
 * student bar, table, summary cards, grading-scale note and signature block, so
 * the two read as one family of school stationery. Self-contained styling, like
 * the other print views, so the app's theming can never leak into something that
 * goes home to a parent.
 */
$cfg        = require ROOT_DIR . '/config/app.php';
$primary    = $tenant['primary_color'] ?? '#10B981';
$secondary  = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$logo       = Controller::logoUrl($tenant['logo'] ?? null);
$issued     = date('d M Y');
$contact    = trim(implode(' · ', array_filter([$tenant['address'] ?? null, $tenant['phone'] ?? null, $tenant['email'] ?? null])));
$single     = !empty($oneStudent) ? ($cards[0]['student'] ?? null) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:8.27in; max-width:100%; flex-wrap:wrap; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; }
  .toolbar .spacer { flex:1; }
  .toolbar a { font-size:13px; color:#374151; text-decoration:none; }
  .toolbar a.btn { padding:10px 18px; border-radius:8px; background:#fff; border:1px solid #d1d5db; color:#111827; font-weight:600; }
  .toolbar select { padding:9px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:13px; background:#fff; }

  .sheet { width:8.27in; min-height:11.69in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.5in 0.6in; position:relative; margin-bottom:22px; page-break-after:always; }
  .sheet:last-of-type { page-break-after:auto; margin-bottom:0; }

  .letterhead { display:flex; align-items:center; gap:16px; border-bottom:3px solid <?= htmlspecialchars($primary) ?>; padding-bottom:14px; margin-bottom:18px; }
  .letterhead .logo { width:56px; height:56px; border-radius:10px; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:22px; flex-shrink:0; overflow:hidden; }
  .letterhead .logo img { width:100%; height:100%; object-fit:cover; }
  .letterhead h1 { font-size:19px; font-weight:800; color:#111827; }
  .letterhead .sub { font-size:11.5px; color:#6b7280; margin-top:2px; }
  .letterhead .doctitle { margin-left:auto; text-align:right; }
  .letterhead .doctitle .tag { font-size:10px; font-weight:800; letter-spacing:0.08em; color:#fff; background:<?= htmlspecialchars($primary) ?>; padding:5px 12px; border-radius:20px; }
  .letterhead .doctitle .issued { font-size:10.5px; color:#6b7280; margin-top:6px; }

  .studentbar { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 16px; margin-bottom:20px; }
  .studentbar .lbl { display:block; font-size:9px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; font-weight:700; }
  .studentbar .val { display:block; font-size:12.5px; color:#111827; font-weight:600; margin-top:2px; }

  .yearblock { margin-bottom:18px; }
  .yearhead { display:flex; align-items:baseline; gap:10px; border-left:4px solid <?= htmlspecialchars($primary) ?>; padding-left:10px; margin-bottom:8px; }
  .yearhead .yr { font-size:13px; font-weight:800; color:#111827; }
  .yearhead .avg { margin-left:auto; font-size:11px; color:#6b7280; font-weight:600; }

  table.grades { width:100%; border-collapse:collapse; }
  table.grades th { background:<?= htmlspecialchars($primary) ?>; color:#fff; font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em; padding:8px 10px; text-align:left; }
  table.grades td { font-size:12px; padding:7px 10px; border-bottom:1px solid #e5e7eb; color:#1f2937; }
  table.grades tr:nth-child(even) td { background:#f9fafb; }
  table.grades td.num, table.grades th.num { text-align:center; width:90px; }

  .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin:22px 0; }
  .summary-card { border:1px solid #e5e7eb; border-radius:10px; padding:12px; text-align:center; }
  .summary-card .lbl { font-size:9px; text-transform:uppercase; color:#9ca3af; font-weight:700; letter-spacing:0.04em; }
  .summary-card .val { font-size:19px; font-weight:800; color:<?= htmlspecialchars($primary) ?>; margin-top:4px; }

  .scale { border:1px dashed #d1d5db; border-radius:10px; padding:12px 14px; font-size:10.5px; color:#4b5563; margin-bottom:26px; }
  .scale .lbl { font-size:9.5px; text-transform:uppercase; color:#9ca3af; font-weight:700; letter-spacing:0.05em; margin-bottom:6px; }

  .signatures { display:grid; grid-template-columns:repeat(2,1fr); gap:40px; margin-top:36px; }
  .signatures div { text-align:center; font-size:11px; color:#374151; }
  .signatures .line { border-top:1px solid #9ca3af; margin-bottom:6px; padding-top:30px; }

  .doc-footer { margin-top:26px; padding-top:10px; border-top:1px solid #e5e7eb; font-size:9.5px; color:#9ca3af; text-align:center; }
  .empty { text-align:center; padding:40px 20px; color:#9ca3af; font-size:12.5px; }

  @media print {
    /* The letterhead rule and table header are colour, so keep them on paper. */
    * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.4in 0.5in; margin:0; }
    .yearblock { page-break-inside:avoid; }
    @page { size: A4; margin: 0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <?php if ($single): ?>
    <a href="<?= $cfg['url'] ?>/school/students/<?= (int)$single['id'] ?>">&larr; Back to student</a>
  <?php else: ?>
    <a href="<?= $cfg['url'] ?>/school/students?class_id=<?= (int)$classId ?>">&larr; Back to class</a>
  <?php endif; ?>
  <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
    <?php if (!empty($years)): ?>
    <select name="year_id" onchange="this.form.submit()">
      <?php foreach ($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$yearId === (string)$y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option>
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
  <?php if ($single && !empty($classId)): ?>
    <a class="btn" href="<?= $cfg['url'] ?>/school/grades/period-reports/class/<?= (int)$classId ?>?year_id=<?= htmlspecialchars((string)$yearId) ?>&period=<?= htmlspecialchars($period) ?>">
      Print all for this class
    </a>
  <?php endif; ?>
  <button onclick="window.print()">Print / Save as PDF<?= $single ? '' : ' (' . count($cards) . ')' ?></button>
</div>

<?php if (empty($cards)): ?>
  <div class="sheet"><div class="empty">There are no students to report on here.</div></div>
<?php endif; ?>

<?php foreach ($cards as $card): $student = $card['student']; $marked = array_filter($card['rows'], fn($r) => $r['mark'] !== null); ?>
<div class="sheet">

  <div class="letterhead">
    <div class="logo">
      <?php if ($logo): ?>
        <img src="<?= htmlspecialchars($logo) ?>" alt="">
      <?php else: ?>
        <?= strtoupper(substr($schoolName, 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div>
      <h1><?= htmlspecialchars($schoolName) ?></h1>
      <div class="sub"><?= htmlspecialchars($contact) ?></div>
    </div>
    <div class="doctitle">
      <span class="tag">PERIOD REPORT CARD</span>
      <div class="issued">Issued <?= $issued ?></div>
    </div>
  </div>

  <div class="studentbar">
    <div><span class="lbl">Student</span><span class="val"><?= htmlspecialchars($student['name']) ?></span></div>
    <div><span class="lbl">Admission No.</span><span class="val"><?= htmlspecialchars($student['admission_no'] ?? '—') ?></span></div>
    <div><span class="lbl">Date of Birth</span><span class="val"><?= !empty($student['date_of_birth']) ? date('d M Y', strtotime($student['date_of_birth'])) : '—' ?></span></div>
    <div><span class="lbl">Gender</span><span class="val"><?= !empty($student['gender']) ? ucfirst($student['gender']) : '—' ?></span></div>
    <div><span class="lbl">Class</span><span class="val"><?= htmlspecialchars($student['class_name'] ?? '—') ?></span></div>
    <div><span class="lbl">Academic Year</span><span class="val"><?= htmlspecialchars($yearName ?: '—') ?></span></div>
    <div><span class="lbl">Marking Period</span><span class="val"><?= htmlspecialchars($periodName) ?></span></div>
    <div><span class="lbl">Attendance</span><span class="val"><?= $card['attendance'] !== null ? (int)$card['attendance'] . '%' : '—' ?></span></div>
  </div>

  <?php if (empty($card['rows'])): ?>
    <div class="empty">This class has no subjects assigned yet, so there is nothing to report.</div>
  <?php elseif (empty($marked)): ?>
    <div class="empty">No marks have been recorded for <?= htmlspecialchars($periodName) ?> yet.</div>
  <?php else: ?>
    <div class="yearblock">
      <div class="yearhead">
        <span class="yr"><?= htmlspecialchars($periodName) ?> · <?= htmlspecialchars($yearName) ?></span>
        <?php if ($card['average'] !== null): ?>
        <span class="avg">Period average: <?= number_format($card['average'], 1) ?>%</span>
        <?php endif; ?>
      </div>
      <table class="grades">
        <thead>
          <tr><th>Subject</th><th class="num">Assessments</th><th class="num">Average</th><th class="num">Grade</th></tr>
        </thead>
        <tbody>
          <?php foreach ($card['rows'] as $row): $m = $row['mark']; ?>
          <tr>
            <td><?= htmlspecialchars($row['subject']) ?></td>
            <td class="num"><?= (int)$row['assessments'] ?></td>
            <td class="num"><?= $m !== null ? number_format($m, 1) . '%' : '—' ?></td>
            <td class="num"><?= $m !== null ? Controller::transcriptLetter($m) : '—' ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="summary-grid">
      <div class="summary-card"><div class="lbl">Period Average</div><div class="val"><?= $card['average'] !== null ? number_format($card['average'], 1) . '%' : '—' ?></div></div>
      <div class="summary-card"><div class="lbl">Overall Grade</div><div class="val"><?= $card['average'] !== null ? Controller::transcriptLetter($card['average']) : '—' ?></div></div>
      <div class="summary-card"><div class="lbl">GPA (4.0)</div><div class="val"><?= $card['average'] !== null ? number_format(Controller::transcriptGpa($card['average']), 2) : '—' ?></div></div>
      <div class="summary-card"><div class="lbl">Position in Class</div><div class="val"><?= $card['position'] ? $card['position'] . ' / ' . $card['of'] : '—' ?></div></div>
    </div>
  <?php endif; ?>

  <div class="scale">
    <div class="lbl">Grading Scale</div>
    A+ = 90–100 &nbsp;·&nbsp; A = 80–89 &nbsp;·&nbsp; B = 70–79 &nbsp;·&nbsp; C = 60–69 &nbsp;·&nbsp; D = 50–59 &nbsp;·&nbsp; F = below 50.
    Subject averages are the mean of all marks recorded for that subject in the stated marking period.
  </div>

  <div class="signatures">
    <div><div class="line"></div>Class Teacher</div>
    <div><div class="line"></div>Principal</div>
  </div>

  <div class="doc-footer">
    This period report card is issued by <?= htmlspecialchars($schoolName) ?> for <?= htmlspecialchars($periodName) ?>,
    <?= htmlspecialchars($yearName) ?>, and reflects the marks recorded at the date of issue.
  </div>

</div>
<?php endforeach; ?>

</body>
</html>
