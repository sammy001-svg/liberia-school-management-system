<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/analytics">Academic Analytics</a>
  <span>/</span><span>Attendance Heatmap</span>
</div>
<div class="page-header">
    <div>
        <div class="page-header-title">Attendance Heatmap &amp; Chronic Absenteeism</div>
        <div class="page-header-sub">Early identification of students falling below the 75% attendance threshold</div>
    </div>
</div>

<div class="stat-grid">
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Students Flagged</div>
    <div class="stat-value"><?= (int)($stats['flagged'] ?? 0) ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--danger);">
    <div class="stat-label">Critical (Below 50%)</div>
    <div class="stat-value"><?= (int)($stats['critical'] ?? 0) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Avg Attendance Among Flagged</div>
    <div class="stat-value"><?= $stats['avgPct'] !== null ? $stats['avgPct'].'%' : '—' ?></div>
  </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Flagged Students (<?= count($chronicAbsentees) ?>)</div></div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Total Days</th>
                    <th>Days Present</th>
                    <th>Attendance %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($chronicAbsentees as $s): ?>
                <tr>
                    <td class="fw-600"><?= htmlspecialchars($s['name']) ?></td>
                    <td><?= htmlspecialchars($s['class_name'] ?? 'N/A') ?></td>
                    <td><?= $s['total_days'] ?></td>
                    <td><?= $s['present_days'] ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="progress-track" style="width:70px;"><div class="progress-fill" style="width:<?= round($s['percentage']) ?>%;--card-color:<?= $s['percentage']<50?'var(--danger)':'var(--warning)' ?>;"></div></div>
                            <span class="fw-700 <?= $s['percentage'] < 50 ? 'text-danger' : 'text-warning' ?>"><?= round($s['percentage'], 1) ?>%</span>
                        </div>
                    </td>
                    <td>
                        <?php if($s['percentage'] < 50): ?>
                            <span class="badge badge-danger">CRITICAL RISK</span>
                        <?php else: ?>
                            <span class="badge badge-warning">AT RISK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($chronicAbsentees)): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-text">Excellent! No students currently below the 75% attendance threshold.</div>
                    </div>
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
