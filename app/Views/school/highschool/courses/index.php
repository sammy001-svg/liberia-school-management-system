<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div>
        <div class="page-header-title">Subjects</div>
        <div class="page-header-sub">Manage the subject list, class assignment and teacher assignments</div>
    </div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addCourseModal').classList.add('open')">+ Add Subject</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Subjects</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Assigned to a Class</div>
    <div class="stat-value"><?= (int)($stats['assignedToClass'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Without a Teacher</div>
    <div class="stat-value"><?= (int)($stats['unassigned'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">All Subjects (<?= count($courses) ?>)</div>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Subject Name</th>
                    <th>Code</th>
                    <th>Class</th>
                    <th>Teachers</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($courses as $c): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
                    <td><?php if($c['code']): ?><span class="badge badge-primary"><?= htmlspecialchars($c['code']) ?></span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                    <td>
                        <?php if($c['class_names']): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:220px;">
                                <?php foreach(explode(', ', $c['class_names']) as $clName): ?>
                                    <span class="badge badge-info"><?= htmlspecialchars($clName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="badge badge-warning">Not assigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if($c['teacher_count'] > 0): ?>
                            <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:220px;">
                                <?php foreach(explode(', ', $c['teacher_names']) as $tName): ?>
                                    <span class="badge badge-success"><?= htmlspecialchars($tName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="badge badge-warning">Unassigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick='openEditCourseModal(<?= json_encode([
                                "id" => $c['id'], "name" => $c['name'], "code" => $c['code'],
                                "class_ids" => $c['class_ids'] ? array_map("intval", explode(",", $c['class_ids'])) : [],
                                "description" => $c['description'],
                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                            <form method="POST" action="<?= $cfg['url'] ?>/school/courses/<?= $c['id'] ?>/delete" data-confirm="Remove '<?= htmlspecialchars(addslashes($c['name'])) ?>'? Grades, timetable entries, and other records tied to it will keep their data but lose this subject label." data-confirm-title="Remove Subject" data-confirm-label="Remove">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($courses)): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="empty-state-icon">📚</div>
                        <div class="empty-state-text">No subjects found. <a href="javascript:void(0)" onclick="document.getElementById('addCourseModal').classList.add('open')">Add the first subject</a></div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal-overlay" id="addCourseModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Add Subject</div>
      <button class="modal-close" onclick="document.getElementById('addCourseModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/courses/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Subject Name *</label>
          <input type="text" name="name" class="form-control" required placeholder="e.g. Mathematics">
        </div>
        <div class="form-group">
          <label class="form-label">Subject Code</label>
          <input type="text" name="code" class="form-control" placeholder="e.g. MATH101">
        </div>
        <div class="form-group">
          <label class="form-label">Classes</label>
          <div class="form-hint" style="margin-bottom:6px;">Select every class this subject is taught to.</div>
          <div style="max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
            <?php if(empty($classes)): ?>
              <div class="form-hint">No classes exist yet.</div>
            <?php endif; ?>
            <?php foreach($classes as $cl): ?>
              <label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;">
                <input type="checkbox" name="class_ids[]" value="<?= $cl['id'] ?>">
                <?= htmlspecialchars($cl['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addCourseModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Subject</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal-overlay" id="editCourseModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Edit Subject</div>
      <button class="modal-close" onclick="document.getElementById('editCourseModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="editCourseForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Subject Name *</label>
          <input type="text" name="name" id="editCourseName" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Subject Code</label>
          <input type="text" name="code" id="editCourseCode" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Classes</label>
          <div class="form-hint" style="margin-bottom:6px;">Select every class this subject is taught to.</div>
          <div id="editCourseClasses" style="max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
            <?php if(empty($classes)): ?>
              <div class="form-hint">No classes exist yet.</div>
            <?php endif; ?>
            <?php foreach($classes as $cl): ?>
              <label style="display:flex;align-items:center;gap:8px;padding:4px 0;font-size:13px;">
                <input type="checkbox" name="class_ids[]" value="<?= $cl['id'] ?>" class="edit-course-class-cb">
                <?= htmlspecialchars($cl['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="editCourseDescription" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editCourseModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditCourseModal(c) {
  document.getElementById('editCourseForm').action = '<?= $cfg['url'] ?>/school/courses/' + c.id + '/update';
  document.getElementById('editCourseName').value = c.name || '';
  document.getElementById('editCourseCode').value = c.code || '';
  document.getElementById('editCourseDescription').value = c.description || '';
  var classIds = (c.class_ids || []).map(String);
  document.querySelectorAll('.edit-course-class-cb').forEach(function(cb) {
    cb.checked = classIds.indexOf(cb.value) !== -1;
  });
  document.getElementById('editCourseModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
