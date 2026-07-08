<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Student Enrollments</div>
    <a href="<?= $cfg['url'] ?>/school/enrollments/create" class="btn btn-primary">+ Enroll Student</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Enrolled Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($enrollments as $e): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($e['student_name']) ?></td>
                    <td><?= htmlspecialchars($e['course_name']) ?></td>
                    <td>Sem <?= $e['semester'] ?></td>
                    <td>
                        <?php 
                        $statusClass = [
                            'enrolled'  => 'badge-info',
                            'completed' => 'badge-success',
                            'failed'    => 'badge-danger',
                            'dropped'   => 'badge-muted'
                        ][$e['status']] ?? 'badge-muted';
                        ?>
                        <span class="badge <?= $statusClass ?>"><?= ucfirst($e['status']) ?></span>
                    </td>
                    <td class="text-muted"><?= date('M d, Y', strtotime($e['enrolled_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($enrollments)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No enrollments recorded.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
