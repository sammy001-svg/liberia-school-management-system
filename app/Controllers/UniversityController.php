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
        $this->requireAuth(['School Admin']);
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
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/departments');
    }

    public function storeDepartment(): void {
        $this->requireAuth(['School Admin']);
        $this->db->insert(
            "INSERT INTO departments (tenant_id, name, code, head_user_id, description) VALUES (?, ?, ?, ?, ?)",
            [$this->tid, $_POST['name'], $_POST['code'] ?? '', $_POST['head_user_id'] ?: null, $_POST['description'] ?? '']
        );
        $this->flash('success', 'Department created successfully.');
        $this->redirect('/school/departments');
    }

    // --- COURSES / SUBJECTS ---
    public function courses(): void {
        $this->requireAuth(['School Admin', 'Lecturer', 'Teacher']);
        $courses = $this->db->fetchAll("SELECT * FROM courses WHERE tenant_id = ?", [$this->tid]);
        $this->view('school/university/courses/index', [
            'pageTitle' => 'Courses catalog',
            'panelType' => 'school',
            'courses' => $courses,
            'flash' => $this->getFlash()
        ]);
    }

    public function createCourse(): void {
        $this->requireAuth(['School Admin']);
        $this->redirect('/school/courses');
    }

    public function storeCourse(): void {
        $this->requireAuth(['School Admin']);
        $this->db->insert(
            "INSERT INTO courses (tenant_id, name, code, credit_hours, semester_no, description) VALUES (?, ?, ?, ?, ?, ?)",
            [$this->tid, $_POST['name'], $_POST['code'], $_POST['credit_hours'], $_POST['semester_no'], $_POST['description'] ?? '']
        );
        $this->flash('success', 'Course added to catalog.');
        $this->redirect('/school/courses');
    }
}
