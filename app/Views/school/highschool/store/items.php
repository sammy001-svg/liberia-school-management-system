<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'L$'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/finance">Finance</a>
  <span>/</span><span>School Store</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">School Store — Items</div>
    <div class="page-header-sub">Uniforms, stationery and anything else the school sells to students</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/school/store/sell" class="btn btn-primary">＋ New Sale</a>
    <?php if($canManage): ?>
    <button type="button" class="btn btn-secondary" onclick="openItemModal()">＋ Add Item</button>
    <?php endif; ?>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--blue);">
    <div class="stat-value"><?= (int)$stats['total'] ?></div>
    <div class="stat-label">Items (<?= (int)$stats['active'] ?> active)</div>
  </div>
  <div class="stat-card" style="--card-color:var(--teal);">
    <div class="stat-value"><?= htmlspecialchars($cur) ?><?= number_format($stats['retail_value'],2) ?></div>
    <div class="stat-label">Stock Value (retail)</div>
  </div>
  <div class="stat-card" style="--card-color:var(--orange);">
    <div class="stat-value"><?= (int)$stats['low_stock'] ?></div>
    <div class="stat-label">Low Stock — at or below reorder level</div>
  </div>
  <div class="stat-card" style="--card-color:var(--purple);">
    <div class="stat-value"><a href="<?= $cfg['url'] ?>/school/store/reports" style="color:inherit;">View</a></div>
    <div class="stat-label">Sales &amp; Profit Reports</div>
  </div>
</div>

