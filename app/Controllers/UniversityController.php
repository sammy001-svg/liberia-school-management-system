<?php
require_once ROOT_DIR . '/core/Controller.php';

class UniversityController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    // --- DEPARTMENTS ---
    public function departments(): void {
        $this->requirePermission(['university.manage']);
        $departments = $this->db->fetchAll(
            "SELECT d.*, u.name as head_name
             FROM departments d
             LEFT JOIN users u ON d.head_user_id = u.id
             WHERE d.tenant_id = ?",
            [$this->tid]
        );
        $staff = $this->db->fetchAll("SELECT id, name FROM users WHERE tenant_id = ? AND role_id IN (SELECT id FROM roles WHERE name IN ('Teacher', 'Lecturer', 'Staff'))", [$this->tid]);
        $this->view('school/university/departments/index', [
            'pageTitle' => 'Departments',
            'panelType' => 'school',
            'departments' => $departments,
            'staff' => $staff,
            'flash' => $this->getFlash()
        ]);
    }

    public function createDepartment(): void {
        $this->requirePermission(['university.manage']);
        $this->redirect('/school/departments');
    }

    public function storeDepartment(): void {
        $this->requirePermission(['university.manage']);
        $errors = $this->validate($_POST, ['name' => 'required|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/departments'); }
        $this->db->insert(
            "INSERT INTO departments (tenant_id, name, code, head_user_id, description) VALUES (?, ?, ?, ?, ?)",
            [$this->tid, $_POST['name'], $_POST['code'] ?? '', $_POST['head_user_id'] ?: null, $_POST['description'] ?? '']
        );
        $this->flash('success', 'Department created successfully.');
        $this->redirect('/school/departments');
    }

    // --- COURSES / SUBJECTS ---
    public function courses(): void {
        $this->requirePermission(['university.view','university.manage']);
        $courses = $this->db->fetchAll(
            "SELECT c.*, cl.name AS class_name,
                    (SELECT COUNT(*) FROM teacher_courses tc WHERE tc.course_id=c.id) AS teacher_count,
                    (SELECT GROUP_CONCAT(u.name SEPARATOR ', ') FROM teacher_courses tc JOIN teachers t ON tc.teacher_id=t.id JOIN users u ON t.user_id=u.id WHERE tc.course_id=c.id) AS teacher_names
             FROM courses c LEFT JOIN classes cl ON c.class_id=cl.id
             WHERE c.tenant_id = ? ORDER BY c.name", [$this->tid]
        );
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total, COALESCE(SUM(credit_hours),0) totalCredits,
                    SUM(CASE WHEN NOT EXISTS(SELECT 1 FROM teacher_courses tc WHERE tc.course_id=courses.id) THEN 1 ELSE 0 END) unassigned
             FROM courses WHERE tenant_id=?", [$this->tid]
        );
        $this->view('school/university/courses/index', [
            'pageTitle' => 'Courses catalog',
            'panelType' => 'school',
            'courses' => $courses,
            'stats' => $stats,
            'flash' => $this->getFlash()
        ]);
    }

    public function createCourse(): void {
        $this->requirePermission(['university.manage']);
        $this->redirect('/school/courses');
    }

    public function storeCourse(): void {
        $this->requirePermission(['university.manage']);
        $errors = $this->validate($_POST, [
            'name' => 'required|max:150',
            'credit_hours' => 'numeric',
            'semester_no'  => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/courses'); }
        $this->db->insert(
            "INSERT INTO courses (tenant_id, name, code, credit_hours, semester_no, description) VALUES (?, ?, ?, ?, ?, ?)",
            [$this->tid, $_POST['name'], $_POST['code'], $_POST['credit_hours'], $_POST['semester_no'], $_POST['description'] ?? '']
        );
        $this->flash('success', 'Course added to catalog.');
        $this->redirect('/school/courses');
    }
}
