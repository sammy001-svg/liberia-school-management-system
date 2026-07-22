<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/students">Students</a>
  <span>/</span><span>Register Returning Student</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Register Returning Student</div>
    <div class="page-header-sub">Search for a withdrawn or graduated student to re-enrol them, instead of creating a duplicate record</div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or admission number…" class="form-control" style="max-width:320px;" autofocus>
    <button type="submit" class="btn btn-primary">Search</button>
    <a href="<?= $cfg['url'] ?>/school/students" class="btn btn-outline">Cancel</a>
  </div>
</form>

<?php if($search): ?>
<div class="card">
  <div class="card-header"><div class="card-title">Matches (<?= count($results) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Name</th><th>Admission No</th><th>Previous Class</th><th>Status</th><th>Last Enrolled</th><th></th></tr></thead>
      <tbody>
        <?php foreach($results as $r): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($r['name']) ?></td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['admission_no']) ?></td>
          <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $r['status']==='graduated'?'info':'warning' ?>"><?= ucfirst($r['status']) ?></span></td>
          <td><?= $r['admission_date'] ? date('M d, Y', strtotime($r['admission_date'])) : '—' ?></td>
          <td><a href="<?= $cfg['url'] ?>/school/students/<?= $r['id'] ?>/reactivate" class="btn btn-sm btn-primary">Re-enrol</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($results)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">🔍</div>
            <div class="empty-state-text">No withdrawn or graduated student matches "<?= htmlspecialchars($search) ?>". <a href="<?= $cfg['url'] ?>/school/students">Register them as a new student instead</a>.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
