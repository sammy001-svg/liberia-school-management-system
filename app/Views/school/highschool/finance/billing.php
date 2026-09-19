<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$tab = ($_GET['tab'] ?? 'new') === 'old' ? 'old' : 'new';
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance/billing';
?>
<style>
  .bill-table input.form-control, .bill-table select.form-control { padding:6px 8px; font-size:13px; min-width:0; }
  .bill-table td { vertical-align: top; }
  .type-pick { position: relative; }
  .type-pick summary { list-style:none; cursor:pointer; padding:6px 10px; border:1px solid var(--border); border-radius:8px; font-size:12.5px; background:var(--bg-input); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:190px; }
  .type-pick summary::-webkit-details-marker { display:none; }
  .type-pick[open] .type-menu { display:block; }
  .type-menu { display:none; position:absolute; z-index:20; top:calc(100% + 4px); left:0; min-width:220px; background:var(--bg-card); border:1px solid var(--border); border-radius:10px; padding:8px; box-shadow:0 12px 30px rgba(0,0,0,.35); }
  .type-menu label { display:flex; gap:8px; align-items:center; padding:5px 6px; font-size:12.5px; cursor:pointer; border-radius:6px; }
  .type-menu label:hover { background:var(--surface-1); }
  .tabs-line { display:flex; gap:4px; border-bottom:1px solid var(--border); margin-bottom:0; }
  .tabs-line a { padding:10px 16px; font-size:13px; font-weight:600; color:var(--text-muted); border-bottom:2px solid transparent; }
  .tabs-line a.active { color:var(--primary); border-bottom-color:var(--primary); }
</style>

<div class="page-header">
  <div>
    <div class="page-header-title">Billing Setup<?= $year ? ' — ' . htmlspecialchars($year['name']) : '' ?><?= $class ? ' · ' . htmlspecialchars($class['name']) : '' ?></div>
    <div class="page-header-sub">What each class pays this year, per installment, for new and old students and each student type. Saving updates every enrolled student’s bills.</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <button type="button" class="btn btn-outline" onclick="openModal('typesModal')">⚙ Student Types</button>
    <button type="button" class="btn btn-outline" onclick="openModal('mergeModal')">Merge Descriptions</button>
    <button type="button" class="btn btn-secondary" onclick="openModal('carryModal')">Copy From Previous Year</button>
  </div>
</div>

<?php if (!$year): ?>
  <div class="alert alert-warning">Create an academic year first (Academics → Academic Years) and mark it as current.</div>
