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
<title>Report Card — <?= htmlspecialchars($student['name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Segoe UI', Arial, sans-serif; background:#e5e7eb; padding:28px; display:flex; flex-direction:column; align-items:center; }
  .toolbar { display:flex; align-items:center; gap:12px; margin-bottom:18px; width:8.27in; max-width:100%; }
  .toolbar select { padding:8px 12px; border-radius:8px; border:1px solid #d1d5db; font-size:13px; font-family:inherit; }
  .toolbar button { padding:10px 20px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; background:<?= htmlspecialchars($primary) ?>; color:#fff; }
  .toolbar .spacer { flex:1; }

  .sheet { width:8.27in; min-height:11.69in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18); padding:0.5in 0.6in; }

  .letterhead { display:flex; align-items:center; gap:16px; border-bottom:3px solid <?= htmlspecialchars($primary) ?>; padding-bottom:14px; margin-bottom:18px; }
  .letterhead .logo { width:56px; height:56px; border-radius:10px; background:linear-gradient(135deg,<?= htmlspecialchars($primary) ?>,<?= htmlspecialchars($secondary) ?>); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:22px; flex-shrink:0; overflow:hidden; }
  .letterhead .logo img { width:100%; height:100%; object-fit:cover; }
  .letterhead h1 { font-size:19px; font-weight:800; color:#111827; }
  .letterhead .sub { font-size:11.5px; color:#6b7280; margin-top:2px; }
  .letterhead .doctitle { margin-left:auto; text-align:right; }
  .letterhead .doctitle .tag { font-size:10px; font-weight:800; letter-spacing:0.08em; color:#fff; background:<?= htmlspecialchars($primary) ?>; padding:5px 12px; border-radius:20px; }
  .letterhead .doctitle .examname { font-size:11px; color:#6b7280; margin-top:6px; }

  .studentbar { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:12px 16px; margin-bottom:20px; }
  .studentbar div span { display:block; }
  .studentbar .lbl { font-size:9px; text-transform:uppercase; letter-spacing:0.05em; color:#9ca3af; font-weight:700; }
  .studentbar .val { font-size:12.5px; color:#111827; font-weight:600; margin-top:2px; }

  table.grades { width:100%; border-collapse:collapse; margin-bottom:20px; }
  table.grades th { background:<?= htmlspecialchars($primary) ?>; color:#fff; font-size:10.5px; text-transform:uppercase; letter-spacing:0.04em; padding:9px 10px; text-align:left; }
  table.grades td { font-size:12px; padding:8px 10px; border-bottom:1px solid #e5e7eb; color:#1f2937; }
  table.grades tr:nth-child(even) td { background:#f9fafb; }
  table.grades tfoot td { font-weight:800; border-top:2px solid #111827; background:#f3f4f6; }

  .summary-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; margin-bottom:20px; }
  .summary-card { border:1px solid #e5e7eb; border-radius:10px; padding:12px; text-align:center; }
  .summary-card .lbl { font-size:9px; text-transform:uppercase; color:#9ca3af; font-weight:700; letter-spacing:0.04em; }
  .summary-card .val { font-size:19px; font-weight:800; color:<?= htmlspecialchars($primary) ?>; margin-top:4px; }

  .remark-box { border:1px dashed #d1d5db; border-radius:10px; padding:14px; font-size:12px; color:#374151; margin-bottom:26px; min-height:50px; }
  .remark-box .lbl { font-size:9.5px; text-transform:uppercase; color:#9ca3af; font-weight:700; letter-spacing:0.05em; margin-bottom:6px; }

  .signatures { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-top:40px; }
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
  <?php if (count($examOptions) + count($termOptions) + count($yearOptions) > 0): ?>
  <select onchange="if(this.value) location.search=this.value;">
    <?php if(!empty($examOptions)): ?>
    <optgroup label="Single Exam">
      <?php foreach($examOptions as $opt): ?>
        <option value="?exam_id=<?= $opt['id'] ?>" <?= $mode==='exam' && (int)$opt['id']===(int)$selectedExamId?'selected':'' ?>><?= htmlspecialchars($opt['name']) ?><?= $opt['exam_date'] ? ' — '.date('M Y', strtotime($opt['exam_date'])) : '' ?></option>
      <?php endforeach; ?>
    </optgroup>
    <?php endif; ?>
    <?php if(!empty($termOptions)): ?>
    <optgroup label="Period Report">
      <?php foreach($termOptions as $opt): ?>
        <option value="?term_id=<?= $opt['id'] ?>" <?= $mode==='term' && (int)$opt['id']===(int)($term['id'] ?? 0)?'selected':'' ?>><?= htmlspecialchars($opt['name']) ?><?= $opt['year_name'] ? ' — '.htmlspecialchars($opt['year_name']) : '' ?></option>
      <?php endforeach; ?>
    </optgroup>
    <?php endif; ?>
    <?php if(!empty($yearOptions)): ?>
    <optgroup label="Annual Report">
      <?php foreach($yearOptions as $opt): ?>
        <option value="?year_id=<?= $opt['id'] ?>" <?= $mode==='annual' && (int)$opt['id']===(int)($year['id'] ?? 0)?'selected':'' ?>>Annual — <?= htmlspecialchars($opt['name']) ?></option>
      <?php endforeach; ?>
    </optgroup>
    <?php endif; ?>
  </select>
  <?php endif; ?>
  <div class="spacer"></div>
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
      <div class="tag"><?= $mode==='annual' ? 'ANNUAL REPORT CARD' : ($mode==='term' ? 'PERIOD REPORT CARD' : 'ACADEMIC REPORT CARD') ?></div>
      <div class="examname"><?= htmlspecialchars($docLabel) ?></div>
    </div>
  </div>

  <?php $noData = ($mode==='exam' && !$exam) || ($mode==='term' && empty($termRows)) || ($mode==='annual' && empty($annual['rows'])); ?>
  <?php if ($noData): ?>
    <div class="remark-box">This student has no recorded grades for this selection yet.</div>
  <?php else: ?>

  <div class="studentbar">
    <div><span class="lbl">Student Name</span><span class="val"><?= htmlspecialchars($student['name']) ?></span></div>
    <div><span class="lbl">Admission No</span><span class="val"><?= htmlspecialchars($student['admission_no']) ?></span></div>
    <div><span class="lbl">Class</span><span class="val"><?= htmlspecialchars($class['name'] ?? '—') ?></span></div>
    <?php if ($mode==='exam'): ?>
    <div><span class="lbl">Exam Date</span><span class="val"><?= $exam['exam_date'] ? date('M d, Y', strtotime($exam['exam_date'])) : '—' ?></span></div>
    <?php elseif ($mode==='term'): ?>
    <div><span class="lbl">Period</span><span class="val"><?= date('M d', strtotime($term['start_date'])) ?> – <?= date('M d, Y', strtotime($term['end_date'])) ?></span></div>
    <?php else: ?>
    <div><span class="lbl">Academic Year</span><span class="val"><?= htmlspecialchars($year['name']) ?></span></div>
    <?php endif; ?>
  </div>

  <?php if ($mode==='exam'): ?>
  <table class="grades">
    <thead><tr><th>Subject</th><th>Marks Obtained</th><th>Total Marks</th><th>Percentage</th><th>Grade</th><th>Remark</th></tr></thead>
    <tbody>
      <?php foreach($grades as $g):
        $pct = $g['total_marks'] > 0 ? round($g['marks_obtained']/$g['total_marks']*100,1) : 0;
        $remark = $pct>=80?'Excellent':($pct>=70?'Very Good':($pct>=60?'Good':($pct>=50?'Fair':'Needs Improvement')));
      ?>
      <tr>
        <td><?= htmlspecialchars($g['course_name'] ?? '—') ?></td>
        <td><?= number_format($g['marks_obtained'],1) ?></td>
        <td><?= number_format($g['total_marks'],1) ?></td>
        <td><?= $pct ?>%</td>
        <td><?= htmlspecialchars($g['grade_letter']) ?></td>
        <td><?= $remark ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($grades)): ?><tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:20px;">No subject grades recorded for this exam.</td></tr><?php endif; ?>
    </tbody>
    <?php if(!empty($grades)): ?>
    <tfoot><tr><td>TOTAL</td><td><?= number_format($totalObtained,1) ?></td><td><?= number_format($totalPossible,1) ?></td><td colspan="2"><?= $overallPct ?>%</td><td><?= $overallGrade ?></td></tr></tfoot>
    <?php endif; ?>
  </table>

  <?php elseif ($mode==='term'): ?>
  <table class="grades">
    <thead><tr><th>Subject</th><th>Assessments</th><th>Marks Obtained</th><th>Total Marks</th><th>Period Average</th><th>Grade</th><th>Remark</th></tr></thead>
    <tbody>
      <?php foreach($termRows as $r):
        $pct = round((float)$r['avg_pct'],1);
        $gl = $pct>=90?'A+':($pct>=80?'A':($pct>=70?'B':($pct>=60?'C':($pct>=50?'D':'F'))));
        $remark = $pct>=80?'Excellent':($pct>=70?'Very Good':($pct>=60?'Good':($pct>=50?'Fair':'Needs Improvement')));
      ?>
      <tr>
        <td><?= htmlspecialchars($r['course_name'] ?? '—') ?></td>
        <td><?= (int)$r['exam_count'] ?></td>
        <td><?= number_format($r['obtained'],1) ?></td>
        <td><?= number_format($r['possible'],1) ?></td>
        <td><?= $pct ?>%</td>
        <td><?= $gl ?></td>
        <td><?= $remark ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>TOTAL</td><td></td><td><?= number_format($totalObtained,1) ?></td><td><?= number_format($totalPossible,1) ?></td><td><?= $overallPct ?>%</td><td colspan="2"><?= $overallGrade ?></td></tr></tfoot>
  </table>

  <?php else: ?>
  <table class="grades">
    <thead><tr>
      <th>Subject</th>
      <?php foreach($annual['periods'] as $p): ?><th><?= htmlspecialchars($p['name']) ?></th><?php endforeach; ?>
      <?php if($annual['hasOther']): ?><th>Other</th><?php endif; ?>
      <th>Yearly Avg</th><th>Grade</th>
    </tr></thead>
    <tbody>
      <?php foreach($annual['rows'] as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['course_name']) ?></td>
        <?php foreach($annual['periods'] as $p): ?>
          <td><?= isset($r['periods'][$p['id']]) ? $r['periods'][$p['id']].'%' : '—' ?></td>
        <?php endforeach; ?>
        <?php if($annual['hasOther']): ?><td><?= isset($r['periods']['other']) ? $r['periods']['other'].'%' : '—' ?></td><?php endif; ?>
        <td style="font-weight:700;"><?= $r['yearly_pct'] !== null ? $r['yearly_pct'].'%' : '—' ?></td>
        <td><?= htmlspecialchars($r['grade_letter'] ?? '—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td>YEARLY AVERAGE</td><td colspan="<?= count($annual['periods']) + ($annual['hasOther']?1:0) ?>"></td><td><?= $overallPct ?>%</td><td><?= $overallGrade ?></td></tr></tfoot>
  </table>
  <?php endif; ?>

  <div class="summary-grid">
    <div class="summary-card"><div class="lbl">Overall Average</div><div class="val"><?= $overallPct ?>%</div></div>
    <div class="summary-card"><div class="lbl">Overall Grade</div><div class="val"><?= htmlspecialchars($overallGrade) ?></div></div>
    <div class="summary-card"><div class="lbl">Class Position</div><div class="val"><?= $rank ? $rank.' of '.$rankOf : '—' ?></div></div>
    <div class="summary-card"><div class="lbl">Attendance</div><div class="val"><?= $attendance && $attendance['pct'] !== null ? $attendance['pct'].'%' : '—' ?></div></div>
  </div>

  <div class="remark-box">
    <div class="lbl">Class Teacher's Remark</div>
    <?= $overallPct>=80 ? 'An excellent performance this term. Keep up the great work.' : ($overallPct>=60 ? 'A good, solid performance. Continue working hard to improve further.' : ($overallPct>0 ? 'This performance needs improvement. Extra effort and support are recommended.' : '')) ?>
  </div>

  <div class="signatures">
    <div><div class="line">Class Teacher</div></div>
    <div><div class="line">Principal</div></div>
    <div><div class="line">Parent / Guardian</div></div>
  </div>

  <?php endif; ?>

  <div class="doc-footer">Generated on <?= date('F d, Y \a\t H:i') ?> · This is a computer-generated document.</div>

</div>

</body>
</html>
