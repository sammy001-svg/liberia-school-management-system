<?php
require_once ROOT_DIR . '/core/Controller.php';

class AnnouncementController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['announcements.manage']);
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
        $this->requirePermission(['announcements.manage']);
        $this->redirect('/school/announcements');
    }

    public function store(): void {
        $this->requirePermission(['announcements.manage']);
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
        $this->requirePermission(['announcements.manage']);
        $this->db->execute("DELETE FROM announcements WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success','Announcement removed.'); $this->redirect('/school/announcements');
    }
}

class MessageController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff']);
        $msgs = $this->db->fetchAll("SELECT m.*, u.name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.recipient_id=? AND m.tenant_id=? ORDER BY m.created_at DESC", [$_SESSION['user_id'],$this->tid]);
        $stats = [
            'total'  => count($msgs),
            'unread' => count(array_filter($msgs, fn($m) => !$m['is_read'])),
        ];
        $this->view('school/highschool/messages/index', ['pageTitle'=>'Messages','panelType'=>'school','messages'=>$msgs,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function compose(): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff']);
        $users = $this->db->fetchAll("SELECT id,name FROM users WHERE tenant_id=? AND id!=? ORDER BY name", [$this->tid,$_SESSION['user_id']]);
        $replyTo = null;
        $prefillSubject = '';
        if (!empty($_GET['reply_to'])) {
            $replyTo = $this->db->fetchOne("SELECT id,name FROM users WHERE id=? AND tenant_id=?", [$_GET['reply_to'], $this->tid]);
            if (!empty($_GET['subject'])) { $prefillSubject = 'Re: ' . $_GET['subject']; }
        }
        $this->view('school/highschool/messages/compose', ['pageTitle'=>'Compose','panelType'=>'school','users'=>$users,'replyTo'=>$replyTo,'prefillSubject'=>$prefillSubject,'flash'=>$this->getFlash()]);
    }

    /**
     * Audience filters for a broadcast, as a WHERE fragment on `users u`.
     *
     * Students and Parents are matched by role name because those roles are the
     * system ones every tenant shares; "staff" is everyone else, so a custom role
     * a school invents (Librarian, Bursar) is included without needing this list
     * updated.
     */
    private const MESSAGE_AUDIENCES = [
        'all'      => ['label' => 'Everyone',     'where' => ''],
        'students' => ['label' => 'All Students', 'where' => "AND r.name = 'Student'"],
        'parents'  => ['label' => 'All Parents',  'where' => "AND r.name = 'Parent'"],
        'staff'    => ['label' => 'All Staff',    'where' => "AND r.name NOT IN ('Student','Parent')"],
    ];

    /**
     * Sends to one person or broadcasts to an audience.
     *
     * A broadcast fans out to one message row per recipient rather than storing a
     * single "to everyone" row, so each person's inbox, read state and reply work
     * exactly as they do for a direct message. Done as INSERT…SELECT so a whole
     * school is one statement rather than hundreds of round trips.
     */
    public function send(): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff']);
        $audience = $_POST['audience'] ?? 'individual';
        $errors = $this->validate($_POST, ['body' => 'required']);
        if ($audience === 'individual' && empty($_POST['recipient_id'])) {
            $errors['recipient_id'] = 'Choose who to send this to.';
        }
        if ($audience !== 'individual' && !isset(self::MESSAGE_AUDIENCES[$audience])) {
            $errors['audience'] = 'Choose a valid audience.';
        }
        if ($errors) { $this->failValidation($errors, '/school/messages/compose'); }

        $subject = $_POST['subject'] ?? '';
        $body    = $_POST['body'];
        $me      = $_SESSION['user_id'];

        if ($audience === 'individual') {
            $this->db->insert("INSERT INTO messages (tenant_id,sender_id,recipient_id,subject,body) VALUES (?,?,?,?,?)",
                [$this->tid, $me, $_POST['recipient_id'], $subject, $body]);
            $this->flash('success', 'Message sent.');
            $this->redirect('/school/messages');
        }

        $filter = self::MESSAGE_AUDIENCES[$audience]['where'];
        // Excludes the sender so a broadcast doesn't land in their own inbox.
        $sent = $this->db->execute(
            "INSERT INTO messages (tenant_id,sender_id,recipient_id,subject,body)
             SELECT ?, ?, u.id, ?, ?
               FROM users u JOIN roles r ON u.role_id = r.id
              WHERE u.tenant_id = ? AND u.id <> ? AND u.status = 'active' {$filter}",
            [$this->tid, $me, $subject, $body, $this->tid, $me]
        );

        $label = self::MESSAGE_AUDIENCES[$audience]['label'];
        $this->flash($sent > 0 ? 'success' : 'warning', $sent > 0
            ? "Message sent to {$sent} recipient(s) — {$label}."
            : "No active recipients matched \"{$label}\", so nothing was sent.");
        $this->redirect('/school/messages');
    }

    public function show(string $id): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff']);
        $msg = $this->db->fetchOne("SELECT m.*,u.name AS sender_name FROM messages m JOIN users u ON m.sender_id=u.id WHERE m.id=? AND m.tenant_id=? AND m.recipient_id=?",[$id,$this->tid,$_SESSION['user_id']]);
        if (!$msg) { $this->redirect('/school/messages'); }
        if (!$msg['is_read']) { $this->db->execute("UPDATE messages SET is_read=1,read_at=NOW() WHERE id=?",[$id]); }
        $this->view('school/highschool/messages/show', ['pageTitle'=>'Message','panelType'=>'school','message'=>$msg,'flash'=>$this->getFlash()]);
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff']);
        $this->db->execute("DELETE FROM messages WHERE id=? AND tenant_id=? AND recipient_id=?", [$id, $this->tid, $_SESSION['user_id']]);
        $this->flash('success','Message deleted.'); $this->redirect('/school/messages');
    }
}

