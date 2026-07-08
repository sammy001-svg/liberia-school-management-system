<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Learning Materials</div></div>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Teacher</th>
                    <th>Type</th>
                    <th>Added On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($materials as $m): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($m['title']) ?></td>
                    <td><?= htmlspecialchars($m['teacher_name']) ?></td>
                    <td><span class="badge badge-muted"><?= strtoupper($m['file_type']) ?></span></td>
                    <td class="text-muted"><?= date('M d, Y', strtotime($m['created_at'])) ?></td>
                    <td>
                        <a href="<?= $cfg['url'] ?>/<?= htmlspecialchars($m['file_path']) ?>" class="btn btn-sm btn-primary" download>Download</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($materials)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No materials uploaded for your class yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
