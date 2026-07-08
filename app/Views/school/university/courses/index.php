<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Courses / Subjects</div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addCourseModal').classList.add('open')">+ Add Course</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Code</th>
                    <th>Credits</th>
                    <th>Sem No</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($courses as $c): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($c['code']) ?></span></td>
                    <td><?= $c['credit_hours'] ?> Units</td>
                    <td>Semester <?= $c['semester_no'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($courses)): ?>
                <tr><td colspan="4" class="text-center text-muted" style="padding:40px;">No courses found. <a href="javascript:void(0)" onclick="document.getElementById('addCourseModal').classList.add('open')">Add one</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal-overlay" id="addCourseModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Course / Subject</div>
      <button class="modal-close" onclick="document.getElementById('addCourseModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/courses/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Course / Subject Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Course Code</label>
            <input type="text" name="code" class="form-control" placeholder="e.g. MATH101">
          </div>
          <div class="form-group">
            <label class="form-label">Credit Hours (Units)</label>
            <input type="number" name="credit_hours" class="form-control" value="3">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Target Semester</label>
          <input type="number" name="semester_no" class="form-control" value="1">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addCourseModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Course</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
