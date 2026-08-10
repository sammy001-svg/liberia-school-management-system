<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'Ksh'; $money = fn($n) => $cur.' '.number_format((float)$n, 2); ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Other Income</div>
    <div class="page-header-sub">Income that isn't student fees — donations, grants, rentals, fundraising</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= $cfg['url'] ?>/school/finance/budgets" class="btn btn-outline">📊 Budgets</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addIncomeModal').classList.add('open')">+ Record Income</button>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:11px;">From</label>
      <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control">
    </div>
    <div class="form-group" style="margin:0;">
      <label class="form-label" style="font-size:11px;">To</label>
      <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
    </div>
    <button type="submit" class="btn btn-secondary" style="margin-top:18px;">Apply</button>
    <div style="margin-left:auto;font-size:14px;">
      Total for period: <strong><?= $money($total) ?></strong>
    </div>
  </div>
</form>

<?php if(!empty($byCategory)): ?>
<div class="card" style="margin-bottom:20px;">
  <div class="card-header"><div class="card-title">By Category</div></div>
  <div class="card-body">
    <div style="display:flex;flex-wrap:wrap;gap:10px;">
      <?php foreach($byCategory as $c): ?>
        <span class="badge badge-primary" style="padding:8px 13px;font-size:12px;">
          <?= htmlspecialchars($c['category']) ?> — <?= $money($c['total']) ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">Income Entries (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Source</th><th>Amount</th><th>Recorded By</th><th></th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <tr>
          <td><?= date('d M Y', strtotime($r['income_date'])) ?></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($r['category']) ?></span></td>
          <td>
            <?= htmlspecialchars($r['description'] ?? '—') ?>
            <?php if($r['reference']): ?><div style="font-size:11px;color:var(--text-muted);">Ref: <?= htmlspecialchars($r['reference']) ?></div><?php endif; ?>
          </td>
          <td><?= htmlspecialchars($r['source'] ?? '—') ?></td>
          <td class="fw-600"><?= $money($r['amount']) ?></td>
          <td style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($r['recorded_by_name'] ?? '—') ?></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/finance/incomes/<?= $r['id'] ?>/delete"
                  data-confirm="Remove this income entry?" data-confirm-title="Remove Income" data-confirm-label="Remove">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">💰</div>
            <div class="empty-state-text">No income recorded in this period.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addIncomeModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Record Income</div>
      <button class="modal-close" onclick="document.getElementById('addIncomeModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/incomes/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category *</label>
            <input type="text" name="category" class="form-control" required list="incomeCats" placeholder="e.g. Donations, Grants">
            <datalist id="incomeCats">
              <?php foreach($byCategory as $c): ?><option value="<?= htmlspecialchars($c['category']) ?>"></option><?php endforeach; ?>
              <option value="Donations"></option><option value="Grants"></option>
              <option value="Fundraising"></option><option value="Rental Income"></option>
              <option value="Uniform Sales"></option><option value="Miscellaneous"></option>
            </datalist>
            <div class="form-hint">Match this to your budget line category.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Amount *</label>
            <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date Received *</label>
            <input type="date" name="income_date" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Method</label>
            <select name="method" class="form-control">
              <option value="cash">Cash</option>
              <option value="bank">Bank Transfer</option>
              <option value="cheque">Cheque</option>
              <option value="mobile">Mobile Money</option>
              <option value="other">Other</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Received From</label>
          <input type="text" name="source" class="form-control" placeholder="e.g. Ministry of Education, PTA">
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <input type="text" name="description" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Reference</label>
          <input type="text" name="reference" class="form-control" placeholder="Receipt or cheque number">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addIncomeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Income</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
