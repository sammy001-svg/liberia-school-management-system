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
<?php $days = ['monday','tuesday','wednesday','thursday','friday']; ?>
<?php if (!empty($timetable)): ?>
<?php foreach($days as $day): ?>
<?php if (!empty($timetable[$day])): ?>
<div class="card mb-16">
  <div class="card-header"><div class="card-title"><?= ucfirst($day) ?></div></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>Time</th><th>Subject</th><th>Teacher</th><th>Room</th></tr></thead>
    <tbody>
      <?php foreach($timetable[$day] as $slot): ?>
      <tr>
        <td style="font-family:monospace;font-size:12px"><?= substr($slot['start_time'],0,5) ?> – <?= substr($slot['end_time'],0,5) ?></td>
        <td class="fw-600"><?= htmlspecialchars($slot['course_name']??'—') ?></td>
        <td><?= htmlspecialchars($slot['teacher_name']??'—') ?></td>
        <td><?= htmlspecialchars($slot['room']??'—') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php endforeach; ?>
<?php else: ?><div class="card"><div class="card-body text-center text-muted">Select a class to view timetable.</div></div><?php endif; ?>

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
