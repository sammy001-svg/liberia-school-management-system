<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Custom Layout Styles for Dashboard to match the desired layout while keeping dark theme base */
.dash-grid { display: grid; gap: 20px; margin-bottom: 24px; }
.dash-stats-row { grid-template-columns: repeat(4, 1fr); }
.dash-mid-row { grid-template-columns: 2fr 1.5fr 1.2fr; }
.dash-bot-row { grid-template-columns: 1fr 1fr 1.2fr; }

@media(max-width:1024px) {
  .dash-mid-row, .dash-bot-row { grid-template-columns: 1fr 1fr; }
}
@media(max-width:768px) {
  .dash-stats-row { grid-template-columns: 1fr 1fr; }
  .dash-mid-row, .dash-bot-row { grid-template-columns: 1fr; }
}

.dash-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  display: flex;
  flex-direction: column;
}
.dash-card-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 16px;
}
.dash-card-title { font-weight: 700; font-size: 14px; color: var(--text); }
.dash-card-action { font-size: 12px; color: var(--primary); font-weight: 600; cursor: pointer; }

/* Stat Cards */
.stat-item { display: flex; align-items: center; gap: 16px; }
.stat-icon-wrapper {
  width: 50px; height: 50px; border-radius: var(--radius-sm);
  display: flex; align-items: center; justify-content: center;
}
.stat-icon-wrapper svg { width: 24px; height: 24px; }
.stat-details h4 { font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 4px; }
.stat-details h2 { font-size: 26px; font-weight: 800; color: var(--text); line-height: 1; }
.stat-trend { font-size: 11px; margin-top: 6px; display: flex; align-items: center; gap: 4px; font-weight: 500; }
.trend-up { color: var(--success); }
.trend-down { color: var(--danger); }
.trend-flat { color: var(--text-muted); }

/* Quick Links */
.ql-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.ql-btn {
  background: rgba(255,255,255,0.03);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px 10px;
  display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;
  cursor: pointer; transition: all 0.2s;
  text-align: center;
}
.ql-btn:hover { background: rgba(16,185,129,0.1); border-color: var(--primary); }
.ql-btn svg { width: 24px; height: 24px; color: var(--primary); }
.ql-btn span { font-size: 11px; font-weight: 600; color: var(--text); }

/* Calendar */
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; font-size: 12px; }
.cal-header { font-weight: 600; color: var(--text-muted); padding-bottom: 8px; }
.cal-day { padding: 6px 0; border-radius: 4px; cursor: pointer; color: var(--text-light); }
.cal-day:hover { background: rgba(255,255,255,0.1); }
.cal-day.active { background: var(--primary); color: #fff; font-weight: 700; }
.cal-day.has-event { position: relative; }
.cal-day.has-event::after {
  content: ''; position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%);
  width: 4px; height: 4px; border-radius: 50%; background: var(--warning);
}

