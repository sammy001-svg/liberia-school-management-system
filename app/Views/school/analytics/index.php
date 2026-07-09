<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-header">
    <div>
        <div class="page-header-title">Advanced Academic Analytics</div>
        <div class="page-header-sub">School-wide performance and attendance data</div>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Active Students</div>
        <div class="stat-value"><?= (int)($stats['totalStudents'] ?? 0) ?></div>
    </div>
    <div class="stat-card" style="--card-color: var(--success);">
        <div class="stat-label">Global Avg Score</div>
        <div class="stat-value"><?= $stats['globalAvg'] !== null ? $stats['globalAvg'].'%' : '—' ?></div>
        <div class="stat-sub"><?= $stats['globalAvg'] !== null ? 'Across all recorded grades' : 'No grades recorded yet' ?></div>
    </div>
    <div class="stat-card" style="--card-color: var(--warning);">
        <div class="stat-label">Critical Attendance</div>
        <div class="stat-value"><?= (int)($stats['criticalAttendance'] ?? 0) ?></div>
        <div class="stat-sub">Students below 75% threshold</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-bottom:24px;">
    <div class="card">
        <div class="card-header"><div class="card-title">Subject Performance Comparison</div></div>
        <div class="card-body">
            <?php if(empty($subjectPerformance)): ?>
              <div class="empty-state"><div class="empty-state-icon">📊</div><div class="empty-state-text">No graded subjects yet.</div></div>
            <?php else: ?>
              <canvas id="subjectChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">School-wide Attendance Trend (Last 7 Days)</div></div>
        <div class="card-body">
            <?php if(empty($attendanceTrend)): ?>
              <div class="empty-state"><div class="empty-state-icon">📈</div><div class="empty-state-text">No attendance recorded in the last 7 days.</div></div>
            <?php else: ?>
              <canvas id="attendanceChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const subjectCanvas = document.getElementById('subjectChart');
if (subjectCanvas) {
new Chart(subjectCanvas.getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($subjectPerformance, 'subject')) ?>,
        datasets: [{
            label: 'Average Score (%)',
            data: <?= json_encode(array_column($subjectPerformance, 'avg_score')) ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.6)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 100 } }
    }
});
}

const attendanceCanvas = document.getElementById('attendanceChart');
if (attendanceCanvas) {
new Chart(attendanceCanvas.getContext('2d'), {
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
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
