<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php
$cur   = htmlspecialchars($tenant['currency'] ?? '$');
$money = fn($v) => $cur . ' ' . number_format((float)$v, 2);
$hasFinance = !empty($finance);
$totalStudents = $people['female'] + $people['male'] + $people['other'];
// Breakdown dots cycle through the theme's accent palette.
$dotColors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316', '#14B8A6', '#6366F1'];
?>

<style>
.dash-grid { display: grid; gap: 20px; margin-bottom: 20px; }
.dash-stats-row { grid-template-columns: repeat(4, 1fr); }
.dash-three { grid-template-columns: 1.15fr 1.15fr 1fr; }
@media (max-width: 1180px) { .dash-three { grid-template-columns: 1fr 1fr; } .dash-three > :last-child { grid-column: 1 / -1; } }
@media (max-width: 900px)  { .dash-stats-row { grid-template-columns: 1fr 1fr; } }
@media (max-width: 720px)  { .dash-three { grid-template-columns: 1fr; } .dash-stats-row { grid-template-columns: 1fr; } }

.dash-card {
  background: var(--bg-card); border: 1px solid var(--border); border-radius: var(--radius); padding: 20px;
  display: flex; flex-direction: column; min-width: 0;
  box-shadow: var(--card-glow), inset 0 1px 0 var(--card-highlight);
}
.dash-card-header { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; }
.dash-card-title { font-weight: 700; font-size: 15px; color: var(--text); }
.dash-card-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

