<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a>
  <span>/</span><span>Promotion Statements</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Promotion Statements</div>
    <div class="page-header-sub">
      The year-end decision printed on the left panel of each report card. Leave a student blank
      and their card prints the ruled statement for the sponsor to complete by hand.
    </div>
  </div>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:18px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (string)$c['id']===(string)$selectedClassId?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="year_id" class="form-control" style="max-width:200px;">
      <?php foreach($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$y['id']===(string)$selectedYearId?'selected':'' ?>><?= htmlspecialchars($y['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load Class</button>
  </div>
</form>

<?php if (!empty($students)): ?>
<?php
  // One closing date covers the class; seed the field from whatever is already stored.
  $closing = '';
  foreach ($existing as $e) { if (!empty($e['closing_date'])) { $closing = $e['closing_date']; break; } }
?>
<form method="POST" action="<?= $cfg['url'] ?>/school/grades/promotions/save">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <input type="hidden" name="class_id" value="<?= htmlspecialchars($selectedClassId) ?>">
  <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($selectedYearId) ?>">

  <div class="card">
    <div class="card-header">
      <div class="card-title"><?= count($students) ?> student<?= count($students)===1?'':'s' ?></div>
      <div style="display:flex;gap:10px;align-items:center;">
        <label class="form-label" style="margin:0;font-size:12px;">Closing Date</label>
        <input type="date" name="closing_date" value="<?= htmlspecialchars($closing) ?>" class="form-control" style="max-width:170px;padding:6px;">
        <button type="submit" class="btn btn-sm btn-primary">Save Statements</button>
      </div>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student</th><th>Completed the work?</th><th>Decision</th><th>Promoted to / Condition in</th></tr></thead>
        <tbody>
          <?php foreach($students as $s): $e = $existing[(int)$s['id']] ?? null; ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
            <td>
              <select name="promotion[<?= $s['id'] ?>][satisfactory]" class="form-control" style="max-width:150px;padding:6px;">
                <?php $sat = $e['satisfactory'] ?? null; ?>
                <option value="" <?= $sat===null?'selected':'' ?>>— blank —</option>
                <option value="1" <?= $sat==='1'||$sat===1?'selected':'' ?>>HAS</option>
                <option value="0" <?= ($sat==='0'||$sat===0)&&$sat!==null?'selected':'' ?>>HAS NOT</option>
              </select>
            </td>
            <td>
              <select name="promotion[<?= $s['id'] ?>][decision]" class="form-control" style="max-width:230px;padding:6px;">
                <?php $d = $e['decision'] ?? ''; ?>
                <option value="" <?= $d===''?'selected':'' ?>>— blank —</option>
                <option value="promoted"   <?= $d==='promoted'?'selected':'' ?>>A. Promoted to</option>
                <option value="condition"  <?= $d==='condition'?'selected':'' ?>>B. Condition in</option>
                <option value="repeat"     <?= $d==='repeat'?'selected':'' ?>>C. Repeat the grade</option>
                <option value="not_enroll" <?= $d==='not_enroll'?'selected':'' ?>>D. Not to enroll next year</option>
              </select>
            </td>
            <td>
              <input type="text" name="promotion[<?= $s['id'] ?>][detail]" value="<?= htmlspecialchars($e['decision_detail'] ?? '') ?>"
                     class="form-control" style="max-width:220px;padding:6px;" placeholder="e.g. 10th Grade">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>
<?php elseif ($selectedClassId): ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">🎓</div>
  <div class="empty-state-text">No active students in that class.</div>
</div></div>
<?php else: ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">🎓</div>
  <div class="empty-state-text">Select a class above to record its promotion statements.</div>
</div></div>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
