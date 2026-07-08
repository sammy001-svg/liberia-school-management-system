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
             WHERE g.student_id = ? ORDER BY e.date DESC LIMIT 5", 
            [$this->sid]
        );

        $this->view('school/portals/student/dashboard', [
            'pageTitle' => 'Student Dashboard',
            'panelType' => 'student',
            'student' => $student,
            'attendance' => $attendance,
            'recentGrades' => $recentGrades
        ]);
    }

    public function timetable(): void {
        $class_id = $_SESSION['class_id'] ?? 0;
        $timetable = $this->db->fetchAll(
            "SELECT t.*, c.name as course_name, u.name as teacher_name 
             FROM timetable t 
             JOIN courses c ON t.course_id = c.id 
             LEFT JOIN teachers te ON t.teacher_id = te.id 
             LEFT JOIN users u ON te.user_id = u.id 
             WHERE t.class_id = ? ORDER BY FIELD(t.day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), t.start_time", 
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
             WHERE g.student_id = ? ORDER BY e.date DESC", 
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
}
