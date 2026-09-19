<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/finance'; $s = $finSettings; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Finance Settings</div>
    <div class="page-header-sub">Currencies, approval rules and the category lists used by expenses and extra collections.</div>
  </div>
  <a href="<?= $base ?>/billing" class="btn btn-outline">Student Types (Billing Setup)</a>
</div>

<form method="POST" action="<?= $base ?>/settings/save" style="max-width:760px;">
  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
  <div class="card">
    <div class="card-header"><div class="card-title">💱 Currency</div></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group"><label class="form-label">Default Currency</label>
          <select name="default_currency" class="form-control"><?php foreach (Finance::CURRENCIES as $c => $sym): ?><option value="<?= $c ?>" <?= $s['default_currency'] === $c ? 'selected' : '' ?>><?= $c ?> (<?= $sym ?>)</option><?php endforeach; ?></select></div>
        <div class="form-group"><label class="form-label">Second Currency</label>
          <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:8px;"><input type="checkbox" name="allow_secondary" value="1" <?= $s['secondary_currency'] ? 'checked' : '' ?> onchange="document.getElementById('secCur').disabled=!this.checked"> Allow a second currency</label>
          <select name="secondary_currency" id="secCur" class="form-control" <?= $s['secondary_currency'] ? '' : 'disabled' ?>><?php foreach (Finance::CURRENCIES as $c => $sym): ?><option value="<?= $c ?>" <?= ($s['secondary_currency'] ?: 'USD') === $c ? 'selected' : '' ?>><?= $c ?> (<?= $sym ?>)</option><?php endforeach; ?></select></div>
      </div>
      <div class="form-hint">With a second currency, each bill, expense and collection can be in either currency. Reports total each currency separately — they are never added together.</div>
    </div>
  </div>

  <div class="card mt-16">
    <div class="card-header"><div class="card-title">✔ Approvals &amp; Controls</div></div>
    <div class="card-body" style="display:grid;gap:14px;">
      <?php foreach ([
        ['payment_approval', 'Payment Approval System', 'Fee payments recorded by staff who can’t approve wait in Payment Approvals and don’t count until a School Admin (or anyone with finance.approve) accepts them.'],
        ['expense_approval', 'Expense Approval System', 'Expenses recorded by staff who can’t approve wait in Expense Approvals before they count.'],
        ['block_arrears_enrollment', 'Block Enrollment With Arrears', 'A student who still owes from a previous year can’t be enrolled for a new year until the arrears are paid, written off, or an override is approved.'],
        ['payment_audit', 'Payment Records Audit', 'Show the reconciliation view (payments by method and by cashier, including cancelled ones) in Financial Records.'],
        ['daily_receipts', 'Daily Receipts Summary', 'Show the end-of-day summary of everything received.'],
      ] as [$k, $label, $hint]): ?>
        <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;">
          <input type="checkbox" name="<?= $k ?>" value="1" <?= $s[$k] === '1' ? 'checked' : '' ?> style="margin-top:3px;">
          <span><span class="fw-600"><?= $label ?></span><br><span class="form-hint" style="font-size:12px;"><?= $hint ?></span></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>
  <div style="margin-top:16px;"><button type="submit" class="btn btn-primary">Save Settings</button></div>
</form>

<?php foreach (['expense' => '🧾 Expense Categories', 'collection' => '💵 Collection Categories'] as $kind => $title): ?>
<div class="card mt-16" id="<?= $kind ?>" style="max-width:760px;">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;"><div class="card-title"><?= $title ?></div>
    <button type="button" class="btn btn-sm btn-outline" onclick="document.getElementById('merge_<?= $kind ?>').classList.add('open')">Merge Categories</button></div>
  <form method="POST" action="<?= $base ?>/settings/categories">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="kind" value="<?= $kind ?>">
    <div class="card-body">
      <div class="form-hint" style="margin:0 0 12px;">Renaming a category renames it on every past entry too. Untick “In use” to hide it from the forms without touching history.</div>
      <div style="display:grid;grid-template-columns:1fr auto auto;gap:8px 12px;align-items:center;">
        <?php foreach ($cats[$kind] as $c): ?>
          <input type="text" name="cats[<?= $c['id'] ?>][name]" class="form-control" value="<?= htmlspecialchars($c['name']) ?>" maxlength="120">
          <span style="font-size:11.5px;color:var(--text-muted);white-space:nowrap;"><?= (int)$c['uses'] ?> entries</span>
          <label style="display:flex;gap:6px;font-size:12.5px;white-space:nowrap;"><input type="checkbox" name="cats[<?= $c['id'] ?>][active]" value="1" <?= $c['is_active'] ? 'checked' : '' ?>> In use</label>
        <?php endforeach; ?>
      </div>
      <div id="new_<?= $kind ?>" style="margin-top:10px;"></div>
      <button type="button" class="btn btn-sm btn-outline" style="margin-top:8px;" onclick="document.getElementById('new_<?= $kind ?>').insertAdjacentHTML('beforeend','<input type=text name=new[] class=form-control style=margin-bottom:8px maxlength=120 placeholder=&quot;New category&quot;>')">＋ Add Category</button>
    </div>
    <div style="padding:0 20px 18px;"><button type="submit" class="btn btn-primary">Save Categories</button></div>
  </form>
</div>

<div class="modal-overlay" id="merge_<?= $kind ?>">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Merge <?= ucfirst($kind) ?> Categories</div><button type="button" class="modal-close" onclick="document.getElementById('merge_<?= $kind ?>').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/settings/categories/merge">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="kind" value="<?= $kind ?>">
      <div class="modal-body">
        <div style="max-height:240px;overflow:auto;border:1px solid var(--border);border-radius:8px;padding:8px;margin-bottom:12px;">
          <?php foreach ($cats[$kind] as $c): ?><label style="display:flex;gap:8px;font-size:13px;padding:3px;"><input type="checkbox" name="sources[]" value="<?= htmlspecialchars($c['name']) ?>"> <?= htmlspecialchars($c['name']) ?> <span style="color:var(--text-muted)">(<?= (int)$c['uses'] ?>)</span></label><?php endforeach; ?>
        </div>
        <div class="form-group"><label class="form-label">Keep this name</label>
          <select name="target" class="form-control"><?php foreach ($cats[$kind] as $c): ?><option><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('merge_<?= $kind ?>').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Merge</button></div>
    </form>
  </div>
</div>
<?php endforeach; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
