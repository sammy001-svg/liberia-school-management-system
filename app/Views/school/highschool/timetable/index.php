<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Timetable</div>
    <div class="page-header-sub">Manage class schedules and lesson slots</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addTimetableModal').classList.add('open')">+ Add Entry</button>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;align-items:center;">
    <select name="class_id" class="form-control" style="max-width:220px;">
      <option value="">— Select Class —</option>
      <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Load</button>
  </div>
</form>
<?php $days = ['monday','tuesday','wednesday','thursday','friday','saturday']; ?>
<?php if (!empty($timetable)): ?>
<?php $slotCount = array_sum(array_map('count', $timetable)); ?>
<div class="stat-grid" style="grid-template-columns:repeat(3,1fr);">
  <div class="stat-card">
    <div class="stat-label">Scheduled Slots</div>
    <div class="stat-value"><?= $slotCount ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Days With Lessons</div>
    <div class="stat-value"><?= count($timetable) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Slots Without a Teacher</div>
    <div class="stat-value"><?= array_sum(array_map(fn($slots) => count(array_filter($slots, fn($s) => empty($s['teacher_name']))), $timetable)) ?></div>
  </div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px;align-items:start;">
  <?php foreach($days as $day): ?>
  <?php if (!empty($timetable[$day])): ?>
  <div class="card">
    <div class="card-header"><div class="card-title"><?= ucfirst($day) ?></div></div>
    <div class="card-body" style="padding:12px;">
      <?php foreach($timetable[$day] as $slot): ?>
      <div style="padding:10px 8px;border-bottom:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
          <div>
            <div class="fw-600" style="font-size:13px;"><?= htmlspecialchars($slot['course_name'] ?? 'Free Period') ?></div>
            <div class="text-muted" style="font-size:11px;margin-top:2px;"><?= htmlspecialchars($slot['teacher_name'] ?? 'No teacher assigned') ?><?= $slot['room'] ? ' · '.htmlspecialchars($slot['room']) : '' ?></div>
            <div class="text-muted" style="font-size:11px;font-family:monospace;margin-top:4px;"><?= substr($slot['start_time'],0,5) ?>–<?= substr($slot['end_time'],0,5) ?></div>
          </div>
          <form method="POST" action="<?= $cfg['url'] ?>/school/timetable/<?= $slot['id'] ?>/delete" data-confirm="Remove this <?= htmlspecialchars($slot['course_name'] ?? 'slot') ?> entry?" data-confirm-label="Remove">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" class="btn btn-sm btn-outline" style="padding:2px 8px;font-size:11px;" title="Remove">&times;</button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">🗓️</div>
  <div class="empty-state-text"><?= $classId ? 'No timetable entries for this class yet. Use "+ Add Entry" to schedule one.' : 'Select a class above to view its timetable.' ?></div>
</div></div>
<?php endif; ?>

<!-- Add Timetable Entry Modal -->
<div class="modal-overlay" id="addTimetableModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Timetable Entry</div>
      <button class="modal-close" onclick="document.getElementById('addTimetableModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/timetable/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Schedule Details</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class *</label>
            <select name="class_id" class="form-control" required>
              <option value="">— Select —</option>
              <?php foreach($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Day *</label>
            <select name="day_of_week" class="form-control" required>
              <?php foreach(['monday','tuesday','wednesday','thursday','friday','saturday'] as $d): ?><option value="<?= $d ?>"><?= ucfirst($d) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Start Time *</label>
            <input type="time" name="start_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Time *</label>
            <input type="time" name="end_time" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($academicYears as $y): ?><option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($terms as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="modal-section-title">Subject &amp; Teacher</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Subject / Course</label>
            <select name="course_id" class="form-control">
              <option value="">— Select —</option>
              <?php foreach($courses as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Teacher</label>
            <select name="teacher_id" class="form-control">
              <option value="">— Select —</option>
              <?php foreach($teachers as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Room / Venue</label>
          <input type="text" name="room" class="form-control" placeholder="e.g. Room 101">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addTimetableModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Entry</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
