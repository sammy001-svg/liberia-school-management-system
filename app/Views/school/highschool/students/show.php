<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/students">Students</a><span>/</span><span><?= htmlspecialchars($student['name']) ?></span></div>
<div class="page-header">
  <div>
    <div style="display:flex;align-items:center;gap:14px;">
      <div class="avatar avatar-lg"><?= strtoupper(substr($student['name'],0,1)) ?></div>
      <div>
        <div class="page-header-title"><?= htmlspecialchars($student['name']) ?></div>
        <div class="page-header-sub"><?= htmlspecialchars($student['admission_no']) ?> · <?= htmlspecialchars($student['class_name']??'No Class') ?></div>
      </div>
    </div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/students/<?= $student['id'] ?>/edit" class="btn btn-secondary">Edit Profile</a>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:24px;">
  <div class="card"><div class="card-body"><div class="stat-label">Email</div><div class="fw-600"><?= htmlspecialchars($student['email']??'—') ?></div></div></div>
  <div class="card"><div class="card-body"><div class="stat-label">Phone</div><div class="fw-600"><?= htmlspecialchars($student['phone']??'—') ?></div></div></div>
  <div class="card"><div class="card-body"><div class="stat-label">Status</div><span class="badge badge-<?= $student['status']==='active'?'success':'warning' ?>"><?= ucfirst($student['status']) ?></span></div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="card">
    <div class="card-header"><div class="card-title">Recent Grades</div><a href="<?= $cfg['url'] ?>/school/grades/report/<?= $student['id'] ?>" class="btn btn-sm btn-outline">Full Report</a></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Subject</th><th>Score</th><th>Grade</th></tr></thead>
      <tbody>
        <?php foreach($grades as $g): ?>
        <tr><td><?= htmlspecialchars($g['course_name']??'—') ?></td><td><?= $g['marks_obtained'] ?>%</td><td><span class="badge badge-<?= $g['grade_letter']==='F'?'danger':'success' ?>"><?= $g['grade_letter'] ?></span></td></tr>
        <?php endforeach; ?>
        <?php if(empty($grades)): ?><tr><td colspan="3" class="text-center text-muted" style="padding:20px">No grades yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="card">
    <div class="card-header"><div class="card-title">Invoices</div><a href="<?= $cfg['url'] ?>/school/finance/invoices/create" class="btn btn-sm btn-primary">+ Invoice</a></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Invoice</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach($invoices as $inv): ?>
        <tr><td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($inv['invoice_no']) ?></td><td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($inv['amount_due'],2) ?></td><td><span class="badge badge-<?= $inv['status']==='paid'?'success':($inv['status']==='overdue'?'danger':'warning') ?>"><?= ucfirst($inv['status']) ?></span></td></tr>
        <?php endforeach; ?>
        <?php if(empty($invoices)): ?><tr><td colspan="3" class="text-center text-muted" style="padding:20px">No invoices.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