class GradeController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    /**
     * Who may overwrite an already-recorded grade.
     *
     * A School Admin always can — checked by role name, which is set from the roles table at
     * login and so does not depend on the grades.edit row existing in role_permissions. That
     * seed is missing on some installs, which silently locked every recorded grade for
     * everyone. The permission is still honoured so a custom role can also be granted it.
     */
    private function canEditGrades(): bool {
        return $this->hasPermission('grades.edit') || ($_SESSION['role'] ?? '') === 'School Admin';
    }

    public function index(): void {
        $this->requirePermission(['grades.manage']);
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
        $this->requirePermission(['grades.manage']);
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

    public function publish(string $id): void {
        $this->requirePermission(['grades.manage']);
        $exam = $this->db->fetchOne("SELECT * FROM exams WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$exam) { $this->redirect('/school/grades'); }
        if ($exam['status'] === 'draft') {
            $count = $this->db->fetchOne("SELECT COUNT(*) c FROM grades WHERE exam_id=?", [$id])['c'];
            if ($count < 1) {
                $this->flash('danger', 'Enter at least one grade before publishing.');
                $this->redirect('/school/grades');
            }
            $this->db->execute("UPDATE exams SET status='published' WHERE id=?", [$id]);
            $this->flash('success', 'Exam published — students and parents can now see these grades.');
        } else {
            $this->db->execute("UPDATE exams SET status='draft' WHERE id=?", [$id]);
            $this->flash('success', 'Exam unpublished (moved back to draft).');
        }
        $this->redirect('/school/grades');
    }

    public function enter(): void {
        $this->requirePermission(['grades.manage']);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        // Once a class is chosen, offer only that class's exams — otherwise a teacher
        // can file marks against another class's marking period, which then never
        // appears on the report card. Report-card slots sort first, in card order.
        $selectedClass = $_GET['class_id'] ?? '';
        $exams = $selectedClass
            ? $this->db->fetchAll(
                "SELECT id, name, report_column FROM exams
                 WHERE tenant_id=? AND class_id=?
                 ORDER BY report_column IS NULL, FIELD(report_column,'p1','p2','p3','e1','p4','p5','p6','e2'), name",
                [$this->tid, $selectedClass])
            : $this->db->fetchAll("SELECT id, name, report_column FROM exams WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $students = []; $courses = []; $existingGrades = [];
        $selectedExam = $_GET['exam_id'] ?? '';
        if (!empty($_GET['class_id'])) {
            $students = $this->db->fetchAll("SELECT s.id,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",[$_GET['class_id'],$this->tid]);
            // Same order as the report card, so a teacher reading down a printed card
            // and typing across this grid is looking at the same subject sequence.
            $courses  = $this->db->fetchAll(
                "SELECT c.id,c.name FROM courses c JOIN course_classes cc ON cc.course_id=c.id
                 WHERE cc.class_id=? AND c.tenant_id=? ORDER BY cc.sort_order, c.name",[$_GET['class_id'],$this->tid]
            );
            // Prefill already-recorded marks for this exam so re-opening the screen shows what's
            // there instead of blank cells — needed now that a Teacher without grades.edit can no
            // longer silently overwrite them (see store()).
            $rows = $this->db->fetchAll(
                "SELECT student_id, course_id, marks_obtained FROM grades WHERE tenant_id=? AND exam_id <=> ?",
                [$this->tid, $selectedExam ?: null]
            );
            foreach ($rows as $r) { $existingGrades[$r['student_id']][$r['course_id']] = $r['marks_obtained']; }
        }
        $this->view('school/highschool/grades/enter', [
            'pageTitle'=>'Enter Grades','panelType'=>'school','classes'=>$classes,'exams'=>$exams,'students'=>$students,'courses'=>$courses,
            'selectedClass'=>$_GET['class_id']??'','selectedExam'=>$selectedExam,'existingGrades'=>$existingGrades,
            'canEditGrades'=>$this->canEditGrades(),'flash'=>$this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requirePermission(['grades.manage']);
        $canEdit = $this->canEditGrades();
        $examId = $_POST['exam_id'] ?: null;
        $skipped = 0;
        foreach ($_POST['grades'] ?? [] as $studentId => $marks) {
            foreach ($marks as $courseId => $score) {
                if ($score === '') { continue; }
                $pct = (float)$score;
                // The school's own scale (E/S/I/N/C) — the same one printed in the
                // METHOD OF GRADING key on the report card, so a letter shown in the
                // portal always matches the letter on the card.
                $letter = self::celdiLetter($pct);
                $gpa    = ['E'=>4.0,'S'=>3.0,'I'=>2.0,'N'=>1.0,'C'=>0.0][$letter] ?? 0.0;
                $existing = $this->db->fetchOne(
                    "SELECT id FROM grades WHERE tenant_id=? AND student_id=? AND course_id=? AND exam_id <=> ?",
                    [$this->tid, $studentId, $courseId, $examId]
                );
                if ($existing) {
                    if (!$canEdit) { $skipped++; continue; }
                    $this->db->execute("UPDATE grades SET marks_obtained=?,grade_letter=?,gpa_points=?,graded_by=? WHERE id=?",
                        [$pct, $letter, $gpa, $_SESSION['user_id'], $existing['id']]);
                } else {
                    $this->db->insert(
                        "INSERT INTO grades (tenant_id,student_id,course_id,exam_id,marks_obtained,total_marks,grade_letter,gpa_points,graded_by) VALUES (?,?,?,?,?,100,?,?,?)",
                        [$this->tid,$studentId,$courseId,$examId,$pct,$letter,$gpa,$_SESSION['user_id']]
                    );
                }
            }
        }
        if ($skipped > 0) {
            $this->flash('warning', "Grades saved. {$skipped} already-recorded grade(s) were left unchanged — only a School Admin can overwrite an existing grade.");
        } else {
            $this->flash('success','Grades saved.');
        }
        $this->redirect('/school/grades');
    }

    // Default names for the eight recordable slots. The card itself prints the
    // labels from Controller::CELDI_COLUMNS, so these only need to read well in
    // the exam list and the grade-entry dropdown — renaming one is harmless.
    private const MARKING_PERIOD_NAMES = [
        'p1' => '1st Pd.', 'p2' => '2nd Pd.', 'p3' => '3rd Pd.', 'e1' => '1st Semester Exam',
        'p4' => '4th Pd.', 'p5' => '5th Pd.', 'p6' => '6th Pd.', 'e2' => '2nd Semester Exam',
    ];

    /**
     * Switches a class between numeric marks and letter grades on the report card.
     *
     * The early-years classes (Nursery, Day Care and any others the school chooses)
     * print E/S/I/N/C in every cell instead of scores, including the Average row.
     * Made a per-class toggle rather than inferred from the class name so a school
     * that renames or adds a section — "Nursery 1", "Nursery 2" — can set it
     * without a code change.
     */
    public function toggleReportStyle(string $id): void {
        $this->requirePermission(['grades.manage']);
        $class = $this->db->fetchOne("SELECT id, name, report_style FROM classes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$class) { $this->redirect('/school/grades/marking-periods'); }
        $new = ($class['report_style'] ?? 'numeric') === 'letter' ? 'numeric' : 'letter';
        $this->db->execute("UPDATE classes SET report_style=? WHERE id=? AND tenant_id=?", [$new, $id, $this->tid]);
        $this->flash('success', $new === 'letter'
            ? "{$class['name']} report cards will now print letter grades (E/S/I/N/C)."
            : "{$class['name']} report cards will now print numeric marks.");
        $this->redirect('/school/grades/marking-periods?year_id=' . urlencode((string)($_POST['year_id'] ?? '')));
    }

    /** Setup screen: which classes have their report-card slots wired up for a year. */
    public function markingPeriods(): void {
        $this->requirePermission(['grades.manage']);
        $years = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $yearId = $_GET['year_id'] ?? ($years[0]['id'] ?? null);
        $classes = $this->db->fetchAll(
            "SELECT c.id, c.name, c.grade_level, c.report_style,
                    (SELECT COUNT(*) FROM exams e
                      WHERE e.tenant_id=c.tenant_id AND e.class_id=c.id
                        AND e.academic_year_id <=> ? AND e.report_column IS NOT NULL) AS slots,
                    (SELECT COUNT(*) FROM exams e
                      WHERE e.tenant_id=c.tenant_id AND e.class_id=c.id
                        AND e.academic_year_id <=> ? AND e.report_column IS NOT NULL
                        AND e.status='published') AS published_slots,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id=c.id AND s.status='active') AS student_count,
                    (SELECT COUNT(*) FROM course_classes cc WHERE cc.class_id=c.id) AS subject_count
             FROM classes c WHERE c.tenant_id=? ORDER BY c.name", [$yearId, $yearId, $this->tid]
        );
        $this->view('school/highschool/grades/marking_periods', [
            'pageTitle'=>'Marking Periods','panelType'=>'school','classes'=>$classes,
            'years'=>$years,'selectedYearId'=>$yearId,
            'slotNames'=>self::MARKING_PERIOD_NAMES,'flash'=>$this->getFlash(),
        ]);
    }

    /**
     * Creates the eight report-card slots for one class (or every class) in a year.
     *
     * Idempotent: the uniq_exam_report_slot index means re-running only fills gaps,
     * so this is safe to hit again after adding a class. Existing ad-hoc exams are
     * untouched — they carry a NULL report_column and simply don't appear on the card.
     */
    public function setupMarkingPeriods(): void {
        $this->requirePermission(['grades.manage']);
        $yearId = $_POST['academic_year_id'] ?: null;
        if (!$yearId) {
            $this->flash('danger', 'Choose an academic year first.');
            $this->redirect('/school/grades/marking-periods');
        }
        $classIds = !empty($_POST['class_id'])
            ? [(int)$_POST['class_id']]
            : array_map(fn($c) => (int)$c['id'],
                $this->db->fetchAll("SELECT id FROM classes WHERE tenant_id=?", [$this->tid]));

        $created = 0;
        foreach ($classIds as $classId) {
            foreach (self::MARKING_PERIOD_NAMES as $slot => $name) {
                $exists = $this->db->fetchOne(
                    "SELECT id FROM exams WHERE tenant_id=? AND class_id=? AND academic_year_id <=> ? AND report_column=?",
                    [$this->tid, $classId, $yearId, $slot]
                );
                if ($exists) { continue; }
                $this->db->insert(
                    "INSERT INTO exams (tenant_id,name,class_id,academic_year_id,total_marks,pass_marks,report_column,status)
                     VALUES (?,?,?,?,100,60,?, 'draft')",
                    [$this->tid, $name, $classId, $yearId, $slot]
                );
                $created++;
            }
        }
        $this->flash('success', $created > 0
            ? "Marking periods ready — {$created} slot(s) created. Enter marks against them from Enter Grades."
            : 'Marking periods were already set up for that selection.');
        $this->redirect('/school/grades/marking-periods?year_id=' . urlencode((string)$yearId));
    }

    /**
     * Every card in a class as one printable document.
     *
     * This is how the school produces cards in practice — their own year-end export
     * is a single long PDF of consecutive cards, not 200 separate prints. Each
     * student's card is built through the same builder the single-student view
     * uses, so the two outputs cannot disagree.
     */
    public function classReportCards(string $classId): void {
        $this->requirePermission(['grades.manage']);
        $class = $this->db->fetchOne("SELECT * FROM classes WHERE id=? AND tenant_id=?", [$classId, $this->tid]);
        if (!$class) { $this->redirect('/school/classes'); }
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);

        $students = $this->db->fetchAll(
            "SELECT s.*, u.name, u.gender, u.date_of_birth FROM students s JOIN users u ON s.user_id=u.id
             WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",
            [$classId, $this->tid]
        );

        $cards = [];
        $meta = [];
        foreach ($students as $s) {
            $built = $this->buildCeldiReportCard((string)$s['id'], $s, false);
            $cards[] = array_merge($built, ['student' => $s]);
            $meta = $built; // year/slot info is identical across the class
        }

        $this->view('school/report_cards_batch', [
            'pageTitle' => 'Report Cards — ' . $class['name'],
            'tenant' => $tenant, 'class' => $class, 'cards' => $cards,
            'yearOptions' => $meta['yearOptions'] ?? [],
            'selectedYearId' => $meta['selectedYearId'] ?? null,
            'yearName' => $meta['year']['name'] ?? null,
            'slotsConfigured' => $meta['slotsConfigured'] ?? 0,
        ]);
    }

    /**
     * Releases (or withdraws) a class's whole report card for a year.
     *
     * The card is only meaningful as a set — publishing the eight slots one at a
     * time would show parents a card with holes in it — so this flips all of them
     * together. Ad-hoc exams keep their own per-exam publish button.
     */
    public function publishReportCards(): void {
        $this->requirePermission(['grades.manage']);
        $classId = $_POST['class_id'] ?? null;
        $yearId  = $_POST['academic_year_id'] ?: null;
        $status  = ($_POST['status'] ?? 'published') === 'published' ? 'published' : 'draft';
        if (!$classId || !$yearId) {
            $this->flash('danger', 'Choose a class and academic year first.');
            $this->redirect('/school/grades/marking-periods');
        }
        $this->db->execute(
            "UPDATE exams SET status=? WHERE tenant_id=? AND class_id=? AND academic_year_id <=> ? AND report_column IS NOT NULL",
            [$status, $this->tid, $classId, $yearId]
        );
        $this->flash('success', $status === 'published'
            ? 'Report cards released — parents and students can now see them.'
            : 'Report cards withdrawn — they are hidden from parents and students again.');
        $this->redirect('/school/grades/marking-periods?year_id=' . urlencode((string)$yearId));
    }

    /**
     * Year-end promotion decisions for a whole class — the left panel of sheet 1.
     *
     * Done per class rather than per student because it is a single end-of-year
     * sitting for the class sponsor, who works down the roster in one go.
     */
    public function promotions(): void {
        $this->requirePermission(['grades.manage']);
        $years = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $classId = $_GET['class_id'] ?? null;
        $yearId  = $_GET['year_id'] ?? ($years[0]['id'] ?? null);

        $students = []; $existing = [];
        if ($classId) {
            $students = $this->db->fetchAll(
                "SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id
                 WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",
                [$classId, $this->tid]
            );
            $rows = $this->db->fetchAll(
                "SELECT * FROM student_promotions WHERE tenant_id=? AND academic_year_id <=> ?", [$this->tid, $yearId]
            );
            foreach ($rows as $r) { $existing[(int)$r['student_id']] = $r; }
        }
        $this->view('school/highschool/grades/promotions', [
            'pageTitle'=>'Promotion Statements','panelType'=>'school',
            'years'=>$years,'classes'=>$classes,'students'=>$students,'existing'=>$existing,
            'selectedClassId'=>$classId,'selectedYearId'=>$yearId,'flash'=>$this->getFlash(),
        ]);
    }

    /** Upserts one row per student; blank decisions are stored as NULL so the card prints ruled blanks. */
    public function savePromotions(): void {
        $this->requirePermission(['grades.manage']);
        $yearId  = $_POST['academic_year_id'] ?: null;
        $classId = $_POST['class_id'] ?: null;
        if (!$yearId || !$classId) {
            $this->flash('danger', 'Choose a class and academic year first.');
            $this->redirect('/school/grades/promotions');
        }
        $closing = $_POST['closing_date'] ?: null;
        $valid = ['promoted','condition','repeat','not_enroll'];
        $saved = 0;
        foreach ($_POST['promotion'] ?? [] as $studentId => $p) {
            $decision = in_array($p['decision'] ?? '', $valid, true) ? $p['decision'] : null;
            $sat = ($p['satisfactory'] ?? '') === '' ? null : (int)$p['satisfactory'];
            $detail = trim($p['detail'] ?? '') ?: null;
            $this->db->execute(
                "INSERT INTO student_promotions (tenant_id,student_id,academic_year_id,decision,decision_detail,satisfactory,closing_date)
                 VALUES (?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE decision=VALUES(decision), decision_detail=VALUES(decision_detail),
                                         satisfactory=VALUES(satisfactory), closing_date=VALUES(closing_date)",
                [$this->tid, (int)$studentId, $yearId, $decision, $detail, $sat, $closing]
            );
            $saved++;
        }
        $this->flash('success', "Promotion statements saved for {$saved} student(s).");
        $this->redirect('/school/grades/promotions?class_id=' . urlencode((string)$classId) . '&year_id=' . urlencode((string)$yearId));
    }

    public function report(string $studentId): void {
        $this->requirePermission(['grades.manage']);
        $student = $this->db->fetchOne("SELECT s.*,u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",[$studentId,$this->tid]);
        $grades  = $this->db->fetchAll("SELECT g.*,c.name AS course_name,e.name AS exam_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id LEFT JOIN exams e ON g.exam_id=e.id WHERE g.student_id=? AND g.tenant_id=? ORDER BY g.created_at DESC",[$studentId,$this->tid]);
        $this->view('school/highschool/grades/report', ['pageTitle'=>'Grade Report','panelType'=>'school','student'=>$student,'grades'=>$grades,'flash'=>$this->getFlash()]);
    }

    public function reportCard(string $studentId): void {
        $this->requirePermission(['grades.manage']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.gender, u.date_of_birth FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?",
            [$studentId, $this->tid]
        );
        if (!$student) { $this->redirect('/school/students'); }
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);

        // Staff proof the card before release, so unpublished marks are included.
        $this->view('school/report_card_celdi', array_merge(
            ['pageTitle' => 'Report Card', 'tenant' => $tenant, 'student' => $student],
            $this->buildCeldiReportCard($studentId, $student, false)
        ));
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
        $this->requirePermission(['grades.manage']);
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
        $this->requirePermission(['grades.manage']);
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
        $this->requirePermission(['grades.manage']);
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
        $this->requirePermission(['grades.manage']);
        $this->downloadCsvTemplate('rankings_template.csv',
            ['TSM ID','Name','Class','Period','Grade','Rank','Group Size'],
            ['CAS0001','John Doe','3rd Grade','1st Pd.','88.5','4','197']
        );
    }

    public function bulkUploadRankings(): void {
        $this->requirePermission(['grades.manage']);
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
        $this->requirePermission(['timetable.view','timetable.manage']);
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
        $this->requirePermission(['timetable.manage']);
        $this->redirect('/school/timetable');
    }

    public function store(): void {
        $this->requirePermission(['timetable.manage']);
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
        $this->requirePermission(['timetable.manage']);
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
        $this->requirePermission(['parents.manage']);
        $search = $_GET['q'] ?? '';
        $params = [$this->tid];
        $where = "p.tenant_id=?";
        if ($search) { $where .= " AND (u.name LIKE ? OR u.email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM parents p JOIN users u ON p.user_id=u.id WHERE $where", $params)['c'];
        $p2 = $this->paginate($totalCount);
        $parents = $this->db->fetchAll(
            "SELECT p.*,u.name,u.email,u.username,u.phone,u.employee_no,u.status,
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
        $this->requirePermission(['parents.manage']);
        $this->redirect('/school/parents');
    }

    public function bulkTemplate(): void {
        $this->requirePermission(['parents.manage']);
        $this->downloadCsvTemplate('parents_template.csv',
            ['name','email','phone','gender','dob','occupation','workplace','student_admission_no','relationship'],
            ['John Doe','john.doe@example.com','0779876543','male','1980-02-10','Trader','Waterside Market','ADM-2026-0001','Father']
        );
    }

    public function bulkUpload(): void {
        $this->requirePermission(['parents.manage']);
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
                $username = $this->generateUniqueUsername($name, $this->tid);
                $userId = $this->db->insert(
                    "INSERT INTO users (tenant_id,role_id,name,email,username,phone,gender,date_of_birth,status) VALUES (?,?,?,?,?,?,?,?,?)",
                    [$this->tid, $roleId, $name, $email ?: null, $username, $row['phone'] ?? '', $row['gender'] ?: null, $row['dob'] ?: null, 'active']
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
        $this->requirePermission(['parents.manage']);
        $errors = $this->validate($_POST, [
            'name'  => 'required|max:150',
            'email' => 'email|max:150',
            'phone' => 'required|max:30',
            'dob'   => 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/parents'); }
        $roleId = $this->db->fetchOne("SELECT id FROM roles WHERE name='Parent' LIMIT 1")['id'] ?? 8;
        $pw = password_hash($_POST['password'] ?: 'Parent@123', PASSWORD_BCRYPT);
        $username = $this->generateUniqueUsername($_POST['name'], $this->tid);
        $userId = $this->db->insert(
            "INSERT INTO users (tenant_id,role_id,name,email,username,phone,gender,date_of_birth,address,employee_no,status) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$this->tid,$roleId,$_POST['name'],$_POST['email']?:null,$username,$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$_POST['address']??'',$_POST['employee_no']?:null,'active']
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
        $this->flash('success',"Parent account created. Username: {$username}."); $this->redirect('/school/parents');
    }

    public function show(string $id): void {
        $this->requirePermission(['parents.manage']);
        $parent = $this->db->fetchOne(
            "SELECT p.*, u.name, u.email, u.username, u.phone, u.gender, u.date_of_birth, u.address
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
        $this->requirePermission(['parents.manage']);
        $parent = $this->db->fetchOne(
            "SELECT p.*, u.name, u.email, u.username, u.phone, u.gender, u.date_of_birth, u.address, u.employee_no
             FROM parents p JOIN users u ON p.user_id=u.id WHERE p.id=? AND p.tenant_id=?", [$id, $this->tid]
        );
        if (!$parent) { $this->redirect('/school/parents'); }
        $this->view('school/highschool/parents/form', ['pageTitle'=>'Edit Parent','panelType'=>'school','parent'=>$parent,'flash'=>$this->getFlash()]);
    }

    public function update(string $id): void {
        $this->requirePermission(['parents.manage']);
        $parent = $this->db->fetchOne("SELECT user_id FROM parents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$parent) { $this->redirect('/school/parents'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150', 'email' => 'email|max:150', 'username' => 'required|max:60']);
        $username = trim($_POST['username'] ?? '');
        if (!$errors) {
            $taken = $this->db->fetchOne("SELECT id FROM users WHERE username=? AND tenant_id=? AND id!=?", [$username, $this->tid, $parent['user_id']]);
            if ($taken) { $errors['username'] = 'That username is already taken.'; }
        }
        if ($errors) { $this->failValidation($errors, '/school/parents/'.$id.'/edit'); }
        $this->db->execute("UPDATE users SET name=?,email=?,username=?,phone=?,gender=?,date_of_birth=?,address=?,employee_no=? WHERE id=?",
            [$_POST['name'],$_POST['email']?:null,$username,$_POST['phone']??'',$_POST['gender']??null,$_POST['dob']??null,$_POST['address']??'',$_POST['employee_no']?:null,$parent['user_id']]);
        $this->db->execute("UPDATE parents SET occupation=?,workplace=?,national_id=?,emergency_contact_phone=? WHERE id=? AND tenant_id=?",
            [$_POST['occupation']??'',$_POST['workplace']??null,$_POST['national_id']??null,$_POST['emergency_contact_phone']??null,$id,$this->tid]);
        $this->flash('success','Parent updated.'); $this->redirect('/school/parents/'.$id);
    }

    public function delete(string $id): void {
        $this->requirePermission(['parents.manage']);
        $parent = $this->db->fetchOne("SELECT user_id FROM parents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if ($parent) {
            $this->db->execute("DELETE FROM parents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->db->execute("DELETE FROM users WHERE id=?", [$parent['user_id']]);
        }
        $this->flash('success','Parent removed.'); $this->redirect('/school/parents');
    }

    public function linkChild(string $id): void {
        $this->requirePermission(['parents.manage']);
        $errors = $this->validate($_POST, ['student_id' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/parents/'.$id); }
        $this->db->insert("INSERT INTO parent_students (parent_id,student_id,relationship) VALUES (?,?,?)",
            [$id, $_POST['student_id'], $_POST['relationship'] ?: 'parent']);
        $this->flash('success','Child linked.'); $this->redirect('/school/parents/'.$id);
    }

    public function unlinkChild(string $id, string $studentId): void {
        $this->requirePermission(['parents.manage']);
        $this->db->execute("DELETE FROM parent_students WHERE parent_id=? AND student_id=?", [$id, $studentId]);
        $this->flash('success','Child unlinked.'); $this->redirect('/school/parents/'.$id);
    }
}

class SchoolSettingsController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['settings.manage']);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/settings', ['pageTitle'=>'School Settings','panelType'=>'school','tenant'=>$tenant,'flash'=>$this->getFlash()]);
    }

    public function update(): void {
        $this->requirePermission(['settings.manage']);

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

        $studentLoginMode = in_array($_POST['student_login_mode'] ?? '', ['email_password','admission_pin'], true) ? $_POST['student_login_mode'] : 'admission_pin';
        $parentLoginMode  = in_array($_POST['parent_login_mode'] ?? '', ['email_password','username_password'], true) ? $_POST['parent_login_mode'] : 'username_password';
        $restrictParentArrears = !empty($_POST['restrict_parent_arrears']) ? 1 : 0;

        $this->db->execute("UPDATE tenants SET name=?,email=?,phone=?,address=?,country=?,timezone=?,academic_year=?,currency=?,domain=?,primary_color=?,secondary_color=?,accent_color=?,logo=?,student_login_mode=?,parent_login_mode=?,restrict_parent_arrears=? WHERE id=?",
            [
                $_POST['name'], $_POST['email']??'', $_POST['phone']??'', $_POST['address']??'',
                $_POST['country']??'', $_POST['timezone']??'UTC', $_POST['academic_year']??'',
                $_POST['currency']??'Ksh',
                $_POST['domain']??null, $_POST['primary_color']??'#4F46E5',
                $_POST['secondary_color']??'#7C3AED', $_POST['accent_color']??'#06B6D4',
                $logo, $studentLoginMode, $parentLoginMode, $restrictParentArrears,
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
