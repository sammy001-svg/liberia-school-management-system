<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Students</div>
    <div class="page-header-sub">Manage student enrolment and profiles</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/students/create" class="btn btn-primary">+ Admit Student</a>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or admission no…" class="form-control" style="max-width:280px;">
    <select name="class_id" class="form-control" style="max-width:200px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/students" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">All Students (<?= count($students) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Admission No</th><th>Class</th><th>Phone</th><th>Gender</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($students as $s): ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="avatar"><?= strtoupper(substr($s['name'],0,1)) ?></div>
              <div>
                <div class="fw-600"><?= htmlspecialchars($s['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($s['email']) ?></div>
              </div>
            </div>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($s['admission_no']) ?></td>
          <td><?= htmlspecialchars($s['class_name']??'—') ?></td>
          <td><?= htmlspecialchars($s['phone']??'—') ?></td>
          <td><?= ucfirst($s['gender']??'—') ?></td>
          <td><span class="badge badge-<?= $s['status']==='active'?'success':($s['status']==='graduated'?'info':'danger') ?>"><?= ucfirst($s['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>" class="btn btn-sm btn-outline">View</a>
              <a href="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>/edit" class="btn btn-sm btn-secondary">Edit</a>
              <form method="POST" action="<?= $cfg['url'] ?>/school/students/<?= $s['id'] ?>/delete" onsubmit="return confirm('Remove student?')">
                <button class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($students)): ?>
          <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">No students found. <a href="<?= $cfg['url'] ?>/school/students/create">Admit first student</a></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
