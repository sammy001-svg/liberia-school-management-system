<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Leave Applications</div></div>
<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($leaves as $l): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($l['staff_name']) ?></td>
                    <td style="text-transform:capitalize;"><?= $l['leave_type'] ?></td>
                    <td><?= $l['start_date'] ?> to <?= $l['end_date'] ?></td>
                    <td class="text-muted" style="font-size:12px;"><?= htmlspecialchars($l['reason']) ?></td>
                    <td>
                        <?php 
                        $badge = $l['status'] === 'approved' ? 'badge-success' : ($l['status'] === 'rejected' ? 'badge-danger' : 'badge-warning');
                        ?>
                        <span class="badge <?= $badge ?>"><?= strtoupper($l['status']) ?></span>
                    </td>
                    <td>
                        <?php if($l['status'] === 'pending'): ?>
                        <form method="POST" action="<?= $cfg['url'] ?>/school/hr/leaves/approve" style="display:inline;">
                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                            <button name="status" value="approved" class="btn btn-sm btn-success">Approve</button>
                            <button name="status" value="rejected" class="btn btn-sm btn-danger">Reject</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($leaves)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No leave applications found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
