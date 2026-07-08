<?php
require_once ROOT_DIR . '/core/Controller.php';

class AttendanceController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $date    = $_GET['date'] ?? date('Y-m-d');
        $classId = $_GET['class_id'] ?? '';
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $students = [];
        $records  = [];
        if ($classId) {
            $students = $this->db->fetchAll("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.class_id=? AND s.status='active' ORDER BY u.name", [$this->tid,$classId]);
            $recs = $this->db->fetchAll("SELECT * FROM attendance WHERE tenant_id=? AND class_id=? AND date=?", [$this->tid,$classId,$date]);
            foreach ($recs as $r) { $records[$r['student_id']] = $r['status']; }
        }
        $this->view('school/highschool/attendance/index', ['pageTitle'=>'Attendance','panelType'=>'school','classes'=>$classes,'students'=>$students,'records'=>$records,'date'=>$date,'classId'=>$classId,'flash'=>$this->getFlash()]);
    }

    public function mark(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $classId = $_POST['class_id'] ?? '';
        $date    = $_POST['date'] ?? date('Y-m-d');
        $statuses = $_POST['status'] ?? [];
        $this->db->execute("DELETE FROM attendance WHERE tenant_id=? AND class_id=? AND date=?", [$this->tid,$classId,$date]);
        foreach ($statuses as $studentId => $status) {
            $this->db->insert("INSERT INTO attendance (tenant_id,student_id,class_id,date,status,marked_by) VALUES (?,?,?,?,?,?)",
                [$this->tid,$studentId,$classId,$date,$status,$_SESSION['user_id']]);
        }
        $this->flash('success','Attendance saved for '.$date);
        $this->redirect('/school/attendance?date='.$date.'&class_id='.$classId);
    }

    public function report(): void {
        $this->requireAuth(['School Admin','Teacher','Super Admin']);
        $classId = $_GET['class_id'] ?? '';
        $from    = $_GET['from'] ?? date('Y-m-01');
        $to      = $_GET['to']   ?? date('Y-m-d');
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=?", [$this->tid]);
        $report  = [];
        if ($classId) {
            $report = $this->db->fetchAll("SELECT u.name, a.status, COUNT(*) AS cnt FROM attendance a JOIN students s ON a.student_id=s.id JOIN users u ON s.user_id=u.id WHERE a.tenant_id=? AND a.class_id=? AND a.date BETWEEN ? AND ? GROUP BY a.student_id, a.status ORDER BY u.name",
                [$this->tid,$classId,$from,$to]);
        }
        $this->view('school/highschool/attendance/report', ['pageTitle'=>'Attendance Report','panelType'=>'school','classes'=>$classes,'report'=>$report,'classId'=>$classId,'from'=>$from,'to'=>$to,'flash'=>$this->getFlash()]);
    }
}
