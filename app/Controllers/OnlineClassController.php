<?php
require_once ROOT_DIR . '/core/Controller.php';

class OnlineClassController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function currentTeacherId(): ?int {
        if (($_SESSION['role'] ?? '') !== 'Teacher') { return null; }
        $t = $this->db->fetchOne("SELECT id FROM teachers WHERE user_id=? AND tenant_id=?", [$_SESSION['user_id'], $this->tid]);
        return $t['id'] ?? null;
    }

    public function index(): void {
        $this->requirePermission(['online_class.manage']);
        $classes_list = $this->db->fetchAll(
            "SELECT oc.*, c.name AS class_name, co.name AS course_name, u.name AS teacher_name,
                    (SELECT COUNT(*) FROM students s WHERE s.class_id=oc.class_id AND s.tenant_id=oc.tenant_id AND s.status='active') AS student_count,
                    (SELECT COUNT(*) FROM attendance a WHERE a.class_id=oc.class_id AND a.date=oc.scheduled_date AND a.course_id <=> oc.course_id) AS marked_count
             FROM online_classes oc
             LEFT JOIN classes c ON oc.class_id=c.id
             LEFT JOIN courses co ON oc.course_id=co.id
             LEFT JOIN teachers t ON oc.teacher_id=t.id
             LEFT JOIN users u ON t.user_id=u.id
             WHERE oc.tenant_id=? ORDER BY oc.scheduled_date DESC, oc.start_time DESC",
            [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $courses = $this->db->fetchAll("SELECT id,name FROM courses WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $stats = [
            'total'     => count($classes_list),
            'upcoming'  => count(array_filter($classes_list, fn($c) => $c['status']==='scheduled' && strtotime($c['scheduled_date']) >= strtotime(date('Y-m-d')))),
            'completed' => count(array_filter($classes_list, fn($c) => $c['status']==='completed')),
        ];
        $this->view('school/highschool/online_classes/index', [
            'pageTitle'=>'Online Classes','panelType'=>'school','onlineClasses'=>$classes_list,'classes'=>$classes,'courses'=>$courses,
            'stats'=>$stats,'flash'=>$this->getFlash(),
        ]);
    }

    public function store(): void {
        $this->requirePermission(['online_class.manage']);
        $errors = $this->validate($_POST, [
            'title'          => 'required|max:200',
            'class_id'       => 'required',
            'meeting_link'   => 'required|max:500',
            'scheduled_date' => 'required|date',
            'start_time'     => 'required',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/online-classes'); }
        $this->db->insert(
            "INSERT INTO online_classes (tenant_id,class_id,course_id,teacher_id,title,description,meeting_link,platform,scheduled_date,start_time,duration_minutes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['class_id'], $_POST['course_id'] ?: null, $this->currentTeacherId(),
                $_POST['title'], $_POST['description'] ?? '', $_POST['meeting_link'], $_POST['platform'] ?: null,
                $_POST['scheduled_date'], $_POST['start_time'], $_POST['duration_minutes'] ?: 60,
            ]
        );
        $this->flash('success', 'Online class scheduled.');
        $this->redirect('/school/online-classes');
    }

    public function delete(string $id): void {
        $this->requirePermission(['online_class.manage']);
        $this->db->execute("DELETE FROM online_classes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Online class removed.');
        $this->redirect('/school/online-classes');
    }

    public function cancel(string $id): void {
        $this->requirePermission(['online_class.manage']);
        $this->db->execute("UPDATE online_classes SET status='cancelled' WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Online class cancelled.');
        $this->redirect('/school/online-classes');
    }

    public function attendance(string $id): void {
        $this->requirePermission(['online_class.manage']);
        $class = $this->db->fetchOne(
            "SELECT oc.*, c.name AS class_name, co.name AS course_name
             FROM online_classes oc LEFT JOIN classes c ON oc.class_id=c.id LEFT JOIN courses co ON oc.course_id=co.id
             WHERE oc.id=? AND oc.tenant_id=?", [$id, $this->tid]
        );
        if (!$class) { $this->redirect('/school/online-classes'); }

        $roster = $this->db->fetchAll(
            "SELECT s.id AS student_id, u.name AS student_name, s.admission_no, a.status
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN attendance a ON a.student_id=s.id AND a.date=? AND a.course_id <=> ?
             WHERE s.class_id=? AND s.tenant_id=? AND s.status='active' ORDER BY u.name",
            [$class['scheduled_date'], $class['course_id'], $class['class_id'], $this->tid]
        );
        $this->view('school/highschool/online_classes/attendance', [
            'pageTitle'=>'Attendance — '.$class['title'],'panelType'=>'school',
            'onlineClass'=>$class,'roster'=>$roster,'flash'=>$this->getFlash(),
        ]);
    }

    public function markAttendance(string $id): void {
        $this->requirePermission(['online_class.manage']);
        $class = $this->db->fetchOne("SELECT * FROM online_classes WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$class) { $this->redirect('/school/online-classes'); }

        foreach ($_POST['status'] ?? [] as $studentId => $status) {
            if (!in_array($status, ['present','absent','late','excused'], true)) { continue; }
            $existing = $this->db->fetchOne(
                "SELECT id FROM attendance WHERE student_id=? AND date=? AND course_id <=> ? AND tenant_id=?",
                [$studentId, $class['scheduled_date'], $class['course_id'], $this->tid]
            );
            if ($existing) {
                $this->db->execute("UPDATE attendance SET status=?,remarks=?,marked_by=? WHERE id=?",
                    [$status, 'Online class: '.$class['title'], $_SESSION['user_id'], $existing['id']]);
            } else {
                $this->db->insert(
                    "INSERT INTO attendance (tenant_id,student_id,class_id,course_id,date,status,remarks,marked_by) VALUES (?,?,?,?,?,?,?,?)",
                    [$this->tid, $studentId, $class['class_id'], $class['course_id'], $class['scheduled_date'], $status, 'Online class: '.$class['title'], $_SESSION['user_id']]
                );
            }
        }
        $this->db->execute("UPDATE online_classes SET status='completed' WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Attendance recorded and saved to the class attendance register.');
        $this->redirect('/school/online-classes/'.$id.'/attendance');
    }
}
