<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/finance';
$def = $finSettings['default_currency'];
$activeSchemes = array_values(array_filter($schemes, fn($s) => (int)$s['is_active'] === 1));
?>
<style>
.sch-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:16px; margin-bottom:18px; }
.sch-card { border:1px solid var(--border); border-radius:12px; background:var(--surface-1); padding:16px; border-top:3px solid var(--purple); }
.sch-card h3 { margin:0 0 2px; font-size:15px; }
.sch-card .who { font-size:11.5px; color:var(--text-muted); margin-bottom:10px; }
.sch-fig { display:flex; justify-content:space-between; font-size:12.5px; padding:3px 0; }
.sch-fig span:last-child { font-weight:700; white-space:nowrap; }
.sch-actions { display:flex; gap:6px; margin-top:12px; }
</style>

<div class="page-header">
  <div>
    <div class="page-header-title">Sponsorships</div>
    <div class="page-header-sub">Schemes that pay children's fees. A sponsored child is billed as usual; the sponsor's money is recorded on Fees Payment and counted here.</div>
  </div>
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <form method="GET"><select name="year" class="form-control" onchange="this.form.submit()"><?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>" <?= $year && $year['id'] == $y['id'] ? 'selected' : '' ?>><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></form>
    <button type="button" class="btn btn-secondary" onclick="schemeModal()">＋ Add Scheme</button>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('sponsorModal').classList.add('open')">＋ Sponsor a Child</button>
  </div>
</div>

<div class="sch-grid">
  <?php foreach ($schemes as $s): $t = $totals[(int)$s['id']] ?? [];
    $mine = array_filter($rows, fn($r) => (int)$r['scheme_id'] === (int)$s['id']);
    $n = count($mine); $nActive = count(array_filter($mine, fn($r) => $r['status'] === 'active')); ?>
    <div class="sch-card" style="<?= (int)$s['is_active'] ? '' : 'opacity:.6;' ?>">
      <h3><?= htmlspecialchars($s['name']) ?> <?= (int)$s['is_active'] ? '' : '<span class="badge badge-muted">Off</span>' ?></h3>
      <div class="who">
        <?= $s['contact_person'] ? htmlspecialchars($s['contact_person']) : 'No contact person' ?>
        <?= $s['phone'] ? ' · ' . htmlspecialchars($s['phone']) : '' ?><?= $s['email'] ? ' · ' . htmlspecialchars($s['email']) : '' ?>
      </div>
      <div class="sch-fig"><span>Children this year</span><span><?= $n ?><?= $n && $nActive < $n ? ' <span style="font-weight:400;color:var(--text-muted);font-size:11px;">(' . $nActive . ' active)</span>' : '' ?></span></div>
      <?php foreach ($t as $cur => $v): ?>
        <div class="sch-fig"><span>Covers<?= count($t) > 1 ? " ({$cur})" : '' ?></span><span><?= Finance::money($v['cover'], $cur) ?></span></div>
        <div class="sch-fig"><span>Paid in</span><span style="color:var(--success);"><?= Finance::money($v['paid'], $cur) ?></span></div>
        <div class="sch-fig"><span>Still to pay</span><span style="color:<?= $v['owing'] > 0.005 ? 'var(--danger)' : 'var(--success)' ?>;"><?= Finance::money($v['owing'], $cur) ?></span></div>
      <?php endforeach; ?>
      <?php if (!$t): ?><div class="sch-fig" style="color:var(--text-muted);"><span><?= $n ? 'Nothing billed to these children yet' : 'No children on this scheme yet' ?></span><span></span></div><?php endif; ?>
      <div class="sch-actions">
        <a href="<?= $base ?>/sponsorships/<?= $s['id'] ?><?= $year ? '?year=' . $year['id'] : '' ?>" class="btn btn-sm btn-secondary">Open</a>
        <button type="button" class="btn btn-sm btn-outline" onclick='schemeModal(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Sponsored Children — <?= htmlspecialchars($year['name'] ?? '') ?> (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Scheme</th><th>Covers</th><th style="text-align:right;">Cover value</th><th style="text-align:right;">Sponsor paid</th><th style="text-align:right;">Sponsor owes</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr style="<?= $r['status'] === 'ended' ? 'opacity:.55;' : '' ?>">
            <td class="fw-600"><a href="<?= $base ?>/fees-payment?student=<?= (int)$r['student_id'] ?>&year=<?= (int)$r['academic_year_id'] ?>"><?= htmlspecialchars($r['student_name']) ?></a>
              <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?></div></td>
            <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['scheme_name']) ?></td>
            <td><?= htmlspecialchars(Finance::coverLabel($r, $def)) ?></td>
            <?php foreach (['cover', 'paid', 'owing'] as $k): ?>
              <td style="text-align:right;white-space:nowrap;<?= $k === 'owing' ? 'font-weight:600;' : '' ?>">
                <?php foreach ($r['totals'] as $cur => $v): ?><div style="<?= $k === 'owing' && $v[$k] > 0.005 ? 'color:var(--danger);' : '' ?>"><?= Finance::money($v[$k], $cur) ?></div><?php endforeach; ?>
                <?php if (!$r['totals']): ?>—<?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td><span class="badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-muted' ?>"><?= ucfirst($r['status']) ?></span></td>
            <td style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-outline" onclick='sponsorModal(<?= json_encode([
                'scheme_id' => (int)$r['scheme_id'], 'student_id' => (int)$r['student_id'], 'cover_type' => $r['cover_type'],
                'cover_value' => (float)$r['cover_value'], 'currency' => $r['currency'], 'reference' => $r['reference'], 'notes' => $r['notes'],
              ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
              <?php if ($r['status'] === 'active'): ?>
              <form method="POST" action="<?= $base ?>/sponsorships/<?= (int)$r['id'] ?>/end" data-confirm="End <?= htmlspecialchars($r['student_name']) ?>'s sponsorship under <?= htmlspecialchars($r['scheme_name']) ?>? Money already received stays on record." data-confirm-title="End Sponsorship" data-confirm-label="End">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-sm btn-danger">End</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:26px;">No children are sponsored for this year yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Sponsor a child -->
