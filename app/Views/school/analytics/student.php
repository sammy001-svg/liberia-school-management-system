<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/analytics">Academic Analytics</a>
  <span>/</span><span><?= htmlspecialchars($studentName) ?></span>
</div>
<div class="page-header">
    <div class="page-header-title">Academic Growth: <?= htmlspecialchars($studentName) ?></div>
    <a href="<?= $cfg['url'] ?>/school/analytics" class="btn btn-secondary">Back to Analytics</a>
</div>

<?php if(!empty($growth)):
  $scores = array_column($growth, 'avg_score');
  $latest = end($scores);
  $best = max($scores);
  $trend = count($scores) >= 2 ? round($scores[count($scores)-1] - $scores[count($scores)-2], 1) : null;
?>
<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Latest Exam Score</div>
    <div class="stat-value"><?= round($latest,1) ?>%</div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Best Score</div>
    <div class="stat-value"><?= round($best,1) ?>%</div>
  </div>
  <div class="stat-card" style="--card-color: <?= $trend===null?'var(--info)':($trend>=0?'var(--success)':'var(--danger)') ?>;">
    <div class="stat-label">Trend Since Last Exam</div>
    <div class="stat-value"><?= $trend===null ? '—' : ($trend>=0?'+':'').$trend.'%' ?></div>
  </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">Performance Trend (Avg Score per Exam)</div></div>
    <div class="card-body">
        <?php if(empty($growth)): ?>
          <div class="empty-state"><div class="empty-state-icon">📈</div><div class="empty-state-text">No exam grades recorded for this student yet.</div></div>
        <?php else: ?>
          <canvas id="growthChart" style="max-height:400px;"></canvas>
        <?php endif; ?>
    </div>
</div>

<?php if(!empty($growth)): ?>
<script>
const ctxGrowth = document.getElementById('growthChart').getContext('2d');
new Chart(ctxGrowth, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($growth, 'exam')) ?>,
        datasets: [{
            label: 'Average Score',
            data: <?= json_encode(array_column($growth, 'avg_score')) ?>,
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, max: 100 }
        }
    }
});
</script>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
