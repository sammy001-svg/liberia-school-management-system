<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-header">
    <div class="page-header-title">Academic Growth: <?= htmlspecialchars($studentName) ?></div>
    <a href="<?= $cfg['url'] ?>/school/analytics" class="btn btn-secondary">Back to Analytics</a>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">Performance Trend (Avg Score per Exam)</div></div>
    <div class="card-body">
        <canvas id="growthChart" style="max-height:400px;"></canvas>
    </div>
</div>

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
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
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
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
