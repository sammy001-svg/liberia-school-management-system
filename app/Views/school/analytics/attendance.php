<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Attendance Heatmap & Chronic Absenteeism</div>
    <div class="text-muted">Early identification of students falling below the 75% attendance threshold.</div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Total Days</th>
                    <th>Days Present</th>
                    <th>Attendance %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($chronicAbsentees as $s): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['class_name'] ?? 'N/A') ?></td>
                    <td><?= $s['total_days'] ?></td>
                    <td><?= $s['present_days'] ?></td>
                    <td class="fw-700 <?= $s['percentage'] < 50 ? 'text-danger' : 'text-warning' ?>">
                        <?= round($s['percentage'], 1) ?>%
                    </td>
                    <td>
                        <span class="badge badge-danger">CRITICAL RISK</span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($chronicAbsentees)): ?>
                <tr><td colspan="6" class="text-center text-success fw-600" style="padding:60px;">Excellent! No students currently below the critical threshold.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
