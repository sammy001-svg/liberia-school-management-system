<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Courses Catalog</div>
    <a href="<?= $cfg['url'] ?>/school/courses/create" class="btn btn-primary">+ Add Course</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Course Name</th>
                    <th>Code</th>
                    <th>Associated Program</th>
                    <th>Credits</th>
                    <th>Sem No</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($courses as $c): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($c['name']) ?></td>
                    <td><span class="badge badge-primary"><?= htmlspecialchars($c['code']) ?></span></td>
                    <td><?= htmlspecialchars($c['program_name'] ?? 'General / Common') ?></td>
                    <td><?= $c['credit_hours'] ?> Units</td>
                    <td>Semester <?= $c['semester_no'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($courses)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No courses found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
