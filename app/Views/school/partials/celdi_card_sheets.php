<?php
/**
 * The two landscape sheets of one student's CELDI report card.
 *
 * Included once per student, so everything it needs arrives in a single $card
 * array (the return of Controller::buildCeldiReportCard() plus a 'student' key)
 * rather than as loose variables — that keeps one student's figures from leaking
 * into the next when the class batch loops over this file.
 *
 * Expects from the including scope: $card, $tenant, $schoolName, $logo.
 */
$student       = $card['student'];
$class         = $card['class']         ?? null;
$year          = $card['year']          ?? null;
$columns       = $card['columns']       ?? [];
$rows          = $card['rows']          ?? [];
$columnAverage = $card['columnAverage'] ?? [];
$rank          = $card['rank']          ?? [];
$absence       = $card['absence']       ?? [];
$scale         = $card['scale']         ?? [];
$letterStyle   = !empty($card['letterStyle']);
$promotion     = $card['promotion']     ?? null;
$sponsorName   = $card['sponsorName']   ?? null;

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

// Column groups in print order; the borderless gap sits between them.
$sem1 = ['p1','p2','p3','e1','s1'];
$sem2 = ['p4','p5','p6','e2','s2'];
$labelOf = [];
foreach ($columns as $c) { $labelOf[$c['key']] = $c['label']; }

/** Renders one <td> for a grid cell. */
$cell = function (?float $v, string $key) use ($fmt, $RED_BELOW) {
    $cls = 'score';
    if ($v !== null && $v < $RED_BELOW) { $cls .= ' low'; }
    if ($key === 's1' || $key === 's2') { $cls .= ' semave'; }
    if ($key === 'yr') { $cls .= ' yearly'; }
    return '<td class="' . $cls . '">' . htmlspecialchars($fmt($v)) . '</td>';
};
?>

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
