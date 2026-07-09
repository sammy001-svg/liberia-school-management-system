<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Grades &amp; Exams</div>
    <div class="page-header-sub">Create exams and manage grading</div>
  </div>
  <div style="display:flex;gap:10px;">
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addExamModal').classList.add('open')">+ Add Exam</button>
    <a href="<?= $cfg['url'] ?>/school/grades/enter" class="btn btn-primary">Enter Grades</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Exams</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Upcoming</div>
    <div class="stat-value"><?= (int)($stats['upcoming'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Grading Started</div>
    <div class="stat-value"><?= (int)($stats['graded'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Exams (<?= count($exams) ?>)</div></div>
  <div class="table-wrapper"><table>
    <thead><tr><th>Exam</th><th>Class</th><th>Date</th><th>Total Marks</th><th>Pass Marks</th><th>Status</th></tr></thead>
    <tbody>
      <?php foreach($exams as $e): ?>
      <?php $isUpcoming = $e['exam_date'] && strtotime($e['exam_date']) >= strtotime(date('Y-m-d')); ?>
      <tr>
        <td class="fw-600"><?= htmlspecialchars($e['name']) ?></td>
        <td><?= htmlspecialchars($e['class_name']??'All Classes') ?></td>
        <td><?= $e['exam_date']?date('M d, Y',strtotime($e['exam_date'])):'—' ?></td>
        <td><?= $e['total_marks'] ?></td>
        <td><?= $e['pass_marks'] ?></td>
        <td>
          <?php if($e['graded_count'] > 0): ?>
            <span class="badge badge-success"><?= $e['graded_count'] ?> graded</span>
          <?php elseif($isUpcoming): ?>
            <span class="badge badge-info">Upcoming</span>
          <?php else: ?>
            <span class="badge badge-warning">Not graded</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($exams)): ?>
      <tr><td colspan="6">
        <div class="empty-state">
          <div class="empty-state-icon">📝</div>
          <div class="empty-state-text">No exams created yet. <a href="javascript:void(0)" onclick="document.getElementById('addExamModal').classList.add('open')">Add one</a></div>
        </div>
      </td></tr>
      <?php endif; ?>
    </tbody>
  </table></div>
</div>

<!-- Add Exam Modal -->
<div class="modal-overlay" id="addExamModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Exam</div>
      <button class="modal-close" onclick="document.getElementById('addExamModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/exams/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Exam Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Midterm Exam">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class</label>
            <select name="class_id" class="form-control">
              <option value="">All Classes</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Exam Date</label>
            <input type="date" name="exam_date" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($terms as $t): ?>
                <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-control">
              <option value="">— Not Assigned —</option>
              <?php foreach($academicYears as $y): ?>
                <option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Total Marks</label>
            <input type="number" name="total_marks" class="form-control" value="100">
          </div>
          <div class="form-group">
            <label class="form-label">Pass Marks</label>
            <input type="number" name="pass_marks" class="form-control" value="40">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addExamModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Exam</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
