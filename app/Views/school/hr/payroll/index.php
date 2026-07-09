<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div>
        <div class="page-header-title">Staff Payroll</div>
        <div class="page-header-sub"><?= date("F", mktime(0, 0, 0, $month, 10)) ?> <?= $year ?></div>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/hr/payroll/generate" style="display:flex; gap:10px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <button type="submit" class="btn btn-primary">Generate Payroll Draft</button>
    </form>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <label style="color:var(--text-muted);font-size:13px">Period:</label>
    <select name="month" class="form-control" style="max-width:160px;">
      <?php for($m=1;$m<=12;$m++): ?>
        <option value="<?= $m ?>" <?= $m==$month?'selected':'' ?>><?= date('F', mktime(0,0,0,$m,10)) ?></option>
      <?php endfor; ?>
    </select>
    <select name="year" class="form-control" style="max-width:120px;">
      <?php for($y=(int)date('Y')+1;$y>=(int)date('Y')-3;$y--): ?>
        <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>
    <button type="submit" class="btn btn-secondary">View</button>
  </div>
</form>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Staff on Payroll</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Total Net Payroll</div>
    <div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['netTotal'] ?? 0,0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Draft</div>
    <div class="stat-value"><?= (int)($stats['draft'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Paid</div>
    <div class="stat-value"><?= (int)($stats['paid'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Payroll Records (<?= count($records) ?>)</div></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Basic Salary</th>
                    <th>Allowances</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($records as $r): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($r['staff_name']) ?></td>
                    <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($r['basic_salary'], 2) ?></td>
                    <td class="text-success">+<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($r['allowances'], 2) ?></td>
                    <td class="text-danger">-<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($r['deductions'], 2) ?></td>
                    <td class="fw-700"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($r['net_salary'], 2) ?></td>
                    <td>
                        <span class="badge <?= $r['status'] === 'paid' ? 'badge-success' : 'badge-warning' ?>">
                            <?= strtoupper($r['status']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="<?= $cfg['url'] ?>/school/hr/payroll/<?= $r['id'] ?>/payslip" target="_blank" class="btn btn-sm btn-outline">Payslip</a>
                            <?php if($r['status'] !== 'paid'): ?>
                            <form method="POST" action="<?= $cfg['url'] ?>/school/hr/payroll/<?= $r['id'] ?>/pay" data-confirm="Mark <?= htmlspecialchars($r['staff_name']) ?>'s payroll as paid?" data-confirm-label="Mark Paid">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                <button type="submit" class="btn btn-sm btn-success">Mark Paid</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($records)): ?>
                <tr><td colspan="7">
                    <div class="empty-state">
                        <div class="empty-state-icon">💰</div>
                        <div class="empty-state-text">No payroll records for this period. Click "Generate Payroll Draft" to start.</div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
