<?php
require_once ROOT_DIR . '/core/Controller.php';

class SchoolDashboardController extends Controller {
    public function index(): void {
        // Any logged-in school-panel user (built-in or custom role) can view the dashboard —
        // Student/Parent accounts have their own dedicated portals and never route here.
        if (!isset($_SESSION['user_id'])) { $this->redirect('/login'); }
        if (!empty($_SESSION['student_id']) || !empty($_SESSION['parent_id'])) { $this->redirect('/login'); }
        $tid = $this->tenantId();
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$tid]);

        // An instructor without finance access gets a teaching-focused dashboard instead of
        // the admin one — the admin view reports school-wide fee collection, which is not a
        // teacher's business. Anyone holding finance.manage (admin/accountant) keeps the
        // full view even if they also happen to teach.
        if ($this->isInstructor() && !$this->hasPermission('finance.manage')) {
            $this->teacherDashboard($tid, $tenant);
            return;
        }

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

        $this->view('school/highschool/dashboard', [
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
     * Teaching-focused dashboard: only the classes, subjects and students this teacher is
     * actually responsible for. Deliberately carries no fee, payroll or school-wide figures.
     */
    private function teacherDashboard(?int $tid, ?array $tenant): void {
        $teacher = $this->db->fetchOne(
            "SELECT id, class_id FROM teachers WHERE user_id=? AND tenant_id=?", [$_SESSION['user_id'], $tid]
        );
        $teacherId = (int)($teacher['id'] ?? 0);
        $formClassId = (int)($teacher['class_id'] ?? 0);

        // Classes reached either by teaching one of their subjects, or by being the form teacher.
        $myClasses = $this->db->fetchAll(
            "SELECT DISTINCT c.id, c.name, c.grade_level, c.section, c.room_number,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id AND s.status='active') AS student_count,
                    (SELECT COUNT(*) FROM attendance a WHERE a.class_id=c.id AND a.date=CURDATE()) AS marked_today,
                    (c.id = ?) AS is_form_class
             FROM classes c
             WHERE c.tenant_id=? AND (
                   c.id = ?
                OR c.id IN (SELECT cc.class_id FROM course_classes cc
                            JOIN teacher_courses tc ON tc.course_id=cc.course_id
                            WHERE tc.teacher_id=?)
             )
             ORDER BY c.name", [$formClassId, $tid, $formClassId, $teacherId]
        );

        $mySubjects = $this->db->fetchAll(
            "SELECT co.id, co.name,
                    (SELECT COUNT(*) FROM course_classes cc WHERE cc.course_id=co.id) AS class_count
             FROM courses co JOIN teacher_courses tc ON tc.course_id=co.id
             WHERE tc.teacher_id=? AND co.tenant_id=? ORDER BY co.name", [$teacherId, $tid]
        );

        $classIds = array_map('intval', array_column($myClasses, 'id'));
        $inClasses = $classIds ? '(' . implode(',', $classIds) . ')' : '(0)';

        $todaySchedule = $this->db->fetchAll(
            "SELECT tt.start_time, tt.end_time, tt.room, c.name AS class_name, co.name AS course_name
             FROM timetable tt
             LEFT JOIN classes c ON tt.class_id=c.id
             LEFT JOIN courses co ON tt.course_id=co.id
             WHERE tt.tenant_id=? AND tt.teacher_id=? AND tt.day_of_week=?
             ORDER BY tt.start_time", [$tid, $teacherId, strtolower(date('l'))]
        );

        // Submissions still waiting on a mark, newest deadline first.
        $pendingGrading = $this->db->fetchAll(
            "SELECT h.id, h.title, h.due_date, c.name AS class_name, COUNT(hs.id) AS ungraded
             FROM homework h
             JOIN homework_submissions hs ON hs.homework_id=h.id AND hs.score IS NULL
             LEFT JOIN classes c ON h.class_id=c.id
             WHERE h.tenant_id=? AND h.teacher_id=?
             GROUP BY h.id, h.title, h.due_date, c.name
             ORDER BY h.due_date DESC LIMIT 6", [$tid, $teacherId]
        );

        $upcomingExams = $this->db->fetchAll(
            "SELECT e.id, e.name, e.exam_date, e.status, c.name AS class_name
             FROM exams e LEFT JOIN classes c ON e.class_id=c.id
             WHERE e.tenant_id=? AND e.exam_date >= CURDATE() AND (e.class_id IS NULL OR e.class_id IN {$inClasses})
             ORDER BY e.exam_date ASC LIMIT 6", [$tid]
        );

        $announcements = $this->db->fetchAll(
            "SELECT a.*, u.name AS author FROM announcements a JOIN users u ON a.author_id=u.id
             WHERE a.tenant_id=? AND (a.audience IN ('all','teachers') OR a.audience IS NULL)
             ORDER BY a.is_pinned DESC, a.published_at DESC LIMIT 4", [$tid]
        );

        $totalStudents = array_sum(array_column($myClasses, 'student_count'));
        $classesMarked = count(array_filter($myClasses, fn($c) => (int)$c['marked_today'] > 0));

        $stats = [
            'classes'   => count($myClasses),
            'subjects'  => count($mySubjects),
            'students'  => $totalStudents,
            'marked'    => $classesMarked,
            'toMark'    => max(0, count($myClasses) - $classesMarked),
            'toGrade'   => array_sum(array_column($pendingGrading, 'ungraded')),
        ];

        $this->view('school/highschool/teacher_dashboard', [
            'pageTitle'      => 'Dashboard',
            'panelType'      => 'school',
            'tenant'         => $tenant,
            'teacherName'    => $_SESSION['user_name'] ?? 'Teacher',
            'stats'          => $stats,
            'myClasses'      => $myClasses,
            'mySubjects'     => $mySubjects,
            'todaySchedule'  => $todaySchedule,
            'pendingGrading' => $pendingGrading,
            'upcomingExams'  => $upcomingExams,
            'announcements'  => $announcements,
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
