<?php
require_once ROOT_DIR . '/core/Controller.php';

/**
 * Make-up exams.
 *
 * A make-up exam is an ordinary online exam (same questions, same timer, same
 * auto-marking) with one difference: only the students the school puts on its
 * sitting list can see it or sit it. That keeps a missed test between the
 * school and the student who missed it, instead of reopening it to the class.
 *
 * The rows live in `online_exams` with exam_type='makeup', so results, grades
 * and attempt history all behave exactly as they do for a normal online exam.
 */
class MakeupExamController extends Controller {
    private int $tid;

    /** Why a student missed the original sitting — shown on the sitting list. */
    public const REASONS = [
        'absent'      => 'Absent on the day',
        'sick'        => 'Illness / hospital',
        'bereavement' => 'Bereavement',
        'travel'      => 'Away / travelling',
        'late'        => 'Arrived after the exam closed',
        'technical'   => 'Technical problem during the exam',
        'school_duty' => 'Away on school duty',
        'other'       => 'Other (see note)',
    ];

    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function currentTeacherId(): ?int {
        if (($_SESSION['role'] ?? '') !== 'Teacher') { return null; }
        $t = $this->db->fetchOne("SELECT id FROM teachers WHERE user_id=? AND tenant_id=?", [$_SESSION['user_id'], $this->tid]);
        return $t['id'] ?? null;
    }

    /** One make-up exam of this school, or a redirect back to the list. */
    private function exam(string $id): array {
        $exam = $this->db->fetchOne(
            "SELECT e.*, c.name AS class_name, co.name AS course_name
             FROM online_exams e
             LEFT JOIN classes c ON e.class_id=c.id
             LEFT JOIN courses co ON e.course_id=co.id
             WHERE e.id=? AND e.tenant_id=? AND e.exam_type='makeup'",
            [$id, $this->tid]
        );
        if (!$exam) {
            $this->flash('danger', 'That make-up exam no longer exists.');
            $this->redirect('/school/makeup-exams');
        }
        return $exam;
    }

