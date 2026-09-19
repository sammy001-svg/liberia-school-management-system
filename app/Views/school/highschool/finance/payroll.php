<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$pbase = $cfg['url'] . '/school/payroll';
$def = $finSettings['default_currency'];
$tabs = ['overview' => 'Overview', 'staff' => 'Staff & Salaries', 'components' => 'Components', 'process' => 'Process Payroll', 'reports' => 'Reports', 'tax' => 'Tax Settings'];
$stBadge = ['calculated' => 'badge-info', 'approved' => 'badge-warning', 'paid' => 'badge-success'];
?>
<style>.tabs-line{display:flex;gap:4px;border-bottom:1px solid var(--border);margin-bottom:16px;flex-wrap:wrap}.tabs-line a{padding:10px 16px;font-size:13px;font-weight:600;color:var(--text-muted);border-bottom:2px solid transparent}.tabs-line a.active{color:var(--primary);border-bottom-color:var(--primary)}</style>
<div class="page-header">
  <div>
    <div class="page-header-title">Payroll</div>
    <div class="page-header-sub">Staff salaries, allowances, deductions and tax. Pay runs go Calculated → Approved → Paid; paid payslips post to Expenses automatically.</div>
  </div>
</div>
<div class="tabs-line"><?php foreach ($tabs as $k => $l): ?><a href="?tab=<?= $k ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= $l ?></a><?php endforeach; ?></div>

<?php if ($tab === 'overview'): ?>
  <div class="stat-grid">
    <div class="stat-card" style="--card-color:var(--blue);"><div class="stat-value"><?= $stats['staff'] ?></div><div class="stat-label">Staff on payroll</div></div>
    <div class="stat-card" style="--card-color:var(--purple);"><div class="stat-value"><?= $stats['components'] ?></div><div class="stat-label">Active components</div></div>
    <div class="stat-card" style="--card-color:var(--warning);"><div class="stat-value"><?= $stats['pending'] ?></div><div class="stat-label">Pending runs</div></div>
    <div class="stat-card" style="--card-color:var(--success);"><div class="stat-value"><?= $stats['paid'] ?></div><div class="stat-label">Paid payslips</div></div>
  </div>
  <div class="card"><div class="card-header"><div class="card-title">Getting started</div></div><div class="card-body" style="font-size:13.5px;line-height:1.9;">
    <ol style="padding-left:18px;margin:0;">
      <li><a href="?tab=components">Components</a> — switch on the allowances and deductions your school uses.</li>
      <li><a href="?tab=tax">Tax Settings</a> — add income tax / social security if you deduct any (leave empty if not).</li>
      <li><a href="?tab=staff">Staff &amp; Salaries</a> — set each staff member’s salary and how they are paid (cash, bank or mobile money). They see it on My Payroll.</li>
      <li><a href="?tab=process">Process Payroll</a> — calculate a run, approve it, then mark it paid. Paid payslips post to Expenses and the P&amp;L.</li>
    </ol>
  </div></div>
<?php endif; ?>