<div class="modal-overlay" id="sponsorModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">Sponsor a Child</div>
      <button class="modal-close" onclick="document.getElementById('sponsorModal').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/sponsorships/sponsor">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="academic_year_id" value="<?= (int)($year['id'] ?? 0) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Scheme *</label>
            <select name="scheme_id" id="sp_scheme" class="form-control" required>
              <?php foreach ($activeSchemes as $s): ?><option value="<?= $s['id'] ?>" <?= $schemeId === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Student *</label>
            <select name="student_id" id="sp_student" class="form-control" required>
              <option value="">Choose a student</option>
              <?php foreach ($students as $st): ?><option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?> — <?= htmlspecialchars($st['admission_no']) ?></option><?php endforeach; ?>
            </select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">What the scheme covers *</label>
            <select name="cover_type" id="sp_type" class="form-control" onchange="coverFields()">
              <option value="full">All of this year's fees</option>
              <option value="percent">A share of the fees (%)</option>
              <option value="amount">A set amount for the year</option>
            </select></div>
          <div class="form-group" id="sp_valueWrap" style="display:none;"><label class="form-label" id="sp_valueLabel">Value</label>
            <input type="number" step="0.01" min="0" name="cover_value" id="sp_value" class="form-control"></div>
        </div>
        <div class="form-row" id="sp_curWrap" style="display:none;">
          <div class="form-group"><label class="form-label">Currency</label>
            <select name="currency" class="form-control"><?php foreach ($currencies as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?></select></div>
          <div class="form-group"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Sponsor's reference</label><input type="text" name="reference" id="sp_ref" class="form-control" placeholder="Child ID with the sponsor, if any"></div>
          <div class="form-group"><label class="form-label">Note</label><input type="text" name="notes" id="sp_notes" class="form-control"></div>
        </div>
        <div class="form-hint">The child keeps their normal bills. When the scheme sends money, record it on Fees Payment and choose the scheme under "Received from".</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('sponsorModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Add / edit a scheme -->
<div class="modal-overlay" id="schemeModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title" id="sc_title">Add Scheme</div>
      <button class="modal-close" onclick="document.getElementById('schemeModal').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/sponsorships/schemes/save">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="id" id="sc_id" value="">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Scheme name *</label><input type="text" name="name" id="sc_name" class="form-control" required></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Contact person</label><input type="text" name="contact_person" id="sc_contact" class="form-control"></div>
          <div class="form-group"><label class="form-label">Phone</label><input type="text" name="phone" id="sc_phone" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" id="sc_email" class="form-control"></div>
          <div class="form-group"><label class="form-label">Note</label><input type="text" name="notes" id="sc_notes" class="form-control"></div>
        </div>
        <label style="display:flex;gap:8px;align-items:center;font-size:13px;"><input type="checkbox" name="is_active" id="sc_active" value="1" checked> In use</label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('schemeModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Scheme</button>
      </div>
    </form>
  </div>
</div>

<script>
function coverFields() {
  var t = document.getElementById('sp_type').value;
  document.getElementById('sp_valueWrap').style.display = t === 'full' ? 'none' : '';
  document.getElementById('sp_curWrap').style.display = t === 'amount' ? '' : 'none';
  document.getElementById('sp_value').required = t !== 'full';
  document.getElementById('sp_valueLabel').textContent = t === 'percent' ? 'Share of the fees (%)' : 'Amount for the year';
}
function sponsorModal(s) {
  if (s) {
    document.getElementById('sp_scheme').value = s.scheme_id;
    document.getElementById('sp_student').value = s.student_id;
    document.getElementById('sp_type').value = s.cover_type;
    document.getElementById('sp_value').value = s.cover_type === 'full' ? '' : s.cover_value;
    document.getElementById('sp_ref').value = s.reference || '';
    document.getElementById('sp_notes').value = s.notes || '';
  }
  coverFields();
  document.getElementById('sponsorModal').classList.add('open');
}
function schemeModal(s) {
  document.getElementById('sc_title').textContent = s ? 'Edit Scheme' : 'Add Scheme';
  document.getElementById('sc_id').value = s ? s.id : '';
  document.getElementById('sc_name').value = s ? s.name : '';
  document.getElementById('sc_contact').value = (s && s.contact_person) || '';
  document.getElementById('sc_phone').value = (s && s.phone) || '';
  document.getElementById('sc_email').value = (s && s.email) || '';
  document.getElementById('sc_notes').value = (s && s.notes) || '';
  document.getElementById('sc_active').checked = s ? Number(s.is_active) === 1 : true;
  document.getElementById('schemeModal').classList.add('open');
}
coverFields();
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