/* Headline counts */
.kpi { display: flex; align-items: center; gap: 16px; }
.kpi-icon { width: 58px; height: 58px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.kpi-icon svg { width: 28px; height: 28px; }
.kpi-body { flex: 1; text-align: right; }
.kpi-label { font-size: 12.5px; color: var(--text-muted); font-weight: 600; }
.kpi-value { font-size: 28px; font-weight: 800; color: var(--text); line-height: 1.15; margin-top: 2px; }
.kpi-trend { font-size: 11px; margin-top: 4px; font-weight: 500; }
.trend-up { color: var(--success); } .trend-down { color: var(--danger); } .trend-flat { color: var(--text-muted); }

/* Money figures with a coloured rule underneath, as on a statement */
.fig { padding-bottom: 8px; border-bottom: 3px solid var(--fig, var(--success)); }
.fig-label { font-size: 11.5px; color: var(--text-muted); }
.fig-value { font-size: 15px; font-weight: 800; color: var(--text); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.fig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 18px; }
.fig-grid .fig-wide { grid-column: 1 / -1; }

/* Category breakdowns */
.bd-total { font-size: 24px; font-weight: 800; color: var(--text); margin: 2px 0 12px; }
.bd-list { flex: 1; overflow-y: auto; max-height: 300px; margin: 0 -6px; padding: 0 6px; }
.bd-row { display: grid; grid-template-columns: 12px 1fr auto 52px; gap: 10px; align-items: center; padding: 9px 0; border-bottom: 1px solid var(--border); font-size: 12.5px; }
.bd-row:last-child { border-bottom: 0; }
.bd-dot { width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 0 3px color-mix(in srgb, var(--dot) 22%, transparent); background: var(--dot); }
.bd-name { color: var(--text); }
.bd-amt { color: var(--text); font-weight: 600; white-space: nowrap; }
.bd-pct { color: var(--text-muted); text-align: right; }
.bd-bar { grid-column: 2 / -1; height: 3px; border-radius: 3px; background: var(--surface-1); overflow: hidden; margin-top: -4px; }
.bd-bar span { display: block; height: 100%; background: var(--dot); border-radius: 3px; }

/* Calendar */
.cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; text-align: center; font-size: 12px; }
.cal-header { font-weight: 600; color: var(--text-muted); padding-bottom: 8px; }
.cal-day { padding: 7px 0; border-radius: 6px; color: var(--text-light); position: relative; }
.cal-day.active { background: var(--primary); color: #fff; font-weight: 700; }
.cal-day.has-event::after { content: ''; position: absolute; bottom: 2px; left: 50%; transform: translateX(-50%); width: 4px; height: 4px; border-radius: 50%; background: var(--warning); }
.cal-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.cal-nav a { display: flex; color: var(--text-muted); padding: 4px; border-radius: 6px; }
.cal-nav a:hover { background: var(--surface-1); color: var(--text); }
.cal-legend { display: flex; gap: 14px; font-size: 11px; color: var(--text-muted); margin-top: 12px; }
.cal-legend span { display: inline-flex; align-items: center; gap: 6px; }

/* Lists */
.list-item { display: flex; gap: 12px; margin-bottom: 14px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }
.list-item:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
.list-icon { width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: var(--purple-soft); color: var(--purple); }
.list-icon svg { width: 18px; height: 18px; }
.list-content { min-width: 0; flex: 1; }
.list-content h5 { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 2px; }
.list-content p { font-size: 11.5px; color: var(--text-muted); line-height: 1.45; }
.list-date { font-size: 11px; color: var(--text-muted); white-space: nowrap; }

/* Quick links */
.ql-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.ql-btn { background: var(--surface-1); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 14px 8px; display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; transition: all .2s; }
.ql-btn:hover { background: var(--ql-soft); border-color: var(--ql-accent); transform: translateY(-2px); }
.ql-btn svg { width: 22px; height: 22px; color: var(--ql-accent); }
.ql-btn span { font-size: 11px; font-weight: 600; color: var(--text); }

.empty-note { font-size: 12.5px; color: var(--text-muted); text-align: center; padding: 30px 10px; }
.cta-strip { text-align: center; padding: 28px 20px; }
.cta-strip h3 { font-size: 18px; font-weight: 700; color: var(--text); }
.cta-strip p { font-size: 13px; color: var(--text-muted); margin: 6px 0 14px; }
</style>

<div class="page-header" style="margin-bottom: 20px;">
  <div>
    <div class="page-header-title">Welcome to <?= htmlspecialchars($tenant['name'] ?? 'the') ?> Portal</div>
    <div class="page-header-sub">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?> 👋 — here’s your school at a glance<?= $hasFinance ? ' for ' . htmlspecialchars($finance['label']) : '' ?>.</div>
  </div>
</div>

<!-- ROW 1: HEADLINE COUNTS -->
<?php
$trendHtml = function (array $t): string {
    $path = $t['dir'] === 'up' ? 'M5 10l7-7m0 0l7 7m-7-7v18' : ($t['dir'] === 'down' ? 'M19 14l-7 7m0 0l-7-7m7 7V3' : 'M20 12H4');
    return '<div class="kpi-trend trend-' . $t['dir'] . '"><svg style="width:11px;vertical-align:-1px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $path . '"/></svg> ' . htmlspecialchars($t['label']) . '</div>';
};
$kpis = [
    ['Registered Students', number_format($stats['students']), 'var(--success)', 'var(--primary-soft)', $trendHtml($trends['students']), '/school/students',
     'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118.82 17.5 11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.557 12.078 12.078 0 01.665-6.479L12 14z'],
    ['Faculty & Staff', number_format($people['staff']), '#3B82F6', 'var(--blue-soft)', '<div class="kpi-trend trend-flat">' . number_format($stats['teachers']) . ' teachers</div>', '/school/teachers',
     'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
    ['Active Parents', number_format($people['parents']), '#EF4444', 'var(--rose-soft)', '<div class="kpi-trend trend-flat">with an enrolled child</div>', '/school/parents',
     'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    ["Today's Attendance", number_format($stats['attendance_pct'], 1) . '%', '#F59E0B', 'var(--amber-soft)', $trendHtml($trends['attendance']), '/school/attendance',
     'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
];
?>
<div class="dash-grid dash-stats-row">
  <?php foreach ($kpis as [$label, $value, $color, $soft, $trend, $link, $icon]): ?>
    <a class="dash-card" href="<?= $cfg['url'] . $link ?>" style="text-decoration:none;">
      <div class="kpi">
        <div class="kpi-icon" style="background:<?= $soft ?>;color:<?= $color ?>;">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $icon ?>"/></svg>
        </div>
        <div class="kpi-body">
          <div class="kpi-label"><?= $label ?></div>
          <div class="kpi-value"><?= $value ?></div>
          <?= $trend ?>
        </div>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<!-- ROW 2: STUDENTS · FINANCES · ARREARS -->
<div class="dash-grid dash-three">
  <div class="dash-card">
    <div class="dash-card-header">
      <div><div class="dash-card-title">Students</div><div class="dash-card-sub">Active enrolment by gender</div></div>
      <a href="<?= $cfg['url'] ?>/school/students" class="btn btn-sm btn-outline">View All</a>
    </div>
    <?php if ($totalStudents > 0): ?>
      <div style="position:relative;height:220px;">
        <canvas id="genderChart"></canvas>
        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;pointer-events:none;">
          <div style="font-size:26px;font-weight:800;color:var(--text);"><?= number_format($totalStudents) ?></div>
          <div style="font-size:11px;color:var(--text-muted);">students</div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr<?= $people['other'] ? ' 1fr' : '' ?>;gap:12px;margin-top:18px;">
        <div class="fig" style="--fig:#8B5CF6;"><div class="fig-label">Female Students</div><div class="fig-value"><?= number_format($people['female']) ?></div></div>
        <div class="fig" style="--fig:#3B82F6;"><div class="fig-label">Male Students</div><div class="fig-value"><?= number_format($people['male']) ?></div></div>
        <?php if ($people['other']): ?><div class="fig" style="--fig:#94A3B8;"><div class="fig-label">Not recorded</div><div class="fig-value"><?= number_format($people['other']) ?></div></div><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="empty-note">No active students yet.</div>
    <?php endif; ?>
  </div>

  <?php if ($hasFinance): ?>
  <div class="dash-card">
    <div class="dash-card-header">
      <div><div class="dash-card-title">Finances</div><div class="dash-card-sub"><?= htmlspecialchars($finance['label']) ?></div></div>
      <a href="<?= $cfg['url'] ?>/school/finance/payments" class="btn btn-sm btn-success">Go to Fees Payment</a>
    </div>
    <div class="fig-grid">
      <div class="fig" style="--fig:var(--success);" title="Fees <?= $money($finance['fee_income']) ?> + other income <?= $money($finance['other_income']) ?>">
        <div class="fig-label">Total Income</div><div class="fig-value"><?= $money($finance['income']) ?></div></div>
      <div class="fig" style="--fig:var(--danger);"><div class="fig-label">Expenses</div><div class="fig-value"><?= $money($finance['expenses']) ?></div></div>
      <div class="fig fig-wide" style="--fig:#3B82F6;"><div class="fig-label">Current Balance</div>
        <div class="fig-value" style="<?= $finance['balance'] < 0 ? 'color:var(--danger);' : '' ?>"><?= $money($finance['balance']) ?></div></div>
    </div>
    <div style="position:relative;flex:1;min-height:170px;margin-top:16px;"><canvas id="financeChart"></canvas></div>
  </div>

  <div class="dash-card">
    <div class="dash-card-header">
      <div><div class="dash-card-title">Arrears</div><div class="dash-card-sub">Fees still owed</div></div>
      <a href="<?= $cfg['url'] ?>/school/finance/arrears" class="btn btn-sm btn-success">Process Arrears</a>
    </div>
    <div style="display:grid;gap:16px;">
      <div class="fig" style="--fig:var(--danger);"><div class="fig-label">Outstanding balances (all years)</div><div class="fig-value"><?= $money($finance['owing']) ?></div></div>
      <div class="fig" style="--fig:var(--warning);"><div class="fig-label">Students owing</div><div class="fig-value"><?= number_format($finance['owing_count']) ?></div></div>
      <div class="fig" style="--fig:#F97316;"><div class="fig-label">Overdue invoices</div><div class="fig-value"><?= $money($finance['overdue']) ?></div></div>
      <div class="fig" style="--fig:var(--success);"><div class="fig-label">Fees collected this year</div><div class="fig-value"><?= $money($finance['fee_income']) ?></div></div>
    </div>
    <p style="font-size:11.5px;color:var(--text-muted);line-height:1.6;margin-top:14px;">
      Outstanding balances come from each student’s account; see Arrears &amp; Aging for how long each amount has been owed.
    </p>
  </div>
  <?php else: ?>
  <div class="dash-card" style="grid-column: span 2;">
    <div class="dash-card-header">
      <div><div class="dash-card-title">Attendance This Week</div><div class="dash-card-sub">Share of marked students present</div></div>
    </div>
    <div style="position:relative;flex:1;min-height:240px;"><canvas id="attendanceChart"></canvas></div>
  </div>
  <?php endif; ?>
</div>

<!-- ROW 3: EXPENSES · EXTRA COLLECTIONS · CALENDAR -->
<div class="dash-grid dash-three">
  <?php if ($hasFinance): ?>
    <?php foreach ([
      ['Expenses', 'Expense breakdown', $finance['expense_rows'], $finance['expenses'], '/school/finance/expenses', 'Manage Expenses', 'No expenses recorded for this year yet.'],
      ['Extra Collections', 'Other income (uniforms, cafeteria, books…)', $finance['income_rows'], $finance['other_income'], '/school/finance/incomes', 'Manage Collections', 'No other income recorded for this year yet.'],
    ] as $bi => [$title, $sub, $rows, $total, $link, $btn, $empty]): ?>
      <div class="dash-card">
        <div class="dash-card-header">
          <div><div class="dash-card-title"><?= $title ?></div><div class="dash-card-sub"><?= htmlspecialchars($finance['label']) ?> · <?= $sub ?></div></div>
          <a href="<?= $cfg['url'] . $link ?>" class="btn btn-sm btn-success"><?= $btn ?></a>
        </div>
        <div class="bd-total"><?= $money($total) ?></div>
        <?php if ($rows): ?>
          <div class="bd-list">
            <?php foreach ($rows as $i => $r): $pct = $total > 0 ? (float)$r['total'] / $total * 100 : 0; $c = $dotColors[($i + $bi * 2) % count($dotColors)]; ?>
              <div class="bd-row" style="--dot:<?= $c ?>;">
                <span class="bd-dot"></span>
                <span class="bd-name"><?= htmlspecialchars($r['category']) ?></span>
                <span class="bd-amt"><?= $money($r['total']) ?></span>
                <span class="bd-pct"><?= number_format($pct, 2) ?>%</span>
                <span class="bd-bar"><span style="width:<?= max(1, round($pct, 1)) ?>%;"></span></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="empty-note"><?= $empty ?></div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="dash-card" style="grid-column: span 2;">
      <div class="dash-card-header">
        <div class="dash-card-title">Recent Announcements</div>
        <a href="<?= $cfg['url'] ?>/school/announcements" class="btn btn-sm btn-outline">View All</a>
      </div>
      <?php require __DIR__ . '/partials/dashboard_announcements.php'; ?>
    </div>
  <?php endif; ?>

  <div class="dash-card">
    <div class="dash-card-header">
      <div><div class="dash-card-title">Event Calendar</div><div class="dash-card-sub">Exams and announcements</div></div>
      <a href="<?= $cfg['url'] ?>/school/dashboard" class="btn btn-sm btn-outline">Today</a>
    </div>
    <div class="cal-nav">
      <a href="<?= $cfg['url'] ?>/school/dashboard?cal_month=<?= $calendar['prevMonth'] ?>" aria-label="Previous month"><svg style="width:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></a>
      <span style="font-weight:700;font-size:14px;color:var(--text);"><?= htmlspecialchars($calendar['label']) ?></span>
      <a href="<?= $cfg['url'] ?>/school/dashboard?cal_month=<?= $calendar['nextMonth'] ?>" aria-label="Next month"><svg style="width:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
    </div>
    <div class="cal-grid cal-header"><div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div></div>
    <div class="cal-grid">
      <?php foreach ($calendar['days'] as $d): ?>
        <div class="cal-day<?= $d['isToday'] ? ' active' : '' ?><?= $d['hasEvent'] ? ' has-event' : '' ?>" <?= $d['inMonth'] ? '' : 'style="opacity:0.3"' ?>><?= $d['day'] ?></div>
      <?php endforeach; ?>
    </div>
    <div class="cal-legend">
      <span><i style="width:8px;height:8px;border-radius:50%;background:var(--primary);display:inline-block;"></i> Today</span>
      <span><i style="width:6px;height:6px;border-radius:50%;background:var(--warning);display:inline-block;"></i> Exam or announcement</span>
    </div>
  </div>
</div>

<!-- ROW 4: ATTENDANCE · ANNOUNCEMENTS · QUICK LINKS -->
<?php
  // Same per-role visibility as the sidebar — only show a quick link if this
  // role's requireAuth() on the target page would actually let them through.
  $quickLinks = [
    ['url'=>'/school/students?open=admitModal', 'label'=>'Add Student', 'roles'=>['School Admin'],
     'icon'=>'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
    ['url'=>'/school/teachers?open=addTeacherModal', 'label'=>'Add Teacher', 'roles'=>['School Admin'],
     'icon'=>'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z'],
    ['url'=>'/school/classes?open=addClassModal', 'label'=>'Add Class', 'roles'=>['School Admin'],
     'icon'=>'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
    ['url'=>'/school/attendance', 'label'=>'Mark Attendance', 'roles'=>['School Admin','Teacher'],
     'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
    ['url'=>'/school/announcements?open=addAnnouncementModal', 'label'=>'Post Announcement', 'roles'=>['School Admin','Teacher'],
     'icon'=>'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
    ['url'=>'/school/analytics', 'label'=>'View Reports', 'roles'=>['School Admin','Teacher'],
     'icon'=>'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
    ['url'=>'/school/finance/invoices/create', 'label'=>'Create Invoice', 'roles'=>['School Admin','Accountant'],
     'icon'=>'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v14a2 2 0 01-2 2z'],
    ['url'=>'/school/finance/bus-billing', 'label'=>'Bus Billing', 'roles'=>['School Admin','Accountant'],
     'icon'=>'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25'],
    ['url'=>'/school/inventory', 'label'=>'Inventory', 'roles'=>['Staff'],
     'icon'=>'M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z'],
  ];
  $visibleQuickLinks = array_filter($quickLinks, fn($l) => in_array($role, $l['roles'], true));
?>
<?php if ($hasFinance || !empty($visibleQuickLinks)): ?>
<div class="dash-grid dash-three">
  <?php if ($hasFinance): ?>
    <div class="dash-card">
      <div class="dash-card-header">
        <div><div class="dash-card-title">Attendance This Week</div><div class="dash-card-sub">Share of marked students present</div></div>
      </div>
      <div style="position:relative;flex:1;min-height:210px;"><canvas id="attendanceChart"></canvas></div>
    </div>
    <div class="dash-card">
      <div class="dash-card-header">
        <div class="dash-card-title">Recent Announcements</div>
        <a href="<?= $cfg['url'] ?>/school/announcements" class="btn btn-sm btn-outline">View All</a>
      </div>
      <?php require __DIR__ . '/partials/dashboard_announcements.php'; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($visibleQuickLinks)): ?>
    <div class="dash-card"<?= $hasFinance ? '' : ' style="grid-column:1 / -1;"' ?>>
      <div class="dash-card-header"><div class="dash-card-title">Quick Links</div></div>
      <div class="ql-grid">
        <?php $qlAccents = ['--primary','--blue','--purple','--pink','--orange','--teal','--indigo','--cyan','--rose']; $qi = 0; ?>
        <?php foreach ($visibleQuickLinks as $l): $qa = $qlAccents[$qi++ % count($qlAccents)]; ?>
          <a href="<?= $cfg['url'] . $l['url'] ?>" class="ql-btn" style="--ql-accent:var(<?= $qa ?>);--ql-soft:var(<?= $qa ?>-soft);">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="<?= $l['icon'] ?>"/></svg>
            <span><?= htmlspecialchars($l['label']) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="dash-card cta-strip">
  <h3>Want a deeper look at your school’s numbers?</h3>
  <p>Students, grades, attendance and finance trends in one place.</p>
  <div><a href="<?= $cfg['url'] ?>/school/analytics" class="btn btn-success">View Analytics</a></div>
</div>

<script>
Chart.defaults.color = '#8892A4';
Chart.defaults.font.family = "'Inter', sans-serif";
const gridColor = 'rgba(136,146,164,0.15)';

<?php if ($totalStudents > 0): ?>
new Chart(document.getElementById('genderChart'), {
  type: 'doughnut',
  data: {
    labels: <?= json_encode(array_values(array_filter(['Female' => 'Female', 'Male' => 'Male', 'Not recorded' => $people['other'] ? 'Not recorded' : null]))) ?>,
    datasets: [{
      data: <?= json_encode(array_values(array_filter([$people['female'], $people['male'], $people['other'] ?: null], fn($v) => $v !== null))) ?>,
      backgroundColor: ['#8B5CF6', '#3B82F6', '#94A3B8'], borderWidth: 0, cutout: '72%'
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
<?php endif; ?>

<?php if ($hasFinance): ?>
new Chart(document.getElementById('financeChart'), {
  type: 'bar',
  data: {
    labels: ['Total Income', 'Expenses', 'Balance'],
    datasets: [{
      label: 'Amount (<?= $cur ?>)',
      data: [<?= (float)$finance['income'] ?>, <?= (float)$finance['expenses'] ?>, <?= (float)$finance['balance'] ?>],
      backgroundColor: ['#10B981', '#EF4444', '#3B82F6'], borderRadius: 6, maxBarThickness: 56
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => '<?= $cur ?> ' + Number(c.raw).toLocaleString(undefined, { minimumFractionDigits: 2 }) } } },
    scales: {
      y: { beginAtZero: true, grid: { color: gridColor }, ticks: { callback: v => Intl.NumberFormat(undefined, { notation: 'compact' }).format(v) } },
      x: { grid: { display: false } }
    }
  }
});
<?php endif; ?>

new Chart(document.getElementById('attendanceChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode(array_keys($attendance_hist)) ?>,
    datasets: [{
      label: 'Attendance %', data: <?= json_encode(array_values($attendance_hist)) ?>,
      borderColor: '#3B82F6', backgroundColor: 'rgba(59,130,246,0.12)', borderWidth: 3, tension: 0.4, fill: true,
      pointBackgroundColor: '#3B82F6', pointRadius: 4, pointHoverRadius: 6
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, max: 100, grid: { color: gridColor } }, x: { grid: { display: false } } }
  }
});
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
