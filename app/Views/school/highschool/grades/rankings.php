<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb"><a href="<?= $cfg['url'] ?>/school/grades">Grades &amp; Exams</a><span>/</span><span>Rankings</span></div>

<div class="page-header">
  <div>
    <div class="page-header-title">Student Rankings</div>
    <div class="page-header-sub">Composite period scores and class/school position</div>
  </div>
  <div style="display:flex;gap:10px;">
    <?php if (!empty($periods)): ?>
    <a href="<?= $cfg['url'] ?>/school/grades/rankings/export?period=<?= urlencode($selectedPeriod) ?>&class_id=<?= urlencode($selectedClass) ?>" class="btn btn-outline">⬇️ CSV</a>
    <a href="<?= $cfg['url'] ?>/school/grades/rankings/print?period=<?= urlencode($selectedPeriod) ?>&class_id=<?= urlencode($selectedClass) ?>" target="_blank" class="btn btn-outline">🖨️ PDF</a>
    <?php endif; ?>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('bulkUploadModal').classList.add('open')">Bulk Upload</button>
  </div>
</div>

<?php if (empty($periods)): ?>
  <div class="card">
    <div class="card-body">
      <div class="empty-state">
        <div class="empty-state-icon">🏆</div>
        <div class="empty-state-text">No ranking data yet. <a href="javascript:void(0)" onclick="document.getElementById('bulkUploadModal').classList.add('open')">Bulk upload a rankings CSV</a> to get started.</div>
      </div>
    </div>
  </div>
<?php else: ?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Students Ranked</div>
    <div class="stat-value"><?= (int)$stats['count'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Average Score</div>
    <div class="stat-value"><?= $stats['avg'] !== null ? $stats['avg'].'%' : '—' ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Top Performer</div>
    <div class="stat-value" style="font-size:16px;"><?= $stats['top'] ? htmlspecialchars($stats['top']['student_name']) : '—' ?></div>
  </div>
</div>

<form method="GET" class="card" style="padding:16px 20px;margin-bottom:20px;">
  <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <label style="color:var(--text-muted);font-size:13px">Period:</label>
    <select name="period" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
      <?php foreach ($periods as $p): ?>
        <option value="<?= htmlspecialchars($p) ?>" <?= $p === $selectedPeriod ? 'selected' : '' ?>><?= htmlspecialchars($p) ?></option>
      <?php endforeach; ?>
    </select>
    <label style="color:var(--text-muted);font-size:13px">Class:</label>
    <select name="class_id" class="form-control" style="max-width:180px;" onchange="this.form.submit()">
      <option value="">All Classes</option>
      <?php foreach ($classes as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (string)$selectedClass === (string)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <a href="<?= $cfg['url'] ?>/school/grades/rankings" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title"><?= htmlspecialchars($selectedPeriod) ?> — Rankings (<?= count($rankings) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Rank</th><th>Student</th><th>Admission No</th><th>Class</th><th>Score</th><th>Group Size</th></tr></thead>
      <tbody>
        <?php foreach ($rankings as $r): ?>
        <tr>
          <td class="fw-600"><?= $r['rank_position'] !== null ? '#'.$r['rank_position'] : '—' ?></td>
          <td>
            <a href="<?= $cfg['url'] ?>/school/students/<?= $r['student_id'] ?>" style="display:flex;align-items:center;gap:10px;color:inherit;">
              <div class="avatar"><?= strtoupper(substr($r['student_name'],0,1)) ?></div>
              <?= htmlspecialchars($r['student_name']) ?>
            </a>
          </td>
          <td style="font-family:monospace;font-size:12px"><?= htmlspecialchars($r['admission_no']) ?></td>
          <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $r['score'] >= 70 ? 'success' : ($r['score'] >= 50 ? 'warning' : 'danger') ?>"><?= number_format($r['score'],1) ?>%</span></td>
          <td class="text-muted"><?= $r['group_size'] ?? '—' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rankings)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">🏆</div><div class="empty-state-text">No rankings for this filter.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<!-- Bulk Upload Modal -->
<div class="modal-overlay" id="bulkUploadModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Bulk Upload Rankings</div>
      <button class="modal-close" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/grades/rankings/bulk-upload" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <p class="text-muted" style="font-size:13px;margin-bottom:16px;">
          Upload a CSV of per-period scores and ranks (one row per student per period).
          <a href="<?= $cfg['url'] ?>/school/grades/rankings/bulk-template">Download the CSV template</a> to see the expected columns.
        </p>
        <div class="form-group">
          <label class="form-label">CSV File *</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <div class="form-hint">Students are matched by TSM ID (admission number). Re-uploading updates existing scores for the same student + period rather than duplicating them.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('bulkUploadModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
