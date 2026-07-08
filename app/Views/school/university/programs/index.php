<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Academic Programs</div>
    <a href="<?= $cfg['url'] ?>/school/programs/create" class="btn btn-primary">+ Create Program</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Program Name</th>
                    <th>Code</th>
                    <th>Department</th>
                    <th>Duration</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($programs as $p): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($p['name']) ?></td>
                    <td><span class="badge badge-info"><?= htmlspecialchars($p['code']) ?></span></td>
                    <td><?= htmlspecialchars($p['dept_name']) ?></td>
                    <td><?= $p['duration_years'] ?> Years</td>
                    <td style="text-transform:capitalize;"><?= htmlspecialchars($p['degree_type']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($programs)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No programs defined yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