/* Lists */
.list-item { display: flex; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 12px; }
.list-item:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
.list-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.list-icon svg { width: 20px; height: 20px; }
.list-content h5 { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.list-content p { font-size: 11px; color: var(--text-muted); line-height: 1.4; }
.list-date { font-size: 11px; color: var(--text-muted); font-weight: 500; white-space: nowrap; margin-left: auto; }
</style>

<div class="page-header" style="margin-bottom: 20px;">
  <div>
    <div class="page-header-title">Dashboard</div>
    <div class="page-header-sub">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>! 👋</div>
  </div>
</div>

<!-- ROW 1: STATS -->
<div class="dash-grid dash-stats-row">
  <div class="dash-card">
    <div class="stat-item">
      <div class="stat-icon-wrapper" style="background:rgba(59,130,246,0.15);color:#3B82F6;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
      </div>
      <div class="stat-details">
        <h4>Total Students</h4>
        <h2><?= number_format($stats['students']) ?></h2>
        <div class="stat-trend trend-up"><svg style="width:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg> 5.4% from last month</div>
      </div>
    </div>
  </div>
  
  <div class="dash-card">
    <div class="stat-item">
      <div class="stat-icon-wrapper" style="background:rgba(16,185,129,0.15);color:var(--success);">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      </div>
      <div class="stat-details">
        <h4>Total Teachers</h4>
        <h2><?= number_format($stats['teachers']) ?></h2>
        <div class="stat-trend trend-up"><svg style="width:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg> 3.2% from last month</div>
      </div>
    </div>
  </div>

  <div class="dash-card">
    <div class="stat-item">
      <div class="stat-icon-wrapper" style="background:rgba(139,92,246,0.15);color:#8B5CF6;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      </div>
      <div class="stat-details">
        <h4>Total Classes</h4>
        <h2><?= number_format($stats['classes']) ?></h2>
        <div class="stat-trend trend-flat"><svg style="width:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg> No change</div>
      </div>
    </div>
  </div>

  <div class="dash-card">
    <div class="stat-item">
      <div class="stat-icon-wrapper" style="background:rgba(245,158,11,0.15);color:#F59E0B;">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="stat-details">
        <h4>Today's Attendance</h4>
        <h2><?= number_format($stats['attendance_pct'], 1) ?>%</h2>
        <div class="stat-trend trend-up"><svg style="width:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg> 2.6% from yesterday</div>
      </div>
    </div>
  </div>
</div>

<!-- ROW 2: LINE CHART, ANNOUNCEMENTS, CALENDAR -->
<div class="dash-grid dash-mid-row">
  <div class="dash-card">
    <div class="dash-card-header">
      <div class="dash-card-title">Attendance Overview</div>
      <div class="dash-card-action">
        <select class="form-control" style="padding:4px 8px;font-size:11px;width:auto;height:auto;background:rgba(255,255,255,0.05);border:none;">
          <option>This Week</option>
        </select>
      </div>
    </div>
    <div style="flex:1;position:relative;height:200px;">
      <canvas id="attendanceChart"></canvas>
    </div>
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <div class="dash-card-title">Recent Announcements</div>
      <div class="dash-card-action">View All</div>
    </div>
    <div style="flex:1;overflow-y:auto;">
      <?php if(!empty($announcements)): foreach($announcements as $a): ?>
      <div class="list-item">
        <div class="list-icon" style="background:rgba(139,92,246,0.1);color:#8B5CF6;">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
        </div>
        <div class="list-content">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <h5><?= htmlspecialchars($a['title']) ?></h5>
            <span class="list-date"><?= date('M d, Y', strtotime($a['published_at'])) ?></span>
          </div>
          <p><?= htmlspecialchars(substr($a['content']??'', 0, 60)) ?>...</p>
        </div>
      </div>
      <?php endforeach; else: ?>
        <p class="text-muted" style="font-size:12px;text-align:center;margin-top:40px;">No announcements found.</p>
      <?php endif; ?>
    </div>
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <div class="dash-card-title">Events Calendar</div>
      <div class="dash-card-action">View Calendar</div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <svg style="width:16px;cursor:pointer;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      <span style="font-weight:600;font-size:13px;"><?= date('F Y') ?></span>
      <svg style="width:16px;cursor:pointer;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    <div class="cal-grid cal-header">
      <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
    </div>
    <div class="cal-grid">
      <!-- Mock calendar days for visual representation -->
      <div class="cal-day" style="opacity:0.3">27</div><div class="cal-day" style="opacity:0.3">28</div><div class="cal-day" style="opacity:0.3">29</div><div class="cal-day" style="opacity:0.3">30</div>
      <div class="cal-day">1</div><div class="cal-day">2</div><div class="cal-day">3</div>
      <div class="cal-day">4</div><div class="cal-day">5</div><div class="cal-day">6</div><div class="cal-day">7</div><div class="cal-day">8</div><div class="cal-day">9</div><div class="cal-day">10</div>
      <div class="cal-day">11</div><div class="cal-day">12</div><div class="cal-day">13</div><div class="cal-day has-event">14</div><div class="cal-day has-event">15</div><div class="cal-day">16</div><div class="cal-day">17</div>
      <div class="cal-day">18</div><div class="cal-day">19</div><div class="cal-day active">20</div><div class="cal-day">21</div><div class="cal-day">22</div><div class="cal-day">23</div><div class="cal-day">24</div>
      <div class="cal-day">25</div><div class="cal-day">26</div><div class="cal-day">27</div><div class="cal-day">28</div><div class="cal-day">29</div><div class="cal-day">30</div><div class="cal-day">31</div>
    </div>
  </div>
</div>

<!-- ROW 3: DONUT, PIE, QUICK LINKS -->
<div class="dash-grid dash-bot-row">
  <div class="dash-card">
    <div class="dash-card-header">
      <div class="dash-card-title">Fee Collection Overview</div>
      <div class="dash-card-action">
        <select class="form-control" style="padding:4px 8px;font-size:11px;width:auto;height:auto;background:rgba(255,255,255,0.05);border:none;">
          <option>This Month</option>
        </select>
      </div>
    </div>
    <div style="flex:1;position:relative;height:200px;display:flex;justify-content:center;align-items:center;">
      <canvas id="feeChart"></canvas>
      <div style="position:absolute;text-align:center;">
        <div style="font-weight:800;font-size:16px;"><?= htmlspecialchars($tenant['currency'] ?? 'Ksh') ?> <?= number_format($fees['collected'] + $fees['pending'] + $fees['overdue']) ?></div>
        <div style="font-size:10px;color:var(--text-muted);">Total Invoiced</div>
      </div>
    </div>
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <div class="dash-card-title">Exam Overview</div>
      <div class="dash-card-action">
        <select class="form-control" style="padding:4px 8px;font-size:11px;width:auto;height:auto;background:rgba(255,255,255,0.05);border:none;">
          <option>This Term</option>
        </select>
      </div>
    </div>
    <div style="flex:1;position:relative;height:200px;display:flex;justify-content:center;align-items:center;">
      <canvas id="examChart"></canvas>
    </div>
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <div class="dash-card-title">Quick Links</div>
    </div>
    <div class="ql-grid">
      <a href="<?= $cfg['url'] ?>/school/students/create" class="ql-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Add Student</span>
      </a>
      <a href="#" class="ql-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span>Add Teacher</span>
      </a>
      <a href="#" class="ql-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
        <span>Add Class</span>
      </a>
      <a href="<?= $cfg['url'] ?>/school/attendance" class="ql-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Mark Attendance</span>
      </a>
      <a href="#" class="ql-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span>Assign Assignment</span>
      </a>
      <a href="#" class="ql-btn">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Generate Report</span>
      </a>
    </div>
  </div>
</div>

<script>
// Common chart options for dark theme
Chart.defaults.color = '#8892A4';
Chart.defaults.font.family = "'Inter', sans-serif";

// 1. Attendance Line Chart
const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
new Chart(ctxAtt, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        datasets: [{
            label: 'Attendance %',
            data: <?= json_encode(array_values($attendance_hist)) ?>,
            borderColor: '#3B82F6', // Blue like the picture
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 3,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#3B82F6',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// 2. Fee Collection Donut Chart
const ctxFee = document.getElementById('feeChart').getContext('2d');
new Chart(ctxFee, {
    type: 'doughnut',
    data: {
        labels: ['Collected', 'Pending', 'Overdue'],
        datasets: [{
            data: [<?= $fees['collected'] ?>, <?= $fees['pending'] ?>, <?= $fees['overdue'] ?>],
            backgroundColor: ['#10B981', '#F59E0B', '#EF4444'], // Green, Yellow, Red
            borderWidth: 0,
            cutout: '75%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { boxWidth: 10, padding: 15 } }
        }
    }
});

// 3. Exam Overview Pie Chart
const ctxExam = document.getElementById('examChart').getContext('2d');
new Chart(ctxExam, {
    type: 'pie',
    data: {
        labels: ['Upcoming', 'In Progress', 'Completed', 'Cancelled'],
        datasets: [{
            data: [<?= $exams['upcoming'] ?>, <?= $exams['in_progress'] ?>, <?= $exams['completed'] ?>, <?= $exams['cancelled'] ?>],
            backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'], // Blue, Green, Yellow, Red
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { boxWidth: 10, padding: 15 } }
        }
    }
});
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
