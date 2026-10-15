<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/timetable';
$className = '';
foreach ($classes as $c) { if ((int)$c['id'] === (int)$classId) { $className = $c['name']; } }
$dayShort = ['monday' => 'Mon', 'tuesday' => 'Tue', 'wednesday' => 'Wed', 'thursday' => 'Thu', 'friday' => 'Fri', 'saturday' => 'Sat'];
// Only the days that actually carry something, so a Mon–Fri school isn't shown a Saturday column.
$usedDays = [];
foreach ($rows as $r) { foreach (array_keys($r['days']) as $d) { $usedDays[$d] = true; } }
$showDays = array_values(array_filter($days, fn($d) => isset($usedDays[$d]))) ?: array_slice($days, 0, 5);
$hhmm = fn($t) => substr((string)$t, 0, 5);
?>
<style>
.tt-table { width:100%; border-collapse:separate; border-spacing:0; }
.tt-table th, .tt-table td { border-bottom:1px solid var(--border); padding:8px; vertical-align:top; }
.tt-table thead th { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--text-muted); text-align:left; }
.tt-period { width:120px; white-space:nowrap; }
.tt-period b { display:block; font-size:13px; }
.tt-period span { font-size:11px; color:var(--text-muted); font-family:monospace; }
.tt-cell { min-width:150px; }
.tt-slot { border:1px solid var(--border); border-left:3px solid var(--primary-ink, var(--primary-light)); border-radius:8px; padding:7px 9px; background:var(--surface-1); }
.tt-slot .s { font-weight:700; font-size:12.5px; }
.tt-slot .m { font-size:11px; color:var(--text-muted); margin-top:2px; }
.tt-slot .acts { display:flex; gap:4px; margin-top:6px; }
.tt-slot .acts button { padding:1px 7px; font-size:11px; }
.tt-break td { background:rgba(245,158,11,0.08); }
.tt-break .tt-note { font-weight:700; color:var(--warning); font-size:12.5px; letter-spacing:.04em; }
.tt-empty { color:var(--text-muted); font-size:11.5px; }
@media (max-width:900px) { .tt-wrap { overflow-x:auto; } .tt-table { min-width:760px; } }
</style>

<div class="page-header">
  <div>
    <div class="page-header-title">Timetable<?= $className ? ' — ' . htmlspecialchars($className) : '' ?></div>
    <div class="page-header-sub">Lessons by period, with breaks and lunch. Each day's first lesson is Period 1.</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php if ($classId): ?>
      <a href="<?= $base ?>/print?class_id=<?= (int)$classId ?>" target="_blank" class="btn btn-secondary">🖨 Download / Print</a>
    <?php endif; ?>
    <a href="<?= $base ?>/print" target="_blank" class="btn btn-outline">All Classes</a>
    <button type="button" class="btn btn-primary" onclick="ttOpen()">＋ Add Slot</button>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <select name="class_id" class="form-control" style="max-width:240px;" onchange="this.form.submit()">
      <option value="">— Select Class —</option>
      <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= (int)$classId === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load</button>
  </div>
</form>

<?php if (!$classId): ?>
  <div class="card"><div class="empty-state"><div class="empty-state-icon">🗓️</div>
    <div class="empty-state-text">Choose a class to see and edit its timetable.</div></div></div>
<?php elseif (!$rows): ?>
  <div class="card"><div class="empty-state"><div class="empty-state-icon">🗓️</div>
    <div class="empty-state-text">Nothing on <?= htmlspecialchars($className) ?>'s timetable yet. Add the first period, then the break and lunch.</div></div></div>
