<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'L$'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/store/items">School Store</a>
  <span>/</span><span>New Sale</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">New Sale</div>
    <div class="page-header-sub">Pick the buyer, set quantities, then take payment or charge it to the student's account</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/store/sales" class="btn btn-secondary">All Sales</a>
</div>

<?php if (empty($items)): ?>
<div class="card"><div class="empty-state">
  <div class="empty-state-icon">🛍️</div>
  <div class="empty-state-text">
    No active items to sell yet — add them on the <a href="<?= $cfg['url'] ?>/school/store/items">Store Items</a> page first.
  </div>
</div></div>
<?php else: ?>

<form method="POST" action="<?= $cfg['url'] ?>/school/store/sales/store" id="saleForm">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

  <div class="card">
    <div class="card-header"><div class="card-title">Buyer</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Sell to</label>
          <select name="buyer_type" id="buyerType" class="form-control" onchange="buyerTypeChanged()">
            <option value="student">A student (can be charged to their account)</option>
            <option value="walkin">Walk-in buyer (pay now)</option>
          </select>
        </div>
        <div class="form-group" id="studentGroup">
          <label class="form-label">Student *</label>
          <select name="student_id" id="studentSelect" class="form-control">
            <option value="">— Select student —</option>
            <?php foreach($students as $s): ?>
              <option value="<?= $s['id'] ?>">
                <?= htmlspecialchars($s['name']) ?><?= $s['class_name'] ? ' — '.htmlspecialchars($s['class_name']) : '' ?><?= $s['admission_no'] ? ' ('.htmlspecialchars($s['admission_no']).')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" id="walkinGroup" style="display:none;">
          <label class="form-label">Buyer Name *</label>
          <input type="text" name="buyer_name" id="buyerName" class="form-control" placeholder="e.g. Mr. Johnson (parent)">
        </div>
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header">
      <div class="card-title">Items</div>
      <input type="text" id="itemFilter" class="form-control" style="max-width:240px;padding:6px 10px;" placeholder="Filter items…" onkeyup="filterItems()">
    </div>
    <div class="table-wrapper">
      <table id="itemTable">
        <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>In Stock</th><th style="width:130px;">Quantity</th><th>Line Total</th></tr></thead>
        <tbody>
          <?php foreach($items as $i): ?>
          <tr data-name="<?= htmlspecialchars(strtolower($i['name'].' '.($i['sku'] ?? '').' '.($i['category'] ?? ''))) ?>">
            <td class="fw-600"><?= htmlspecialchars($i['name']) ?>
              <?php if(!empty($i['sku'])): ?><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($i['sku']) ?></div><?php endif; ?>
            </td>
            <td><?= htmlspecialchars($i['category'] ?? '—') ?></td>
            <td class="price" data-price="<?= htmlspecialchars($i['unit_price']) ?>"><?= htmlspecialchars($cur) ?><?= number_format($i['unit_price'],2) ?></td>
            <td>
              <span class="badge <?= (int)$i['stock_qty'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                <?= (int)$i['stock_qty'] ?> <?= htmlspecialchars($i['unit']) ?>
              </span>
            </td>
            <td>
              <input type="number" min="0" max="<?= (int)$i['stock_qty'] ?>" step="1"
                     name="qty[<?= $i['id'] ?>]" class="form-control qty-input"
                     style="width:100px;padding:6px;" placeholder="0"
                     data-price="<?= htmlspecialchars($i['unit_price']) ?>"
                     data-stock="<?= (int)$i['stock_qty'] ?>"
                     <?= (int)$i['stock_qty'] < 1 ? 'disabled title="Out of stock"' : '' ?>>
            </td>
            <td class="line-total">—</td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">Payment</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Discount (<?= htmlspecialchars($cur) ?>)</label>
          <input type="number" step="0.01" min="0" name="discount" id="discount" class="form-control" value="0" oninput="recalc()">
        </div>
        <div class="form-group">
          <label class="form-label">Payment Method</label>
          <select name="payment_method" id="paymentMethod" class="form-control" onchange="methodChanged()">
            <option value="cash">Cash</option>
            <option value="mpesa">Mobile Money</option>
            <option value="bank">Bank</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
            <option value="account">Charge to student account (pay later)</option>
          </select>
        </div>
        <div class="form-group" id="paidGroup">
          <label class="form-label">Amount Received (<?= htmlspecialchars($cur) ?>)</label>
          <input type="number" step="0.01" min="0" name="amount_paid" id="amountPaid" class="form-control" oninput="recalc()">
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Leave as the total for a full payment.</div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Notes</label>
        <input type="text" name="notes" class="form-control" placeholder="Optional — e.g. replacement shirt">
      </div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-body">
      <div style="display:flex;justify-content:flex-end;gap:28px;flex-wrap:wrap;align-items:center;">
        <div style="text-align:right;">
          <div style="font-size:12px;color:var(--text-muted);">Subtotal</div>
          <div style="font-size:16px;font-weight:600;" id="subtotalOut"><?= htmlspecialchars($cur) ?>0.00</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:12px;color:var(--text-muted);">Discount</div>
          <div style="font-size:16px;font-weight:600;" id="discountOut"><?= htmlspecialchars($cur) ?>0.00</div>
        </div>
        <div style="text-align:right;">
          <div style="font-size:12px;color:var(--text-muted);">Total</div>
          <div style="font-size:24px;font-weight:800;color:var(--primary);" id="totalOut"><?= htmlspecialchars($cur) ?>0.00</div>
        </div>
        <div style="text-align:right;" id="balanceWrap">
          <div style="font-size:12px;color:var(--text-muted);">Balance Due</div>
          <div style="font-size:16px;font-weight:700;" id="balanceOut"><?= htmlspecialchars($cur) ?>0.00</div>
        </div>
        <button type="submit" class="btn btn-primary" id="completeBtn" disabled>Complete Sale</button>
      </div>
    </div>
  </div>
