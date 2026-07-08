<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Create Invoice</div></div>
<div style="max-width:600px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/finance/invoices/store">
  <div class="card"><div class="card-body">
    <div class="form-group"><label class="form-label">Student *</label>
      <select name="student_id" class="form-control" required>
        <option value="">— Select Student —</option>
        <?php foreach($students as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label class="form-label">Fee Type</label>
      <select name="fee_structure_id" class="form-control" onchange="loadAmount(this)">
        <option value="">— Manual Amount —</option>
        <?php foreach($feeStructs as $f): ?><option value="<?= $f['id'] ?>" data-amount="<?= $f['amount'] ?>"><?= htmlspecialchars($f['name']) ?> — <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($f['amount'],2) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Amount Due (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>) *</label><input type="number" name="amount_due" id="amountInput" class="form-control" step="0.01" required></div>
      <div class="form-group"><label class="form-label">Discount (<?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>)</label><input type="number" name="discount" class="form-control" step="0.01" value="0"></div>
    </div>
    <div class="form-group"><label class="form-label">Due Date</label><input type="date" name="due_date" class="form-control"></div>
    <div class="form-group"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;"><button type="submit" class="btn btn-primary">Generate Invoice</button><a href="<?= $cfg['url'] ?>/school/finance/invoices" class="btn btn-secondary">Cancel</a></div>
</form>
</div>
<script>function loadAmount(sel){const opt=sel.options[sel.selectedIndex];const amt=opt.dataset.amount;if(amt) document.getElementById('amountInput').value=amt;}</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
