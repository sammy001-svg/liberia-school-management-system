<?php
require_once ROOT_DIR . '/core/Controller.php';

class StudentPortalController extends Controller {
    private int $sid;
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requireAuth(['Student']);
        $this->sid = $_SESSION['student_id'] ?? 0;
        $this->tid = $this->tenantId() ?? 0;
    }

    public function dashboard(): void {
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, c.name as class_name 
             FROM students s 
             JOIN users u ON s.user_id = u.id 
             LEFT JOIN classes c ON s.class_id = c.id 
             WHERE s.id = ?", 
            [$this->sid]
        );

        $attendance = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present
             FROM attendance 
             WHERE student_id = ?", 
            [$this->sid]
        );

        $recentGrades = $this->db->fetchAll(
            "SELECT g.*, e.name as exam_name, co.name as course_name
             FROM grades g
             JOIN exams e ON g.exam_id = e.id
             JOIN courses co ON g.course_id = co.id
             WHERE g.student_id = ? ORDER BY e.exam_date DESC LIMIT 5",
            [$this->sid]
        );

        $obtained = 0; $possible = 0;
        foreach ($recentGrades as $g) { $obtained += $g['marks_obtained']; $possible += $g['total_marks']; }
        $averageMark = $possible > 0 ? round($obtained / $possible * 100) : null;

        $classId = $_SESSION['class_id'] ?? 0;
        $todaySchedule = $classId ? $this->db->fetchAll(
            "SELECT t.*, c.name as course_name, u.name as teacher_name
             FROM timetable t
             LEFT JOIN courses c ON t.course_id = c.id
             LEFT JOIN teachers te ON t.teacher_id = te.id
             LEFT JOIN users u ON te.user_id = u.id
             WHERE t.class_id = ? AND t.day_of_week = ? ORDER BY t.start_time",
            [$classId, strtolower(date('l'))]
        ) : [];

        $this->view('school/portals/student/dashboard', [
            'pageTitle' => 'Student Dashboard',
            'panelType' => 'student',
            'student' => $student,
            'attendance' => $attendance,
            'recentGrades' => $recentGrades,
            'averageMark' => $averageMark,
            'todaySchedule' => $todaySchedule,
        ]);
    }

    public function timetable(): void {
        $class_id = $_SESSION['class_id'] ?? 0;
        $timetable = $this->db->fetchAll(
            "SELECT t.*, c.name as course_name, u.name as teacher_name
             FROM timetable t
             LEFT JOIN courses c ON t.course_id = c.id
             LEFT JOIN teachers te ON t.teacher_id = te.id
             LEFT JOIN users u ON te.user_id = u.id
             WHERE t.class_id = ? ORDER BY FIELD(t.day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'), t.start_time",
            [$class_id]
        );

        $this->view('school/portals/student/timetable', [
            'pageTitle' => 'My Timetable',
            'panelType' => 'student',
            'timetable' => $timetable
        ]);
    }

    public function grades(): void {
        $grades = $this->db->fetchAll(
            "SELECT g.*, e.name as exam_name, co.name as course_name 
             FROM grades g 
             JOIN exams e ON g.exam_id = e.id 
             JOIN courses co ON g.course_id = co.id 
             WHERE g.student_id = ? ORDER BY e.exam_date DESC", 
            [$this->sid]
        );

        $this->view('school/portals/student/grades', [
            'pageTitle' => 'My Results',
            'panelType' => 'student',
            'grades' => $grades
        ]);
    }

    public function materials(): void {
        $class_id = $_SESSION['class_id'] ?? 0;
        $materials = $this->db->fetchAll(
            "SELECT m.*, u.name as teacher_name 
             FROM learning_materials m 
             JOIN teachers t ON m.teacher_id = t.id 
             JOIN users u ON t.user_id = u.id 
             WHERE m.tenant_id = ? AND (m.class_id = ? OR m.class_id IS NULL) 
             ORDER BY m.created_at DESC", 
            [$this->tid, $class_id]
        );

        $this->view('school/portals/student/materials', [
            'pageTitle' => 'Learning Materials',
            'panelType' => 'student',
            'materials' => $materials
        ]);
    }

    public function homework(): void {
        $classId = $_SESSION['class_id'] ?? 0;
        $homework = $this->db->fetchAll(
            "SELECT h.*, co.name AS course_name,
                    hs.id AS submission_id, hs.submission_text, hs.attachment_path AS submission_attachment,
                    hs.attachment_name AS submission_attachment_name, hs.submitted_at, hs.score, hs.feedback
             FROM homework h
             LEFT JOIN courses co ON h.course_id=co.id
             LEFT JOIN homework_submissions hs ON hs.homework_id=h.id AND hs.student_id=?
             WHERE h.tenant_id=? AND h.class_id=?
             ORDER BY h.due_date DESC, h.created_at DESC",
            [$this->sid, $this->tid, $classId]
        );
        $this->view('school/portals/student/homework', [
            'pageTitle' => 'Homework', 'panelType' => 'student', 'homework' => $homework,
            'flash' => $this->getFlash(),
        ]);
    }

    public function submitHomework(string $id): void {
        $homework = $this->db->fetchOne("SELECT * FROM homework WHERE id=? AND tenant_id=? AND class_id=?", [$id, $this->tid, $_SESSION['class_id'] ?? 0]);
        if (!$homework) { $this->redirect('/student/homework'); }
        $errors = [];
        [$attachUrl, $attachName] = $this->handleFileUpload('attachment', 'homework_submissions', $errors, 10 * 1024 * 1024);
        $text = trim($_POST['submission_text'] ?? '');
        if ($text === '' && !$attachUrl) {
            $errors['submission_text'] = 'Write an answer or attach a file before submitting.';
        }
        if ($errors) { $this->failValidation($errors, '/student/homework'); }

        $existing = $this->db->fetchOne("SELECT id, attachment_path FROM homework_submissions WHERE homework_id=? AND student_id=?", [$id, $this->sid]);
        if ($existing) {
            $this->db->execute(
                "UPDATE homework_submissions SET submission_text=?, attachment_path=COALESCE(?,attachment_path), attachment_name=COALESCE(?,attachment_name), submitted_at=NOW() WHERE id=?",
                [$text ?: null, $attachUrl, $attachName, $existing['id']]
            );
        } else {
            $this->db->insert(
                "INSERT INTO homework_submissions (tenant_id,homework_id,student_id,submission_text,attachment_path,attachment_name) VALUES (?,?,?,?,?,?)",
                [$this->tid, $id, $this->sid, $text ?: null, $attachUrl, $attachName]
            );
        }
        $this->flash('success', 'Homework submitted.');
        $this->redirect('/student/homework');
    }

    public function onlineClasses(): void {
        $classId = $_SESSION['class_id'] ?? 0;
        $onlineClasses = $this->db->fetchAll(
            "SELECT oc.*, co.name AS course_name, u.name AS teacher_name
             FROM online_classes oc
             LEFT JOIN courses co ON oc.course_id=co.id
             LEFT JOIN teachers t ON oc.teacher_id=t.id
             LEFT JOIN users u ON t.user_id=u.id
             WHERE oc.tenant_id=? AND oc.class_id=?
             ORDER BY oc.scheduled_date DESC, oc.start_time DESC",
            [$this->tid, $classId]
        );
        $this->view('school/portals/student/online_classes', [
            'pageTitle' => 'Online Classes', 'panelType' => 'student', 'onlineClasses' => $onlineClasses,
        ]);
    }

    public function exams(): void {
        $classId = $_SESSION['class_id'] ?? 0;
        $exams = $this->db->fetchAll(
            "SELECT e.*, co.name AS course_name,
                    a.status AS attempt_status, a.score, a.total_marks
             FROM online_exams e
             LEFT JOIN courses co ON e.course_id=co.id
             LEFT JOIN online_exam_attempts a ON a.exam_id=e.id AND a.student_id=?
             WHERE e.tenant_id=? AND e.class_id=? AND e.status='published'
             ORDER BY e.starts_at DESC",
            [$this->sid, $this->tid, $classId]
        );
        $now = time();
        foreach ($exams as &$e) {
            if ($e['attempt_status'] === 'submitted') { $e['state'] = 'completed'; }
            elseif ($now < strtotime($e['starts_at'])) { $e['state'] = 'upcoming'; }
            elseif ($now > strtotime($e['ends_at'])) { $e['state'] = 'closed'; }
            else { $e['state'] = 'open'; }
        }
        unset($e);
        $this->view('school/portals/student/exams', [
            'pageTitle' => 'Online Exams', 'panelType' => 'student', 'exams' => $exams,
        ]);
    }

    public function takeExam(string $id): void {
        $exam = $this->db->fetchOne(
            "SELECT * FROM online_exams WHERE id=? AND tenant_id=? AND class_id=? AND status='published'",
            [$id, $this->tid, $_SESSION['class_id'] ?? 0]
        );
        if (!$exam) { $this->redirect('/student/exams'); }
        $now = time();
        if ($now < strtotime($exam['starts_at']) || $now > strtotime($exam['ends_at'])) {
            $this->flash('danger', 'This exam is not currently open.');
            $this->redirect('/student/exams');
        }
        $attempt = $this->db->fetchOne("SELECT * FROM online_exam_attempts WHERE exam_id=? AND student_id=?", [$id, $this->sid]);
        if ($attempt && $attempt['status'] === 'submitted') {
            $this->redirect('/student/exams/'.$id.'/result');
        }
        if (!$attempt) {
            $attemptId = $this->db->insert("INSERT INTO online_exam_attempts (tenant_id,exam_id,student_id) VALUES (?,?,?)", [$this->tid, $id, $this->sid]);
            $attempt = $this->db->fetchOne("SELECT * FROM online_exam_attempts WHERE id=?", [$attemptId]);
        }
        $questions = $this->db->fetchAll(
            "SELECT id,question_text,option_a,option_b,option_c,option_d,marks FROM online_exam_questions WHERE exam_id=? ORDER BY sort_order,id",
            [$id]
        );
        $existingAnswers = $this->db->fetchAll("SELECT question_id, selected_option FROM online_exam_answers WHERE attempt_id=?", [$attempt['id']]);
        $answerMap = [];
        foreach ($existingAnswers as $a) { $answerMap[$a['question_id']] = $a['selected_option']; }

        $deadline = min(strtotime($attempt['started_at']) + $exam['duration_minutes'] * 60, strtotime($exam['ends_at']));
        $this->view('school/portals/student/exam_take', [
            'pageTitle' => $exam['title'], 'panelType' => 'student',
            'exam' => $exam, 'questions' => $questions, 'answerMap' => $answerMap, 'deadline' => $deadline,
        ]);
    }

    public function submitExam(string $id): void {
        $exam = $this->db->fetchOne("SELECT * FROM online_exams WHERE id=? AND tenant_id=? AND class_id=?", [$id, $this->tid, $_SESSION['class_id'] ?? 0]);
        if (!$exam) { $this->redirect('/student/exams'); }
        $attempt = $this->db->fetchOne("SELECT * FROM online_exam_attempts WHERE exam_id=? AND student_id=? AND status='in_progress'", [$id, $this->sid]);
        if (!$attempt) { $this->redirect('/student/exams/'.$id.'/result'); }

        $questions = $this->db->fetchAll("SELECT id,correct_option,marks FROM online_exam_questions WHERE exam_id=?", [$id]);
        $score = 0; $totalMarks = 0;
        foreach ($questions as $q) {
            $totalMarks += $q['marks'];
            $selected = $_POST['answers'][$q['id']] ?? null;
            $isCorrect = $selected !== null && $selected === $q['correct_option'];
            if ($isCorrect) { $score += $q['marks']; }
            $this->db->execute(
                "INSERT INTO online_exam_answers (tenant_id,attempt_id,question_id,selected_option,is_correct) VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE selected_option=VALUES(selected_option), is_correct=VALUES(is_correct)",
                [$this->tid, $attempt['id'], $q['id'], $selected ?: null, $isCorrect ? 1 : 0]
            );
        }
        $this->db->execute(
            "UPDATE online_exam_attempts SET score=?,total_marks=?,submitted_at=NOW(),status='submitted' WHERE id=?",
            [$score, $totalMarks, $attempt['id']]
        );
        $this->redirect('/student/exams/'.$id.'/result');
    }

    public function examResult(string $id): void {
        $exam = $this->db->fetchOne("SELECT * FROM online_exams WHERE id=? AND tenant_id=? AND class_id=?", [$id, $this->tid, $_SESSION['class_id'] ?? 0]);
        if (!$exam) { $this->redirect('/student/exams'); }
        $attempt = $this->db->fetchOne("SELECT * FROM online_exam_attempts WHERE exam_id=? AND student_id=? AND status='submitted'", [$id, $this->sid]);
        if (!$attempt) { $this->redirect('/student/exams'); }
        $breakdown = $this->db->fetchAll(
            "SELECT q.question_text,q.option_a,q.option_b,q.option_c,q.option_d,q.correct_option,q.marks,
                    ans.selected_option,ans.is_correct
             FROM online_exam_questions q
             LEFT JOIN online_exam_answers ans ON ans.question_id=q.id AND ans.attempt_id=?
             WHERE q.exam_id=? ORDER BY q.sort_order,q.id",
            [$attempt['id'], $id]
        );
        $this->view('school/portals/student/exam_result', [
            'pageTitle' => 'Result — '.$exam['title'], 'panelType' => 'student',
            'exam' => $exam, 'attempt' => $attempt, 'breakdown' => $breakdown,
        ]);
    }
}
