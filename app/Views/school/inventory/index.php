<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
    <div class="page-header-title">School Inventory</div>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('open')">+ Add Item</button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Supplier</th>
                    <th>Location</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $i): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($i['item_name']) ?></td>
                    <td><?= htmlspecialchars($i['category']) ?></td>
                    <td class="fw-700"><?= $i['quantity'] ?></td>
                    <td><?= htmlspecialchars($i['unit']) ?></td>
                    <td><?= $i['unit_price']!==null ? number_format($i['unit_price'],2) : '—' ?></td>
                    <td><?= htmlspecialchars($i['supplier']??'—') ?></td>
                    <td><?= htmlspecialchars($i['location']) ?></td>
                    <td class="text-muted"><?= date('M d, H:i', strtotime($i['last_updated'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">No inventory items yet. <a href="javascript:void(0)" onclick="document.getElementById('addModal').classList.add('open')">Add one</a></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Inventory Item</div>
      <button class="modal-close" onclick="document.getElementById('addModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/inventory/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Item Name *</label>
          <input type="text" name="item_name" class="form-control" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" placeholder="e.g. Stationery">
          </div>
          <div class="form-group">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" value="1">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" value="pcs">
          </div>
          <div class="form-group">
            <label class="form-label">Unit Price</label>
            <input type="number" name="unit_price" class="form-control" step="0.01" placeholder="e.g. 2.50">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Supplier</label>
            <input type="text" name="supplier" class="form-control" placeholder="e.g. ABC Suppliers Ltd">
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" placeholder="e.g. Store A">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Item</button>
      </div>
    </form>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
