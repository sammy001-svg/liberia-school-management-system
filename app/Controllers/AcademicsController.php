<?php
require_once ROOT_DIR . '/core/Controller.php';

class AcademicsController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    // --- DEPARTMENTS ---
    public function departments(): void {
        $this->requirePermission(['academics.manage']);
        $departments = $this->db->fetchAll(
            "SELECT d.*, u.name as head_name
             FROM departments d
             LEFT JOIN users u ON d.head_user_id = u.id
             WHERE d.tenant_id = ?",
            [$this->tid]
        );
        $staff = $this->db->fetchAll("SELECT id, name FROM users WHERE tenant_id = ? AND role_id IN (SELECT id FROM roles WHERE name IN ('Teacher', 'Staff'))", [$this->tid]);
        $this->view('school/highschool/departments/index', [
            'pageTitle' => 'Departments',
            'panelType' => 'school',
            'departments' => $departments,
            'staff' => $staff,
            'flash' => $this->getFlash()
        ]);
    }

    public function createDepartment(): void {
        $this->requirePermission(['academics.manage']);
        $this->redirect('/school/departments');
    }

    public function storeDepartment(): void {
        $this->requirePermission(['academics.manage']);
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
        $this->requirePermission(['academics.view','academics.manage']);
        $courses = $this->db->fetchAll(
            "SELECT c.*,
                    (SELECT GROUP_CONCAT(cl.name ORDER BY cl.name SEPARATOR ', ') FROM course_classes cc JOIN classes cl ON cc.class_id=cl.id WHERE cc.course_id=c.id) AS class_names,
                    (SELECT GROUP_CONCAT(cc.class_id) FROM course_classes cc WHERE cc.course_id=c.id) AS class_ids,
                    (SELECT COUNT(*) FROM teacher_courses tc WHERE tc.course_id=c.id) AS teacher_count,
                    (SELECT GROUP_CONCAT(u.name SEPARATOR ', ') FROM teacher_courses tc JOIN teachers t ON tc.teacher_id=t.id JOIN users u ON t.user_id=u.id WHERE tc.course_id=c.id) AS teacher_names
             FROM courses c
             WHERE c.tenant_id = ? ORDER BY c.name", [$this->tid]
        );
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(CASE WHEN EXISTS(SELECT 1 FROM course_classes cc WHERE cc.course_id=courses.id) THEN 1 ELSE 0 END) assignedToClass,
                    SUM(CASE WHEN NOT EXISTS(SELECT 1 FROM teacher_courses tc WHERE tc.course_id=courses.id) THEN 1 ELSE 0 END) unassigned
             FROM courses WHERE tenant_id=?", [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $this->view('school/highschool/courses/index', [
            'pageTitle' => 'Subjects',
            'panelType' => 'school',
            'courses' => $courses,
            'stats' => $stats,
            'classes' => $classes,
            'flash' => $this->getFlash()
        ]);
    }

    public function createCourse(): void {
        $this->requirePermission(['academics.manage']);
        $this->redirect('/school/courses');
    }

    private function syncCourseClasses(int $courseId, array $classIds): void {
        $classIds = array_unique(array_filter(array_map('intval', $classIds)));
        $this->db->execute("DELETE FROM course_classes WHERE course_id=?", [$courseId]);
        foreach ($classIds as $classId) {
            $this->db->execute("INSERT INTO course_classes (course_id, class_id) VALUES (?, ?)", [$courseId, $classId]);
        }
    }

    public function storeCourse(): void {
        $this->requirePermission(['academics.manage']);
        $errors = $this->validate($_POST, ['name' => 'required|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/courses'); }
        $courseId = $this->db->insert(
            "INSERT INTO courses (tenant_id, name, code, description) VALUES (?, ?, ?, ?)",
            [$this->tid, $_POST['name'], $_POST['code'], $_POST['description'] ?? '']
        );
        $this->syncCourseClasses((int)$courseId, $_POST['class_ids'] ?? []);
        $this->flash('success', 'Subject added.');
        $this->redirect('/school/courses');
    }

    public function updateCourse(string $id): void {
        $this->requirePermission(['academics.manage']);
        $course = $this->db->fetchOne("SELECT id FROM courses WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$course) { $this->redirect('/school/courses'); }
        $errors = $this->validate($_POST, ['name' => 'required|max:150']);
        if ($errors) { $this->failValidation($errors, '/school/courses'); }
        $this->db->execute(
            "UPDATE courses SET name=?, code=?, description=? WHERE id=? AND tenant_id=?",
            [$_POST['name'], $_POST['code'], $_POST['description'] ?? '', $id, $this->tid]
        );
        $this->syncCourseClasses((int)$id, $_POST['class_ids'] ?? []);
        $this->flash('success', 'Subject updated.');
        $this->redirect('/school/courses');
    }

    // Safe to delete outright: grades/timetable/homework/online class/online exam rows
    // that reference this course fall back to SET NULL (they survive, just lose the
    // subject label); only the teacher-course assignment link actually cascades away.
    public function deleteCourse(string $id): void {
        $this->requirePermission(['academics.manage']);
        $this->db->execute("DELETE FROM courses WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Subject removed.');
        $this->redirect('/school/courses');
    }
}
