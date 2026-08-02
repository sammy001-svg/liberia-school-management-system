<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'L$'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/store/items">School Store</a>
  <span>/</span><span><?= htmlspecialchars($item['name']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($item['name']) ?> — Stock History</div>
    <div class="page-header-sub">Every movement in and out, so a discrepancy can be traced to its cause</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/store/items" class="btn btn-secondary">Back to Items</a>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--blue);">
    <div class="stat-value"><?= (int)$item['stock_qty'] ?></div><div class="stat-label">In Stock (<?= htmlspecialchars($item['unit']) ?>)</div></div>
  <div class="stat-card" style="--card-color:var(--teal);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($item['unit_price'],2) ?></div><div class="stat-label">Selling Price</div></div>
  <div class="stat-card" style="--card-color:var(--orange);">
    <div class="stat-value"><?= (int)$item['reorder_level'] ?></div><div class="stat-label">Reorder Level</div></div>
  <div class="stat-card" style="--card-color:var(--purple);">
    <div class="stat-value"><?= count($movements) ?></div><div class="stat-label">Movements Recorded</div></div>
</div>

<div class="card mt-16">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>When</th><th>Reason</th><th>Change</th><th>Balance After</th><th>Reference</th><th>Note</th><th>By</th></tr></thead>
      <tbody>
        <?php foreach($movements as $m): ?>
        <tr>
          <td><?= date('d M Y, H:i', strtotime($m['created_at'])) ?></td>
          <td>
            <?php
              $badge = ['restock'=>'badge-success','sale'=>'badge-info','void'=>'badge-warning',
                        'adjustment'=>'badge-orange','opening'=>'badge-purple'][$m['reason']] ?? 'badge-info';
            ?>
            <span class="badge <?= $badge ?>"><?= ucfirst($m['reason']) ?></span>
          </td>
          <td class="fw-600" style="color:<?= (int)$m['change_qty'] < 0 ? 'var(--danger)' : 'var(--success)' ?>;">
            <?= (int)$m['change_qty'] > 0 ? '+' : '' ?><?= (int)$m['change_qty'] ?>
          </td>
          <td><?= (int)$m['balance_after'] ?></td>
          <td>
            <?php if(!empty($m['sale_no'])): ?>
              <a href="<?= $cfg['url'] ?>/school/store/sales/<?= (int)$m['sale_id'] ?>/receipt" target="_blank"><?= htmlspecialchars($m['sale_no']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?= htmlspecialchars($m['note'] ?? '—') ?></td>
          <td><?= htmlspecialchars($m['user_name'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($movements)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">📦</div>
            <div class="empty-state-text">No stock movements recorded for this item yet.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
