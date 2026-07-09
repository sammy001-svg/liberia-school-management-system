<?php
require_once ROOT_DIR . '/core/Controller.php';

class HomeworkController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function currentTeacherId(): ?int {
        if (($_SESSION['role'] ?? '') !== 'Teacher') { return null; }
        $t = $this->db->fetchOne("SELECT id FROM teachers WHERE user_id=? AND tenant_id=?", [$_SESSION['user_id'], $this->tid]);
        return $t['id'] ?? null;
    }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $homework = $this->db->fetchAll(
            "SELECT h.*, c.name AS class_name, co.name AS course_name, u.name AS teacher_name,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id=h.class_id AND s.tenant_id=h.tenant_id AND s.status='active') AS student_count,
                    (SELECT COUNT(*) FROM homework_submissions hs WHERE hs.homework_id=h.id) AS submission_count,
                    (SELECT COUNT(*) FROM homework_submissions hs WHERE hs.homework_id=h.id AND hs.score IS NOT NULL) AS graded_count
             FROM homework h
             LEFT JOIN classes c ON h.class_id=c.id
             LEFT JOIN courses co ON h.course_id=co.id
             LEFT JOIN teachers t ON h.teacher_id=t.id
             LEFT JOIN users u ON t.user_id=u.id
             WHERE h.tenant_id=? ORDER BY h.due_date DESC, h.created_at DESC",
            [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $courses = $this->db->fetchAll("SELECT id,name,class_id FROM courses WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $stats = [
            'total'    => count($homework),
            'upcoming' => count(array_filter($homework, fn($h) => strtotime($h['due_date']) >= strtotime(date('Y-m-d')))),
            'overdue'  => count(array_filter($homework, fn($h) => strtotime($h['due_date']) < strtotime(date('Y-m-d')))),
        ];
        $this->view('school/highschool/homework/index', [
            'pageTitle'=>'Homework','panelType'=>'school','homework'=>$homework,'classes'=>$classes,'courses'=>$courses,
            'stats'=>$stats,'flash'=>$this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requireAuth(['School Admin','Teacher']);
        $errors = $this->validate($_POST, [
            'title'    => 'required|max:200',
            'class_id' => 'required',
            'due_date' => 'required|date',
            'max_score'=> 'numeric',
        ]);
        [$attachUrl, $attachName] = $this->handleFileUpload('attachment', 'homework', $errors, 10 * 1024 * 1024);
        if ($errors) { $this->failValidation($errors, '/school/homework'); }

        $this->db->insert(
            "INSERT INTO homework (tenant_id,class_id,course_id,teacher_id,title,description,attachment_path,attachment_name,due_date,max_score)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['class_id'], $_POST['course_id'] ?: null, $this->currentTeacherId(),
                $_POST['title'], $_POST['description'] ?? '', $attachUrl, $attachName,
                $_POST['due_date'], $_POST['max_score'] ?: 100,
            ]
        );
        $this->flash('success', 'Homework posted.');
        $this->redirect('/school/homework');
    }

    public function delete(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $this->db->execute("DELETE FROM homework WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Homework removed.');
        $this->redirect('/school/homework');
    }

    public function submissions(string $id): void {
        $this->requireAuth(['School Admin','Teacher']);
        $homework = $this->db->fetchOne(
            "SELECT h.*, c.name AS class_name, co.name AS course_name
             FROM homework h LEFT JOIN classes c ON h.class_id=c.id LEFT JOIN courses co ON h.course_id=co.id
             WHERE h.id=? AND h.tenant_id=?", [$id, $this->tid]
        );
        if (!$homework) { $this->redirect('/school/homework'); }

        $roster = $this->db->fetchAll(
            "SELECT s.id AS student_id, u.name AS student_name, s.admission_no,
                    hs.id AS submission_id, hs.submission_text, hs.attachment_path, hs.attachment_name,
                    hs.submitted_at, hs.score, hs.feedback
             FROM students s
             JOIN users u ON s.user_id=u.id
             LEFT JOIN homework_submissions hs ON hs.student_id=s.id AND hs.homework_id=?
             WHERE s.class_id=? AND s.tenant_id=? AND s.status='active'
             ORDER BY u.name", [$id, $homework['class_id'], $this->tid]
        );
        $this->view('school/highschool/homework/submissions', [
            'pageTitle'=>'Submissions — '.$homework['title'],'panelType'=>'school',
            'homework'=>$homework,'roster'=>$roster,'flash'=>$this->getFlash(),
        ]);
    }

    public function grade(string $submissionId): void {
        $this->requireAuth(['School Admin','Teacher']);
        $submission = $this->db->fetchOne(
            "SELECT hs.*, h.max_score, h.id AS homework_id FROM homework_submissions hs
             JOIN homework h ON hs.homework_id=h.id WHERE hs.id=? AND hs.tenant_id=?", [$submissionId, $this->tid]
        );
        if (!$submission) { $this->redirect('/school/homework'); }
        $errors = $this->validate($_POST, ['score' => 'required|numeric']);
        if ($errors) { $this->failValidation($errors, '/school/homework/'.$submission['homework_id'].'/submissions'); }
        $this->db->execute(
            "UPDATE homework_submissions SET score=?,feedback=?,graded_at=NOW(),graded_by=? WHERE id=? AND tenant_id=?",
            [$_POST['score'], $_POST['feedback'] ?? '', $_SESSION['user_id'], $submissionId, $this->tid]
        );
        $this->flash('success', 'Submission graded.');
        $this->redirect('/school/homework/'.$submission['homework_id'].'/submissions');
    }
}
