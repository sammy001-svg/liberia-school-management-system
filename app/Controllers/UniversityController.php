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
        $this->requireAuth(['School Admin', 'Super Admin']);
        $departments = $this->db->fetchAll(
            "SELECT d.*, u.name as head_name 
             FROM departments d 
             LEFT JOIN users u ON d.head_user_id = u.id 
             WHERE d.tenant_id = ?", 
            [$this->tid]
        );
        $this->view('school/university/departments/index', [
            'pageTitle' => 'Departments',
            'panelType' => 'school',
            'departments' => $departments,
            'flash' => $this->getFlash()
        ]);
    }

    public function createDepartment(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $staff = $this->db->fetchAll("SELECT id, name FROM users WHERE tenant_id = ? AND role_id IN (SELECT id FROM roles WHERE name IN ('Teacher', 'Lecturer', 'Staff'))", [$this->tid]);
        $this->view('school/university/departments/form', [
            'pageTitle' => 'Add Department',
            'panelType' => 'school',
            'staff' => $staff,
            'flash' => $this->getFlash()
        ]);
    }

    public function storeDepartment(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $this->db->insert(
            "INSERT INTO departments (tenant_id, name, head_user_id, description) VALUES (?, ?, ?, ?)",
            [$this->tid, $_POST['name'], $_POST['head_user_id'] ?: null, $_POST['description'] ?? '']
        );
        $this->flash('success', 'Department created successfully.');
        $this->redirect('/school/departments');
    }

    // --- PROGRAMS ---
    public function programs(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $programs = $this->db->fetchAll(
            "SELECT p.*, d.name as dept_name 
             FROM programs p 
             JOIN departments d ON p.department_id = d.id 
             WHERE p.tenant_id = ?", 
            [$this->tid]
        );
        $this->view('school/university/programs/index', [
            'pageTitle' => 'Academic Programs',
            'panelType' => 'school',
            'programs' => $programs,
            'flash' => $this->getFlash()
        ]);
    }

    public function createProgram(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $depts = $this->db->fetchAll("SELECT id, name FROM departments WHERE tenant_id = ?", [$this->tid]);
        $this->view('school/university/programs/form', [
            'pageTitle' => 'Add Program',
            'panelType' => 'school',
            'departments' => $depts,
            'flash' => $this->getFlash()
        ]);
    }

    public function storeProgram(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $this->db->insert(
            "INSERT INTO programs (tenant_id, department_id, name, code, duration_years, degree_type) VALUES (?, ?, ?, ?, ?, ?)",
            [$this->tid, $_POST['department_id'], $_POST['name'], $_POST['code'], $_POST['duration_years'], $_POST['degree_type']]
        );
        $this->flash('success', 'Program created successfully.');
        $this->redirect('/school/programs');
    }

    // --- COURSES ---
    public function courses(): void {
        $this->requireAuth(['School Admin', 'Super Admin', 'Lecturer', 'Teacher']);
        $courses = $this->db->fetchAll(
            "SELECT c.*, p.name as program_name 
             FROM courses c 
             LEFT JOIN programs p ON c.program_id = p.id 
             WHERE c.tenant_id = ?", 
            [$this->tid]
        );
        $this->view('school/university/courses/index', [
            'pageTitle' => 'Courses catalog',
            'panelType' => 'school',
            'courses' => $courses,
            'flash' => $this->getFlash()
        ]);
    }

    public function createCourse(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $programs = $this->db->fetchAll("SELECT id, name FROM programs WHERE tenant_id = ?", [$this->tid]);
        $this->view('school/university/courses/form', [
            'pageTitle' => 'Add Course',
            'panelType' => 'school',
            'programs' => $programs,
            'flash' => $this->getFlash()
        ]);
    }

    public function storeCourse(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $this->db->insert(
            "INSERT INTO courses (tenant_id, program_id, name, code, credit_hours, semester_no) VALUES (?, ?, ?, ?, ?, ?)",
            [$this->tid, $_POST['program_id'] ?: null, $_POST['name'], $_POST['code'], $_POST['credit_hours'], $_POST['semester_no']]
        );
        $this->flash('success', 'Course added to catalog.');
        $this->redirect('/school/courses');
    }

    // --- ENROLLMENTS / REGISTRATION ---
    public function enrollments(): void {
        $this->requireAuth(['School Admin', 'Super Admin', 'Lecturer']);
        $enrollments = $this->db->fetchAll(
            "SELECT e.*, u.name as student_name, c.name as course_name 
             FROM enrollments e 
             JOIN students s ON e.student_id = s.id 
             JOIN users u ON s.user_id = u.id 
             JOIN courses c ON e.course_id = c.id 
             WHERE e.tenant_id = ? ORDER BY e.enrolled_at DESC", 
            [$this->tid]
        );
        $this->view('school/university/enrollments/index', [
            'pageTitle' => 'Course Enrollments',
            'panelType' => 'school',
            'enrollments' => $enrollments,
            'flash' => $this->getFlash()
        ]);
    }

    public function createEnrollment(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $students = $this->db->fetchAll("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id = u.id WHERE s.tenant_id = ?", [$this->tid]);
        $courses = $this->db->fetchAll("SELECT id, name, code FROM courses WHERE tenant_id = ?", [$this->tid]);
        $this->view('school/university/enrollments/form', [
            'pageTitle' => 'Enroll Student',
            'panelType' => 'school',
            'students' => $students,
            'courses' => $courses,
            'flash' => $this->getFlash()
        ]);
    }

    public function storeEnrollment(): void {
        $this->requireAuth(['School Admin', 'Super Admin']);
        $this->db->insert(
            "INSERT INTO enrollments (tenant_id, student_id, course_id, semester, status) VALUES (?, ?, ?, ?, 'enrolled')",
            [$this->tid, $_POST['student_id'], $_POST['course_id'], $_POST['semester']]
        );
        $this->flash('success', 'Student enrolled successfully.');
        $this->redirect('/school/enrollments');
    }
}
