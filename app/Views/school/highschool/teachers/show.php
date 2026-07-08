<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/teachers">Teachers</a>
  <span>/</span><span><?= htmlspecialchars($teacher['name']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($teacher['name']) ?></div>
    <div class="page-header-sub">Employee No: <?= htmlspecialchars($teacher['employee_no']) ?></div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/edit" class="btn btn-secondary">Edit</a>
</div>

<div class="card mb-16">
  <div class="card-header"><div class="card-title">Personal Information</div></div>
  <div class="card-body">
    <div class="form-row">
      <div><div class="form-label">Email</div><div><?= htmlspecialchars($teacher['email']) ?></div></div>
      <div><div class="form-label">Phone</div><div><?= htmlspecialchars($teacher['phone']??'—') ?></div></div>
    </div>
    <div class="form-row mt-16">
      <div><div class="form-label">Gender</div><div><?= ucfirst($teacher['gender']??'—') ?></div></div>
      <div><div class="form-label">Qualification</div><div><?= htmlspecialchars($teacher['qualification']??'—') ?></div></div>
    </div>
    <div class="form-row mt-16">
      <div><div class="form-label">Specialization</div><div><?= htmlspecialchars($teacher['specialization']??'—') ?></div></div>
      <div><div class="form-label">Employment Type</div><div style="text-transform:capitalize;"><?= str_replace('_',' ',$teacher['employment_type']??'—') ?></div></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title">Courses Taught (<?= count($assignedCourses) ?>)</div>
    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('assignCourseModal').classList.add('open')">+ Assign Course</button>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Course</th><th>Code</th><th>Credit Hours</th><th></th></tr></thead>
      <tbody>
        <?php foreach($assignedCourses as $c): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
          <td><?= htmlspecialchars($c['code']??'—') ?></td>
          <td><?= $c['credit_hours'] ?></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/courses/<?= $c['id'] ?>/remove" data-confirm="Unassign <?= htmlspecialchars($c['name']) ?> from <?= htmlspecialchars($teacher['name']) ?>?" data-confirm-label="Unassign">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-outline">Remove</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($assignedCourses)): ?><tr><td colspan="4" class="text-center text-muted" style="padding:32px">No courses assigned yet. <a href="javascript:void(0)" onclick="document.getElementById('assignCourseModal').classList.add('open')">Assign one</a></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Assign Course Modal -->
<div class="modal-overlay" id="assignCourseModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Assign Course to <?= htmlspecialchars($teacher['name']) ?></div>
      <button class="modal-close" onclick="document.getElementById('assignCourseModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/teachers/<?= $teacher['id'] ?>/courses/assign">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Course / Subject *</label>
          <select name="course_id" class="form-control" required>
            <option value="">— Select Course —</option>
            <?php foreach($availableCourses as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if(empty($availableCourses)): ?><div class="form-hint">All courses are already assigned to this teacher.</div><?php endif; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('assignCourseModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
