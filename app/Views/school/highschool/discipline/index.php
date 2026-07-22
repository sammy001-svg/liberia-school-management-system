<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Discipline</div>
    <div class="page-header-sub">Track student behavior — incidents and commendations</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addDisciplineModal').classList.add('open')">+ Add Record</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Total Records</div>
    <div class="stat-value"><?= (int)($stats['total'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Minor</div>
    <div class="stat-value"><?= (int)($stats['minor'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Moderate / Severe</div>
    <div class="stat-value"><?= (int)($stats['moderate'] ?? 0) + (int)($stats['severe'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Commendations</div>
    <div class="stat-value"><?= (int)($stats['commendation'] ?? 0) ?></div>
  </div>
</div>

<!-- FILTERS -->
<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search student name or admission no…" class="form-control" style="max-width:260px;">
    <select name="class_id" class="form-control" style="max-width:180px;">
      <option value="">All Classes</option>
      <?php foreach($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $classId==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="severity" class="form-control" style="max-width:180px;">
      <option value="">All Severities</option>
      <?php foreach(['minor'=>'Minor','moderate'=>'Moderate','severe'=>'Severe','commendation'=>'Commendation'] as $val=>$label): ?>
        <option value="<?= $val ?>" <?= $severity===$val?'selected':'' ?>><?= $label ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $cfg['url'] ?>/school/discipline" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header">
    <div class="card-title">Records (<?= count($records) ?>)</div>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Date</th><th>Student</th><th>Class</th><th>Category</th><th>Severity</th><th>Notes</th><th>Reported By</th><th></th></tr></thead>
      <tbody>
        <?php foreach($records as $d): ?>
        <?php $sevBadge = ['minor'=>'warning','moderate'=>'danger','severe'=>'danger','commendation'=>'success'][$d['severity']] ?? 'muted'; ?>
        <tr>
          <td><?= date('d M Y', strtotime($d['incident_date'])) ?></td>
          <td><a href="<?= $cfg['url'] ?>/school/students/<?= $d['student_id'] ?>" class="fw-600"><?= htmlspecialchars($d['student_name']) ?></a><div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($d['admission_no']) ?></div></td>
          <td><?= htmlspecialchars($d['class_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($d['category']) ?></td>
          <td><span class="badge badge-<?= $sevBadge ?>"><?= ucfirst($d['severity']) ?></span></td>
          <td class="text-muted">
            <?php if($d['description']): ?><div><?= htmlspecialchars($d['description']) ?></div><?php endif; ?>
            <?php if($d['action_taken']): ?><div style="font-size:11px;">Action: <?= htmlspecialchars($d['action_taken']) ?></div><?php endif; ?>
            <?php if(!$d['description'] && !$d['action_taken']): ?>—<?php endif; ?>
          </td>
          <td><?= htmlspecialchars($d['reported_by_name'] ?? '—') ?></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/discipline/<?= $d['id'] ?>/delete" data-confirm="Remove this disciplinary record?" data-confirm-title="Remove Record" data-confirm-label="Remove">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($records)): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <div class="empty-state-icon">🛡️</div>
            <div class="empty-state-text">No disciplinary records yet. <a href="javascript:void(0)" onclick="document.getElementById('addDisciplineModal').classList.add('open')">Add one</a></div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Disciplinary Record Modal -->
<div class="modal-overlay" id="addDisciplineModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Add Disciplinary Record</div>
      <button class="modal-close" onclick="document.getElementById('addDisciplineModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/discipline/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="back_to" value="<?= $cfg['url'] ?>/school/discipline">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Student *</label>
          <select name="student_id" class="form-control" required>
            <option value="">— Select Student —</option>
            <?php foreach($students as $s): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['admission_no']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Date *</label>
            <input type="date" name="incident_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Severity *</label>
            <select name="severity" class="form-control" required>
              <option value="minor">Minor</option>
              <option value="moderate">Moderate</option>
              <option value="severe">Severe</option>
              <option value="commendation">Commendation (Good Behavior)</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Category *</label>
          <select name="category" class="form-control" required>
            <option value="Tardiness">Tardiness</option>
            <option value="Absenteeism">Absenteeism</option>
            <option value="Disrespect/Insubordination">Disrespect / Insubordination</option>
            <option value="Fighting/Physical Altercation">Fighting / Physical Altercation</option>
            <option value="Bullying">Bullying</option>
            <option value="Cheating/Academic Dishonesty">Cheating / Academic Dishonesty</option>
            <option value="Property Damage/Vandalism">Property Damage / Vandalism</option>
            <option value="Dress Code Violation">Dress Code Violation</option>
            <option value="Commendation/Good Behavior">Commendation / Good Behavior</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="What happened?"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Action Taken</label>
          <textarea name="action_taken" class="form-control" rows="2" placeholder="e.g. Verbal warning, detention, parent notified"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addDisciplineModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Record</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
