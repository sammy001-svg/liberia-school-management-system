<?php
require_once ROOT_DIR . '/core/Controller.php';

class AnnouncementController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $announcements = $this->db->fetchAll("SELECT a.*, u.name AS author FROM announcements a JOIN users u ON a.author_id=u.id WHERE a.tenant_id=? ORDER BY a.is_pinned DESC, a.published_at DESC", [$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $stats = [
            'total'  => count($announcements),
            'pinned' => count(array_filter($announcements, fn($a) => $a['is_pinned'])),
            'expired' => count(array_filter($announcements, fn($a) => $a['expires_at'] && strtotime($a['expires_at']) < time())),
        ];
        $this->view('school/highschool/announcements/index', ['pageTitle'=>'Announcements','panelType'=>'school','announcements'=>$announcements,'classes'=>$classes,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $this->redirect('/school/announcements');
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $errors = $this->validate($_POST, [
            'title' => 'required|max:255',
            'body'  => 'required',
            'expires_at' => 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/announcements'); }
        $this->db->insert(
            "INSERT INTO announcements (tenant_id,author_id,title,body,audience,class_id,is_pinned,expires_at) VALUES (?,?,?,?,?,?,?,?)",
            [$this->tid,$_SESSION['user_id'],$_POST['title'],$_POST['body'],$_POST['audience']??'all',$_POST['class_id']?:null,(int)($_POST['is_pinned']??0),$_POST['expires_at']?:null]
        );
        $this->flash('success','Announcement posted.'); $this->redirect('/school/announcements');
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $this->db->execute("DELETE FROM announcements WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success','Announcement removed.'); $this->redirect('/school/announcements');
    }
}

class MessageController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth();
        $msgs = $this->db->fetchAll("SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.recipient_id=? AND m.tenant_id=? ORDER BY m.created_at DESC", [$_SESSION['user_id'],$this->tid]);
        $stats = [
            'total'  => count($msgs),
            'unread' => count(array_filter($msgs, fn($m) => !$m['is_read'])),
        ];
        $this->view('school/highschool/messages/index', ['pageTitle'=>'Messages','panelType'=>'school','messages'=>$msgs,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function compose(): void {
        $this->requireAuth();
        $users = $this->db->fetchAll("SELECT id,name FROM users WHERE tenant_id=? AND id!=? ORDER BY name", [$this->tid,$_SESSION['user_id']]);
        $replyTo = null;
        $prefillSubject = '';
        if (!empty($_GET['reply_to'])) {
            $replyTo = $this->db->fetchOne("SELECT id,name FROM users WHERE id=? AND tenant_id=?", [$_GET['reply_to'], $this->tid]);
            if (!empty($_GET['subject'])) { $prefillSubject = 'Re: ' . $_GET['subject']; }
        }
        $this->view('school/highschool/messages/compose', ['pageTitle'=>'Compose','panelType'=>'school','users'=>$users,'replyTo'=>$replyTo,'prefillSubject'=>$prefillSubject,'flash'=>$this->getFlash()]);
    }

    public function send(): void {
        $this->requireAuth();
        $errors = $this->validate($_POST, ['recipient_id' => 'required', 'body' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/messages/compose'); }
        $this->db->insert("INSERT INTO messages (tenant_id,sender_id,recipient_id,subject,body) VALUES (?,?,?,?,?)",
            [$this->tid,$_SESSION['user_id'],$_POST['recipient_id'],$_POST['subject']??'',$_POST['body']]);
        $this->flash('success','Message sent.'); $this->redirect('/school/messages');
    }

    public function show(string $id): void {
        $this->requireAuth();
        $msg = $this->db->fetchOne("SELECT m.*,u.name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=? AND m.tenant_id=? AND m.recipient_id=?",[$id,$this->tid,$_SESSION['user_id']]);
        if (!$msg) { $this->redirect('/school/messages'); }
        if (!$msg['is_read']) { $this->db->execute("UPDATE messages SET is_read=1,read_at=NOW() WHERE id=?",[$id]); }
        $this->view('school/highschool/messages/show', ['pageTitle'=>'Message','panelType'=>'school','message'=>$msg,'flash'=>$this->getFlash()]);
    }

    public function delete(string $id): void {
        $this->requireAuth();
        $this->db->execute("DELETE FROM messages WHERE id=? AND tenant_id=? AND recipient_id=?", [$id, $this->tid, $_SESSION['user_id']]);
        $this->flash('success','Message deleted.'); $this->redirect('/school/messages');
    }
}

class GradeController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $exams = $this->db->fetchAll(
            "SELECT e.*, c.name AS class_name, (SELECT COUNT(DISTINCT student_id) FROM grades g WHERE g.exam_id=e.id) AS graded_count
             FROM exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.tenant_id=? ORDER BY e.exam_date DESC", [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $terms = $this->db->fetchAll("SELECT id,name FROM terms WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $academicYears = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $stats = [
            'total'    => count($exams),
            'upcoming' => count(array_filter($exams, fn($e) => $e['exam_date'] && strtotime($e['exam_date']) >= strtotime(date('Y-m-d')))),
            'graded'   => count(array_filter($exams, fn($e) => $e['graded_count'] > 0)),
        ];
        $this->view('school/highschool/grades/index', ['pageTitle'=>'Grades & Exams','panelType'=>'school','exams'=>$exams,'classes'=>$classes,'terms'=>$terms,'academicYears'=>$academicYears,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function storeExam(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $errors = $this->validate($_POST, [
            'name'        => 'required|max:150',
            'exam_date'   => 'date',
            'total_marks' => 'numeric',
            'pass_marks'  => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/grades'); }
        $this->db->insert(
            "INSERT INTO exams (tenant_id,name,class_id,term_id,academic_year_id,exam_date,total_marks,pass_marks) VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['name'], $_POST['class_id']?:null, $_POST['term_id']?:null, $_POST['academic_year_id']?:null,
                $_POST['exam_date']?:null, $_POST['total_marks']?:100, $_POST['pass_marks']?:40,
            ]
        );
        $this->flash('success', 'Exam created.');
        $this->redirect('/school/grades');
    }

    public function enter(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $exams   = $this->db->fetchAll("SELECT id,name FROM exams WHERE tenant_id=?", [$this->tid]);
        $students = []; $courses = [];
        if (!empty($_GET['class_id'])) {
            $students = $this->db->fetchAll("SELECT s.id,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",[$_GET['class_id'],$this->tid]);
            $courses  = $this->db->fetchAll("SELECT id,name FROM courses WHERE class_id=? AND tenant_id=?",[$_GET['class_id'],$this->tid]);
        }
        $this->view('school/highschool/grades/enter', ['pageTitle'=>'Enter Grades','panelType'=>'school','classes'=>$classes,'exams'=>$exams,'students'=>$students,'courses'=>$courses,'selectedClass'=>$_GET['class_id']??'','flash'=>$this->getFlash()]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Teacher']);
        foreach ($_POST['grades'] ?? [] as $studentId => $marks) {
            foreach ($marks as $courseId => $score) {
                $pct = (float)$score;
                $letter = $pct>=90?'A+':($pct>=80?'A':($pct>=70?'B':($pct>=60?'C':($pct>=50?'D':'F'))));
                $gpa    = $pct>=90?4.0:($pct>=80?3.5:($pct>=70?3.0:($pct>=60?2.5:($pct>=50?2.0:0.0))));
                $this->db->insert("INSERT INTO grades (tenant_id,student_id,course_id,exam_id,marks_obtained,total_marks,grade_letter,gpa_points,graded_by) VALUES (?,?,?,?,?,100,?,?,?) ON DUPLICATE KEY UPDATE marks_obtained=VALUES(marks_obtained),grade_letter=VALUES(grade_letter)",
                    [$this->tid,$studentId,$courseId,$_POST['exam_id']??null,$pct,$letter,$gpa,$_SESSION['user_id']]);
            }
        }
        $this->flash('success','Grades saved.'); $this->redirect('/school/grades');
    }

    public function report(string $studentId): void {
        $this->requireAuth(['School Admin','Teacher']);
        $student = $this->db->fetchOne("SELECT s.*,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",[$studentId,$this->tid]);
        $grades  = $this->db->fetchAll("SELECT g.*,c.name AS course_name,e.name AS exam_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id LEFT JOIN exams e ON g.exam_id=e.id WHERE g.student_id=? AND g.tenant_id=? ORDER BY g.created_at DESC",[$studentId,$this->tid]);
        $this->view('school/highschool/grades/report', ['pageTitle'=>'Grade Report','panelType'=>'school','student'=>$student,'grades'=>$grades,'flash'=>$this->getFlash()]);
    }

    public function reportCard(string $studentId): void {
        $this->requireAuth(['School Admin','Teacher']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.gender, u.date_of_birth FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",
            [$studentId, $this->tid]
        );
        if (!$student) { $this->redirect('/school/students'); }
        $class  = $student['class_id'] ? $this->db->fetchOne("SELECT * FROM classes WHERE id=?", [$student['class_id']]) : null;
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);

        $examOptions = $this->db->fetchAll(
            "SELECT DISTINCT e.id, e.name, e.exam_date FROM grades g JOIN exams e ON g.exam_id=e.id WHERE g.student_id=? AND g.tenant_id=? ORDER BY e.exam_date DESC",
            [$studentId, $this->tid]
        );
        $examId = $_GET['exam_id'] ?? ($examOptions[0]['id'] ?? null);
        $exam   = $examId ? $this->db->fetchOne("SELECT * FROM exams WHERE id=? AND tenant_id=?", [$examId, $this->tid]) : null;

        $grades = [];
        $totalObtained = 0; $totalPossible = 0;
        if ($examId) {
            $grades = $this->db->fetchAll(
                "SELECT g.*, c.name AS course_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id WHERE g.student_id=? AND g.exam_id=? AND g.tenant_id=? ORDER BY c.name",
                [$studentId, $examId, $this->tid]
            );
            foreach ($grades as $g) { $totalObtained += $g['marks_obtained']; $totalPossible += $g['total_marks']; }
        }
        $overallPct = $totalPossible > 0 ? round($totalObtained / $totalPossible * 100, 1) : 0;
        $overallGrade = $overallPct>=90?'A+':($overallPct>=80?'A':($overallPct>=70?'B':($overallPct>=60?'C':($overallPct>=50?'D':'F'))));

        $rank = null; $rankOf = null;
        if ($examId && $student['class_id']) {
            $classTotals = $this->db->fetchAll(
                "SELECT g.student_id, SUM(g.marks_obtained) obtained
                 FROM grades g JOIN students s2 ON g.student_id=s2.id
                 WHERE g.exam_id=? AND g.tenant_id=? AND s2.class_id=?
                 GROUP BY g.student_id ORDER BY obtained DESC",
                [$examId, $this->tid, $student['class_id']]
            );
            $rankOf = count($classTotals);
            foreach ($classTotals as $i => $row) {
                if ((int)$row['student_id'] === (int)$studentId) { $rank = $i + 1; break; }
            }
        }

        $attendance = null;
        if ($exam && $exam['term_id']) {
            $term = $this->db->fetchOne("SELECT * FROM terms WHERE id=?", [$exam['term_id']]);
            if ($term) {
                $total = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE student_id=? AND tenant_id=? AND date BETWEEN ? AND ?", [$studentId, $this->tid, $term['start_date'], $term['end_date']])['c'];
                $present = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE student_id=? AND tenant_id=? AND status='present' AND date BETWEEN ? AND ?", [$studentId, $this->tid, $term['start_date'], $term['end_date']])['c'];
                $attendance = ['total'=>$total, 'present'=>$present, 'pct'=>$total>0 ? round($present/$total*100,1) : null];
            }
        }

        $this->view('school/report_card', [
            'pageTitle' => 'Report Card', 'tenant' => $tenant,
            'student' => $student, 'class' => $class, 'exam' => $exam,
            'examOptions' => $examOptions, 'selectedExamId' => $examId,
            'grades' => $grades, 'totalObtained' => $totalObtained, 'totalPossible' => $totalPossible,
            'overallPct' => $overallPct, 'overallGrade' => $overallGrade,
            'rank' => $rank, 'rankOf' => $rankOf, 'attendance' => $attendance,
        ]);
    }

    // Standard order for the period types seen in TSM ranking exports —
    // anything not in this list (e.g. a future/unknown period label) sorts after, alphabetically.
    private const RANKING_PERIOD_ORDER = [
        '1st Pd.', '2nd Pd.', '3rd Pd.', 'Sem. Ave. 1', 'Exam 1',
        '4th Pd.', '5th Pd.', '6th Pd.', 'Sem. Ave. 2', 'Exam 2', 'Yearly Ave.',
    ];

    private function rankingPeriodNames(): array {
        $periods = $this->db->fetchAll("SELECT DISTINCT period FROM student_rankings WHERE tenant_id=?", [$this->tid]);
        $periodNames = array_column($periods, 'period');
        usort($periodNames, function ($a, $b) {
            $oa = array_search($a, self::RANKING_PERIOD_ORDER);
            $ob = array_search($b, self::RANKING_PERIOD_ORDER);
            if ($oa === false && $ob === false) return strcmp($a, $b);
            if ($oa === false) return 1;
            if ($ob === false) return -1;
            return $oa <=> $ob;
        });
        return $periodNames;
    }

    private function fetchRankings(string $period, string $classId): array {
        if (!$period) { return []; }
        $params = [$this->tid, $period];
        $where = "r.tenant_id=? AND r.period=?";
        if ($classId) { $where .= " AND s.class_id=?"; $params[] = $classId; }
        return $this->db->fetchAll(
            "SELECT r.*, u.name AS student_name, s.admission_no, c.name AS class_name
             FROM student_rankings r
             JOIN students s ON r.student_id=s.id
             JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE {$where}
             ORDER BY r.rank_position ASC, r.score DESC",
            $params
        );
    }

    public function rankings(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $classId = $_GET['class_id'] ?? '';
        $period  = $_GET['period'] ?? '';

        $periodNames = $this->rankingPeriodNames();
        if (!$period && $periodNames) { $period = end($periodNames); }

        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);

        $rankings = $this->fetchRankings($period, $classId);
        $stats = ['count' => 0, 'avg' => null, 'top' => null];
        if ($rankings) {
            $stats['count'] = count($rankings);
            $stats['avg'] = round(array_sum(array_column($rankings, 'score')) / count($rankings), 1);
            $stats['top'] = $rankings[0];
        }

        $this->view('school/highschool/grades/rankings', [
            'pageTitle' => 'Student Rankings', 'panelType' => 'school',
            'rankings' => $rankings, 'periods' => $periodNames, 'classes' => $classes,
            'selectedPeriod' => $period, 'selectedClass' => $classId, 'stats' => $stats,
            'flash' => $this->getFlash(), 'importErrors' => $this->getImportErrors(),
        ]);
    }

    public function exportRankingsCsv(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $classId = $_GET['class_id'] ?? '';
        $period  = $_GET['period'] ?? '';
        if (!$period) {
            $periodNames = $this->rankingPeriodNames();
            $period = end($periodNames) ?: '';
        }
        $rankings = $this->fetchRankings($period, $classId);
        $rows = array_map(fn($r) => [
            $r['rank_position'] ?? '', $r['student_name'], $r['admission_no'], $r['class_name'] ?? '',
            $period, number_format((float)$r['score'], 1), $r['group_size'] ?? '',
        ], $rankings);
        $safePeriod = preg_replace('/[^a-z0-9]+/i', '_', $period ?: 'rankings');
        $this->downloadCsv("rankings_{$safePeriod}.csv", ['Rank','Student','Admission No','Class','Period','Score','Group Size'], $rows);
    }

    public function printRankings(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $classId = $_GET['class_id'] ?? '';
        $period  = $_GET['period'] ?? '';
        if (!$period) {
            $periodNames = $this->rankingPeriodNames();
            $period = end($periodNames) ?: '';
        }
        $rankings = $this->fetchRankings($period, $classId);
        $className = null;
        if ($classId) {
            $cls = $this->db->fetchOne("SELECT name FROM classes WHERE id=? AND tenant_id=?", [$classId, $this->tid]);
            $className = $cls['name'] ?? null;
        }
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/rankings_print', [
            'pageTitle' => 'Rankings', 'tenant' => $tenant,
            'rankings' => $rankings, 'period' => $period, 'className' => $className,
        ]);
    }

    public function bulkTemplateRankings(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $this->downloadCsvTemplate('rankings_template.csv',
            ['TSM ID','Name','Class','Period','Grade','Rank','Group Size'],
            ['CAS0001','John Doe','3rd Grade','1st Pd.','88.5','4','197']
        );
    }

    public function bulkUploadRankings(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $rows = $this->parseCsvUpload('csv_file');
        $students = $this->db->fetchAll("SELECT id, admission_no FROM students WHERE tenant_id=?", [$this->tid]);
        $studentByAdmNo = [];
        foreach ($students as $s) { $studentByAdmNo[strtolower($s['admission_no'])] = $s['id']; }

        $success = 0;
        $rowErrors = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            try {
                $tsmId  = $row['tsm id'] ?? '';
                $period = trim($row['period'] ?? '');
                $grade  = $row['grade'] ?? '';
                if ($tsmId === '' || $period === '' || $grade === '') {
                    $rowErrors[] = "Row {$line}: TSM ID, Period and Grade are required.";
                    continue;
                }
                $studentId = $studentByAdmNo[strtolower($tsmId)] ?? null;
                if (!$studentId) {
                    $rowErrors[] = "Row {$line}: no student found with TSM ID '{$tsmId}'.";
                    continue;
                }
                $this->db->execute(
                    "INSERT INTO student_rankings (tenant_id,student_id,period,score,rank_position,group_size)
                     VALUES (?,?,?,?,?,?)
                     ON DUPLICATE KEY UPDATE score=VALUES(score),rank_position=VALUES(rank_position),group_size=VALUES(group_size)",
                    [
                        $this->tid, $studentId, $period, (float)$grade,
                        $row['rank'] !== '' ? (int)$row['rank'] : null,
                        ($row['group size'] ?? '') !== '' ? (int)$row['group size'] : null,
                    ]
                );
                $success++;
            } catch (\Throwable $e) {
                error_log("Ranking import row {$line} failed: " . $e->getMessage());
                $reason = substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 120);
                $rowErrors[] = "Row {$line}: could not be imported ({$reason}).";
            }
        }
        $this->finishBulkImport($success, count($rows), $rowErrors, '/school/grades/rankings');
    }
}

class TimetableController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Student']);
        $classId   = $_GET['class_id'] ?? '';
        $classes   = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $courses   = $this->db->fetchAll("SELECT id,name FROM courses WHERE tenant_id=?", [$this->tid]);
        $teachers  = $this->db->fetchAll("SELECT t.id,u.name FROM teachers t JOIN users u ON t.user_id=u.id WHERE t.tenant_id=?", [$this->tid]);
        $academicYears = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $terms     = $this->db->fetchAll("SELECT id,name FROM terms WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $timetable = [];
        if ($classId) {
            $rows = $this->db->fetchAll("SELECT tt.*,c.name AS course_name,u.name AS teacher_name FROM timetable tt LEFT JOIN courses c ON tt.course_id=c.id LEFT JOIN teachers t ON tt.teacher_id=t.id LEFT JOIN users u ON t.user_id=u.id WHERE tt.tenant_id=? AND tt.class_id=? ORDER BY FIELD(tt.day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday'),tt.start_time",[$this->tid,$classId]);
            foreach ($rows as $r) { $timetable[$r['day_of_week']][] = $r; }
        }
        $this->view('school/highschool/timetable/index', ['pageTitle'=>'Timetable','panelType'=>'school','classes'=>$classes,'courses'=>$courses,'teachers'=>$teachers,'academicYears'=>$academicYears,'terms'=>$terms,'timetable'=>$timetable,'classId'=>$classId,'flash'=>$this->getFlash()]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/timetable');
    }

    public function store(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, [
            'class_id'    => 'required',
            'day_of_week' => 'required',
            'start_time'  => 'required',
            'end_time'    => 'required',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/timetable?class_id='.($_POST['class_id'] ?? '')); }
        $this->db->insert(
            "INSERT INTO timetable (tenant_id,class_id,course_id,teacher_id,academic_year_id,term_id,day_of_week,start_time,end_time,room) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid,$_POST['class_id'],$_POST['course_id']?:null,$_POST['teacher_id']?:null,
                $_POST['academic_year_id']?:null,$_POST['term_id']?:null,
                $_POST['day_of_week'],$_POST['start_time'],$_POST['end_time'],$_POST['room']??'',
            ]
        );
        $this->flash('success','Timetable entry added.'); $this->redirect('/school/timetable?class_id='.$_POST['class_id']);
    }

    public function deleteEntry(string $id): void {
        $this->requireAuth(['School Admin']);
        $entry = $this->db->fetchOne("SELECT class_id FROM timetable WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($entry) {
            $this->db->execute("DELETE FROM timetable WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        }
        $this->flash('success','Timetable entry removed.');
        $this->redirect('/school/timetable?class_id='.($entry['class_id'] ?? ''));
    }
}

class ParentController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin']);
        $search = $_GET['q'] ?? '';
        $params = [$this->tid];
        $where = "p.tenant_id=?";
        if ($search) { $where .= " AND (u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM parents p JOIN users u ON p.user_id=u.id WHERE $where", $params)['c'];
        $p2 = $this->paginate($totalCount);
        $parents = $this->db->fetchAll(
            "SELECT p.*,u.name,u.email,u.phone,u.employee_no,u.status,
                    (SELECT COUNT(*) FROM parent_students ps WHERE ps.parent_id=p.id) AS children_count,
                    (SELECT GROUP_CONCAT(cu.name SEPARATOR ', ') FROM parent_students ps JOIN students cs ON ps.student_id=cs.id JOIN users cu ON cs.user_id=cu.id WHERE ps.parent_id=p.id) AS children_names
             FROM parents p JOIN users u ON p.user_id=u.id WHERE $where ORDER BY u.name LIMIT {$p2['perPage']} OFFSET {$p2['offset']}",
            $params
        );
        $students = $this->db->fetchAll("SELECT s.id,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid]);
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN EXISTS(SELECT 1 FROM parent_students ps WHERE ps.parent_id=p.id) THEN 1 ELSE 0 END) linked,
                    SUM(CASE WHEN NOT EXISTS(SELECT 1 FROM parent_students ps WHERE ps.parent_id=p.id) THEN 1 ELSE 0 END) unlinked
             FROM parents p WHERE p.tenant_id=?", [$this->tid]
        );
        $this->view('school/highschool/parents/index', [
            'pageTitle'=>'Parents','panelType'=>'school','parents'=>$parents,'students'=>$students,'search'=>$search,'stats'=>$stats,
            'page'=>$p2['page'],'totalPages'=>$p2['totalPages'],'total'=>$p2['total'],'perPage'=>$p2['perPage'],
            'flash'=>$this->getFlash(), 'importErrors'=>$this->getImportErrors(),
        ]);
    }

    public function create(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/parents');
    }

    public function bulkTemplate(): void {
        $this->requireAuth(['School Admin']);
        $this->downloadCsvTemplate('parents_template.csv',
            ['name','email','phone','gender','dob','occupation','workplace','student_admission_no','relationship'],
            ['John Doe','john.doe@example.com','0779876543','male','1980-02-10','Trader','Waterside Market','ADM-2026-0001','Father']
        );
    }

    public function bulkUpload(): void {
        $this->requireAuth(['School Admin']);
        $rows = $this->parseCsvUpload('csv_file');
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Parent' LIMIT 1")['id'] ?? 8;
        $students = $this->db->fetchAll("SELECT id,admission_no FROM students WHERE tenant_id=?", [$this->tid]);
        $studentByAdmNo = [];
        foreach ($students as $s) { $studentByAdmNo[strtolower($s['admission_no'])] = $s['id']; }

        $success = 0;
        $rowErrors = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            try {
                $name = $row['name'] ?? '';
                $email = $row['email'] ?? '';
                if ($name === '') {
                    $rowErrors[] = "Row {$line}: name is required.";
                    continue;
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "Row {$line}: '{$email}' is not a valid email address.";
                    continue;
                }
                $studentId = null;
                if (!empty($row['student_admission_no'])) {
                    $studentId = $studentByAdmNo[strtolower($row['student_admission_no'])] ?? null;
                    if ($studentId === null) { $rowErrors[] = "Row {$line}: student with admission no '{$row['student_admission_no']}' not found — parent added without a linked student."; }
                }
                $userId = $this->db->insert(
                    "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,status) VALUES (?,?,?,?,?,?,?,?)",
                    [$this->tid, $roleId, $name, $email ?: null, $row['phone'] ?? '', $row['gender'] ?: null, $row['dob'] ?: null, 'active']
                );
                $this->db->execute("UPDATE users SET password_hash=? WHERE id=?", [password_hash('Parent@123', PASSWORD_BCRYPT), $userId]);
                $parentId = $this->db->insert(
                    "INSERT INTO parents (tenant_id,user_id,occupation,workplace) VALUES (?,?,?,?)",
                    [$this->tid, $userId, $row['occupation'] ?? '', $row['workplace'] ?: null]
                );
                if ($studentId !== null) {
                    $this->db->insert("INSERT INTO parent_students (parent_id,student_id,relationship) VALUES (?,?,?)",
                        [$parentId, $studentId, $row['relationship'] ?: 'parent']);
                }
                $success++;
            } catch (\Throwable $e) {
                $reason = str_contains($e->getMessage(), 'Duplicate entry') ? 'that email is already registered.' : 'could not be imported.';
                $rowErrors[] = "Row {$line}: {$reason}";
            }
        }
        $this->finishBulkImport($success, count($rows), $rowErrors, '/school/parents');
    }

    public function store(): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, [
            'name'  => 'required|max:150',
            'email' => 'email|max:150',
            'phone' => 'required|max:30',
            'dob'   => 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/parents'); }
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Parent' LIMIT 1")['id'] ?? 8;
        $pw = password_hash($_POST['password'] ?: 'Parent@123', PASSWORD_BCRYPT);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,phone,gender,date_of_birth,address,employee_no,status) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$this->tid,$roleId,$_POST['name'],$_POST['email']?:null,$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$_POST['address']??'',$_POST['employee_no']?:null,'active']
        );
        $this->db->execute("UPDATE users SET password_hash=? WHERE id=?",[$pw,$userId]);
        $parentId = $this->db->insert(
            "INSERT INTO parents (tenant_id,user_id,occupation,workplace,national_id,emergency_contact_phone) VALUES (?,?,?,?,?,?)",
            [$this->tid,$userId,$_POST['occupation']??'',$_POST['workplace']??null,$_POST['national_id']??null,$_POST['emergency_contact_phone']??null]
        );
        if (!empty($_POST['student_id'])) {
            $this->db->insert("INSERT INTO parent_students (parent_id,student_id,relationship) VALUES (?,?,?)",
                [$parentId,$_POST['student_id'],$_POST['relationship']??'parent']);
        }
        $this->flash('success','Parent account created.'); $this->redirect('/school/parents');
    }

    public function show(string $id): void {
        $this->requireAuth(['School Admin']);
        $parent = $this->db->fetchOne(
            "SELECT p.*, u.name, u.email, u.phone, u.gender, u.date_of_birth, u.address
             FROM parents p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.tenant_id=?", [$id, $this->tid]
        );
        if (!$parent) { $this->redirect('/school/parents'); }
        $children = $this->db->fetchAll(
            "SELECT s.id, s.admission_no, s.status, ps.relationship, u.name, c.name AS class_name
             FROM parent_students ps JOIN students s ON ps.student_id=s.id JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE ps.parent_id=? ORDER BY u.name", [$id]
        );
        $linkedIds = array_column($children, 'id');
        $availableStudents = $this->db->fetchAll(
            "SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active'"
            . (!empty($linkedIds) ? " AND s.id NOT IN (" . implode(',', array_map('intval', $linkedIds)) . ")" : "")
            . " ORDER BY u.name", [$this->tid]
        );
        $this->view('school/highschool/parents/show', [
            'pageTitle'=>$parent['name'],'panelType'=>'school','parent'=>$parent,
            'children'=>$children,'availableStudents'=>$availableStudents,
            'flash'=>$this->getFlash(),
        ]);
    }

    public function edit(string $id): void {
        $this->requireAuth(['School Admin']);
        $parent = $this->db->fetchOne(
            "SELECT p.*, u.name, u.email, u.phone, u.gender, u.date_of_birth, u.address, u.employee_no
             FROM parents p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.tenant_id=?", [$id, $this->tid]
        );
        if (!$parent) { $this->redirect('/school/parents'); }
        $this->view('school/highschool/parents/form', ['pageTitle'=>'Edit Parent','panelType'=>'school','parent'=>$parent,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requireAuth(['School Admin']);
        $parent = $this->db->fetchOne("SELECT user_id FROM parents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$parent) { $this->redirect('/school/parents'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'email' => 'email|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/parents/'.$id.'/edit'); }
        $this->db->execute("UPDATE users SET name=?,email=?,phone=?,gender=?,date_of_birth=?,address=?,employee_no=? WHERE id=?",
            [$_POST['name'],$_POST['email']?:null,$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$_POST['address']??'',$_POST['employee_no']?:null,$parent['user_id']]);
        $this->db->execute("UPDATE parents SET occupation=?,workplace=?,national_id=?,emergency_contact_phone=? WHERE id=? AND tenant_id=?",
            [$_POST['occupation']??'',$_POST['workplace']??null,$_POST['national_id']??null,$_POST['emergency_contact_phone']??null,$id,$this->tid]);
        $this->flash('success','Parent updated.'); $this->redirect('/school/parents/'.$id);
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin']);
        $parent = $this->db->fetchOne("SELECT user_id FROM parents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($parent) {
            $this->db->execute("DELETE FROM parents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?", [$parent['user_id']]);
        }
        $this->flash('success','Parent removed.'); $this->redirect('/school/parents');
    }

    public function linkChild(string $id): void {
        $this->requireAuth(['School Admin']);
        $errors = $this->validate($_POST, ['student_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/parents/'.$id); }
        $this->db->insert("INSERT INTO parent_students (parent_id,student_id,relationship) VALUES (?,?,?)",
            [$id, $_POST['student_id'], $_POST['relationship'] ?: 'parent']);
        $this->flash('success','Child linked.'); $this->redirect('/school/parents/'.$id);
    }

    public function unlinkChild(string $id, string $studentId): void {
        $this->requireAuth(['School Admin']);
        $this->db->execute("DELETE FROM parent_students WHERE parent_id=? AND student_id=?", [$id, $studentId]);
        $this->flash('success','Child unlinked.'); $this->redirect('/school/parents/'.$id);
    }
}

class SchoolSettingsController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin']);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/settings', ['pageTitle'=>'School Settings','panelType'=>'school','tenant'=>$tenant,'flash'=>$this->getFlash()]);
    }

    public function update(): void {
        $this->requireAuth(['School Admin']);

        $errors = [];
        $newLogoUrl = $this->handleImageUpload('logo', 'logos', $errors, 2 * 1024 * 1024);
        if ($errors) { $this->failValidation($errors, '/school/settings'); }

        $current = $this->db->fetchOne("SELECT logo FROM tenants WHERE id=?", [$this->tid]);
        if (!empty($_POST['remove_logo'])) {
            $logo = null;
        } elseif ($newLogoUrl !== null) {
            $logo = $newLogoUrl;
        } else {
            $logo = $current['logo'] ?? null;
        }

        $this->db->execute("UPDATE tenants SET name=?,email=?,phone=?,address=?,country=?,timezone=?,academic_year=?,currency=?,domain=?,primary_color=?,secondary_color=?,accent_color=?,logo=? WHERE id=?",
            [
                $_POST['name'], $_POST['email']??'', $_POST['phone']??'', $_POST['address']??'',
                $_POST['country']??'', $_POST['timezone']??'UTC', $_POST['academic_year']??'',
                $_POST['currency']??'Ksh',
                $_POST['domain']??null, $_POST['primary_color']??'#4F46E5',
                $_POST['secondary_color']??'#7C3AED', $_POST['accent_color']??'#06B6D4',
                $logo,
                $this->tid
            ]);

        // Update session branding if it's the current school
        if ($_SESSION['tenant_id'] == $this->tid) {
            $_SESSION['branding']['name']            = $_POST['name'];
            $_SESSION['branding']['primary_color']   = $_POST['primary_color'];
            $_SESSION['branding']['secondary_color']  = $_POST['secondary_color'];
            $_SESSION['branding']['logo']             = $logo;
        }

        $this->flash('success','Settings and branding updated.'); $this->redirect('/school/settings');
    }
}