</form>

<script>
const CUR = <?= json_encode($cur) ?>;
function money(n) { return CUR + n.toFixed(2); }

function recalc() {
  let subtotal = 0;
  document.querySelectorAll('.qty-input').forEach(function (input) {
    const qty = parseInt(input.value || '0', 10);
    const price = parseFloat(input.dataset.price || '0');
    const cell = input.closest('tr').querySelector('.line-total');
    if (qty > 0) {
      const line = qty * price;
      subtotal += line;
      cell.textContent = money(line);
    } else {
      cell.textContent = '—';
    }
  });

  let discount = parseFloat(document.getElementById('discount').value || '0');
  if (discount < 0) { discount = 0; }
  if (discount > subtotal) { discount = subtotal; }
  const total = subtotal - discount;

  const onAccount = document.getElementById('paymentMethod').value === 'account';
  const paidInput = document.getElementById('amountPaid');
  // An account sale collects nothing now, so the tendered box is not in play.
  const paid = onAccount ? 0 : parseFloat(paidInput.value === '' ? total : paidInput.value || '0');

  document.getElementById('subtotalOut').textContent = money(subtotal);
  document.getElementById('discountOut').textContent = money(discount);
  document.getElementById('totalOut').textContent = money(total);

  const balance = Math.max(0, total - Math.min(paid, total));
  const balanceOut = document.getElementById('balanceOut');
  balanceOut.textContent = money(balance);
  balanceOut.style.color = balance > 0 ? 'var(--warning)' : 'var(--success)';

  document.getElementById('completeBtn').disabled = subtotal <= 0;
}

function methodChanged() {
  const onAccount = document.getElementById('paymentMethod').value === 'account';
  document.getElementById('paidGroup').style.display = onAccount ? 'none' : '';
  recalc();
}

function buyerTypeChanged() {
  const walkin = document.getElementById('buyerType').value === 'walkin';
  document.getElementById('studentGroup').style.display = walkin ? 'none' : '';
  document.getElementById('walkinGroup').style.display = walkin ? '' : 'none';

  // A walk-in has no account to charge, so drop that option while they're selected.
  const method = document.getElementById('paymentMethod');
  const accountOpt = method.querySelector('option[value="account"]');
  accountOpt.disabled = walkin;
  if (walkin && method.value === 'account') { method.value = 'cash'; }
  methodChanged();
}

document.querySelectorAll('.qty-input').forEach(function (input) {
  input.addEventListener('input', function () {
    const stock = parseInt(this.dataset.stock || '0', 10);
    if (parseInt(this.value || '0', 10) > stock) { this.value = stock; }
    recalc();
  });
});

function filterItems() {
  const q = document.getElementById('itemFilter').value.toLowerCase().trim();
  document.querySelectorAll('#itemTable tbody tr').forEach(function (tr) {
    // A row with a quantity typed stays visible so it can't be filtered out of sight.
    const hasQty = parseInt(tr.querySelector('.qty-input')?.value || '0', 10) > 0;
    tr.style.display = (q === '' || tr.dataset.name.includes(q) || hasQty) ? '' : 'none';
  });
}

document.getElementById('saleForm').addEventListener('submit', function (e) {
  const walkin = document.getElementById('buyerType').value === 'walkin';
  if (!walkin && !document.getElementById('studentSelect').value) {
    e.preventDefault(); alert('Choose the student this sale is for.');
  } else if (walkin && !document.getElementById('buyerName').value.trim()) {
    e.preventDefault(); alert("Enter the buyer's name.");
  }
});

buyerTypeChanged();
recalc();
</script>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
