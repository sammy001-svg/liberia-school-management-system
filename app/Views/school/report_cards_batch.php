<?php
/**
 * Every report card for one class, in a single printable document — the form the
 * school actually produces these in (their own 2025-2026 export is one long PDF
 * of consecutive cards).
 *
 * Two landscape sheets per student, page-broken so each student's card starts on
 * a fresh sheet. Renders the same partial as the single-student view, so the two
 * outputs are identical cell for cell by construction.
 */
$cfg = require ROOT_DIR . '/config/app.php';
$schoolName = $tenant['name'] ?? ($cfg['name'] ?? 'School');
$logo = !empty($tenant['logo']) ? $cfg['url'] . '/uploads/logos/' . $tenant['logo'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Report Cards — <?= htmlspecialchars($class['name'] ?? 'Class') ?></title>
<?php require ROOT_DIR . '/app/Views/school/partials/celdi_card_styles.php'; ?>
</head>
<body>

<div class="toolbar">
  <a class="btn" href="<?= $cfg['url'] ?>/school/classes/<?= (int)$class['id'] ?>">&larr; Back to <?= htmlspecialchars($class['name'] ?? 'Class') ?></a>
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
  <span style="font-size:13px;color:#374151;font-family:'Segoe UI',Arial,sans-serif;">
    <?= count($cards) ?> student<?= count($cards) === 1 ? '' : 's' ?>
  </span>
  <button onclick="window.print()">Print All Report Cards</button>
</div>

<?php if (empty($cards)): ?>
  <div class="notice">No active students in this class, so there is nothing to print.</div>
<?php elseif (empty($slotsConfigured)): ?>
  <div class="notice">
    No marking periods have been set up for this class in
    <strong><?= htmlspecialchars($yearName ?? 'this year') ?></strong>, so every grid below is empty.
    Set them up under <strong>Grades &amp; Exams → Marking Periods</strong> first.
  </div>
<?php endif; ?>

<?php foreach ($cards as $card): ?>
  <?php require ROOT_DIR . '/app/Views/school/partials/celdi_card_sheets.php'; ?>
<?php endforeach; ?>

</body>
</html>
