<?php
require_once ROOT_DIR . '/core/Controller.php';

class SchoolDashboardController extends Controller {
    public function index(): void {
        $this->requireAuth(['School Admin','Teacher','Accountant','Staff','Super Admin']);
        $tid = $this->tenantId();
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$tid]);

        // Base Stats
        $students = $this->db->fetchOne("SELECT COUNT(*) AS c FROM students WHERE tenant_id=? AND status='active'",[$tid])['c']??0;
        $teachers = $this->db->fetchOne("SELECT COUNT(*) AS c FROM teachers WHERE tenant_id=?",[$tid])['c']??0;
        $classes  = $this->db->fetchOne("SELECT COUNT(*) AS c FROM classes WHERE tenant_id=?",[$tid])['c']??0;
        $present  = $this->db->fetchOne("SELECT COUNT(*) AS c FROM attendance WHERE tenant_id=? AND date=CURDATE() AND status='present'",[$tid])['c']??0;
        
        $attendance_percent = $students > 0 ? round(($present / $students) * 100, 1) : 0;

        $stats = [
            'students' => $students,
            'teachers' => $teachers,
            'classes'  => $classes,
            'attendance_pct' => $attendance_percent
        ];

        // Announcements
        $announcements = $this->db->fetchAll(
            "SELECT a.*, u.name AS author FROM announcements a JOIN users u ON a.author_id=u.id
             WHERE a.tenant_id=? ORDER BY a.published_at DESC LIMIT 3", [$tid]
        );

        // Chart Data - Attendance
        $att_records = $this->db->fetchAll("SELECT date, 
            COUNT(*) as total_marked, 
            SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as total_present 
            FROM attendance 
            WHERE tenant_id=? AND date >= DATE_SUB(CURDATE(), INTERVAL 5 DAY)
            GROUP BY date ORDER BY date ASC", [$tid]);
            
        $attendance_history = [];
        for($i=5; $i>=0; $i--) {
            $dayName = date('D', strtotime("-$i days"));
            $attendance_history[$dayName] = 0;
        }

        foreach($att_records as $rec) {
            $dayName = date('D', strtotime($rec['date']));
            $pct = $rec['total_marked'] > 0 ? round(($rec['total_present'] / $rec['total_marked']) * 100) : 0;
            if (isset($attendance_history[$dayName])) {
                $attendance_history[$dayName] = $pct;
            }
        }

        // Chart Data - Fees
        $feesData = $this->db->fetchOne("SELECT 
            COALESCE(SUM(amount_paid), 0) as collected,
            COALESCE(SUM(CASE WHEN status IN ('unpaid','partial') THEN amount_due - amount_paid - discount ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'overdue' THEN amount_due - amount_paid - discount ELSE 0 END), 0) as overdue
            FROM invoices WHERE tenant_id=?", [$tid]);

        $fees = [
            'collected' => $feesData['collected'],
            'pending' => $feesData['pending'],
            'overdue' => $feesData['overdue']
        ];

        // Chart Data - Exams
        $examsData = $this->db->fetchOne("SELECT 
            COUNT(CASE WHEN exam_date > CURDATE() THEN 1 END) as upcoming,
            COUNT(CASE WHEN exam_date = CURDATE() THEN 1 END) as in_progress,
            COUNT(CASE WHEN exam_date < CURDATE() THEN 1 END) as completed
            FROM exams WHERE tenant_id=?", [$tid]);

        $exams = [
            'upcoming' => $examsData['upcoming'],
            'in_progress' => $examsData['in_progress'],
            'completed' => $examsData['completed'],
            'cancelled' => 0
        ];

        $view = ($tenant['institution_type'] === 'university') 
                ? 'school/university/dashboard' 
                : 'school/highschool/dashboard';

        $this->view($view, [
            'pageTitle'      => 'Dashboard',
            'panelType'      => 'school',
            'tenant'         => $tenant,
            'stats'          => $stats,
            'announcements'  => $announcements,
            'attendance_hist'=> $attendance_history,
            'fees'           => $fees,
            'exams'          => $exams,
            'flash'          => $this->getFlash(),
        ]);
    }
}
