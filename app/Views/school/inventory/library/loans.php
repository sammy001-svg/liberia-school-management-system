<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Book Loans &amp; Circulation</div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('loanModal').classList.add('open')">Issue Book</button>
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

<!-- Issue Book Modal -->
<div class="modal-overlay" id="loanModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Issue Book</div>
      <button class="modal-close" onclick="document.getElementById('loanModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/library/issue">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Book *</label>
          <select name="book_id" class="form-control" required>
            <option value="">— Select Available Book —</option>
            <?php foreach($availableBooks as $b): ?>
              <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if(empty($availableBooks)): ?><div class="form-hint">No books currently available to issue.</div><?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Issue To *</label>
          <select name="user_id" class="form-control" required>
            <option value="">— Select Borrower —</option>
            <?php foreach($borrowers as $u): ?>
              <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Due Date *</label>
          <input type="date" name="due_date" class="form-control" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('loanModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Issue Book</button>
      </div>
    </form>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
