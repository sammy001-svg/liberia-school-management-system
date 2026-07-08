<?php
require_once ROOT_DIR . '/core/Controller.php';

class SchoolDashboardController extends Controller {
    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff']);
        $tid = $this->tenantId();
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$tid]);

        // Base Stats
        $students = $this->db->fetchOne("SELECT COUNT(*) AS c FROM students WHERE tenant_id=? AND status='active'",[$tid])['c']??0;
        $teachers = $this->db->fetchOne("SELECT COUNT(*) AS c FROM teachers WHERE tenant_id=?",[$tid])['c']??0;
        $classes  = $this->db->fetchOne("SELECT COUNT(*) AS c FROM classes WHERE tenant_id=?",[$tid])['c']??0;
        $present  = $this->db->fetchOne("SELECT COUNT(*) AS c FROM attendance WHERE tenant_id=? AND date=CURDATE() AND status='present'",[$tid])['c']??0;
        
        $attendance_percent = $students > 0 ? round(($present / $students) * 100, 1) : 0;

        $stats = [
            'students' => $students,
            'teachers' => $teachers,
            'classes'  => $classes,
            'attendance_pct' => $attendance_percent
        ];

        // Trends — real month-over-month (day-over-day for attendance) comparisons
        $studentsPrev = $this->db->fetchOne("SELECT COUNT(*) c FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' AND u.created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$tid])['c'] ?? 0;
        $teachersPrev = $this->db->fetchOne("SELECT COUNT(*) c FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=? AND u.created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$tid])['c'] ?? 0;
        $classesPrev  = $this->db->fetchOne("SELECT COUNT(*) c FROM classes WHERE tenant_id=? AND created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)", [$tid])['c'] ?? 0;
        $presentYesterday = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE tenant_id=? AND date=DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND status='present'", [$tid])['c'] ?? 0;
        $attendanceYesterdayPct = $students > 0 ? round(($presentYesterday / $students) * 100, 1) : 0;

        $trends = [
            'students'   => $this->trendBadge($students, $studentsPrev, 'from last month'),
            'teachers'   => $this->trendBadge($teachers, $teachersPrev, 'from last month'),
            'classes'    => $this->trendBadge($classes, $classesPrev, 'from last month'),
            'attendance' => $this->trendBadge($attendance_percent, $attendanceYesterdayPct, 'from yesterday', true),
        ];

        // Calendar — real events (exams + announcements) for the selected month
        $calMonth = $_GET['cal_month'] ?? date('Y-m');
        $calTimestamp = strtotime($calMonth . '-01');
        if ($calTimestamp === false) { $calMonth = date('Y-m'); $calTimestamp = strtotime($calMonth . '-01'); }
        $firstOfMonth = date('Y-m-01', $calTimestamp);
        $lastOfMonth  = date('Y-m-t', $calTimestamp);

        $examDays = $this->db->fetchAll("SELECT DISTINCT DAY(exam_date) d FROM exams WHERE tenant_id=? AND exam_date BETWEEN ? AND ?", [$tid, $firstOfMonth, $lastOfMonth]);
        $annDays  = $this->db->fetchAll("SELECT DISTINCT DAY(published_at) d FROM announcements WHERE tenant_id=? AND published_at BETWEEN ? AND ?", [$tid, $firstOfMonth, $lastOfMonth . ' 23:59:59']);
        $eventDays = array_unique(array_merge(array_column($examDays, 'd'), array_column($annDays, 'd')));

        $startOffset = (int)date('w', $calTimestamp);
        $daysInMonth = (int)date('t', $calTimestamp);
        $daysInPrevMonth = (int)date('t', strtotime('-1 month', $calTimestamp));
        $today = date('Y-m-d');
        $calendarDays = [];
        for ($i = $startOffset; $i > 0; $i--) {
            $calendarDays[] = ['day' => $daysInPrevMonth - $i + 1, 'inMonth' => false, 'isToday' => false, 'hasEvent' => false];
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dateStr = date('Y-m-d', mktime(0, 0, 0, (int)date('n', $calTimestamp), $d, (int)date('Y', $calTimestamp)));
            $calendarDays[] = ['day' => $d, 'inMonth' => true, 'isToday' => ($dateStr === $today), 'hasEvent' => in_array($d, $eventDays, true)];
        }
        $remainder = count($calendarDays) % 7;
        if ($remainder > 0) {
            for ($d = 1; $d <= (7 - $remainder); $d++) {
                $calendarDays[] = ['day' => $d, 'inMonth' => false, 'isToday' => false, 'hasEvent' => false];
            }
        }
        $calendar = [
            'days'      => $calendarDays,
            'label'     => date('F Y', $calTimestamp),
            'prevMonth' => date('Y-m', strtotime('-1 month', $calTimestamp)),
            'nextMonth' => date('Y-m', strtotime('+1 month', $calTimestamp)),
        ];

        // Announcements
        $announcements = $this->db->fetchAll(
            "SELECT a.*, u.name AS author FROM announcements a JOIN users u ON a.author_id=u.id
             WHERE a.tenant_id=? ORDER BY a.published_at DESC LIMIT 3", [$tid]
        );

        // Chart Data - Attendance
        $att_records = $this->db->fetchAll("SELECT date, 
            COUNT(*) as total_marked, 
            SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as total_present 
            FROM attendance 
            WHERE tenant_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
            GROUP BY date ORDER BY date ASC", [$tid]);
            
        $attendance_history = [];
        for($i=5; $i>=0; $i--) {
            $dayName = date('D', strtotime("-$i days"));
            $attendance_history[$dayName] = 0;
        }

        foreach($att_records as $rec) {
            $dayName = date('D', strtotime($rec['date']));
            $pct = $rec['total_marked'] > 0 ? round(($rec['total_present'] / $rec['total_marked']) * 100) : 0;
            if (isset($attendance_history[$dayName])) {
                $attendance_history[$dayName] = $pct;
            }
        }

        // Chart Data - Fees
        $feesData = $this->db->fetchOne("SELECT 
            COALESCE(SUM(amount_paid), 0) as collected,
            COALESCE(SUM(CASE WHEN status IN ('unpaid','partial') THEN amount_due - amount_paid - discount ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'overdue' THEN amount_due - amount_paid - discount ELSE 0 END), 0) as overdue
            FROM invoices WHERE tenant_id=?", [$tid]);

        $fees = [
            'collected' => $feesData['collected'],
            'pending' => $feesData['pending'],
            'overdue' => $feesData['overdue']
        ];

        // Chart Data - Exams
        $examsData = $this->db->fetchOne("SELECT 
            COUNT(CASE WHEN exam_date > CURDATE() THEN 1 END) as upcoming,
            COUNT(CASE WHEN exam_date = CURDATE() THEN 1 END) as in_progress,
            COUNT(CASE WHEN exam_date < CURDATE() THEN 1 END) as completed
            FROM exams WHERE tenant_id=?", [$tid]);

        $exams = [
            'upcoming' => $examsData['upcoming'],
            'in_progress' => $examsData['in_progress'],
            'completed' => $examsData['completed'],
            'cancelled' => 0
        ];

        $view = ($tenant['institution_type'] === 'university') 
                ? 'school/university/dashboard' 
                : 'school/highschool/dashboard';

        $this->view($view, [
            'pageTitle'      => 'Dashboard',
            'panelType'      => 'school',
            'tenant'         => $tenant,
            'stats'          => $stats,
            'trends'         => $trends,
            'calendar'       => $calendar,
            'announcements'  => $announcements,
            'attendance_hist'=> $attendance_history,
            'fees'           => $fees,
            'exams'          => $exams,
            'flash'          => $this->getFlash(),
        ]);
    }

    /**
     * Build a trend badge from a current vs previous value.
     * $pointsMode compares raw difference (for percentages) instead of a percent-of-percent change.
     */
    private function trendBadge(float $current, float $previous, string $period, bool $pointsMode = false): array {
        if ($pointsMode) {
            $diff = round($current - $previous, 1);
            if ($diff > 0) return ['dir' => 'up', 'label' => "+{$diff}% {$period}"];
            if ($diff < 0) return ['dir' => 'down', 'label' => abs($diff) . "% {$period}"];
            return ['dir' => 'flat', 'label' => "No change {$period}"];
        }
        if ($previous == 0) {
            return $current > 0 ? ['dir' => 'up', 'label' => 'New this month'] : ['dir' => 'flat', 'label' => "No change {$period}"];
        }
        $change = round((($current - $previous) / $previous) * 100, 1);
        if ($change > 0) return ['dir' => 'up', 'label' => "+{$change}% {$period}"];
        if ($change < 0) return ['dir' => 'down', 'label' => abs($change) . "% {$period}"];
        return ['dir' => 'flat', 'label' => "No change {$period}"];
    }
}
