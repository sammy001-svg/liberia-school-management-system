<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'L$'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/store/items">School Store</a>
  <span>/</span><span>Sales</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Store Sales</div>
    <div class="page-header-sub">Every sale, with what was collected and what is still owed</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/store/sell" class="btn btn-primary">＋ New Sale</a>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--blue);">
    <div class="stat-value"><?= (int)$totals['count'] ?></div><div class="stat-label">Sales in range</div></div>
  <div class="stat-card" style="--card-color:var(--primary);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($totals['revenue'],2) ?></div><div class="stat-label">Sales Value</div></div>
  <div class="stat-card" style="--card-color:var(--success);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($totals['collected'],2) ?></div><div class="stat-label">Collected</div></div>
  <div class="stat-card" style="--card-color:var(--warning);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($totals['outstanding'],2) ?></div><div class="stat-label">On Account</div></div>
</div>

<form method="GET" class="card mt-16" style="padding:14px 18px;">
  <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div><label class="form-label">From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control"></div>
    <div><label class="form-label">To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control"></div>
    <div><label class="form-label">Status</label>
      <select name="status" class="form-control" style="max-width:160px;">
        <option value="">All</option>
        <?php foreach(['paid'=>'Paid','partial'=>'Part paid','credit'=>'On account','void'=>'Voided'] as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-secondary">Apply</button>
  </div>
</form>

<div class="card mt-16">
  <div class="table-wrapper">
    <table>
      <thead><tr>
        <th>Sale No.</th><th>Date</th><th>Buyer</th><th>Items</th><th>Total</th><th>Paid</th><th>Status</th><th>Sold By</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($sales as $s): $void = $s['status']==='void'; ?>
        <tr style="<?= $void ? 'opacity:0.55;' : '' ?>">
          <td class="fw-600"><?= htmlspecialchars($s['sale_no']) ?></td>
          <td><?= date('d M Y, H:i', strtotime($s['created_at'])) ?></td>
          <td><?= htmlspecialchars($s['student_name'] ?? $s['buyer_name'] ?? '—') ?>
            <?php if(empty($s['student_name']) && !empty($s['buyer_name'])): ?>
              <div style="font-size:11px;color:var(--text-muted);">walk-in</div>
            <?php endif; ?>
          </td>
          <td><?= (int)$s['line_count'] ?></td>
          <td><?= htmlspecialchars($cur) ?><?= number_format($s['total'],2) ?></td>
          <td><?= htmlspecialchars($cur) ?><?= number_format($s['amount_paid'],2) ?></td>
          <td>
            <?php
              $badge = ['paid'=>'badge-success','partial'=>'badge-warning','credit'=>'badge-orange','void'=>'badge-muted'][$s['status']] ?? 'badge-info';
              $label = ['paid'=>'Paid','partial'=>'Part paid','credit'=>'On account','void'=>'Voided'][$s['status']] ?? $s['status'];
            ?>
            <span class="badge <?= $badge ?>"><?= $label ?></span>
          </td>
          <td><?= htmlspecialchars($s['seller_name'] ?? '—') ?></td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/store/sales/<?= $s['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-secondary">Receipt</a>
              <?php if($canManage && !$void): ?>
              <form method="POST" action="<?= $cfg['url'] ?>/school/store/sales/<?= $s['id'] ?>/void"
                    data-confirm="Void <?= htmlspecialchars($s['sale_no']) ?>? Stock goes back on the shelf and the charge is reversed on the student's account."
                    data-confirm-title="Void Sale" data-confirm-label="Void Sale">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Void</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($sales)): ?>
        <tr><td colspan="9">
          <div class="empty-state">
            <div class="empty-state-icon">🧾</div>
            <div class="empty-state-text">No sales in this date range.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