<?php else: ?>
<div class="card">
  <div class="card-header"><div class="card-title">Week at a glance</div></div>
  <div class="tt-wrap">
    <table class="tt-table">
      <thead>
        <tr><th class="tt-period">Period</th><?php foreach ($showDays as $d): ?><th><?= $dayShort[$d] ?></th><?php endforeach; ?></tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $r): $isBreak = $r['type'] !== 'lesson'; ?>
        <tr class="<?= $isBreak ? 'tt-break' : '' ?>">
          <td class="tt-period">
            <b><?= $isBreak ? htmlspecialchars($r['label']) : 'Period ' . ($r['period_no'] ?? '—') ?></b>
            <span><?= $hhmm($r['start']) ?>–<?= $hhmm($r['end']) ?></span>
          </td>
          <?php foreach ($showDays as $d): ?>
            <td class="tt-cell">
              <?php foreach ($r['days'][$d] ?? [] as $e): ?>
                <?php if ($isBreak): ?>
                  <div class="tt-note"><?= htmlspecialchars($e['label'] ?: ucfirst($e['entry_type'])) ?>
                    <span style="font-weight:400;color:var(--text-muted);font-family:monospace;"><?= $hhmm($e['start_time']) ?>–<?= $hhmm($e['end_time']) ?></span>
                  </div>
                <?php else: ?>
                  <div class="tt-slot">
                    <div class="s"><?= htmlspecialchars($e['course_name'] ?? 'Free period') ?></div>
                    <div class="m"><?= htmlspecialchars($e['teacher_name'] ?? 'No teacher') ?><?= $e['room'] ? ' · ' . htmlspecialchars($e['room']) : '' ?></div>
                    <div class="m" style="font-family:monospace;"><?= $hhmm($e['start_time']) ?>–<?= $hhmm($e['end_time']) ?></div>
                  </div>
                <?php endif; ?>
                <div class="acts" style="display:flex;gap:4px;margin-top:5px;">
                  <button type="button" class="btn btn-sm btn-outline" style="padding:1px 7px;font-size:11px;"
                          onclick='ttEdit(<?= json_encode([
                            'id' => (int)$e['id'], 'day' => $e['day_of_week'], 'type' => $e['entry_type'] ?? 'lesson',
                            'period_no' => $e['period_no'], 'course_id' => $e['course_id'], 'teacher_id' => $e['teacher_id'],
                            'academic_year_id' => $e['academic_year_id'], 'term_id' => $e['term_id'],
                            'start' => $hhmm($e['start_time']), 'end' => $hhmm($e['end_time']),
                            'room' => $e['room'], 'label' => $e['label'],
                          ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                  <form method="POST" action="<?= $base ?>/<?= (int)$e['id'] ?>/delete"
                        data-confirm="Remove this slot from <?= ucfirst($e['day_of_week']) ?>?" data-confirm-title="Remove Slot" data-confirm-label="Remove">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn btn-sm btn-danger" style="padding:1px 7px;font-size:11px;">×</button>
                  </form>
                </div>
              <?php endforeach; ?>
              <?php if (empty($r['days'][$d])): ?><span class="tt-empty">—</span><?php endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="modal-overlay" id="ttModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="ttTitle">Add Slot</div>
      <button class="modal-close" onclick="document.getElementById('ttModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="ttForm" action="<?= $base ?>/store">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="class_id" value="<?= (int)$classId ?>">
      <input type="hidden" name="day_of_week" id="tt_day" value="">
      <div class="modal-body">
        <?php if (!$classId): ?>
          <div class="alert alert-warning">Choose a class first, then add slots to its timetable.</div>
        <?php endif; ?>
        <div class="form-row">
          <div class="form-group"><label class="form-label">This slot is *</label>
            <select name="entry_type" id="tt_type" class="form-control" onchange="ttType()">
              <option value="lesson">A lesson</option>
              <option value="break">A break</option>
              <option value="lunch">Lunch</option>
            </select></div>
          <div class="form-group" id="tt_periodWrap"><label class="form-label">Period of the day *</label>
            <select name="period_no" id="tt_period" class="form-control">
              <?php for ($i = 1; $i <= (int)$maxPeriods; $i++): ?><option value="<?= $i ?>">Period <?= $i ?></option><?php endfor; ?>
            </select>
            <div class="form-hint">1 is the first lesson of the day.</div></div>
          <div class="form-group" id="tt_labelWrap" style="display:none;"><label class="form-label">Name on the timetable</label>
            <input type="text" name="label" id="tt_label" class="form-control" placeholder="Break"></div>
        </div>
        <div class="form-group" id="tt_daysWrap">
          <label class="form-label">Day(s) *</label>
          <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <?php foreach ($days as $d): ?>
              <label style="display:flex;gap:6px;align-items:center;font-size:13px;">
                <input type="checkbox" name="days[]" value="<?= $d ?>" class="tt-dayBox" <?= in_array($d, ['saturday'], true) ? '' : 'checked' ?>>
                <?= $dayShort[$d] ?>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="form-hint">Tick every day this slot repeats on — a break can be set for the whole week at once.</div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Starts *</label><input type="time" name="start_time" id="tt_start" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Ends *</label><input type="time" name="end_time" id="tt_end" class="form-control" required></div>
        </div>
        <div id="tt_lessonFields">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Subject</label>
              <select name="course_id" id="tt_course" class="form-control"><option value="">—</option>
                <?php foreach ($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Teacher</label>
              <select name="teacher_id" id="tt_teacher" class="form-control"><option value="">—</option>
                <?php foreach ($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Room</label><input type="text" name="room" id="tt_room" class="form-control"></div>
            <div class="form-group"><label class="form-label">Academic Year</label>
              <select name="academic_year_id" id="tt_year" class="form-control"><option value="">—</option>
                <?php foreach ($academicYears as $y): ?><option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="form-group"><label class="form-label">Term</label>
            <select name="term_id" id="tt_term" class="form-control"><option value="">—</option>
              <?php foreach ($terms as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('ttModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" <?= $classId ? '' : 'disabled' ?>>Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function ttType() {
  var t = document.getElementById('tt_type').value;
  document.getElementById('tt_periodWrap').style.display = t === 'lesson' ? '' : 'none';
  document.getElementById('tt_labelWrap').style.display  = t === 'lesson' ? 'none' : '';
  document.getElementById('tt_lessonFields').style.display = t === 'lesson' ? '' : 'none';
  if (t !== 'lesson') { document.getElementById('tt_label').placeholder = t === 'lunch' ? 'Lunch' : 'Break'; }
}
function ttOpen() {
  document.getElementById('ttTitle').textContent = 'Add Slot';
  document.getElementById('ttForm').action = '<?= $base ?>/store';
  document.getElementById('tt_day').value = '';
  document.getElementById('ttForm').reset();
  document.getElementById('tt_daysWrap').style.display = '';
  document.querySelectorAll('.tt-dayBox').forEach(function (b) { b.disabled = false; });
  ttType();
  document.getElementById('ttModal').classList.add('open');
}
function ttEdit(e) {
  document.getElementById('ttTitle').textContent = 'Edit Slot';
  document.getElementById('ttForm').action = '<?= $base ?>/' + e.id + '/update';
  // One slot belongs to one day; the day tick-boxes only make sense when adding.
  document.getElementById('tt_day').value = e.day;
  document.getElementById('tt_daysWrap').style.display = 'none';
  document.getElementById('tt_type').value = e.type || 'lesson';
  document.getElementById('tt_period').value = e.period_no || 1;
  document.getElementById('tt_label').value = e.label || '';
  document.getElementById('tt_start').value = e.start;
  document.getElementById('tt_end').value = e.end;
  document.getElementById('tt_course').value = e.course_id || '';
  document.getElementById('tt_teacher').value = e.teacher_id || '';
  document.getElementById('tt_room').value = e.room || '';
  document.getElementById('tt_year').value = e.academic_year_id || '';
  document.getElementById('tt_term').value = e.term_id || '';
  ttType();
  document.getElementById('ttModal').classList.add('open');
}
ttType();
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
