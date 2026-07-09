<?php
require_once ROOT_DIR . '/core/Controller.php';

class OnlineExamController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function currentTeacherId(): ?int {
        if (($_SESSION['role'] ?? '') !== 'Teacher') { return null; }
        $t = $this->db->fetchOne("SELECT id FROM teachers WHERE user_id=? AND tenant_id=?", [$_SESSION['user_id'], $this->tid]);
        return $t['id'] ?? null;
    }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $exams = $this->db->fetchAll(
            "SELECT e.*, c.name AS class_name, co.name AS course_name, u.name AS teacher_name,
                    (SELECT COUNT(*) FROM online_exam_questions q WHERE q.exam_id=e.id) AS question_count,
                    (SELECT COUNT(*) FROM online_exam_attempts a WHERE a.exam_id=e.id AND a.status='submitted') AS attempt_count,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id=e.class_id AND s.tenant_id=e.tenant_id AND s.status='active') AS student_count
             FROM online_exams e
             LEFT JOIN classes c ON e.class_id=c.id
             LEFT JOIN courses co ON e.course_id=co.id
             LEFT JOIN teachers t ON e.teacher_id=t.id
             LEFT JOIN users u ON t.user_id=u.id
             WHERE e.tenant_id=? ORDER BY e.starts_at DESC",
            [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $courses = $this->db->fetchAll("SELECT id,name,class_id FROM courses WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $stats = [
            'total'     => count($exams),
            'published' => count(array_filter($exams, fn($e) => $e['status']==='published')),
            'draft'     => count(array_filter($exams, fn($e) => $e['status']==='draft')),
        ];
        $this->view('school/highschool/online_exams/index', [
            'pageTitle'=>'Online Exams','panelType'=>'school','exams'=>$exams,'classes'=>$classes,'courses'=>$courses,
            'stats'=>$stats,'flash'=>$this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $errors = $this->validate($_POST, [
            'title'            => 'required|max:200',
            'class_id'         => 'required',
            'duration_minutes' => 'required|integer',
            'starts_at'        => 'required',
            'ends_at'          => 'required',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/online-exams'); }
        if (strtotime($_POST['ends_at']) <= strtotime($_POST['starts_at'])) {
            $this->flash('danger', 'End time must be after the start time.');
            $this->redirect('/school/online-exams');
        }
        $id = $this->db->insert(
            "INSERT INTO online_exams (tenant_id,class_id,course_id,teacher_id,title,description,duration_minutes,starts_at,ends_at)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['class_id'], $_POST['course_id'] ?: null, $this->currentTeacherId(),
                $_POST['title'], $_POST['description'] ?? '', $_POST['duration_minutes'], $_POST['starts_at'], $_POST['ends_at'],
            ]
        );
        $this->flash('success', 'Exam created as draft. Add questions, then publish it.');
        $this->redirect('/school/online-exams/'.$id.'/questions');
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $this->db->execute("DELETE FROM online_exams WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Exam removed.');
        $this->redirect('/school/online-exams');
    }

    public function publish(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $exam = $this->db->fetchOne("SELECT * FROM online_exams WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$exam) { $this->redirect('/school/online-exams'); }
        if ($exam['status'] === 'draft') {
            $qCount = $this->db->fetchOne("SELECT COUNT(*) c FROM online_exam_questions WHERE exam_id=?", [$id])['c'];
            if ($qCount < 1) {
                $this->flash('danger', 'Add at least one question before publishing.');
                $this->redirect('/school/online-exams/'.$id.'/questions');
            }
            $this->db->execute("UPDATE online_exams SET status='published' WHERE id=?", [$id]);
            $this->flash('success', 'Exam published — students can now see it in their portal.');
        } else {
            $this->db->execute("UPDATE online_exams SET status='draft' WHERE id=?", [$id]);
            $this->flash('success', 'Exam unpublished (moved back to draft).');
        }
        $this->redirect('/school/online-exams');
    }

    public function questions(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $exam = $this->db->fetchOne(
            "SELECT e.*, c.name AS class_name FROM online_exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.id=? AND e.tenant_id=?",
            [$id, $this->tid]
        );
        if (!$exam) { $this->redirect('/school/online-exams'); }
        $questions = $this->db->fetchAll("SELECT * FROM online_exam_questions WHERE exam_id=? ORDER BY sort_order,id", [$id]);
        $this->view('school/highschool/online_exams/questions', [
            'pageTitle'=>'Questions — '.$exam['title'],'panelType'=>'school',
            'exam'=>$exam,'questions'=>$questions,'flash'=>$this->getFlash(),
        ]);
    }

    public function storeQuestion(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $exam = $this->db->fetchOne("SELECT id FROM online_exams WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$exam) { $this->redirect('/school/online-exams'); }
        $errors = $this->validate($_POST, [
            'question_text'  => 'required',
            'option_a'       => 'required|max:255',
            'option_b'       => 'required|max:255',
            'correct_option' => 'required|in:a,b,c,d',
            'marks'          => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/online-exams/'.$id.'/questions'); }
        $maxOrder = $this->db->fetchOne("SELECT COALESCE(MAX(sort_order),0) m FROM online_exam_questions WHERE exam_id=?", [$id])['m'];
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
        $this->redirect('/school/online-exams/'.$id.'/questions');
    }

    public function deleteQuestion(string $id, string $qid): void {
        $this->requireAuth(['School Admin','Teacher']);
        $this->db->execute("DELETE FROM online_exam_questions WHERE id=? AND exam_id=? AND tenant_id=?", [$qid, $id, $this->tid]);
        $this->flash('success', 'Question removed.');
        $this->redirect('/school/online-exams/'.$id.'/questions');
    }

    public function results(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $exam = $this->db->fetchOne(
            "SELECT e.*, c.name AS class_name FROM online_exams e LEFT JOIN classes c ON e.class_id=c.id WHERE e.id=? AND e.tenant_id=?",
            [$id, $this->tid]
        );
        if (!$exam) { $this->redirect('/school/online-exams'); }
        $roster = $this->db->fetchAll(
            "SELECT s.id AS student_id, u.name AS student_name, s.admission_no,
                    a.status AS attempt_status, a.score, a.total_marks, a.submitted_at
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN online_exam_attempts a ON a.student_id=s.id AND a.exam_id=?
             WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",
            [$id, $exam['class_id'], $this->tid]
        );
        $submitted = array_filter($roster, fn($r) => $r['attempt_status']==='submitted');
        $stats = [
            'total'    => count($roster),
            'attempted'=> count($submitted),
            'avgScore' => count($submitted) ? round(array_sum(array_map(fn($r) => $r['total_marks'] > 0 ? $r['score']/$r['total_marks']*100 : 0, $submitted)) / count($submitted), 1) : null,
        ];
        $this->view('school/highschool/online_exams/results', [
            'pageTitle'=>'Results — '.$exam['title'],'panelType'=>'school',
            'exam'=>$exam,'roster'=>$roster,'stats'=>$stats,'flash'=>$this->getFlash(),
        ]);
    }
}
