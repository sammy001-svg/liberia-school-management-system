<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Financial Overview</div>
    <p class="text-muted">Consolidated fee tracking for all your children.</p>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Invoice #</th>
                    <th>Description</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($invoices as $i): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($i['student_name']) ?></td>
                    <td>#INV-<?= $i['id'] ?></td>
                    <td><?= htmlspecialchars($i['description']) ?></td>
                    <td class="fw-700"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($i['amount'], 2) ?></td>
                    <td>
                        <?php 
                        $badge = $i['status'] === 'paid' ? 'badge-success' : ($i['status'] === 'partial' ? 'badge-warning' : 'badge-danger');
                        ?>
                        <span class="badge <?= $badge ?>"><?= strtoupper($i['status']) ?></span>
                    </td>
                    <td><?= date('M d, Y', strtotime($i['due_date'] ?? $i['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($invoices)): ?>
                <tr><td colspan="6" class="text-center text-muted" style="padding:40px;">No financial records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
