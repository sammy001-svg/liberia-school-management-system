<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$statusBadge = [
    'complete' => ['badge-success', 'Paid in Full'], 'pending' => ['badge-warning', 'Part Paid'],
    'none' => ['badge-danger', 'No Payment'], 'nobill' => ['badge-muted', 'No Billing'],
];
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Student Enrollment<?= $year ? ' — ' . htmlspecialchars($year['name']) : '' ?></div>
    <div class="page-header-sub">Enroll students into a class for the year. Enrolling creates each student’s bills from Billing Setup; take payments from here.</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php if ($pendingOverrides): ?><a href="<?= $base ?>/arrears-overrides" class="btn btn-warning">⚠ <?= $pendingOverrides ?> Override Request(s)</a><?php endif; ?>
    <a href="<?= $base ?>/billing?year=<?= $year['id'] ?? '' ?>" class="btn btn-outline">Billing Setup</a>
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkModal').classList.add('open')">Bulk Enroll</button>
  </div>
</div>

<?php if (!$year): ?>
  <div class="alert alert-warning">Create an academic year and mark it current before enrolling students.</div>
<?php else: ?>

<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--blue);"><div class="stat-value"><?= $summary['enrolled'] ?></div><div class="stat-label">Enrolled</div></div>
  <div class="stat-card" style="--card-color:var(--success);"><div class="stat-value"><?= $summary['complete'] ?></div><div class="stat-label">Paid in Full</div></div>
  <div class="stat-card" style="--card-color:var(--warning);"><div class="stat-value"><?= $summary['pending'] ?></div><div class="stat-label">Part Paid</div></div>
  <div class="stat-card" style="--card-color:var(--danger);"><div class="stat-value"><?= $summary['none'] ?></div><div class="stat-label">No Payment</div></div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="card-header"><div class="card-title">Enroll a Student</div></div>
  <div class="card-body">
    <form method="POST" action="<?= $base ?>/enrollment/store" style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr auto;gap:12px;align-items:end;" id="enrollForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?>">
      <div class="form-group" style="margin:0;"><label class="form-label">Student *</label>
        <input type="text" class="form-control" list="studentList" id="studentPick" placeholder="Type a name or admission no." autocomplete="off" required>
        <input type="hidden" name="student_id" id="studentId">
        <datalist id="studentList">
          <?php foreach ($students as $s): ?><option value="<?= htmlspecialchars($s['admission_no'] . ' — ' . $s['name']) ?>" data-id="<?= $s['id'] ?>" data-class="<?= (int)$s['class_id'] ?>" data-enrolled="<?= $s['enrolled_id'] ? 1 : 0 ?>"></option><?php endforeach; ?>
        </datalist>
        <div class="form-hint" id="studentHint"></div>
      </div>
      <div class="form-group" style="margin:0;"><label class="form-label">Class *</label>
        <select name="class_id" id="enrollClass" class="form-control" required><option value="">Select</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group" style="margin:0;"><label class="form-label">Student Type</label>
        <select name="student_type_id" class="form-control"><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $t['name'] === 'Regular' ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group" style="margin:0;"><label class="form-label">New / Old</label>
        <select name="category" class="form-control"><option value="auto">Automatic</option><option value="new">New Student</option><option value="old">Old Student</option></select></div>
      <?php if ($canApprove && $finSettings['block_arrears_enrollment'] === '1'): ?>
        <label style="display:flex;gap:6px;font-size:12px;align-items:center;margin-bottom:10px;" title="If the student owes from previous years, enroll anyway and log an approved override"><input type="checkbox" name="override_now" value="1"> Override arrears</label>
      <?php else: ?><span></span><?php endif; ?>
      <button type="submit" class="btn btn-primary">Enroll</button>
    </form>
  </div>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:16px;">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <select name="year" class="form-control" style="max-width:150px;"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select>
    <input type="text" name="q" class="form-control" style="max-width:220px;" placeholder="Search name or admission no." value="<?= htmlspecialchars($filters['q']) ?>">
    <select name="class" class="form-control" style="max-width:170px;"><option value="">All Classes</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $filters['class'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
    <select name="type" class="form-control" style="max-width:170px;"><option value="">All Types</option><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $filters['type'] == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-control" style="max-width:170px;">
      <option value="">All Statuses</option>
      <?php foreach (['complete' => 'Paid in Full', 'pending' => 'Part Paid', 'none' => 'No Payment'] as $k => $l): ?><option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $base ?>/enrollment?year=<?= $year['id'] ?>" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">Enrolled Students (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Adm. No.</th><th>Student</th><th>Class</th><th>Type</th><th>New/Old</th><th>Status</th><th style="text-align:right;">Paid</th><th style="text-align:right;">Balance</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $cur = strtok((string)$r['currencies'], ',') ?: $def; $mixed = str_contains((string)$r['currencies'], ','); [$cls, $lbl] = $statusBadge[$r['pay_status']]; ?>
          <tr>
            <td style="font-size:12.5px;"><?= htmlspecialchars($r['admission_no']) ?></td>
            <td class="fw-600"><?= htmlspecialchars($r['name']) ?></td>
            <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['type_name'] ?? '—') ?></td>
            <td><?= $r['category'] === 'old' ? 'Old' : 'New' ?></td>
            <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
            <td style="text-align:right;"><?= $mixed ? '<span title="Bills in more than one currency — see the student’s payment page">Mixed</span>' : Finance::money($r['paid'], $cur) ?></td>
            <td style="text-align:right;<?= $r['balance'] > 0 ? 'color:var(--danger);font-weight:600;' : '' ?>"><?= $mixed ? '—' : Finance::money($r['balance'], $cur) ?></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="<?= $base ?>/fees-payment?student=<?= $r['student_id'] ?>&year=<?= $year['id'] ?>" class="btn btn-sm btn-success">Fees Payment</a>
                <button type="button" class="btn btn-sm btn-secondary" onclick='editEnrollment(<?= json_encode(['id' => $r['id'], 'name' => $r['name'], 'class_id' => $r['class_id'], 'type' => $r['student_type_id'], 'category' => $r['category']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                <form method="POST" action="<?= $base ?>/enrollment/<?= $r['id'] ?>/withdraw" data-confirm="Unenroll <?= htmlspecialchars($r['name']) ?> from <?= htmlspecialchars($year['name']) ?>? Their unpaid bills for the year are removed." data-confirm-title="Unenroll Student" data-confirm-label="Unenroll">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><button type="submit" class="btn btn-sm btn-outline">Unenroll</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9"><div class="empty-state"><div class="empty-state-icon">🎓</div><div class="empty-state-text">No students enrolled for <?= htmlspecialchars($year['name']) ?> yet. Enroll them one by one above, or use Bulk Enroll for a whole class.</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Edit enrollment -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="editTitle">Edit Enrollment</div><button type="button" class="modal-close" onclick="document.getElementById('editModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="editForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Class</label><select name="class_id" id="e_class" class="form-control"><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Student Type</label><select name="student_type_id" id="e_type" class="form-control"><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">New / Old</label><select name="category" id="e_cat" class="form-control"><option value="new">New Student</option><option value="old">Old Student</option></select></div>
        <div class="form-hint">Bills are recalculated for the new class, type or category. Anything already paid stays on record.</div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('editModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
    </form>
  </div>
</div>

<!-- Bulk enroll -->
<div class="modal-overlay" id="bulkModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Bulk Enroll a Class</div><button type="button" class="modal-close" onclick="document.getElementById('bulkModal').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/enrollment/bulk">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?>">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Enrolls every active student currently in the first class into the second one for <?= htmlspecialchars($year['name']) ?> — for example, all of this year’s 3rd Grade into 4th Grade. Students already enrolled are skipped; anyone with unpaid previous-year fees is held for an override.</p>
        <div class="form-group"><label class="form-label">Students currently in</label><select name="from_class_id" class="form-control"><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Enroll into</label><select name="class_id" class="form-control"><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Student type</label><select name="student_type_id" class="form-control"><?php foreach ($types as $t): ?><option value="<?= $t['id'] ?>" <?= $t['name'] === 'Regular' ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Enroll Students</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  const pick = document.getElementById('studentPick');
  if (!pick) return;
  const opts = [...document.querySelectorAll('#studentList option')];
  pick.addEventListener('input', () => {
    const o = opts.find(o => o.value === pick.value);
    document.getElementById('studentId').value = o ? o.dataset.id : '';
    document.getElementById('studentHint').textContent = o && o.dataset.enrolled === '1' ? 'Already enrolled this year — saving will update the enrollment.' : '';
    if (o && o.dataset.class !== '0') document.getElementById('enrollClass').value = o.dataset.class;
  });
  document.getElementById('enrollForm').addEventListener('submit', e => {
    if (!document.getElementById('studentId').value) { e.preventDefault(); alert('Choose a student from the list.'); }
  });
})();
function editEnrollment(e) {
  document.getElementById('editTitle').textContent = 'Edit Enrollment — ' + e.name;
  document.getElementById('editForm').action = '<?= $base ?>/enrollment/' + e.id + '/update';
  document.getElementById('e_class').value = e.class_id;
  document.getElementById('e_type').value = e.type || '';
  document.getElementById('e_cat').value = e.category;
  document.getElementById('editModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
