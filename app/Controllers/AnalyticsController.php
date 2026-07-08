<?php
require_once ROOT_DIR . '/core/Controller.php';

class AnalyticsController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requireAuth(['School Admin', 'Super Admin', 'Teacher']);
        $this->tid = $this->tenantId() ?? 0;
    }

    public function index(): void {
        // Average score per subject
        $subjectPerformance = $this->db->fetchAll(
            "SELECT c.name as subject, AVG(g.marks_obtained) as avg_score 
             FROM grades g 
             JOIN courses c ON g.course_id = c.id 
             WHERE g.tenant_id = ? 
             GROUP BY c.id", 
            [$this->tid]
        );

        // Attendance trend (last 7 days)
        $attendanceTrend = $this->db->fetchAll(
            "SELECT date, 
                    COUNT(*) as total, 
                    SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) as present 
             FROM attendance 
             WHERE tenant_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
             GROUP BY date ORDER BY date ASC", 
            [$this->tid]
        );

        $this->view('school/analytics/index', [
            'pageTitle' => 'Academic Analytics',
            'panelType' => 'school',
            'subjectPerformance' => $subjectPerformance,
            'attendanceTrend' => $attendanceTrend
        ]);
    }

    public function studentGrowth(int $sid): void {
        $student = $this->db->fetchOne("SELECT name FROM users u JOIN students s ON u.id = s.user_id WHERE s.id = ?", [$sid]);
        
        // Growth over time (Exam by Exam)
        $growth = $this->db->fetchAll(
            "SELECT e.name as exam, e.date, AVG(g.marks_obtained) as avg_score 
             FROM grades g 
             JOIN exams e ON g.exam_id = e.id 
             WHERE g.student_id = ? 
             GROUP BY e.id ORDER BY e.date ASC", 
            [$sid]
        );

        $this->view('school/analytics/student', [
            'pageTitle' => 'Growth Analysis: ' . $student['name'],
            'panelType' => 'school',
            'studentName' => $student['name'],
            'growth' => $growth
        ]);
    }

    public function attendanceHeatmap(): void {
        // Find students with attendance < 75%
        $chronicAbsentees = $this->db->fetchAll(
            "SELECT u.name, c.name as class_name, 
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present_days,
                    (SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) / COUNT(a.id) * 100) as percentage
             FROM students s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN classes c ON s.class_id = c.id
             JOIN attendance a ON s.id = a.student_id
             WHERE s.tenant_id = ?
             GROUP BY s.id
             HAVING percentage < 75
             ORDER BY percentage ASC",
            [$this->tid]
        );

        $this->view('school/analytics/attendance', [
            'pageTitle' => 'Attendance Heatmap',
            'panelType' => 'school',
            'chronicAbsentees' => $chronicAbsentees
        ]);
    }
}