<?php if ($tab === 'staff'): ?>
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;"><div class="card-title">Faculty &amp; Staff</div></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Staff</th><th>Role</th><th>Department</th><th>Employment</th><th style="text-align:right;">Base Salary</th><th>Paid By</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($staff as $s): ?>
          <tr>
            <td class="fw-600"><?= htmlspecialchars($s['name']) ?><?= $s['employee_no'] ? '<br><span style="font-size:11.5px;color:var(--text-muted)">' . htmlspecialchars($s['employee_no']) . '</span>' : '' ?></td>
            <td><?= htmlspecialchars($s['position'] ?: $s['role_name']) ?></td>
            <td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
            <td style="font-size:12.5px;"><?= $s['profile_id'] ? htmlspecialchars($s['employment_type'] . ' · ' . $s['pay_frequency']) : '—' ?></td>
            <td style="text-align:right;"><?= $s['profile_id'] ? Finance::money($s['base_salary'], $s['currency']) : '<span style="color:var(--text-muted)">Not set</span>' ?></td>
            <td style="font-size:12.5px;"><?= $s['profile_id'] ? htmlspecialchars($s['payment_method'] . ($s['momo_provider'] ? ' · ' . $s['momo_provider'] : '')) : '—' ?></td>
            <td><?php if (!$s['profile_id']): ?><span class="badge badge-muted">Not set up</span><?php else: ?><span class="badge <?= $s['is_enabled'] ? 'badge-success' : 'badge-muted' ?>"><?= $s['is_enabled'] ? 'On payroll' : 'Paused' ?></span><?php endif; ?></td>
            <td><button type="button" class="btn btn-sm btn-secondary" onclick='editProfile(<?= json_encode(array_merge($s, ['overrides' => $overrides[(int)$s['profile_id']] ?? []]), JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><?= $s['profile_id'] ? 'Edit' : 'Set Up' ?></button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="modal-overlay" id="profileModal">
    <div class="modal modal-lg">
      <div class="modal-header"><div class="modal-title" id="pmTitle">Payroll Details</div><button type="button" class="modal-close" onclick="document.getElementById('profileModal').classList.remove('open')">&times;</button></div>
      <form method="POST" action="<?= $pbase ?>/profiles/save">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="user_id" id="pm_user_id">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group"><label class="form-label">Department</label><input type="text" name="department" id="pm_department" class="form-control" maxlength="100"></div>
            <div class="form-group"><label class="form-label">Employment Type</label><select name="employment_type" id="pm_employment_type" class="form-control"><?php foreach ($consts['types'] as $v): ?><option><?= $v ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Employment Period</label><select name="employment_period" id="pm_employment_period" class="form-control"><?php foreach ($consts['periods'] as $v): ?><option><?= $v ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Hire Date</label><input type="date" name="hire_date" id="pm_hire_date" class="form-control"></div>
            <div class="form-group"><label class="form-label">Contract Start</label><input type="date" name="contract_start" id="pm_contract_start" class="form-control"></div>
            <div class="form-group"><label class="form-label">Contract End</label><input type="date" name="contract_end" id="pm_contract_end" class="form-control"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Base Salary (per pay period) *</label>
              <div style="display:flex;gap:6px;"><select name="currency" id="pm_currency" class="form-control" style="max-width:90px;"><?php foreach ($currencies as $c): ?><option><?= $c ?></option><?php endforeach; ?></select>
              <input type="number" name="base_salary" id="pm_base_salary" class="form-control" min="0" step="0.01" required></div></div>
            <div class="form-group"><label class="form-label">Payment Frequency</label><select name="pay_frequency" id="pm_pay_frequency" class="form-control"><?php foreach ($consts['freq'] as $v): ?><option><?= $v ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Preferred Payment Method</label><select name="payment_method" id="pm_payment_method" class="form-control" onchange="pmMethod()"><?php foreach ($consts['methods'] as $v): ?><option><?= $v ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="form-row" id="pmBank">
            <div class="form-group"><label class="form-label">Bank</label><input type="text" name="bank_name" id="pm_bank_name" class="form-control" maxlength="100"></div>
            <div class="form-group"><label class="form-label">Account Number</label><input type="text" name="bank_account" id="pm_bank_account" class="form-control" maxlength="60"></div>
          </div>
          <div class="form-row" id="pmMomo">
            <div class="form-group"><label class="form-label">Mobile Money Provider</label><select name="momo_provider" id="pm_momo_provider" class="form-control"><option value="">— Select —</option><?php foreach ($consts['momo'] as $v): ?><option><?= $v ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Mobile Money Number</label><input type="text" name="momo_number" id="pm_momo_number" class="form-control" maxlength="30"></div>
          </div>
          <?php if ($components): ?>
            <div class="form-label" style="margin-top:6px;">Allowances &amp; Deductions for this staff member</div>
            <div class="form-hint" style="margin-bottom:8px;">“All staff” components apply automatically; tick a “Specific staff” one to apply it. Leave the amount blank to use the default.</div>
            <div style="display:grid;grid-template-columns:auto 1fr 140px;gap:6px 12px;align-items:center;font-size:13px;">
              <?php foreach ($components as $c): ?>
                <input type="checkbox" name="comp[<?= $c['id'] ?>][on]" value="1" id="pmc_<?= $c['id'] ?>" <?= $c['applies_to'] === 'all' ? 'checked disabled' : '' ?>>
                <label for="pmc_<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> <span style="color:var(--text-muted);">(<?= $c['kind'] ?>, <?= $c['applies_to'] === 'all' ? 'all staff' : 'specific' ?><?= $c['is_active'] ? '' : ', inactive' ?>)</span></label>
                <input type="number" name="comp[<?= $c['id'] ?>][value]" id="pmv_<?= $c['id'] ?>" class="form-control" step="0.01" min="0" placeholder="<?= $c['calc'] === 'percent' ? rtrim(rtrim($c['default_value'], '0'), '.') . '%' : number_format($c['default_value'], 2) ?>">
                <?php if ($c['applies_to'] === 'all'): ?><input type="hidden" name="comp[<?= $c['id'] ?>][on]" value="1"><?php endif; ?>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <label style="display:flex;gap:8px;margin-top:14px;font-size:13px;"><input type="checkbox" name="is_enabled" id="pm_is_enabled" value="1" checked> Enable payroll processing for this staff member</label>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('profileModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
  <script>
  function pmMethod() { const m = document.getElementById('pm_payment_method').value; document.getElementById('pmBank').style.display = m === 'Bank' ? '' : 'none'; document.getElementById('pmMomo').style.display = m === 'Mobile Money' ? '' : 'none'; }
  function editProfile(s) {
    document.getElementById('pmTitle').textContent = 'Payroll Details — ' + s.name;
    document.getElementById('pm_user_id').value = s.user_id;
    ['department', 'hire_date', 'contract_start', 'contract_end', 'base_salary', 'bank_name', 'bank_account', 'momo_number'].forEach(k => document.getElementById('pm_' + k).value = s[k] || '');
    [['employment_type', 'Full Time'], ['employment_period', '12 Months'], ['pay_frequency', 'Monthly'], ['payment_method', 'Cash'], ['currency', '<?= $def ?>'], ['momo_provider', '']].forEach(([k, d]) => document.getElementById('pm_' + k).value = s[k] || d);
    document.getElementById('pm_is_enabled').checked = !s.profile_id || String(s.is_enabled) === '1';
    document.querySelectorAll('[id^=pmc_]').forEach(c => { if (!c.disabled) c.checked = false; });
    document.querySelectorAll('[id^=pmv_]').forEach(v => v.value = '');
    Object.entries(s.overrides || {}).forEach(([cid, val]) => { const c = document.getElementById('pmc_' + cid); if (c && !c.disabled) c.checked = true; const v = document.getElementById('pmv_' + cid); if (v && val !== null) v.value = val; });
    pmMethod();
    document.getElementById('profileModal').classList.add('open');
  }
  </script>
<?php endif; ?>

<?php if ($tab === 'components'): ?>
  <div class="alert alert-info">Inactive components do <strong>not</strong> affect payroll. Activate the ones your school uses.</div>
  <?php foreach (['allowance' => 'Allowances', 'deduction' => 'Deductions'] as $kind => $label): ?>
    <div class="card" style="margin-bottom:16px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;"><div class="card-title"><?= $label ?></div>
        <button type="button" class="btn btn-sm btn-primary" onclick='editComp({kind:"<?= $kind ?>"})'>＋ Add <?= rtrim($label, 's') ?></button></div>
      <div class="table-wrapper"><table>
        <thead><tr><th>Name</th><th>Type</th><th>Default</th><th>Applies To</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach (array_filter($components, fn($c) => $c['kind'] === $kind) as $c): ?>
            <tr><td class="fw-600"><?= htmlspecialchars($c['name']) ?><?= $c['is_taxable'] ? ' <span class="badge badge-muted">Taxable</span>' : '' ?></td>
              <td><?= $c['calc'] === 'percent' ? 'Percentage' : 'Fixed amount' ?></td>
              <td><?= $c['calc'] === 'percent' ? rtrim(rtrim($c['default_value'], '0'), '.') . '%' : Finance::money($c['default_value'], $def) ?></td>
              <td><?= $c['applies_to'] === 'all' ? 'All staff' : 'Specific staff' ?></td>
              <td><span class="badge <?= $c['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $c['is_active'] ? 'Active' : 'Inactive' ?></span></td>
              <td><div style="display:flex;gap:6px;"><button type="button" class="btn btn-sm btn-secondary" onclick='editComp(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                <form method="POST" action="<?= $pbase ?>/components/<?= $c['id'] ?>/delete" data-confirm="Delete <?= htmlspecialchars($c['name']) ?>?" data-confirm-label="Delete"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><button class="btn btn-sm btn-outline">Delete</button></form></div></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    </div>
  <?php endforeach; ?>
  <div class="modal-overlay" id="compModal">
    <div class="modal">
      <div class="modal-header"><div class="modal-title">Payroll Component</div><button type="button" class="modal-close" onclick="document.getElementById('compModal').classList.remove('open')">&times;</button></div>
      <form method="POST" action="<?= $pbase ?>/components/save">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="id" id="c_id">
        <div class="modal-body">
          <div class="form-group"><label class="form-label">Component Name *</label><input type="text" name="name" id="c_name" class="form-control" maxlength="100" required></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Type</label><select name="kind" id="c_kind" class="form-control"><option value="allowance">Allowance</option><option value="deduction">Deduction</option></select></div>
            <div class="form-group"><label class="form-label">Calculation</label><select name="calc" id="c_calc" class="form-control"><option value="fixed">Fixed Amount</option><option value="percent">Percentage of pay</option></select></div>
            <div class="form-group"><label class="form-label">Default (amount or %)</label><input type="number" name="default_value" id="c_default_value" class="form-control" min="0" step="0.01"></div>
          </div>
          <div class="form-group"><label class="form-label">Applies To</label><select name="applies_to" id="c_applies_to" class="form-control"><option value="all">All Staff</option><option value="specific">Specific Staff (set on each staff member)</option></select></div>
          <label style="display:flex;gap:8px;font-size:13px;"><input type="checkbox" name="is_taxable" id="c_is_taxable" value="1"> Taxable (allowances only)</label>
          <label style="display:flex;gap:8px;font-size:13px;margin-top:8px;"><input type="checkbox" name="is_active" id="c_is_active" value="1"> Active</label>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('compModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
  <script>
  function editComp(c) {
    document.getElementById('c_id').value = c.id || '';
    document.getElementById('c_name').value = c.name || '';
    document.getElementById('c_kind').value = c.kind || 'allowance';
    document.getElementById('c_calc').value = c.calc || 'fixed';
    document.getElementById('c_default_value').value = c.default_value || '';
    document.getElementById('c_applies_to').value = c.applies_to || 'all';
    document.getElementById('c_is_taxable').checked = String(c.is_taxable) === '1';
    document.getElementById('c_is_active').checked = c.id ? String(c.is_active) === '1' : true;
    document.getElementById('compModal').classList.add('open');
  }
  </script>
<?php endif; ?>

<?php if ($tab === 'process'): $ready = array_filter($staff, fn($s) => $s['profile_id'] && $s['is_enabled']); ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title">Process Payroll</div></div>
    <form method="POST" action="<?= $pbase ?>/calculate">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="card-body">
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px;">Basic salary (prorated for the period) + active allowances = <b>Gross</b>. Gross − (tax + deductions) = <b>Net</b>. Records are created as <b>Calculated</b> and need approval before payment. Re-running a period recalculates records that aren’t approved yet.</p>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Pay Period Start *</label><input type="date" name="pay_period_start" class="form-control" value="<?= date('Y-m-01') ?>" required></div>
          <div class="form-group"><label class="form-label">Pay Period End *</label><input type="date" name="pay_period_end" class="form-control" value="<?= date('Y-m-t') ?>" required></div>
          <div class="form-group"><label class="form-label">Tax Calculation</label><label style="display:flex;gap:8px;font-size:13px;margin-top:10px;"><input type="checkbox" name="include_taxes" value="1"> Include tax calculations</label>
            <?php if (!$hasTax): ?><div class="form-hint">No tax settings yet — if included, a default 2% Social Security is applied.</div><?php endif; ?></div>
        </div>
      </div>
      <div class="table-wrapper"><table>
        <thead><tr><th style="width:36px;"><input type="checkbox" onclick="document.querySelectorAll('.pp-pick').forEach(c=>c.checked=this.checked)" checked></th><th>Staff</th><th>Department</th><th>Employment</th><th style="text-align:right;">Base Salary</th><th>Last Processed</th></tr></thead>
        <tbody>
          <?php foreach ($ready as $s): ?>
            <tr><td><input type="checkbox" class="pp-pick" name="profiles[]" value="<?= $s['profile_id'] ?>" checked></td><td class="fw-600"><?= htmlspecialchars($s['name']) ?></td><td><?= htmlspecialchars($s['department'] ?? '—') ?></td>
              <td style="font-size:12.5px;"><?= htmlspecialchars($s['employment_type'] . ' · ' . $s['pay_frequency']) ?></td><td style="text-align:right;"><?= Finance::money($s['base_salary'], $s['currency']) ?></td>
              <td style="font-size:12.5px;"><?= !empty($lastRun[$s['profile_id']]) ? date('M d, Y', strtotime($lastRun[$s['profile_id']])) : 'Never' ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$ready): ?><tr><td colspan="6"><div class="empty-state"><div class="empty-state-text">No staff members with payroll set up. <a href="?tab=staff">Set up staff salaries first.</a></div></div></td></tr><?php endif; ?>
        </tbody>
      </table></div>
      <?php if ($ready): ?><div style="padding:14px 16px;"><button type="submit" class="btn btn-primary">Calculate Selected Payroll</button></div><?php endif; ?>
    </form>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Awaiting Approval / Payment</div></div>
    <form method="POST" action="<?= $pbase ?>/action" id="runForm">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="tab" value="process"><input type="hidden" name="act" id="runAct">
      <div class="table-wrapper"><table>
        <thead><tr><th style="width:36px;"><input type="checkbox" onclick="document.querySelectorAll('.pr-pick').forEach(c=>c.checked=this.checked)"></th><th>Staff</th><th>Period</th><th style="text-align:right;">Gross</th><th style="text-align:right;">Deductions + Tax</th><th style="text-align:right;">Net Pay</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
            <tr><td><input type="checkbox" class="pr-pick" name="ids[]" value="<?= $r['id'] ?>"></td><td class="fw-600"><?= htmlspecialchars($r['name']) ?></td>
              <td style="font-size:12.5px;"><?= date('M j', strtotime($r['period_start'])) ?> – <?= date('M j, Y', strtotime($r['period_end'])) ?></td>
              <td style="text-align:right;"><?= Finance::money($r['gross_pay'], $r['currency']) ?></td><td style="text-align:right;"><?= Finance::money((float)$r['deductions'] + (float)$r['tax'], $r['currency']) ?></td>
              <td style="text-align:right;font-weight:700;"><?= Finance::money($r['net_pay'], $r['currency']) ?></td><td><span class="badge <?= $stBadge[$r['status']] ?>"><?= ucfirst($r['status']) ?></span></td>
              <td><a href="<?= $pbase ?>/payslips/<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline">Payslip</a></td></tr>
          <?php endforeach; ?>
          <?php if (!$recent): ?><tr><td colspan="8" style="color:var(--text-muted);">No calculated or approved records waiting.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
      <?php if ($recent): ?>
        <div style="padding:14px 16px;display:flex;gap:8px;flex-wrap:wrap;">
          <?php if ($canApprove): ?>
            <button type="button" class="btn btn-success" onclick="runAct('approve')">Approve Selected</button>
            <button type="button" class="btn btn-primary" onclick="runAct('pay')">Mark Selected Paid</button>
          <?php endif; ?>
          <button type="button" class="btn btn-outline" onclick="runAct('delete')">Delete Selected</button>
        </div>
      <?php endif; ?>
    </form>
  </div>
  <script>
  function runAct(a) {
    const msg = { approve: 'Approve the selected payroll records?', pay: 'Mark the selected approved records as paid? Each one is posted to Expenses (Salaries).', delete: 'Delete the selected records (paid ones are kept)?' }[a];
    if (!document.querySelector('.pr-pick:checked')) { alert('Select at least one record.'); return; }
    if (!confirm(msg)) return;
    document.getElementById('runAct').value = a; document.getElementById('runForm').submit();
  }
  </script>
<?php endif; ?>

<?php if ($tab === 'reports'): $tot = []; foreach ($records as $r) { $c = $r['currency']; $tot[$c] ??= [0, 0, 0]; $tot[$c][0] += $r['gross_pay']; $tot[$c][1] += (float)$r['deductions'] + (float)$r['tax']; $tot[$c][2] += $r['net_pay']; } ?>
  <form method="GET" class="card" style="padding:14px 18px;margin-bottom:16px;">
    <input type="hidden" name="tab" value="reports">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
      <input type="date" name="from" class="form-control" style="max-width:160px;" value="<?= htmlspecialchars($filters['from']) ?>"><span style="font-size:12px;color:var(--text-muted);">to</span>
      <input type="date" name="to" class="form-control" style="max-width:160px;" value="<?= htmlspecialchars($filters['to']) ?>">
      <select name="status" class="form-control" style="max-width:150px;"><option value="">All Statuses</option><?php foreach (['calculated', 'approved', 'paid'] as $st): ?><option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option><?php endforeach; ?></select>
      <select name="staff" class="form-control" style="max-width:190px;"><option value="">All Staff</option><?php foreach ($staffList as $s): ?><option value="<?= $s['id'] ?>" <?= $filters['staff'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select>
      <button type="submit" class="btn btn-secondary">Apply Filters</button>
      <a href="?tab=reports" class="btn btn-outline">Reset</a>
      <a href="<?= $pbase ?>/export?<?= http_build_query(array_filter($filters)) ?>" class="btn btn-outline">⬇ Export CSV</a>
    </div>
  </form>
  <div class="stat-grid">
    <div class="stat-card" style="--card-color:var(--blue);"><div class="stat-value"><?= count($records) ?></div><div class="stat-label">Total records</div></div>
    <?php foreach ($tot as $c => [$g, $d, $n]): ?><div class="stat-card" style="--card-color:var(--success);"><div class="stat-value" style="font-size:20px;"><?= Finance::money($n, $c) ?></div><div class="stat-label">Net pay · gross <?= Finance::money($g, $c) ?></div></div><?php endforeach; ?>
  </div>
  <div class="card"><div class="table-wrapper"><table>
    <thead><tr><th>Staff</th><th>Department</th><th>Pay Period</th><th style="text-align:right;">Gross</th><th style="text-align:right;">Deductions</th><th style="text-align:right;">Net Pay</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($records as $r): ?>
        <tr><td class="fw-600"><?= htmlspecialchars($r['name']) ?></td><td><?= htmlspecialchars($r['department'] ?? '—') ?></td><td style="font-size:12.5px;"><?= date('M j', strtotime($r['period_start'])) ?> – <?= date('M j, Y', strtotime($r['period_end'])) ?></td>
          <td style="text-align:right;"><?= Finance::money($r['gross_pay'], $r['currency']) ?></td><td style="text-align:right;"><?= Finance::money((float)$r['deductions'] + (float)$r['tax'], $r['currency']) ?></td>
          <td style="text-align:right;font-weight:700;"><?= Finance::money($r['net_pay'], $r['currency']) ?></td><td><span class="badge <?= $stBadge[$r['status']] ?>"><?= ucfirst($r['status']) ?></span></td>
          <td><a href="<?= $pbase ?>/payslips/<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-outline">Payslip</a></td></tr>
      <?php endforeach; ?>
      <?php if (!$records): ?><tr><td colspan="8" style="color:var(--text-muted);">No payroll records.</td></tr><?php endif; ?>
    </tbody>
  </table></div></div>
<?php endif; ?>

<?php if ($tab === 'tax'): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;"><div class="card-title">Current Tax Settings</div><button type="button" class="btn btn-sm btn-primary" onclick="editTax({})">＋ Add Tax Setting</button></div>
    <div class="table-wrapper"><table>
      <thead><tr><th>Name</th><th>Type</th><th>Rate / Amount</th><th>Applies To</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($taxes as $t): ?>
          <tr><td class="fw-600"><?= htmlspecialchars($t['name']) ?></td><td><?= $t['calc'] === 'percent' ? 'Percentage' : 'Fixed amount' ?></td>
            <td><?= $t['calc'] === 'percent' ? rtrim(rtrim($t['value'], '0'), '.') . '%' : Finance::money($t['value'], $t['currency']) ?></td>
            <td style="font-size:12.5px;"><?= $t['applies_to'] === 'all' ? 'All staff' : ($t['applies_to'] === 'range' ? 'Salary ' . ($t['min_salary'] !== null ? 'from ' . number_format($t['min_salary']) : '') . ($t['max_salary'] !== null ? ' up to ' . number_format($t['max_salary']) : '') : htmlspecialchars((string)$t['employment_types'])) ?></td>
            <td><span class="badge <?= $t['is_active'] ? 'badge-success' : 'badge-muted' ?>"><?= $t['is_active'] ? 'Active' : 'Off' ?></span></td>
            <td><div style="display:flex;gap:6px;"><button type="button" class="btn btn-sm btn-secondary" onclick='editTax(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $pbase ?>/taxes/<?= $t['id'] ?>/delete" data-confirm="Delete this tax setting?" data-confirm-label="Delete"><input type="hidden" name="csrf_token" value="<?= $csrf ?>"><button class="btn btn-sm btn-outline">Delete</button></form></div></td></tr>
        <?php endforeach; ?>
        <?php if (!$taxes): ?><tr><td colspan="6"><div class="empty-state"><div class="empty-state-text">No tax settings configured. Payroll will not deduct tax unless you tick “Include tax calculations” when processing (a default 2% Social Security then applies).</div></div></td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <div class="modal-overlay" id="taxModal">
    <div class="modal">
      <div class="modal-header"><div class="modal-title">Tax Setting</div><button type="button" class="modal-close" onclick="document.getElementById('taxModal').classList.remove('open')">&times;</button></div>
      <form method="POST" action="<?= $pbase ?>/taxes/save">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>"><input type="hidden" name="id" id="t_id">
        <div class="modal-body">
          <div class="form-group"><label class="form-label">Tax Name *</label><input type="text" name="name" id="t_name" class="form-control" maxlength="100" required placeholder="e.g. Income Tax"></div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Type</label><select name="calc" id="t_calc" class="form-control"><option value="percent">Percentage</option><option value="fixed">Fixed Amount</option></select></div>
            <div class="form-group"><label class="form-label">Rate (%) or Amount *</label><input type="number" name="value" id="t_value" class="form-control" min="0" step="0.01" required></div>
            <div class="form-group"><label class="form-label">Currency</label><select name="currency" id="t_currency" class="form-control"><?php foreach ($currencies as $c): ?><option><?= $c ?></option><?php endforeach; ?></select></div>
          </div>
          <div class="form-group"><label class="form-label">Applies To</label><select name="applies_to" id="t_applies_to" class="form-control" onchange="taxApplies()"><option value="all">All Staff</option><option value="range">Salary Range</option><option value="employment">Employment Type</option></select></div>
          <div class="form-row" id="tRange"><div class="form-group"><label class="form-label">Minimum Salary</label><input type="number" name="min_salary" id="t_min_salary" class="form-control" step="0.01"></div><div class="form-group"><label class="form-label">Maximum Salary (optional)</label><input type="number" name="max_salary" id="t_max_salary" class="form-control" step="0.01"></div></div>
          <div id="tTypes" style="display:flex;gap:14px;flex-wrap:wrap;font-size:13px;"><?php foreach ($consts['types'] as $v): ?><label style="display:flex;gap:6px;"><input type="checkbox" name="employment_types[]" value="<?= $v ?>" class="t-type"> <?= $v ?></label><?php endforeach; ?></div>
          <label style="display:flex;gap:8px;font-size:13px;margin-top:12px;"><input type="checkbox" name="is_active" id="t_is_active" value="1" checked> Active</label>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="document.getElementById('taxModal').classList.remove('open')">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
      </form>
    </div>
  </div>
  <script>
  function taxApplies() { const v = document.getElementById('t_applies_to').value; document.getElementById('tRange').style.display = v === 'range' ? '' : 'none'; document.getElementById('tTypes').style.display = v === 'employment' ? 'flex' : 'none'; }
  function editTax(t) {
    ['id', 'name', 'value', 'min_salary', 'max_salary'].forEach(k => document.getElementById('t_' + k).value = t[k] ?? '');
    document.getElementById('t_calc').value = t.calc || 'percent';
    document.getElementById('t_currency').value = t.currency || '<?= $def ?>';
    document.getElementById('t_applies_to').value = t.applies_to || 'all';
    document.getElementById('t_is_active').checked = !t.id || String(t.is_active) === '1';
    const types = (t.employment_types || '').split(',');
    document.querySelectorAll('.t-type').forEach(c => c.checked = types.includes(c.value));
    taxApplies();
    document.getElementById('taxModal').classList.add('open');
  }
  </script>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
