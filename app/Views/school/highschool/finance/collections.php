<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$justSaved = (int)($_GET['receipt'] ?? 0);
$typeLabel = ['student' => 'Student', 'parent' => 'Parent', 'staff' => 'Staff', 'other' => ''];
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Extra Collections</div>
    <div class="page-header-sub">Money received outside school fees — uniforms, cafeteria, textbooks, entrance tests, donations. It counts as income in reports and budgets.</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('colModal').classList.add('open')">＋ Add Collection</button>
</div>

<?php if ($justSaved): ?>
  <div class="alert alert-success" style="display:flex;justify-content:space-between;align-items:center;gap:12px;">
    <span>Collection saved.</span><a href="<?= $base ?>/collections/<?= $justSaved ?>/receipt" target="_blank" class="btn btn-sm btn-success">🖨 Print Receipt</a>
  </div>
<?php endif; ?>

<div class="stat-grid">
  <?php foreach ($sums ?: [['cur' => $def, 't' => 0, 'n' => 0, 'owed' => 0]] as $s): ?>
    <div class="stat-card" style="--card-color:var(--success);"><div class="stat-value" style="font-size:22px;"><?= Finance::money($s['t'], $s['cur']) ?></div><div class="stat-label">across <?= (int)$s['n'] ?> record(s) — excludes cancelled</div></div>
    <?php if ((float)$s['owed'] > 0): ?><div class="stat-card" style="--card-color:var(--warning);"><div class="stat-value" style="font-size:22px;"><?= Finance::money($s['owed'], $s['cur']) ?></div><div class="stat-label">still owed on part-paid items</div></div><?php endif; ?>
  <?php endforeach; ?>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:16px;">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" class="form-control" style="max-width:200px;" placeholder="Payer, description, ref" value="<?= htmlspecialchars($filters['q']) ?>">
    <select name="category" class="form-control" style="max-width:190px;"><option value="">All Categories</option><?php foreach ($categories as $c): ?><option <?= $filters['category'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?></select>
    <select name="status" class="form-control" style="max-width:140px;"><option value="">All Statuses</option><option value="active" <?= $filters['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="cancelled" <?= $filters['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option></select>
    <input type="date" name="from" class="form-control" style="max-width:150px;" value="<?= htmlspecialchars($filters['from']) ?>">
    <span style="color:var(--text-muted);font-size:12px;">to</span>
    <input type="date" name="to" class="form-control" style="max-width:150px;" value="<?= htmlspecialchars($filters['to']) ?>">
    <details style="width:100%;" <?= ($filters['method'] || $filters['class'] || $filters['gender'] || $filters['balance']) ? 'open' : '' ?>>
      <summary style="cursor:pointer;font-size:12.5px;color:var(--primary);margin:4px 0;">More filters</summary>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
        <select name="method" class="form-control" style="max-width:150px;"><option value="">All Methods</option><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>" <?= $filters['method'] === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
        <select name="class" class="form-control" style="max-width:160px;"><option value="">All Classes</option><?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $filters['class'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select>
        <select name="gender" class="form-control" style="max-width:140px;"><option value="">All Genders</option><option value="male" <?= $filters['gender'] === 'male' ? 'selected' : '' ?>>Male</option><option value="female" <?= $filters['gender'] === 'female' ? 'selected' : '' ?>>Female</option></select>
        <select name="balance" class="form-control" style="max-width:150px;"><option value="">Any Balance</option><option value="outstanding" <?= $filters['balance'] === 'outstanding' ? 'selected' : '' ?>>Outstanding</option><option value="settled" <?= $filters['balance'] === 'settled' ? 'selected' : '' ?>>Settled</option></select>
      </div>
    </details>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $base ?>/collections" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">All Collection Activities (<?= $total ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>ID</th><th>Payer</th><th>Date</th><th>Category</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Balance</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): $cur = $r['currency'] ?: $def; ?>
          <tr style="<?= $r['status'] === 'cancelled' ? 'opacity:.55;' : '' ?>">
            <td style="font-size:12px;">#<?= $r['id'] ?></td>
            <td><div class="fw-600"><?= htmlspecialchars($r['source'] ?: '—') ?></div><div style="font-size:11px;color:var(--text-muted);"><?= $typeLabel[$r['payer_type']] ?? '' ?><?= $r['description'] ? ' · ' . htmlspecialchars(mb_strimwidth($r['description'], 0, 60, '…')) : '' ?></div></td>
            <td style="font-size:12.5px;white-space:nowrap;"><?= date('M d, Y', strtotime($r['income_date'])) ?></td>
            <td><span class="badge badge-muted"><?= htmlspecialchars($r['category']) ?></span></td>
            <td style="text-align:right;font-weight:700;"><?= Finance::money($r['amount'], $cur) ?></td>
            <td style="text-align:right;<?= (float)$r['balance_owed'] > 0 ? 'color:var(--danger);font-weight:600;' : 'color:var(--text-muted);' ?>"><?= (float)$r['balance_owed'] > 0 ? Finance::money($r['balance_owed'], $cur) : '—' ?></td>
            <td><span class="badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= ucfirst($r['status']) ?></span></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <?php if ($r['status'] === 'active'): ?>
                  <a href="<?= $base ?>/collections/<?= $r['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-outline">Receipt</a>
                  <?php if ((float)$r['balance_owed'] > 0): ?><button type="button" class="btn btn-sm btn-success" onclick="settle(<?= $r['id'] ?>, <?= (float)$r['balance_owed'] ?>)">Pay Balance</button><?php endif; ?>
                  <button type="button" class="btn btn-sm btn-danger" onclick="cancelCol(<?= $r['id'] ?>)">Cancel</button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-secondary" onclick='viewCol(<?= json_encode([
                    'id' => $r['id'], 'payer' => $r['source'], 'date' => $r['income_date'], 'category' => $r['category'], 'amount' => Finance::money($r['amount'], $cur),
                    'balance' => (float)$r['balance_owed'] > 0 ? Finance::money($r['balance_owed'], $cur) : '—', 'method' => Finance::PAYMENT_METHODS[$r['method']] ?? $r['method'],
                    'reference' => $r['reference'], 'description' => $r['description'], 'status' => $r['status'], 'reason' => $r['cancel_reason'],
                    'by' => $r['recorded_by_name'], 'file' => $r['receipt_file']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>View</button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-state"><div class="empty-state-icon">💵</div><div class="empty-state-text">No collections match.</div></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/pagination.php'; ?>

<div class="modal-overlay" id="colModal">
  <div class="modal modal-lg">
    <div class="modal-header"><div class="modal-title">Add New Collection</div><button type="button" class="modal-close" onclick="document.getElementById('colModal').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/collections/store" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="payer_ref" id="payerRef">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Payer *</label>
            <input type="text" name="payer" id="payerPick" class="form-control" list="payerList" maxlength="150" required placeholder="Select a student, parent or staff — or type a name">
            <datalist id="payerList"><?php foreach ($payers as $p): ?><option value="<?= htmlspecialchars($p['label']) ?>" data-ref="<?= $p['t'] ?>:<?= $p['id'] ?>"></option><?php endforeach; ?></datalist></div>
          <div class="form-group"><label class="form-label">Payment Date *</label><input type="date" name="income_date" class="form-control" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Category *</label>
            <input type="text" name="category" class="form-control" list="colCats" maxlength="120" required placeholder="Choose or type a new one">
            <datalist id="colCats"><?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?></datalist></div>
          <div class="form-group"><label class="form-label">Payment Method</label><select name="method" class="form-control"><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Amount *</label>
            <div style="display:flex;gap:6px;"><select name="currency" class="form-control" style="max-width:90px;"><?php foreach ($currencies as $c): ?><option><?= $c ?></option><?php endforeach; ?></select>
            <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required></div></div>
          <div class="form-group"><label class="form-label">Balance Still Owed</label><input type="number" name="balance_owed" class="form-control" min="0" step="0.01"><div class="form-hint">Leave blank if fully paid.</div></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Payment Reference</label><input type="text" name="reference" class="form-control" maxlength="60"></div>
          <div class="form-group"><label class="form-label">Receipt Attachment</label><input type="file" name="receipt_file" class="form-control" accept="image/*,.pdf"></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" maxlength="255"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('colModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Save Collection</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="settleModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Pay Balance</div><button type="button" class="modal-close" onclick="document.getElementById('settleModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="settleForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Amount Received *</label><input type="number" name="amount" id="settleAmount" class="form-control" min="0.01" step="0.01" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Method</label><select name="method" class="form-control"><?php foreach (Finance::PAYMENT_METHODS as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Reference</label><input type="text" name="reference" class="form-control" maxlength="60"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('settleModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Record Payment</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="cancelModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Cancel Collection</div><button type="button" class="modal-close" onclick="document.getElementById('cancelModal').classList.remove('open')">&times;</button></div>
    <form method="POST" id="cancelForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body"><div class="form-group"><label class="form-label">Reason *</label><textarea name="reason" class="form-control" rows="2" required></textarea></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('cancelModal').classList.remove('open')">Keep</button><button type="submit" class="btn btn-danger">Cancel Collection</button></div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="viewModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="viewTitle">Payment Details</div><button type="button" class="modal-close" onclick="document.getElementById('viewModal').classList.remove('open')">&times;</button></div>
    <div class="modal-body"><table style="width:100%;font-size:13px;" id="viewTable"></table></div>
  </div>
</div>

<script>
const COL_BASE = '<?= $base ?>/collections';
document.getElementById('payerPick').addEventListener('input', function () {
  const o = [...document.querySelectorAll('#payerList option')].find(o => o.value === this.value);
  document.getElementById('payerRef').value = o ? o.dataset.ref : '';
});
function cancelCol(id) { document.getElementById('cancelForm').action = COL_BASE + '/' + id + '/cancel'; document.getElementById('cancelModal').classList.add('open'); }
function settle(id, bal) { document.getElementById('settleForm').action = COL_BASE + '/' + id + '/settle'; const a = document.getElementById('settleAmount'); a.max = bal; a.value = bal.toFixed(2); document.getElementById('settleModal').classList.add('open'); }
function viewCol(r) {
  document.getElementById('viewTitle').textContent = 'Payment Details #' + r.id;
  const rows = [['Payer', r.payer], ['Date', r.date], ['Category', r.category], ['Amount', r.amount], ['Balance owed', r.balance], ['Method', r.method],
    ['Reference', r.reference || '—'], ['Description', r.description || '—'], ['Status', r.status + (r.reason ? ' — ' + r.reason : '')], ['Recorded by', r.by || '—']];
  const t = document.getElementById('viewTable'); t.innerHTML = '';
  rows.forEach(([k, v]) => { const tr = t.insertRow(); tr.insertCell().textContent = k; tr.cells[0].style.cssText = 'color:var(--text-muted);padding:6px 10px 6px 0;width:130px;'; tr.insertCell().textContent = v; });
  if (r.file) { const tr = t.insertRow(); tr.insertCell().textContent = 'Attachment'; const a = document.createElement('a'); a.href = r.file; a.target = '_blank'; a.textContent = 'Open'; tr.insertCell().appendChild(a); }
  document.getElementById('viewModal').classList.add('open');
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
