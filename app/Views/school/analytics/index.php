<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-header">
    <div class="page-header-title">Advanced Academic Analytics</div>
    <div class="text-muted">High-density data visualization for school performance.</div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Global Avg Score</div>
        <div class="stat-value">72.4%</div>
        <div class="stat-sub">+2.1% from last term</div>
    </div>
    <div class="stat-card" style="--card-color: var(--warning);">
        <div class="stat-label">Critical Attendance</div>
        <div class="stat-value"><?= count($subjectPerformance) ?></div>
        <div class="stat-sub">Students below 75% threshold</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Subject Performance Comparison</div></div>
        <div class="card-body">
            <canvas id="subjectChart"></canvas>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">School-wide Attendance Trend</div></div>
        <div class="card-body">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
</div>

<script>
const ctxSubject = document.getElementById('subjectChart').getContext('2d');
new Chart(ctxSubject, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($subjectPerformance, 'subject')) ?>,
        datasets: [{
            label: 'Average Score (%)',
            data: <?= json_encode(array_column($subjectPerformance, 'avg_score')) ?>,
            backgroundColor: 'rgba(79, 70, 229, 0.6)',
            borderColor: 'rgba(79, 70, 229, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});

const ctxAttendance = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctxAttendance, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($attendanceTrend, 'date')) ?>,
        datasets: [{
            label: 'Present Students',
            data: <?= json_encode(array_column($attendanceTrend, 'present')) ?>,
            fill: true,
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderColor: '#10b981',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: false } }
    }
});
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
