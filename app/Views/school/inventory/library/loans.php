<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Book Loans & Circulation</div>
    <button class="btn btn-primary" onclick="document.getElementById('loanModal').style.display='flex'">Issue Book</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Book Title</th>
                    <th>Issued To</th>
                    <th>Issued Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($loans as $l): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($l['book_title']) ?></td>
                    <td><?= htmlspecialchars($l['user_name']) ?></td>
                    <td><?= date('M d, Y', strtotime($l['issued_at'])) ?></td>
                    <td class="<?= (strtotime($l['due_date']) < time() && !$l['returned_at']) ? 'text-danger fw-700' : '' ?>">
                        <?= date('M d, Y', strtotime($l['due_date'])) ?>
                    </td>
                    <td>
                        <?php if($l['returned_at']): ?>
                            <span class="badge badge-success">RETURNED</span>
                        <?php elseif(strtotime($l['due_date']) < time()): ?>
                            <span class="badge badge-danger">OVERDUE</span>
                        <?php else: ?>
                            <span class="badge badge-info">ISSUED</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($loans)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No circulation history.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Issue Modal Stub -->
<div class="modal-overlay" id="loanModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Issue Book</div>
      <button class="modal-close" onclick="document.getElementById('loanModal').style.display='none'">&times;</button>
    </div>
    <div class="modal-body">
      <p class="text-muted">Form logic for issuing books (selecting book, user, and due date).</p>
    </div>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
