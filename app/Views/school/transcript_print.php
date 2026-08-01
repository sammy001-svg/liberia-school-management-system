<?php
$cfg = require ROOT_DIR . '/config/app.php';
$primary   = $tenant['primary_color'] ?? '#10B981';
$secondary = $tenant['secondary_color'] ?? '#059669';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$issued = date('d M Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Transcript — <?= htmlspecialchars($student['name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:8.27in; max-width:100%; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; }
  .toolbar .spacer { flex:1; }
  .toolbar a { font-size:13px; color:#374151; text-decoration:none; }

  .sheet { width:8.27in; min-height:11.69in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.5in 0.6in; position:relative; }

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
    body { background:#fff; padding:0; }
    .toolbar { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.4in 0.5in; }
    .yearblock { page-break-inside:avoid; }
    @page { size: A4; margin: 0; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>">&larr; Back to student</a>
  <div class="spacer"></div>
  <button onclick="window.print()">Print / Save as PDF</button>
</div>

<div class="sheet">

  <div class="letterhead">
    <div class="logo">
      <?php if (!empty($tenant['logo'])): ?>
        <img src="<?= htmlspecialchars($tenant['logo']) ?>" alt="">
      <?php else: ?>
        <?= strtoupper(substr($schoolName, 0, 1)) ?>
      <?php endif; ?>
    </div>
    <div>
      <h1><?= htmlspecialchars($schoolName) ?></h1>
      <div class="sub">
        <?= htmlspecialchars(trim(implode(' · ', array_filter([$tenant['address'] ?? null, $tenant['phone'] ?? null, $tenant['email'] ?? null])))) ?>
      </div>
    </div>
    <div class="doctitle">
      <span class="tag">OFFICIAL TRANSCRIPT</span>
      <div class="issued">Issued <?= $issued ?></div>
    </div>
  </div>

  <div class="studentbar">
    <div><span class="lbl">Student</span><span class="val"><?= htmlspecialchars($student['name']) ?></span></div>
    <div><span class="lbl">Admission No.</span><span class="val"><?= htmlspecialchars($student['admission_no']) ?></span></div>
    <div><span class="lbl">Date of Birth</span><span class="val"><?= $student['date_of_birth'] ? date('d M Y', strtotime($student['date_of_birth'])) : '—' ?></span></div>
    <div><span class="lbl">Gender</span><span class="val"><?= $student['gender'] ? ucfirst($student['gender']) : '—' ?></span></div>
    <div><span class="lbl">Class</span><span class="val"><?= htmlspecialchars($student['class_name'] ?? '—') ?></span></div>
    <div><span class="lbl">Admitted</span><span class="val"><?= $student['admission_date'] ? date('d M Y', strtotime($student['admission_date'])) : '—' ?></span></div>
    <div><span class="lbl">Status</span><span class="val"><?= ucfirst($student['status']) ?></span></div>
    <div><span class="lbl">Attendance</span><span class="val"><?= $attendanceRate !== null ? $attendanceRate.'%' : '—' ?></span></div>
  </div>

  <?php if (empty($years)): ?>
    <div class="empty">No academic results have been recorded for this student yet.</div>
  <?php else: ?>
    <?php foreach ($years as $yearName => $data): ?>
    <div class="yearblock">
      <div class="yearhead">
        <span class="yr"><?= htmlspecialchars($yearName) ?></span>
        <?php if ($data['average'] !== null): ?>
        <span class="avg">Year average: <?= number_format($data['average'], 1) ?>%</span>
        <?php endif; ?>
      </div>
      <table class="grades">
        <thead>
          <tr><th>Subject</th><th class="num">Assessments</th><th class="num">Average</th><th class="num">Grade</th></tr>
        </thead>
        <tbody>
          <?php foreach ($data['rows'] as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['course']) ?></td>
            <td class="num"><?= (int)$row['assessments'] ?></td>
            <td class="num"><?= number_format($row['average'], 1) ?>%</td>
            <td class="num"><?= htmlspecialchars($row['letter']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; ?>

    <div class="summary-grid">
      <div class="summary-card"><div class="lbl">Cumulative Average</div><div class="val"><?= $overall !== null ? number_format($overall, 1).'%' : '—' ?></div></div>
      <div class="summary-card"><div class="lbl">Overall Grade</div><div class="val"><?= htmlspecialchars($overallLetter ?? '—') ?></div></div>
      <div class="summary-card"><div class="lbl">GPA (4.0)</div><div class="val"><?= $overallGpa !== null ? number_format($overallGpa, 2) : '—' ?></div></div>
      <div class="summary-card"><div class="lbl">Years Recorded</div><div class="val"><?= count($years) ?></div></div>
    </div>
  <?php endif; ?>

  <div class="scale">
    <div class="lbl">Grading Scale</div>
    A+ = 90–100 &nbsp;·&nbsp; A = 80–89 &nbsp;·&nbsp; B = 70–79 &nbsp;·&nbsp; C = 60–69 &nbsp;·&nbsp; D = 50–59 &nbsp;·&nbsp; F = below 50.
    Subject averages are the mean of all recorded assessments for that subject in the stated academic year.
  </div>

  <div class="signatures">
    <div><div class="line"></div>Registrar / Principal</div>
    <div><div class="line"></div>Official School Seal</div>
  </div>

  <div class="doc-footer">
    This transcript is issued by <?= htmlspecialchars($schoolName) ?> and is valid only when bearing the official school seal and an authorised signature.
  </div>

</div>
</body>
</html>
