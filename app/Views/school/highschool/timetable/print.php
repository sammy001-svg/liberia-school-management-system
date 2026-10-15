<?php
// Printable class timetable(s) on the school's own letterhead — one sheet per class.
$dayShort = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
             'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday'];
$hhmm = function ($t) { return substr((string)$t, 0, 5); };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars($tenant['name'] ?? '') ?></title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: #e9e9ee; font-family: Arial, Helvetica, sans-serif; color: #111; }
  .toolbar { position: sticky; top: 0; background: #1e1b2e; color: #fff; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; z-index: 2; }
  .toolbar button { background: #4cae72; color: #fff; border: 0; border-radius: 6px; padding: 8px 16px; font-weight: 700; cursor: pointer; }
  .sheet { width: 297mm; max-width: 100%; margin: 12px auto; background: #fff; padding: 12mm; page-break-after: always; }
  .sheet:last-child { page-break-after: auto; }
  .head { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #222; padding-bottom: 10px; margin-bottom: 12px; }
  .head img { width: 74px; height: 74px; object-fit: contain; }
  .head .school { flex: 1; text-align: center; }
  .head .name { font-size: 25px; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; }
  .head .meta { font-size: 11.5px; color: #333; margin-top: 3px; }
  .title { text-align: center; margin-bottom: 12px; }
  .title h2 { margin: 0; font-size: 17px; letter-spacing: .05em; }
  .title .sub { font-size: 12px; color: #444; margin-top: 3px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #333; padding: 6px 7px; font-size: 11.5px; vertical-align: top; }
  thead th { background: #efefef; text-align: center; font-size: 11px; letter-spacing: .05em; text-transform: uppercase; }
  td.period { width: 108px; text-align: center; font-weight: 700; }
  td.period span { display: block; font-weight: 400; font-size: 10px; color: #444; }
  .subject { font-weight: 700; }
  .who { font-size: 10.5px; color: #333; }
  tr.break td { background: #f6f1e2; text-align: center; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; font-size: 11px; }
  .foot { display: flex; justify-content: space-between; font-size: 10.5px; color: #555; margin-top: 10px; }
  .empty { text-align: center; color: #777; padding: 24px; font-size: 12px; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .sheet { margin: 0; width: auto; padding: 0; }
    @page { size: A4 landscape; margin: 10mm; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <span><?= htmlspecialchars($pageTitle) ?> · <?= count($sheets) ?> class(es)</span>
  <button onclick="window.print()">🖨 Print / Save as PDF</button>
</div>

<?php foreach ($sheets as $sheet): $rows = $sheet['rows'];
  $used = [];
  foreach ($rows as $r) { foreach (array_keys($r['days']) as $d) { $used[$d] = true; } }
  $showDays = array_values(array_filter($days, fn($d) => isset($used[$d]))) ?: array_slice($days, 0, 5);
?>
  <div class="sheet">
    <div class="head">
      <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php endif; ?>
      <div class="school">
        <div class="name"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
        <div class="meta"><?= htmlspecialchars(trim((string)($tenant['address'] ?? ''))) ?></div>
        <div class="meta">
          <?= !empty($tenant['phone']) ? 'Phone: ' . htmlspecialchars($tenant['phone']) : '' ?>
          <?= !empty($tenant['email']) ? ' &nbsp;·&nbsp; Email: ' . htmlspecialchars($tenant['email']) : '' ?>
        </div>
      </div>
      <?php if (!empty($tenant['logo'])): ?><span style="width:74px;"></span><?php endif; ?>
    </div>

    <div class="title">
      <h2>CLASS TIMETABLE — <?= htmlspecialchars(mb_strtoupper($sheet['class']['name'])) ?></h2>
      <?php if (!empty($yearName)): ?><div class="sub">Academic Year <?= htmlspecialchars($yearName) ?></div><?php endif; ?>
    </div>

    <?php if (!$rows): ?>
      <div class="empty">No timetable has been set up for this class yet.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th style="width:108px;">Period</th><?php foreach ($showDays as $d): ?><th><?= $dayShort[$d] ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): $isBreak = $r['type'] !== 'lesson'; ?>
          <?php if ($isBreak): ?>
            <tr class="break">
              <td class="period"><?= htmlspecialchars($r['label']) ?><span><?= $hhmm($r['start']) ?>–<?= $hhmm($r['end']) ?></span></td>
              <td colspan="<?= count($showDays) ?>"><?= htmlspecialchars($r['label']) ?> · <?= $hhmm($r['start']) ?>–<?= $hhmm($r['end']) ?></td>
            </tr>
          <?php else: ?>
            <tr>
              <td class="period">Period <?= $r['period_no'] ?? '—' ?><span><?= $hhmm($r['start']) ?>–<?= $hhmm($r['end']) ?></span></td>
              <?php foreach ($showDays as $d): ?>
                <td>
                  <?php foreach ($r['days'][$d] ?? [] as $e): ?>
                    <div class="subject"><?= htmlspecialchars($e['course_name'] ?? 'Free period') ?></div>
                    <?php if (!empty($e['teacher_name']) || !empty($e['room'])): ?>
                      <div class="who"><?= htmlspecialchars($e['teacher_name'] ?? '') ?><?= $e['room'] ? ' · ' . htmlspecialchars($e['room']) : '' ?></div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                  <?php if (empty($r['days'][$d])): ?>&nbsp;<?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <div class="foot">
      <span>Printed <?= date('M j, Y') ?></span>
      <span><?= htmlspecialchars($tenant['name'] ?? '') ?></span>
    </div>
  </div>
<?php endforeach; ?>

<?php if (!$sheets): ?>
  <div class="sheet"><div class="empty">No class has a timetable yet.</div></div>
<?php endif; ?>
</body>
</html>