    public function index(): void {
        $this->requirePermission(['online_exam.manage']);
        $exams = $this->db->fetchAll(
            "SELECT e.*, c.name AS class_name, co.name AS course_name, u.name AS teacher_name,
                    (SELECT COUNT(*) FROM online_exam_questions q WHERE q.exam_id=e.id) AS question_count,
                    (SELECT COUNT(*) FROM makeup_exam_students m WHERE m.exam_id=e.id) AS listed_count,
                    (SELECT COUNT(*) FROM online_exam_attempts a
                      JOIN makeup_exam_students m2 ON m2.exam_id=a.exam_id AND m2.student_id=a.student_id
                      WHERE a.exam_id=e.id AND a.status='submitted') AS sat_count
             FROM online_exams e
             LEFT JOIN classes c ON e.class_id=c.id
             LEFT JOIN courses co ON e.course_id=co.id
             LEFT JOIN teachers t ON e.teacher_id=t.id
             LEFT JOIN users u ON t.user_id=u.id
             WHERE e.tenant_id=? AND e.exam_type='makeup'
             ORDER BY e.starts_at DESC",
            [$this->tid]
        );
        $now = time();
        foreach ($exams as &$e) {
            $e['window'] = $e['status'] !== 'published' ? 'draft'
                : ($now < strtotime($e['starts_at']) ? 'upcoming'
                : ($now > strtotime($e['ends_at']) ? 'closed' : 'open'));
        }
        unset($e);
        $this->view('school/highschool/makeup_exams/index', [
            'pageTitle' => 'Make-up Exams', 'panelType' => 'school',
            'exams'   => $exams,
            'classes' => $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]),
            'courses' => $this->db->fetchAll("SELECT id,name FROM courses WHERE tenant_id=? ORDER BY name", [$this->tid]),
            'stats'   => [
                'total'   => count($exams),
                'open'    => count(array_filter($exams, fn($e) => $e['window'] === 'open')),
                'waiting' => array_sum(array_map(fn($e) => max(0, (int)$e['listed_count'] - (int)$e['sat_count']), $exams)),
            ],
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requirePermission(['online_exam.manage']);
        $errors = $this->validate($_POST, [
            'title'            => 'required|max:200',
            'class_id'         => 'required',
            'duration_minutes' => 'required|integer',
            'starts_at'        => 'required',
            'ends_at'          => 'required',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/makeup-exams'); }
        if (strtotime($_POST['ends_at']) <= strtotime($_POST['starts_at'])) {
            $this->flash('danger', 'The closing time must be after the opening time.');
            $this->redirect('/school/makeup-exams');
        }
        $id = $this->db->insert(
            "INSERT INTO online_exams
               (tenant_id,class_id,course_id,teacher_id,title,description,duration_minutes,starts_at,ends_at,exam_type,missed_label,missed_on)
             VALUES (?,?,?,?,?,?,?,?,?, 'makeup',?,?)",
            [
                $this->tid, $_POST['class_id'], $_POST['course_id'] ?: null, $this->currentTeacherId(),
                $_POST['title'], $_POST['description'] ?? '', $_POST['duration_minutes'],
                $_POST['starts_at'], $_POST['ends_at'],
                trim((string)($_POST['missed_label'] ?? '')) ?: null,
                ($_POST['missed_on'] ?? '') ?: null,
            ]
        );
        $this->flash('success', 'Make-up exam created. Add the students who missed the exam, then the questions, then publish it.');
        $this->redirect('/school/makeup-exams/' . $id);
    }

    /** The sitting list: who may take this make-up exam, and how each of them is doing. */
    public function show(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $exam = $this->exam($id);
        $sitting = $this->db->fetchAll(
            "SELECT m.*, u.name AS student_name, s.admission_no, c.name AS class_name,
                    a.status AS attempt_status, a.score, a.total_marks, a.started_at, a.submitted_at,
                    appr.name AS approved_by_name
             FROM makeup_exam_students m
             JOIN students s ON m.student_id=s.id
             JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             LEFT JOIN online_exam_attempts a ON a.exam_id=m.exam_id AND a.student_id=m.student_id
             LEFT JOIN users appr ON m.approved_by=appr.id
             WHERE m.exam_id=? AND m.tenant_id=?
             ORDER BY u.name",
            [$id, $this->tid]
        );
        // Everyone in the class who is not on the list yet.
        $available = $this->db->fetchAll(
            "SELECT s.id, u.name, s.admission_no
             FROM students s JOIN users u ON s.user_id=u.id
             WHERE s.class_id=? AND s.tenant_id=? AND s.status='active'
               AND s.id NOT IN (SELECT student_id FROM makeup_exam_students WHERE exam_id=?)
             ORDER BY u.name",
            [$exam['class_id'], $this->tid, $id]
        );
        $questions = $this->db->fetchAll("SELECT marks FROM online_exam_questions WHERE exam_id=?", [$id]);
        $submitted = array_filter($sitting, fn($r) => $r['attempt_status'] === 'submitted');
        $this->view('school/highschool/makeup_exams/show', [
            'pageTitle' => $exam['title'], 'panelType' => 'school',
            'exam' => $exam, 'sitting' => $sitting, 'available' => $available,
            'reasons' => self::REASONS,
            'courses' => $this->db->fetchAll("SELECT id,name FROM courses WHERE tenant_id=? ORDER BY name", [$this->tid]),
            'questionCount' => count($questions),
            'totalMarks' => array_sum(array_column($questions, 'marks')),
            'stats' => [
                'listed'   => count($sitting),
                'sat'      => count($submitted),
                'avgScore' => count($submitted)
                    ? round(array_sum(array_map(fn($r) => $r['total_marks'] > 0 ? $r['score'] / $r['total_marks'] * 100 : 0, $submitted)) / count($submitted), 1)
                    : null,
            ],
            'flash' => $this->getFlash(),
        ]);
    }

    public function addStudent(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $exam = $this->exam($id);
        $studentIds = array_filter(array_map('intval', (array)($_POST['student_ids'] ?? [])));
        if (!$studentIds) {
            $this->flash('danger', 'Choose at least one student.');
            $this->redirect('/school/makeup-exams/' . $id);
        }
        $reason = array_key_exists((string)($_POST['reason'] ?? ''), self::REASONS) ? $_POST['reason'] : 'absent';
        $note   = trim((string)($_POST['note'] ?? '')) ?: null;
        $added  = 0;
        foreach ($studentIds as $sid) {
            // Only a student of this exam's own class, and only once.
            $ok = $this->db->fetchOne(
                "SELECT id FROM students WHERE id=? AND tenant_id=? AND class_id=?",
                [$sid, $this->tid, $exam['class_id']]
            );
            if (!$ok) { continue; }
            $this->db->execute(
                "INSERT INTO makeup_exam_students (tenant_id,exam_id,student_id,reason,note,approved_by)
                 VALUES (?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE reason=VALUES(reason), note=VALUES(note), approved_by=VALUES(approved_by)",
                [$this->tid, $id, $sid, $reason, $note, $_SESSION['user_id'] ?? null]
            );
            $added++;
        }
        $this->flash($added ? 'success' : 'danger', $added
            ? ($added === 1 ? 'Student added to the sitting list.' : $added . ' students added to the sitting list.')
            : 'Nobody was added — those students are not in this class.');
        $this->redirect('/school/makeup-exams/' . $id);
    }

    public function removeStudent(string $id, string $sid): void {
        $this->requirePermission(['online_exam.manage']);
        $this->exam($id);
        // Taking a student off the list also clears the attempt they have not finished,
        // so nothing half-done is left behind; a submitted paper is kept.
        $attempt = $this->db->fetchOne(
            "SELECT id, status FROM online_exam_attempts WHERE exam_id=? AND student_id=?",
            [$id, $sid]
        );
        if ($attempt && $attempt['status'] === 'in_progress') {
            $this->db->execute("DELETE FROM online_exam_attempts WHERE id=?", [$attempt['id']]);
        }
        $this->db->execute("DELETE FROM makeup_exam_students WHERE exam_id=? AND student_id=? AND tenant_id=?", [$id, $sid, $this->tid]);
        $this->flash('success', 'Student taken off the sitting list.');
        $this->redirect('/school/makeup-exams/' . $id);
    }

    /** Let a student sit again — clears their paper so they can start afresh. */
    public function resetAttempt(string $id, string $sid): void {
        $this->requirePermission(['online_exam.manage']);
        $this->exam($id);
        $onList = $this->db->fetchOne("SELECT id FROM makeup_exam_students WHERE exam_id=? AND student_id=? AND tenant_id=?", [$id, $sid, $this->tid]);
        if ($onList) {
            $this->db->execute("DELETE FROM online_exam_attempts WHERE exam_id=? AND student_id=?", [$id, $sid]);
            $this->flash('success', 'That paper has been cleared — the student can sit the exam again while the window is open.');
        }
        $this->redirect('/school/makeup-exams/' . $id);
    }

    public function publish(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $exam = $this->exam($id);
        if ($exam['status'] === 'published') {
            $this->db->execute("UPDATE online_exams SET status='draft' WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $this->flash('success', 'Make-up exam withdrawn — the students can no longer see it.');
            $this->redirect('/school/makeup-exams/' . $id);
        }
        $qCount = (int)$this->db->fetchOne("SELECT COUNT(*) c FROM online_exam_questions WHERE exam_id=?", [$id])['c'];
        if ($qCount < 1) {
            $this->flash('danger', 'Add at least one question before publishing this make-up exam.');
            $this->redirect('/school/makeup-exams/' . $id . '/questions');
        }
        $listed = (int)$this->db->fetchOne("SELECT COUNT(*) c FROM makeup_exam_students WHERE exam_id=?", [$id])['c'];
        if ($listed < 1) {
            $this->flash('danger', 'Add the student or students who missed the exam before publishing it.');
            $this->redirect('/school/makeup-exams/' . $id);
        }
        $this->db->execute("UPDATE online_exams SET status='published' WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Published — the ' . $listed . ' student' . ($listed === 1 ? '' : 's') . ' on the list will now see it in their portal.');
        $this->redirect('/school/makeup-exams/' . $id);
    }

    public function update(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $this->exam($id);
        $errors = $this->validate($_POST, [
            'title'            => 'required|max:200',
            'duration_minutes' => 'required|integer',
            'starts_at'        => 'required',
            'ends_at'          => 'required',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/makeup-exams/' . $id); }
        if (strtotime($_POST['ends_at']) <= strtotime($_POST['starts_at'])) {
            $this->flash('danger', 'The closing time must be after the opening time.');
            $this->redirect('/school/makeup-exams/' . $id);
        }
        $this->db->execute(
            "UPDATE online_exams
                SET title=?, description=?, course_id=?, duration_minutes=?, starts_at=?, ends_at=?, missed_label=?, missed_on=?
              WHERE id=? AND tenant_id=? AND exam_type='makeup'",
            [
                $_POST['title'], $_POST['description'] ?? '', $_POST['course_id'] ?: null,
                $_POST['duration_minutes'], $_POST['starts_at'], $_POST['ends_at'],
                trim((string)($_POST['missed_label'] ?? '')) ?: null,
                ($_POST['missed_on'] ?? '') ?: null,
                $id, $this->tid,
            ]
        );
        $this->flash('success', 'Make-up exam updated.');
        $this->redirect('/school/makeup-exams/' . $id);
    }

    public function delete(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $this->db->execute("DELETE FROM online_exams WHERE id=? AND tenant_id=? AND exam_type='makeup'", [$id, $this->tid]);
        $this->flash('success', 'Make-up exam removed.');
        $this->redirect('/school/makeup-exams');
    }

    // ---- Questions: the same screen the online exams use, under this module's own URLs.

    public function questions(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $exam = $this->exam($id);
        $this->view('school/highschool/online_exams/questions', [
            'pageTitle' => 'Questions — ' . $exam['title'], 'panelType' => 'school',
            'exam' => $exam, 'flash' => $this->getFlash(),
            'examBase'  => '/school/makeup-exams',
            'examLabel' => 'Make-up Exams',
            'backUrl'   => '/school/makeup-exams/' . $id,
            'questions' => $this->db->fetchAll("SELECT * FROM online_exam_questions WHERE exam_id=? ORDER BY sort_order,id", [$id]),
        ]);
    }

    public function storeQuestion(string $id): void {
        $this->requirePermission(['online_exam.manage']);
        $this->exam($id);
        $errors = $this->validate($_POST, [
            'question_text'  => 'required',
            'option_a'       => 'required|max:255',
            'option_b'       => 'required|max:255',
            'correct_option' => 'required|in:a,b,c,d',
            'marks'          => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/makeup-exams/' . $id . '/questions'); }
        $maxOrder = (int)$this->db->fetchOne("SELECT COALESCE(MAX(sort_order),0) m FROM online_exam_questions WHERE exam_id=?", [$id])['m'];
        $this->db->insert(
            "INSERT INTO online_exam_questions (tenant_id,exam_id,question_text,option_a,option_b,option_c,option_d,correct_option,marks,sort_order)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $id, $_POST['question_text'], $_POST['option_a'], $_POST['option_b'],
                $_POST['option_c'] ?: null, $_POST['option_d'] ?: null, $_POST['correct_option'],
                $_POST['marks'] ?: 1, $maxOrder + 1,
            ]
        );
        $this->flash('success', 'Question added.');
        $this->redirect('/school/makeup-exams/' . $id . '/questions');
    }

    public function deleteQuestion(string $id, string $qid): void {
        $this->requirePermission(['online_exam.manage']);
        $this->exam($id);
        $this->db->execute("DELETE FROM online_exam_questions WHERE id=? AND exam_id=? AND tenant_id=?", [$qid, $id, $this->tid]);
        $this->flash('success', 'Question removed.');
        $this->redirect('/school/makeup-exams/' . $id . '/questions');
    }
}
