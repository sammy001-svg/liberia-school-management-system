<?php
/**
 * CELDI ACADEMY report card — a facsimile of the school's printed stationery.
 *
 * Two landscape sheets per student:
 *   Sheet 1  Promotion Statement (left panel) + card face with the
 *            parent/guardian signature table (right panel)
 *   Sheet 2  The eleven-column grade grid, Days Absent / Average / Class Rank
 *            footer rows, and the METHOD OF GRADING key
 *
 * Self-contained (own <style>, no style.css) like the other print views, so the
 * app's theming can never leak into something that goes home to a parent.
 */
$cfg = require ROOT_DIR . '/config/app.php';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$logo = !empty($tenant['logo']) ? $cfg['url'] . '/uploads/logos/' . $tenant['logo'] : null;

// Scores below the "Concert Not Understood" band print in red on the school's
// cards; everything at or above it is blue. Single threshold so it is one edit
// if the school ever moves the line.
$RED_BELOW = 72;

$gradeLabel = strtoupper($class['name'] ?? $class['grade_level'] ?? '');
$yearLabel  = $year['name'] ?? '';

/** Formats one grid cell: a letter for early-years classes, else the number. */
$fmt = function (?float $v, bool $oneDecimal = false) use ($letterStyle) {
    if ($v === null) { return ''; }
    if ($letterStyle) { return Controller::celdiLetter($v); }
    return $oneDecimal ? number_format($v, 1) : (string)(int)round($v);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Report Card — <?= htmlspecialchars($student['name']) ?></title>
<style>
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'Times New Roman', Times, serif; background:#e5e7eb; padding:24px;
         display:flex; flex-direction:column; align-items:center; gap:24px; }

  .toolbar { display:flex; align-items:center; gap:12px; width:11.69in; max-width:100%; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar select, .toolbar button, .toolbar a.btn {
      padding:9px 16px; border-radius:8px; font-size:13px; font-family:inherit; border:1px solid #d1d5db;
      background:#fff; color:#111827; text-decoration:none; cursor:pointer; }
  .toolbar button { background:#4c1d95; color:#fff; border-color:#4c1d95; font-weight:600; }
  .toolbar .spacer { flex:1; }
  .notice { width:11.69in; max-width:100%; background:#fef3c7; border:1px solid #f59e0b; color:#78350f;
            padding:12px 16px; border-radius:8px; font-family:'Segoe UI',Arial,sans-serif; font-size:13px; }

  .sheet { width:11.69in; min-height:8.27in; background:#fff; box-shadow:0 8px 24px rgba(0,0,0,0.18);
           padding:0.4in 0.45in; position:relative; }

  /* ── Sheet 1 ───────────────────────────────────────────────────────────── */
  .face { display:grid; grid-template-columns:1fr 1fr; gap:0.4in; height:100%; }
  .panel { border:1.5px solid #111; padding:16px 18px; display:flex; flex-direction:column; }

  .panel h2 { font-size:15px; font-weight:700; text-align:center; letter-spacing:0.02em; margin-bottom:14px; }
  .certify { text-align:center; font-size:13px; margin-bottom:4px; }
  .student-name-script { text-align:center; font-size:20px; font-weight:700; margin:6px 0 12px; }
  .promo-body { font-size:13px; line-height:1.9; text-align:center; }
  .promo-opts { font-size:13px; line-height:2.1; margin:14px 0 0 6px; }
  .promo-opts .fill { display:inline-block; border-bottom:1px solid #111; min-width:150px; }
  .promo-opts .chosen { font-weight:700; }

  .sig-lines { margin-top:auto; padding-top:18px; }
  .sig-lines .line { border-bottom:1px solid #111; height:26px; }
  .sig-lines .cap { text-align:right; font-size:11px; letter-spacing:0.06em; margin:2px 0 14px; }

  .crest { text-align:center; margin-bottom:6px; }
  .crest img { height:74px; object-fit:contain; }
  .school-name { text-align:center; font-size:30px; font-weight:700; color:#5b21b6; letter-spacing:0.01em; line-height:1.1; }
  .school-meta { text-align:center; font-size:11px; line-height:1.5; margin-top:4px; }
  .doc-title { text-align:center; font-size:15px; font-weight:700; margin:12px 0 10px; letter-spacing:0.02em; }

  .ruled { border-bottom:1px solid #111; text-align:center; font-size:15px; font-weight:700; padding-bottom:2px; text-transform:uppercase; }
  .ruled-cap { text-align:center; font-size:10.5px; margin-top:2px; }
  .two-up { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:14px; }

  .guardians-title { text-align:center; font-size:13px; font-weight:700; margin:14px 0 4px; letter-spacing:0.02em; }
  .guardians-note { text-align:center; font-size:10.5px; margin-bottom:8px; }
  table.guardians { width:100%; border-collapse:collapse; }
  table.guardians th, table.guardians td { border:1px solid #111; font-size:11px; padding:5px 8px; }
  table.guardians th { font-weight:700; text-align:center; }
  table.guardians td.period { width:26%; }
  table.guardians td { height:24px; }

  /* ── Sheet 2 ───────────────────────────────────────────────────────────── */
  table.grid { width:100%; border-collapse:collapse; table-layout:fixed; }
  table.grid th, table.grid td { font-size:11px; text-align:center; padding:3px 2px; border:1px solid #111; }
  table.grid th { font-weight:700; }
  table.grid .subj { text-align:left; width:15%; padding-left:6px; font-size:11.5px; }
  /* The printed card is two bordered blocks with white space between them and a
     detached Yearly column. A borderless spacer column reproduces that gap while
     keeping every row in a single table, so subject rows can never drift apart. */
  table.grid .gap { border:none; width:14px; }
  table.grid .score { color:#1d4ed8; }
  table.grid .score.low { color:#dc2626; }
  table.grid .semave, table.grid .yearly { font-weight:700; }
  table.grid tr.foot td { font-weight:700; }
  table.grid tr.foot td.subj { text-align:left; }

  .method { margin-top:16px; text-align:center; }
  .method h3 { font-size:12px; font-weight:700; text-decoration:underline; letter-spacing:0.04em; margin-bottom:6px; }
  .method .bands { font-size:11px; line-height:1.7; }
  .method .bands span { margin:0 12px; white-space:nowrap; }

  @media print {
    body { background:#fff; padding:0; gap:0; display:block; }
    .toolbar, .notice { display:none; }
    .sheet { box-shadow:none; width:auto; min-height:auto; padding:0.3in 0.35in; page-break-after:always; }
    .sheet:last-of-type { page-break-after:auto; }
    @page { size:A4 landscape; margin:0.25in; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <form method="GET" style="display:flex;gap:10px;align-items:center;">
    <?php if (!empty($yearOptions)): ?>
    <select name="year_id" onchange="this.form.submit()">
      <?php foreach ($yearOptions as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$y['id'] === (string)$selectedYearId ? 'selected' : '' ?>>
          <?= htmlspecialchars($y['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>
  </form>
  <div class="spacer"></div>
  <button onclick="window.print()">Print Report Card</button>
</div>

<?php if (empty($slotsConfigured)): ?>
<div class="notice">
  No marking periods have been set up for this class in <strong><?= htmlspecialchars($yearLabel ?: 'this year') ?></strong>,
  so the grid below is empty. Open <strong>Grades &amp; Exams → Marking Periods</strong> and set up the six periods
  and two semester exams for this class, then enter marks against them.
</div>
<?php endif; ?>

<!-- ══ SHEET 1 — promotion statement + card face ══════════════════════════ -->
<div class="sheet">
  <div class="face">

    <div class="panel">
      <h2>PROMOTION STATEMENT</h2>
      <div class="certify">This certifies that</div>
      <div class="student-name-script"><?= htmlspecialchars($student['name']) ?></div>
      <div class="promo-body">
        <?php $sat = $promotion['satisfactory'] ?? null; ?>
        <?php if ($sat === null): ?>HAS/HAS NOT<?php else: ?><strong><?= $sat ? 'HAS' : 'HAS NOT' ?></strong><?php endif; ?>
        satisfactorily completed the<br>
        work of <strong><?= htmlspecialchars($gradeLabel ?: '—') ?></strong> and is
      </div>
      <?php $decision = $promotion['decision'] ?? null; $detail = $promotion['decision_detail'] ?? ''; ?>
      <div class="promo-opts">
        <div class="<?= $decision === 'promoted' ? 'chosen' : '' ?>">
          A. Promoted to <span class="fill"><?= $decision === 'promoted' ? htmlspecialchars($detail) : '' ?></span>
        </div>
        <div class="<?= $decision === 'condition' ? 'chosen' : '' ?>">
          B. Condition in <span class="fill"><?= $decision === 'condition' ? htmlspecialchars($detail) : '' ?></span>
        </div>
        <div class="<?= $decision === 'repeat' ? 'chosen' : '' ?>">C. Required to repeat the grade.</div>
        <div class="<?= $decision === 'not_enroll' ? 'chosen' : '' ?>">D. Ask not to enroll next year.</div>
      </div>
      <div class="sig-lines">
        <div class="line"><?= $sponsorName ? '&nbsp;' . htmlspecialchars($sponsorName) : '' ?></div>
        <div class="cap">CLASS SPONSOR</div>
        <div class="line"></div>
        <div class="cap">PRINCIPAL</div>
        <div class="line"><?= !empty($promotion['closing_date']) ? '&nbsp;' . htmlspecialchars(date('F j, Y', strtotime($promotion['closing_date']))) : '' ?></div>
        <div class="cap">CLOSING DATE</div>
      </div>
    </div>

    <div class="panel">
      <?php if ($logo): ?><div class="crest"><img src="<?= htmlspecialchars($logo) ?>" alt=""></div><?php endif; ?>
      <div class="school-name"><?= htmlspecialchars(strtoupper($schoolName)) ?></div>
      <div class="school-meta">
        <?php if (!empty($tenant['address'])): ?><?= nl2br(htmlspecialchars($tenant['address'])) ?><br><?php endif; ?>
        <?php if (!empty($tenant['phone'])): ?>Phone: <?= htmlspecialchars($tenant['phone']) ?><br><?php endif; ?>
        <?php if (!empty($tenant['email'])): ?>Email: <?= htmlspecialchars($tenant['email']) ?><?php endif; ?>
      </div>

      <div class="doc-title">ACADEMIC REPORT CARD</div>

      <div class="ruled"><?= htmlspecialchars($student['name']) ?></div>
      <div class="ruled-cap">Name of Student</div>

      <div class="two-up">
        <div>
          <div class="ruled"><?= htmlspecialchars($yearLabel ?: '—') ?></div>
          <div class="ruled-cap">Academic Year</div>
        </div>
        <div>
          <div class="ruled"><?= htmlspecialchars($gradeLabel ?: '—') ?></div>
          <div class="ruled-cap">Class/Grade</div>
        </div>
      </div>

      <div class="guardians-title">PARENTS/GUARDIANS</div>
      <div class="guardians-note">Must sign each period as evidence that they have seen the period report.</div>
      <table class="guardians">
        <thead><tr><th>Period</th><th>Teacher</th><th>Parent/Guardian</th></tr></thead>
        <tbody>
          <?php foreach (['1st Period','2nd Period','3rd Period','4th Period','5th Period','6th Period'] as $p): ?>
          <tr><td class="period"><?= $p ?></td><td></td><td></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<!-- ══ SHEET 2 — grade grid ═══════════════════════════════════════════════ -->
<?php
// Column groups, in print order, with the borderless gap between them.
$sem1 = ['p1','p2','p3','e1','s1'];
$sem2 = ['p4','p5','p6','e2','s2'];
$labelOf = [];
foreach ($columns as $c) { $labelOf[$c['key']] = $c['label']; }

/** Renders one <td> for a grid cell. */
$cell = function (?float $v, string $key) use ($fmt, $RED_BELOW, $letterStyle) {
    $derived = in_array($key, ['s1','s2','yr'], true);
    $cls = 'score';
    if ($v !== null && $v < $RED_BELOW) { $cls .= ' low'; }
    if ($key === 's1' || $key === 's2') { $cls .= ' semave'; }
    if ($key === 'yr') { $cls .= ' yearly'; }
    return '<td class="' . $cls . '">' . htmlspecialchars($fmt($v)) . '</td>';
};
?>
<div class="sheet">
  <table class="grid">
    <thead>
      <tr>
        <th class="subj" rowspan="2">SUBJECTS</th>
        <th colspan="5">FIRST SEMESTER</th>
        <th class="gap"></th>
        <th colspan="5">SECOND SEMESTER</th>
        <th class="gap"></th>
        <th rowspan="2">Yearly<br>Ave.</th>
      </tr>
      <tr>
        <?php foreach ($sem1 as $k): ?><th><?= htmlspecialchars($labelOf[$k]) ?></th><?php endforeach; ?>
        <th class="gap"></th>
        <?php foreach ($sem2 as $k): ?><th><?= htmlspecialchars($labelOf[$k]) ?></th><?php endforeach; ?>
        <th class="gap"></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
      <tr><td class="subj" colspan="13" style="text-align:center;padding:18px;font-style:italic;">
        This class has no subjects assigned yet.
      </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td class="subj"><?= htmlspecialchars($r['subject']) ?></td>
        <?php foreach ($sem1 as $k) { echo $cell($r['cells'][$k], $k); } ?>
        <td class="gap"></td>
        <?php foreach ($sem2 as $k) { echo $cell($r['cells'][$k], $k); } ?>
        <td class="gap"></td>
        <?= $cell($r['cells']['yr'], 'yr') ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="foot">
        <td class="subj">Days Absent</td>
        <?php foreach ($sem1 as $k): ?>
          <td><?= isset($absence[$k]) && $absence[$k] !== null ? (int)$absence[$k] : '' ?></td>
        <?php endforeach; ?>
        <td class="gap"></td>
        <?php foreach ($sem2 as $k): ?>
          <td><?= isset($absence[$k]) && $absence[$k] !== null ? (int)$absence[$k] : '' ?></td>
        <?php endforeach; ?>
        <td class="gap"></td>
        <td></td>
      </tr>
      <tr class="foot">
        <td class="subj">Average</td>
        <?php foreach ($sem1 as $k): ?><td><?= htmlspecialchars($fmt($columnAverage[$k] ?? null, true)) ?></td><?php endforeach; ?>
        <td class="gap"></td>
        <?php foreach ($sem2 as $k): ?><td><?= htmlspecialchars($fmt($columnAverage[$k] ?? null, true)) ?></td><?php endforeach; ?>
        <td class="gap"></td>
        <td><?= htmlspecialchars($fmt($columnAverage['yr'] ?? null, true)) ?></td>
      </tr>
      <tr class="foot">
        <td class="subj">Class Rank</td>
        <?php foreach ($sem1 as $k): ?>
          <td><?= !empty($rank[$k]) ? (int)$rank[$k]['position'] . '/' . (int)$rank[$k]['of'] : '' ?></td>
        <?php endforeach; ?>
        <td class="gap"></td>
        <?php foreach ($sem2 as $k): ?>
          <td><?= !empty($rank[$k]) ? (int)$rank[$k]['position'] . '/' . (int)$rank[$k]['of'] : '' ?></td>
        <?php endforeach; ?>
        <td class="gap"></td>
        <td><?= !empty($rank['yr']) ? (int)$rank['yr']['position'] . '/' . (int)$rank['yr']['of'] : '' ?></td>
      </tr>
    </tfoot>
  </table>

  <div class="method">
    <h3>METHOD OF GRADING</h3>
    <div class="bands">
      <?php foreach ($scale as $i => $b): ?>
        <span><?= (int)$b['min'] ?> - <?= (int)$b['max'] ?> = <?= htmlspecialchars($b['label']) ?> (<?= $b['letter'] ?>)</span><?php if ($i === 2): ?><br><?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
</div>

</body>
</html>
