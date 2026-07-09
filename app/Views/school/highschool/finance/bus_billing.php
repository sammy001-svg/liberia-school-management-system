<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/finance">Finance</a><span>/</span><span>Bus Billing</span></div>

<div class="page-header">
  <div>
    <div class="page-header-title">Bus Billing</div>
    <div class="page-header-sub">Generate monthly invoices for students using the school bus</div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/transport/routes" class="btn btn-outline">Manage Routes</a>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="stat-label">Active Routes</div><div class="stat-value"><?= (int)$stats['totalRoutes'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--info);"><div class="stat-label">Students Riding</div><div class="stat-value"><?= (int)$stats['totalStudents'] ?></div></div>
  <div class="stat-card" style="--card-color: var(--success);"><div class="stat-label">Monthly Potential</div><div class="stat-value"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($stats['monthlyPotential'],0) ?></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">Routes (<?= count($routes) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Route</th><th>Bus</th><th>Students</th><th>Monthly Fee</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($routes as $r): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['bus_number'] ?? '—') ?></td>
          <td><span class="badge badge-info"><?= (int)$r['student_count'] ?></span></td>
          <td><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?><?= number_format($r['monthly_fee'],2) ?></td>
          <td>
            <button type="button" class="btn btn-sm btn-primary" <?= $r['student_count']==0 ? 'disabled title="No students assigned to this route"' : '' ?> onclick='openGenerateModal(<?= json_encode([
              "id"=>$r["id"], "name"=>$r["name"], "student_count"=>$r["student_count"], "fee"=>$r["monthly_fee"],
            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Generate Invoices</button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($routes)): ?>
        <tr><td colspan="5"><div class="empty-state"><div class="empty-state-icon">🚌</div><div class="empty-state-text">No active bus routes yet. <a href="<?= $cfg['url'] ?>/school/transport/routes">Set one up</a> first.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Generate Invoices Modal -->
<div class="modal-overlay" id="generateModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="generateModalTitle">Generate Bus Invoices</div>
      <button class="modal-close" onclick="document.getElementById('generateModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/finance/bus-billing/generate">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="route_id" id="generateRouteId">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;" id="generateSummary"></p>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Bill For (Month) *</label>
            <input type="month" name="month" id="generateMonth" class="form-control" required value="<?= date('Y-m') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" class="form-control">
          </div>
        </div>
        <div class="form-hint">One invoice is created per student currently assigned to this route. Students already billed for the selected month are automatically skipped — safe to re-run.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('generateModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Generate</button>
      </div>
    </form>
  </div>
</div>

<script>
function openGenerateModal(r) {
  document.getElementById('generateRouteId').value = r.id;
  document.getElementById('generateModalTitle').textContent = 'Generate Bus Invoices — ' + r.name;
  document.getElementById('generateSummary').textContent = 'This will bill ' + r.student_count + ' student(s) on this route <?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?>' + Number(r.fee).toFixed(2) + ' each for the selected month.';
  document.getElementById('generateModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
