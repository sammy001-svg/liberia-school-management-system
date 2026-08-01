<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $cur = $tenant['currency'] ?? 'Ksh'; $money = fn($n) => $cur.' '.number_format((float)$n, 2); ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Student Accounts</div>
    <div class="page-header-sub">Ledger balances, statements and adjustments</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <a href="<?= $cfg['url'] ?>/school/finance/arrears" class="btn btn-outline">📊 Arrears &amp; Aging</a>
    <a href="<?= $cfg['url'] ?>/school/finance/scholarships" class="btn btn-secondary">🎓 Scholarships</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Charged</div>
    <div class="stat-value" style="font-size:20px;"><?= $money($totals['charged']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Total Credited</div>
    <div class="stat-value" style="font-size:20px;"><?= $money($totals['credited']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Outstanding</div>
    <div class="stat-value" style="font-size:20px;"><?= $money($totals['owing']) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Held in Credit</div>
    <div class="stat-value" style="font-size:20px;"><?= $money($totals['inCredit']) ?></div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name or admission no…" class="form-control" style="max-width:260px;">
    <select name="class_id" class="form-control" style="max-width:200px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <label style="display:flex;align-items:center;gap:7px;cursor:pointer;font-size:13px;">
      <input type="checkbox" name="owing" value="1" <?= $onlyOwing?'checked':'' ?>> Only students who owe
    </label>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/finance/accounts" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">Accounts (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Charged</th><th>Credited</th><th>Balance</th><th></th></tr></thead>
      <tbody>
        <?php foreach($rows as $r): ?>
        <?php $bal = (float)$r['balance']; ?>
        <tr>
          <td>
            <a href="<?= $cfg['url'] ?>/school/students/<?= $r['id'] ?>" class="fw-600"><?= htmlspecialchars($r['name']) ?></a>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?></div>
          </td>
          <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
          <td><?= $money($r['charged']) ?></td>
          <td><?= $money($r['credited']) ?></td>
          <td>
            <?php if ($bal > 0.009): ?>
              <span class="badge badge-danger"><?= $money($bal) ?></span>
            <?php elseif ($bal < -0.009): ?>
              <span class="badge badge-info"><?= $money(abs($bal)) ?> credit</span>
            <?php else: ?>
              <span class="badge badge-success">Settled</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="<?= $cfg['url'] ?>/school/finance/accounts/<?= $r['id'] ?>/statement" target="_blank" class="btn btn-sm btn-outline">Statement</a>
              <button type="button" class="btn btn-sm btn-secondary"
                onclick="openAdjust('<?= $r['id'] ?>','<?= htmlspecialchars(addslashes($r['name'])) ?>')">Adjust</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($rows)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">💳</div><div class="empty-state-text">No student accounts match this filter.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Ledger Adjustment Modal -->
<div class="modal-overlay" id="adjustModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Adjust Account — <span id="adjustName"></span></div>
      <button class="modal-close" onclick="document.getElementById('adjustModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="adjustForm" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Entry Type *</label>
            <select name="entry_type" class="form-control" required>
              <option value="charge">Charge (increases balance)</option>
              <option value="discount">Discount (reduces balance)</option>
              <option value="waiver">Waiver (reduces balance)</option>
              <option value="adjustment">Adjustment (reduces balance)</option>
              <option value="refund">Refund (increases balance)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date</label>
            <input type="date" name="entry_date" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Description *</label>
          <input type="text" name="description" class="form-control" required placeholder="e.g. Late registration fee">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Amount *</label>
            <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
            <div class="form-hint">Enter a positive figure — the entry type decides the direction.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Reference</label>
            <input type="text" name="reference" class="form-control">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('adjustModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Record Entry</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAdjust(id, name) {
  document.getElementById('adjustName').textContent = name;
  document.getElementById('adjustForm').action = '<?= $cfg['url'] ?>/school/finance/accounts/' + id + '/adjust';
  document.getElementById('adjustModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
