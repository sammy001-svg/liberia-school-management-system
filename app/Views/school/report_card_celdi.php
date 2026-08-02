<?php
/**
 * CELDI ACADEMY report card — one student.
 *
 * A facsimile of the school's printed stationery: two landscape sheets, the
 * Promotion Statement and card face, then the eleven-column grade grid. The
 * sheets themselves live in a partial shared with the class batch view so the
 * two can never drift apart.
 *
 * Self-contained (own <style>, no style.css) like the other print views, so the
 * app's theming can never leak into something that goes home to a parent.
 */
$cfg = require ROOT_DIR . '/config/app.php';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$logo = !empty($tenant['logo']) ? $cfg['url'] . '/uploads/logos/' . $tenant['logo'] : null;

// The controller extracts the builder's result as loose variables; the sheet
// partial takes a single $card so the class batch can loop it per student.
$card = [
    'student'       => $student,
    'class'         => $class         ?? null,
    'year'          => $year          ?? null,
    'columns'       => $columns       ?? [],
    'rows'          => $rows          ?? [],
    'columnAverage' => $columnAverage ?? [],
    'rank'          => $rank          ?? [],
    'absence'       => $absence       ?? [],
    'scale'         => $scale         ?? [],
    'letterStyle'   => $letterStyle   ?? false,
    'promotion'     => $promotion     ?? null,
    'sponsorName'   => $sponsorName   ?? null,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Report Card — <?= htmlspecialchars($student['name']) ?></title>
<?php require ROOT_DIR . '/app/Views/school/partials/celdi_card_styles.php'; ?>
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
  <?php if (!empty($class['id']) && empty($isParentView)): ?>
    <a class="btn" href="<?= $cfg['url'] ?>/school/grades/report-cards/class/<?= (int)$class['id'] ?>?year_id=<?= htmlspecialchars((string)$selectedYearId) ?>">
      Print Whole Class
    </a>
  <?php endif; ?>
  <div class="spacer"></div>
  <button onclick="window.print()">Print Report Card</button>
</div>

<?php if (empty($slotsConfigured)): ?>
<div class="notice">
  No marking periods have been set up for this class in <strong><?= htmlspecialchars($year['name'] ?? 'this year') ?></strong>,
  so the grid below is empty. Open <strong>Grades &amp; Exams → Marking Periods</strong> and set up the six periods
  and two semester exams for this class, then enter marks against them.
</div>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/school/partials/celdi_card_sheets.php'; ?>

</body>
</html>
