<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Staff Payroll (<?= date("F", mktime(0, 0, 0, $month, 10)) ?> <?= $year ?>)</div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/hr/payroll/generate" style="display:flex; gap:10px;">
        <input type="hidden" name="month" value="<?= $month ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <button type="submit" class="btn btn-primary">Generate Payroll Draft</button>
    </form>
</div>

<div class="card">
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
                        <a href="#" class="btn btn-sm btn-outline">Payslip</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($records)): ?>
                <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">No payroll records for this period. Click generate to start.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
