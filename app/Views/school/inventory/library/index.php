<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">Library Books</div>
    <div>
        <a href="<?= $cfg['url'] ?>/school/library/loans" class="btn btn-outline">View Loans</a>
        <button class="btn btn-primary">+ Add Book</button>
    </div>
</div>

<div class="card">
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
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($b['title']) ?></td>
                    <td><?= htmlspecialchars($b['author']) ?></td>
                    <td><?= htmlspecialchars($b['isbn']) ?></td>
                    <td><?= htmlspecialchars($b['category']) ?></td>
                    <td>
                        <?php 
                        $badge = $b['status'] === 'available' ? 'badge-success' : 'badge-danger';
                        ?>
                        <span class="badge <?= $badge ?>"><?= strtoupper($b['status']) ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($books)): ?>
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No books in library catalog.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