<?php endif; ?>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:16px;">
  <div style="display:flex;gap:14px;flex-wrap:wrap;align-items:flex-end;">
    <div class="form-group" style="margin:0;min-width:180px;">
      <label class="form-label">Academic Year</label>
      <select name="year" class="form-control" onchange="this.form.submit()">
        <?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year && $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?><?= $y['is_current'] ? ' (current)' : '' ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group" style="margin:0;min-width:200px;">
      <label class="form-label">Class</label>
      <select name="class" class="form-control" onchange="this.form.submit()">
        <option value="">— Select Class —</option>
        <?php foreach ($classes as $c): ?><option value="<?= $c['id'] ?>" <?= $class && $class['id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if ($class): ?>
      <div style="font-size:12.5px;color:var(--text-muted);padding-bottom:8px;"><?= (int)$enrolled ?> student(s) enrolled in this class for <?= htmlspecialchars($year['name']) ?>.</div>
    <?php endif; ?>
  </div>
</form>

<?php if ($class && $year): ?>
  <?php if ($typeTotals): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title">Year Total per Student Type</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student Type</th><th style="text-align:right;">New Students</th><th style="text-align:right;">Old Students</th></tr></thead>
        <tbody>
          <?php foreach ($typeTotals as $typeName => $byCat): ?>
            <tr>
              <td class="fw-600"><?= htmlspecialchars($typeName) ?></td>
              <?php foreach (['new', 'old'] as $cat): ?>
                <td style="text-align:right;"><?= !empty($byCat[$cat]) ? implode('<br>', array_map(fn($cur, $amt) => Finance::money($amt, $cur), array_keys($byCat[$cat]), $byCat[$cat])) : '<span style="color:var(--text-muted)">—</span>' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="tabs-line" style="padding:0 12px;">
      <?php foreach (['new' => 'New Students', 'old' => 'Old Students'] as $k => $label): ?>
        <a href="<?= $base ?>?year=<?= $year['id'] ?>&class=<?= $class['id'] ?>&tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= $label ?> (<?= count($bills[$k]) ?>)</a>
      <?php endforeach; ?>
    </div>
    <form method="POST" action="<?= $base ?>/save" id="billForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?>">
      <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
      <input type="hidden" name="category" value="<?= $tab ?>">
      <div class="table-wrapper">
        <table class="bill-table">
          <thead><tr><th style="min-width:230px;">Description</th><th style="width:130px;">Amount</th><th style="width:90px;">Currency</th><th style="width:150px;">Pay From</th><th style="width:150px;">Pay By</th><th style="width:200px;">Student Types</th><th style="width:60px;" title="Charge once per year">Once</th><th style="width:44px;"></th></tr></thead>
          <tbody id="billRows">
            <?php foreach ($bills[$tab] as $i => $b): ?>
              <?php require __DIR__ . '/partials/bill_row.php'; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:14px 16px;border-top:1px solid var(--border);">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
          <button type="button" class="btn btn-outline" onclick="addBillRow()">＋ Add Bill</button>
          <button type="button" class="btn btn-outline" onclick="openModal('copyModal')">Copy Bills…</button>
        </div>
        <button type="submit" class="btn btn-primary">✓ Save Billing</button>
      </div>
    </form>
  </div>

  <template id="billRowTpl">
    <?php $b = ['id' => '', 'description' => '', 'amount' => '', 'currency' => $finSettings['default_currency'], 'start_date' => '', 'end_date' => '', 'applies_all' => 1, 'type_ids' => [], 'once_per_year' => 0]; $i = '__I__'; ?>
    <?php require __DIR__ . '/partials/bill_row.php'; ?>
  </template>
<?php elseif ($year): ?>
  <div class="card"><div class="empty-state"><div class="empty-state-icon">🧾</div><div class="empty-state-text">Select a class to set up its bills for <?= htmlspecialchars($year['name']) ?>.</div></div></div>
<?php endif; ?>

<!-- Copy bills -->
<?php if ($class && $year): ?>
<div class="modal-overlay" id="copyModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Copy Bills from <?= htmlspecialchars($class['name']) ?></div><button type="button" class="modal-close" onclick="closeModal('copyModal')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/copy">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?>">
      <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
      <input type="hidden" name="category" value="<?= $tab ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">What to copy</label>
          <select name="scope" class="form-control" onchange="document.getElementById('copyCatWrap').style.display=this.value==='class'?'none':''">
            <option value="category">Only the <?= $tab === 'new' ? 'New' : 'Old' ?> Students bills</option>
            <option value="class">Entire class (New + Old Students)</option>
          </select></div>
        <div class="form-group" id="copyCatWrap"><label class="form-label">Copy into category</label>
          <select name="target_category" class="form-control"><option value="new" <?= $tab === 'new' ? 'selected' : '' ?>>New Students</option><option value="old" <?= $tab === 'old' ? 'selected' : '' ?>>Old Students</option></select></div>
        <div class="form-group"><label class="form-label">Copy to class(es)</label>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:6px;max-height:220px;overflow:auto;border:1px solid var(--border);border-radius:8px;padding:10px;">
            <?php foreach ($classes as $c): ?><label style="display:flex;gap:6px;font-size:13px;"><input type="checkbox" name="target_classes[]" value="<?= $c['id'] ?>"> <?= htmlspecialchars($c['name']) ?></label><?php endforeach; ?>
          </div></div>
        <label style="display:flex;gap:8px;font-size:13px;"><input type="checkbox" name="replace" value="1"> Replace the bills already in those classes (otherwise only missing bills are added)</label>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('copyModal')">Cancel</button><button type="submit" class="btn btn-primary">Copy Bills</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Carry forward -->
<div class="modal-overlay" id="carryModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Copy Bills From a Previous Year</div><button type="button" class="modal-close" onclick="closeModal('carryModal')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/carry-forward">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?? '' ?>">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px;">Every class’s bills are copied into <strong><?= htmlspecialchars($year['name'] ?? '') ?></strong>, with the payment dates moved forward to the new year. Bills that already exist are left as they are.</p>
        <div class="form-group"><label class="form-label">Copy from</label>
          <select name="from_year_id" class="form-control">
            <?php foreach ($years as $y): if ($year && $y['id'] == $year['id']) continue; ?><option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?>
          </select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('carryModal')">Cancel</button><button type="submit" class="btn btn-primary">Copy Bills</button></div>
    </form>
  </div>
</div>

<!-- Merge descriptions -->
<div class="modal-overlay" id="mergeModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Manage Bill Descriptions</div><button type="button" class="modal-close" onclick="closeModal('mergeModal')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/descriptions/merge">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?? '' ?>">
      <input type="hidden" name="class_id" value="<?= $class['id'] ?? '' ?>">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Tick descriptions that mean the same thing, then choose the one name they should all use. Reports group payments by description, so consistent names keep them tidy.</p>
        <div style="max-height:240px;overflow:auto;border:1px solid var(--border);border-radius:8px;padding:8px;margin-bottom:12px;">
          <?php foreach ($descriptions as $d => $n): ?>
            <label style="display:flex;gap:8px;font-size:13px;padding:4px;"><input type="checkbox" name="sources[]" value="<?= htmlspecialchars($d) ?>"> <?= htmlspecialchars($d) ?> <span style="color:var(--text-muted)">(<?= $n ?>)</span></label>
          <?php endforeach; ?>
          <?php if (!$descriptions): ?><div style="font-size:13px;color:var(--text-muted);padding:6px;">No bills in this year yet.</div><?php endif; ?>
        </div>
        <div class="form-group"><label class="form-label">New description</label><input type="text" name="target" class="form-control" maxlength="150" list="descList" placeholder="e.g. Tuition: 2nd Installment">
          <datalist id="descList"><?php foreach ($descriptions as $d => $n): ?><option value="<?= htmlspecialchars($d) ?>"><?php endforeach; ?></datalist></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('mergeModal')">Cancel</button><button type="submit" class="btn btn-primary">Merge</button></div>
    </form>
  </div>
</div>

<!-- Student types -->
<div class="modal-overlay" id="typesModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Manage Student Types</div><button type="button" class="modal-close" onclick="closeModal('typesModal')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/types">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= $year['id'] ?? '' ?>">
      <input type="hidden" name="class_id" value="<?= $class['id'] ?? '' ?>">
      <div class="modal-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Each enrolled student has one type. A bill can apply to all types or only some — for example, Proprietor Wards pay nothing.</p>
        <?php foreach ($types as $t): ?>
          <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
            <input type="text" name="types[<?= $t['id'] ?>][name]" class="form-control" value="<?= htmlspecialchars($t['name']) ?>" maxlength="80">
            <label style="display:flex;gap:6px;font-size:12.5px;white-space:nowrap;"><input type="checkbox" name="types[<?= $t['id'] ?>][active]" value="1" <?= $t['is_active'] ? 'checked' : '' ?>> In use</label>
          </div>
        <?php endforeach; ?>
        <div id="newTypes"></div>
        <button type="button" class="btn btn-sm btn-outline" onclick="addTypeRow()">＋ Add Type</button>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('typesModal')">Cancel</button><button type="submit" class="btn btn-primary">Save Types</button></div>
    </form>
  </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
let billSeq = 1000;
function addBillRow() {
  const tpl = document.getElementById('billRowTpl');
  if (!tpl) return;
  const html = tpl.innerHTML.replaceAll('__I__', 'n' + (billSeq++));
  document.getElementById('billRows').insertAdjacentHTML('beforeend', html);
  const rows = document.querySelectorAll('#billRows tr');
  const last = rows[rows.length - 1];
  wireRow(last);
  last.querySelector('input[type=text]').focus();
}
function wireRow(row) {
  const pick = row.querySelector('.type-pick');
  if (!pick) return;
  const all = pick.querySelector('input[value=all]');
  const others = [...pick.querySelectorAll('input[type=checkbox]')].filter(c => c !== all);
  const label = () => {
    const chosen = others.filter(c => c.checked).map(c => c.dataset.name);
    pick.querySelector('summary').textContent = all.checked || !chosen.length ? 'All types' : chosen.join(', ');
  };
  all.addEventListener('change', () => { if (all.checked) others.forEach(c => c.checked = false); label(); });
  others.forEach(c => c.addEventListener('change', () => { if (c.checked) all.checked = false; if (!others.some(o => o.checked)) all.checked = true; label(); }));
  label();
}
document.querySelectorAll('#billRows tr').forEach(wireRow);
document.addEventListener('click', e => { document.querySelectorAll('.type-pick[open]').forEach(d => { if (!d.contains(e.target)) d.open = false; }); });
function addTypeRow() {
  document.getElementById('newTypes').insertAdjacentHTML('beforeend', '<div style="margin-bottom:8px;"><input type="text" name="new_types[]" class="form-control" maxlength="80" placeholder="New student type"></div>');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
