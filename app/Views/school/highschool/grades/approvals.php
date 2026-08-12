<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a>
  <span>/</span><span>Grade Approvals</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Grade Approvals</div>
    <div class="page-header-sub">
      The principal reviews each class's marks before they are released to parents.
      Approving does not publish the card — the office still chooses when to release it.
    </div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/grades/marking-periods?year_id=<?= htmlspecialchars($selectedYearId) ?>" class="btn btn-secondary">Marking Periods</a>
</div>

<?php if(!$canApprove): ?>
<div class="alert alert-info">
  You can see the status of every class here, but only a user with the
  <strong>Approve grades</strong> permission (the principal) can approve or return them.
</div>
<?php endif; ?>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:18px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <label class="form-label" style="margin:0;">Academic Year</label>
    <select name="year_id" class="form-control" style="max-width:240px;" onchange="this.form.submit()">
      <?php foreach($years as $y): ?>
        <option value="<?= $y['id'] ?>" <?= (string)$y['id']===(string)$selectedYearId?'selected':'' ?>><?= htmlspecialchars($y['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<?php
  $waiting  = array_filter($classes, fn($c) => $c['approval_status'] === 'submitted');
  $approved = array_filter($classes, fn($c) => $c['approval_status'] === 'approved');
  $returned = array_filter($classes, fn($c) => $c['approval_status'] === 'returned');
?>
<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--warning);">
    <div class="stat-value"><?= count($waiting) ?></div><div class="stat-label">Awaiting Approval</div></div>
  <div class="stat-card" style="--card-color:var(--success);">
    <div class="stat-value"><?= count($approved) ?></div><div class="stat-label">Approved</div></div>
  <div class="stat-card" style="--card-color:var(--danger);">
    <div class="stat-value"><?= count($returned) ?></div><div class="stat-label">Returned for Correction</div></div>
  <div class="stat-card" style="--card-color:var(--blue);">
    <div class="stat-value"><?= count($classes) ?></div><div class="stat-label">Classes Set Up</div></div>
</div>

<div class="card mt-16">
  <div class="table-wrapper">
    <table>
      <thead><tr>
        <th>Class</th><th>Students</th><th>Marks Entered</th><th>Status</th><th>Submitted</th><th>Decision</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($classes as $c): ?>
        <?php
          $st = $c['approval_status'];
          [$badge, $label] = [
              'entered'   => ['badge-muted',   'Not submitted'],
              'submitted' => ['badge-warning', 'Awaiting approval'],
              'approved'  => ['badge-success', 'Approved'],
              'returned'  => ['badge-danger',  'Returned'],
          ][$st] ?? ['badge-muted', ucfirst($st)];
        ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($c['class_name']) ?></td>
          <td><?= (int)$c['student_count'] ?></td>
          <td>
            <?php if((int)$c['grade_count'] > 0): ?>
              <?= (int)$c['grade_count'] ?>
            <?php else: ?>
              <span style="color:var(--text-muted);font-size:12px;">None yet</span>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
          <td style="font-size:12px;">
            <?php if(!empty($c['submitted_at'])): ?>
              <?= date('d M Y, H:i', strtotime($c['submitted_at'])) ?>
              <?php if(!empty($c['submitted_by_name'])): ?>
                <div style="color:var(--text-muted);">by <?= htmlspecialchars($c['submitted_by_name']) ?></div>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td style="font-size:12px;">
            <?php if(in_array($st, ['approved','returned'], true) && !empty($c['approved_at'])): ?>
              <?= date('d M Y, H:i', strtotime($c['approved_at'])) ?>
              <?php if(!empty($c['approved_by_name'])): ?>
                <div style="color:var(--text-muted);">by <?= htmlspecialchars($c['approved_by_name']) ?></div>
              <?php endif; ?>
              <?php if(!empty($c['review_note'])): ?>
                <div style="color:var(--danger);margin-top:3px;max-width:220px;"><?= htmlspecialchars($c['review_note']) ?></div>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= $cfg['url'] ?>/school/grades/report-cards/class/<?= $c['class_id'] ?>?year_id=<?= htmlspecialchars($selectedYearId) ?>"
                 target="_blank" class="btn btn-sm btn-secondary">Review Cards</a>
              <?php if($canApprove && $st === 'submitted'): ?>
                <form method="POST" action="<?= $cfg['url'] ?>/school/grades/approvals/review"
                      data-confirm="Approve <?= htmlspecialchars($c['class_name']) ?>'s grades? They can then be released to parents."
                      data-confirm-title="Approve Grades" data-confirm-label="Approve">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($selectedYearId) ?>">
                  <input type="hidden" name="class_id" value="<?= $c['class_id'] ?>">
                  <input type="hidden" name="decision" value="approve">
                  <button type="submit" class="btn btn-sm btn-primary">Approve</button>
                </form>
                <button type="button" class="btn btn-sm btn-danger"
                        onclick='openReturnModal(<?= (int)$c["class_id"] ?>,<?= json_encode($c["class_name"], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Return</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($classes)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🗂️</div>
            <div class="empty-state-text">
              No classes have report card marking periods set up for this year yet —
              do that on <a href="<?= $cfg['url'] ?>/school/grades/marking-periods">Marking Periods</a> first.
            </div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($canApprove): ?>
<!-- Return for correction — a reason is required so the teacher knows what to fix. -->
<div class="modal-overlay" id="returnModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Return Grades — <span id="returnClassName"></span></div>
      <button type="button" class="modal-close" onclick="document.getElementById('returnModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/grades/approvals/review">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="academic_year_id" value="<?= htmlspecialchars($selectedYearId) ?>">
      <input type="hidden" name="class_id" id="returnClassId">
      <input type="hidden" name="decision" value="return">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Reason for returning *</label>
          <textarea name="review_note" class="form-control" rows="4" required
                    placeholder="e.g. Mathematics 3rd Period marks are missing for six students."></textarea>
          <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
            Shown to the teacher on the Marking Periods screen. Returning also withdraws the card
            if it was already released.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('returnModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-danger">Return for Correction</button>
      </div>
    </form>
  </div>
</div>
<script>
function openReturnModal(classId, className) {
  document.getElementById('returnClassId').value = classId;
  document.getElementById('returnClassName').textContent = className;
  document.getElementById('returnModal').classList.add('open');
}
</script>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
