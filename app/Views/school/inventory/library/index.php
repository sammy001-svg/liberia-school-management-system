<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div>
        <div class="page-header-title">Library Books</div>
        <div class="page-header-sub">Manage the school's book catalog</div>
    </div>
    <div style="display:flex;gap:10px;">
        <a href="<?= $cfg['url'] ?>/school/library/loans" class="btn btn-outline">View Loans</a>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.add('open')">Bulk Upload</button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('addBookModal').classList.add('open')">+ Add Book</button>
    </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Books</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Available</div>
    <div class="stat-value"><?= (int)($stats['available'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Issued</div>
    <div class="stat-value"><?= (int)($stats['issued'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Lost / Damaged</div>
    <div class="stat-value"><?= (int)($stats['lostDamaged'] ?? 0) ?></div>
  </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">All Books (<?= count($books) ?>)</div></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Category</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($books as $b): ?>
                <?php $badgeMap = ['available'=>'badge-success','issued'=>'badge-info','lost'=>'badge-danger','damaged'=>'badge-warning']; ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['author'] ?: '—') ?></td>
                    <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($b['isbn'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($b['category'] ?: '—') ?></td>
                    <td><span class="badge <?= $badgeMap[$b['status']] ?? 'badge-muted' ?>"><?= strtoupper($b['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($books)): ?>
                <tr><td colspan="5">
                    <div class="empty-state">
                        <div class="empty-state-icon">📚</div>
                        <div class="empty-state-text">No books in library catalog. <a href="javascript:void(0)" onclick="document.getElementById('addBookModal').classList.add('open')">Add one</a></div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Book Modal -->
<div class="modal-overlay" id="addBookModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Book to Catalog</div>
      <button class="modal-close" onclick="document.getElementById('addBookModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/library/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Author</label>
            <input type="text" name="author" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category</label>
          <input type="text" name="category" class="form-control" placeholder="e.g. Fiction, Science, Reference">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addBookModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Book</button>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Upload Modal -->
<div class="modal-overlay" id="bulkUploadModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Bulk Upload Books</div>
      <button class="modal-close" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/library/bulk-upload" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
          Upload a CSV file to add multiple books to the catalog at once.
          <a href="<?= $cfg['url'] ?>/school/library/bulk-template">Download the CSV template</a> to see the expected columns.
        </p>
        <div class="form-group">
          <label class="form-label">CSV File *</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <div class="form-hint">Rows with a missing title are skipped and reported.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
      </div>
    </form>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
