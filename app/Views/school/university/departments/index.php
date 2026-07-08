<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Departments</div>
    <a href="<?= $cfg['url'] ?>/school/departments/create" class="btn btn-primary">+ Add Department</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Head of Department</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($departments as $d): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($d['name']) ?></td>
                    <td><?= htmlspecialchars($d['head_name'] ?? 'Not Assigned') ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($d['description']) ?></td>
                    <td>
                        <a href="<?= $cfg['url'] ?>/school/departments/<?= $d['id'] ?>/edit" class="btn btn-sm btn-outline">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($departments)): ?>
                <tr>
                    <td colspan="4" class="text-center text-muted" style="padding:40px;">No departments found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
