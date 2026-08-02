<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$cur = $tenant['currency'] ?? 'L$';
$grossProfit = (float)$profit['revenue'] - (float)$profit['cost'];
$margin = (float)$profit['revenue'] > 0 ? ($grossProfit / (float)$profit['revenue']) * 100 : 0;
$maxDay = 0;
foreach ($daily as $d) { $maxDay = max($maxDay, (float)$d['revenue']); }
?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/store/items">School Store</a>
  <span>/</span><span>Reports</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Store Reports</div>
    <div class="page-header-sub">Sales, margin and stock health. Voided sales are excluded throughout.</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/store/sales?from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>" class="btn btn-secondary">View Sales</a>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:18px;">
  <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
    <div><label class="form-label">From</label><input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control"></div>
    <div><label class="form-label">To</label><input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control"></div>
    <button type="submit" class="btn btn-secondary">Apply</button>
  </div>
</form>

<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--primary);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($summary['revenue'],2) ?></div>
    <div class="stat-label">Sales Value (<?= (int)$summary['sales'] ?> sales)</div></div>
  <div class="stat-card" style="--card-color:var(--success);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($summary['collected'],2) ?></div>
    <div class="stat-label">Cash Collected</div></div>
  <div class="stat-card" style="--card-color:var(--warning);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($summary['outstanding'],2) ?></div>
    <div class="stat-label">Charged to Accounts</div></div>
  <div class="stat-card" style="--card-color:<?= $grossProfit >= 0 ? 'var(--teal)' : 'var(--danger)' ?>;">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($grossProfit,2) ?></div>
    <div class="stat-label">Gross Profit (<?= number_format($margin,1) ?>% margin)</div></div>
</div>

<?php if((float)$profit['cost'] <= 0 && (float)$profit['revenue'] > 0): ?>
<div class="alert alert-info mt-16">
  Profit is shown as the full sales value because no cost prices are recorded yet. Add a cost price to your
  items and future sales will report a true margin — past sales keep the cost that applied when they were rung up.
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:16px;margin-top:16px;">

  <div class="card">
    <div class="card-header"><div class="card-title">Best Sellers</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Item</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php foreach($topItems as $t): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($t['item_name']) ?></td>
            <td><?= (int)$t['qty'] ?></td>
            <td><?= htmlspecialchars($cur) ?><?= number_format($t['revenue'],2) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($topItems)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:18px;">No sales in this range.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Low Stock — Reorder Now</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Item</th><th>In Stock</th><th>Reorder At</th></tr></thead>
        <tbody>
          <?php foreach($lowStock as $l): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($l['name']) ?></td>
            <td><span class="badge badge-danger"><?= (int)$l['stock_qty'] ?> <?= htmlspecialchars($l['unit']) ?></span></td>
            <td><?= (int)$l['reorder_level'] ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($lowStock)): ?>
          <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:18px;">Every item is above its reorder level.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<div class="card mt-16">
  <div class="card-header"><div class="card-title">Daily Sales</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Date</th><th>Sales</th><th>Revenue</th><th style="width:45%;"></th></tr></thead>
      <tbody>
        <?php foreach($daily as $d): $pct = $maxDay > 0 ? ((float)$d['revenue'] / $maxDay) * 100 : 0; ?>
        <tr>
          <td><?= date('d M Y', strtotime($d['day'])) ?></td>
          <td><?= (int)$d['sales'] ?></td>
          <td class="fw-600"><?= htmlspecialchars($cur) ?><?= number_format($d['revenue'],2) ?></td>
          <td>
            <div style="background:var(--bg-muted,#e5e7eb);border-radius:5px;height:9px;overflow:hidden;">
              <div style="width:<?= number_format($pct,1) ?>%;height:100%;background:linear-gradient(90deg,var(--primary),var(--teal));"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($daily)): ?>
        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:18px;">No sales in this range.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-16">
  <div class="card-header"><div class="card-title">Current Stock Valuation</div></div>
  <div class="card-body">
    <div style="display:flex;gap:36px;flex-wrap:wrap;">
      <div><div style="font-size:12px;color:var(--text-muted);">Units on hand</div>
        <div style="font-size:22px;font-weight:700;"><?= (int)$valuation['units'] ?></div></div>
      <div><div style="font-size:12px;color:var(--text-muted);">Value at cost</div>
        <div style="font-size:22px;font-weight:700;"><?= htmlspecialchars($cur) ?><?= number_format($valuation['at_cost'],2) ?></div></div>
      <div><div style="font-size:12px;color:var(--text-muted);">Value at retail</div>
        <div style="font-size:22px;font-weight:700;color:var(--primary);"><?= htmlspecialchars($cur) ?><?= number_format($valuation['retail'],2) ?></div></div>
    </div>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