<form method="GET" class="card mt-16" style="padding:14px 18px;">
  <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" style="max-width:260px;" placeholder="Search name or SKU…">
    <select name="category" class="form-control" style="max-width:200px;">
      <option value="">All categories</option>
      <?php foreach($categories as $c): ?>
        <option value="<?= htmlspecialchars($c['category']) ?>" <?= $selectedCategory===$c['category']?'selected':'' ?>><?= htmlspecialchars($c['category']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if($search!==''||$selectedCategory!==''): ?>
      <a href="<?= $cfg['url'] ?>/school/store/items" class="btn btn-outline btn-sm">Clear</a>
    <?php endif; ?>
  </div>
</form>

<div class="card mt-16">
  <div class="table-wrapper">
    <table>
      <thead><tr>
        <th>Item</th><th>SKU</th><th>Category</th><th>Price</th><th>Cost</th><th>Stock</th><th>Status</th><th>Actions</th>
      </tr></thead>
      <tbody>
        <?php foreach($items as $i): $low = (int)$i['reorder_level']>0 && (int)$i['stock_qty'] <= (int)$i['reorder_level']; ?>
        <tr>
          <td class="fw-600">
            <?= htmlspecialchars($i['name']) ?>
            <?php if(!empty($i['description'])): ?>
              <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($i['description']) ?></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($i['sku'] ?? '—') ?></td>
          <td><?= htmlspecialchars($i['category'] ?? '—') ?></td>
          <td><?= htmlspecialchars($cur) ?><?= number_format($i['unit_price'],2) ?></td>
          <td><?= $i['cost_price']!==null ? htmlspecialchars($cur).number_format($i['cost_price'],2) : '—' ?></td>
          <td>
            <span class="badge <?= $low ? 'badge-danger' : 'badge-success' ?>"><?= (int)$i['stock_qty'] ?> <?= htmlspecialchars($i['unit']) ?></span>
            <?php if($low): ?><div style="font-size:11px;color:var(--danger);">reorder at <?= (int)$i['reorder_level'] ?></div><?php endif; ?>
          </td>
          <td><?= $i['is_active'] ? '<span class="badge badge-info">Active</span>' : '<span class="badge badge-muted">Retired</span>' ?></td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= $cfg['url'] ?>/school/store/items/<?= $i['id'] ?>/movements" class="btn btn-sm btn-outline">History</a>
              <?php if($canManage): ?>
              <button type="button" class="btn btn-sm btn-secondary"
                      onclick='openItemModal(<?= json_encode($i, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
              <button type="button" class="btn btn-sm btn-secondary"
                      onclick='openStockModal(<?= (int)$i["id"] ?>,<?= json_encode($i["name"], JSON_HEX_APOS|JSON_HEX_QUOT) ?>,<?= (int)$i["stock_qty"] ?>)'>Stock</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($items)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🛍️</div>
            <div class="empty-state-text">
              No items yet.<?= $canManage ? ' Add uniforms, pens, exercise books and anything else you sell.' : '' ?>
            </div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($canManage): ?>
<!-- ── Add / edit item ── -->
<div class="modal-overlay" id="itemModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="itemModalTitle">Add Item</div>
      <button type="button" class="modal-close" onclick="closeModal('itemModal')">&times;</button></div>
    <form method="POST" id="itemForm" action="<?= $cfg['url'] ?>/school/store/items/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Item Name *</label>
            <input type="text" name="name" id="f_name" class="form-control" required placeholder="e.g. School Uniform Shirt"></div>
          <div class="form-group"><label class="form-label">SKU / Code</label>
            <input type="text" name="sku" id="f_sku" class="form-control" placeholder="e.g. UNI-SHIRT-M"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Category</label>
            <input type="text" name="category" id="f_category" class="form-control" list="catlist" placeholder="e.g. Uniform">
            <datalist id="catlist"><?php foreach($categories as $c): ?><option value="<?= htmlspecialchars($c['category']) ?>"><?php endforeach; ?></datalist>
          </div>
          <div class="form-group"><label class="form-label">Unit</label>
            <input type="text" name="unit" id="f_unit" class="form-control" value="pcs" placeholder="pcs"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Selling Price (<?= htmlspecialchars($cur) ?>) *</label>
            <input type="number" step="0.01" min="0" name="unit_price" id="f_unit_price" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Cost Price (<?= htmlspecialchars($cur) ?>)</label>
            <input type="number" step="0.01" min="0" name="cost_price" id="f_cost_price" class="form-control" placeholder="optional — used for profit"></div>
        </div>
        <div class="form-row">
          <div class="form-group" id="openingStockGroup"><label class="form-label">Opening Stock</label>
            <input type="number" min="0" name="stock_qty" id="f_stock_qty" class="form-control" value="0"></div>
          <div class="form-group"><label class="form-label">Reorder Level</label>
            <input type="number" min="0" name="reorder_level" id="f_reorder_level" class="form-control" value="0" placeholder="0 = no alert"></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label>
          <input type="text" name="description" id="f_description" class="form-control"></div>
        <div class="form-group" id="activeGroup" style="display:none;">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
            <input type="checkbox" name="is_active" id="f_is_active" value="1" checked> Active (available to sell)
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('itemModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Item</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Stock in / correct ── -->
<div class="modal-overlay" id="stockModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Stock — <span id="stockItemName"></span></div>
      <button type="button" class="modal-close" onclick="closeModal('stockModal')">&times;</button></div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/store/stock/adjust">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="item_id" id="s_item_id">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Action</label>
          <select name="mode" id="s_mode" class="form-control" onchange="stockModeChanged()">
            <option value="restock">Receive stock (add to current)</option>
            <option value="adjustment">Correct stock (set counted total)</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" id="s_qty_label">Quantity Received</label>
          <input type="number" name="quantity" id="s_quantity" class="form-control" required>
          <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Currently in stock: <strong id="s_current">0</strong></div>
        </div>
        <div class="form-group"><label class="form-label">Unit Cost (<?= htmlspecialchars($cur) ?>)</label>
          <input type="number" step="0.01" min="0" name="unit_cost" class="form-control" placeholder="optional"></div>
        <div class="form-group"><label class="form-label">Note</label>
          <input type="text" name="note" class="form-control" placeholder="e.g. Delivery from supplier"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('stockModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Stock</button>
      </div>
    </form>
  </div>
</div>

<script>
const STORE_BASE = '<?= $cfg['url'] ?>/school/store';
function openItemModal(item) {
  const f = document.getElementById('itemForm');
  const fields = ['name','sku','category','unit','unit_price','cost_price','reorder_level','description'];
  if (item) {
    document.getElementById('itemModalTitle').textContent = 'Edit Item';
    f.action = STORE_BASE + '/items/' + item.id + '/update';
    fields.forEach(k => { document.getElementById('f_' + k).value = item[k] === null ? '' : item[k]; });
    // Stock only moves through Receive/Correct so every change is auditable.
    document.getElementById('openingStockGroup').style.display = 'none';
    document.getElementById('activeGroup').style.display = '';
    document.getElementById('f_is_active').checked = String(item.is_active) === '1';
  } else {
    document.getElementById('itemModalTitle').textContent = 'Add Item';
    f.action = STORE_BASE + '/items/store';
    fields.forEach(k => { document.getElementById('f_' + k).value = ''; });
    document.getElementById('f_unit').value = 'pcs';
    document.getElementById('f_reorder_level').value = '0';
    document.getElementById('f_stock_qty').value = '0';
    document.getElementById('openingStockGroup').style.display = '';
    document.getElementById('activeGroup').style.display = 'none';
  }
  document.getElementById('itemModal').classList.add('open');
}
function openStockModal(id, name, current) {
  document.getElementById('s_item_id').value = id;
  document.getElementById('stockItemName').textContent = name;
  document.getElementById('s_current').textContent = current;
  document.getElementById('s_quantity').value = '';
  document.getElementById('s_mode').value = 'restock';
  stockModeChanged();
  document.getElementById('stockModal').classList.add('open');
}
function stockModeChanged() {
  const restock = document.getElementById('s_mode').value === 'restock';
  document.getElementById('s_qty_label').textContent = restock ? 'Quantity Received' : 'Counted Total In Stock';
}
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
