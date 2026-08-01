<?php
require_once ROOT_DIR . '/core/Controller.php';

class DocumentController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    /** Preset categories offered in the upload dropdown. Stored as plain text, so a school
     *  can record anything not listed here via "Other" without a schema change. */
    public const DOCUMENT_TYPES = [
        'Transcript',
        'Report Card',
        'Letter of Recommendation',
        'Birth Certificate',
        'Transfer Certificate',
        'Health / Immunization Record',
        'National ID / Passport',
        'Photograph',
        'Other',
    ];

    // --- STORED DOCUMENTS ---

    public function upload(string $studentId): void {
        $this->requirePermission(['students.manage']);
        $student = $this->db->fetchOne("SELECT id FROM students WHERE id=? AND tenant_id=?", [$studentId, $this->tid]);
        if (!$student) { $this->redirect('/school/students'); }

        $backTo = '/school/students/' . $studentId;
        $errors = $this->validate($_POST, [
            'document_type' => 'required|max:80',
            'title'         => 'required|max:150',
            'issue_date'    => 'date',
        ]);
        // "Other" reveals a free-text box in the modal; prefer whatever was typed there.
        $type = trim($_POST['document_type'] ?? '');
        if ($type === 'Other' && trim($_POST['custom_type'] ?? '') !== '') {
            $type = trim($_POST['custom_type']);
        }
        [$fileUrl, $origName] = $this->handleFileUpload('document_file', 'student_documents', $errors);
        if (!$errors && !$fileUrl) { $errors['document_file'] = 'Choose a file to upload.'; }
        if ($errors) { $this->failValidation($errors, $backTo); }

        $this->db->insert(
            "INSERT INTO student_documents (tenant_id,student_id,document_type,title,issued_by,issue_date,file_url,file_name,notes,uploaded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)",
            [
                $this->tid, $studentId, $type, $_POST['title'], $_POST['issued_by'] ?: null,
                $_POST['issue_date'] ?: null, $fileUrl, $origName, $_POST['notes'] ?: null, $_SESSION['user_id'],
            ]
        );
        $this->flash('success', 'Document uploaded.');
        $this->redirect($backTo);
    }

    public function delete(string $id): void {
        $this->requirePermission(['students.manage']);
        $record = $this->db->fetchOne("SELECT student_id FROM student_documents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$record) { $this->redirect('/school/students'); }
        $this->db->execute("DELETE FROM student_documents WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Document removed.');
        $this->redirect('/school/students/' . $record['student_id']);
    }

    // --- GENERATED TRANSCRIPT (outgoing: student leaving for another school) ---

    public function transcript(string $studentId): void {
        $this->requirePermission(['students.view','students.edit','students.manage']);
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, u.gender, u.date_of_birth, c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]
        );
        if (!$student) { $this->redirect('/school/students'); }

        // Every graded result this student has, oldest first, tagged with the academic year
        // it belongs to (directly on the exam, or inherited from the exam's term).
        $rows = $this->db->fetchAll(
            "SELECT co.name AS course_name,
                    e.name AS exam_name, e.exam_date,
                    t.name AS term_name,
                    ay.name AS year_name, ay.start_date AS year_start,
                    g.marks_obtained, g.total_marks, g.grade_letter, g.gpa_points
             FROM grades g
             LEFT JOIN courses co ON g.course_id=co.id
             LEFT JOIN exams e ON g.exam_id=e.id
             LEFT JOIN terms t ON e.term_id=t.id
             LEFT JOIN academic_years ay ON ay.id=COALESCE(e.academic_year_id, t.academic_year_id)
             WHERE g.student_id=? AND g.tenant_id=?
             ORDER BY ay.start_date, e.exam_date, co.name",
            [$studentId, $this->tid]
        );

        // Collapse into year -> subject -> averaged percentage, which is what a transcript
        // reports (a leaving student's standing per subject per year, not every single test).
        $years = [];
        foreach ($rows as $r) {
            $yearKey    = $r['year_name'] ?: 'Unassigned';
            $courseName = $r['course_name'] ?: 'General';
            $total      = (float)($r['total_marks'] ?: 0);
            if ($total <= 0) { continue; }
            $years[$yearKey]['subjects'][$courseName][] = (float)$r['marks_obtained'] / $total * 100;
        }

        $letterFor = fn(float $p) => $p>=90?'A+':($p>=80?'A':($p>=70?'B':($p>=60?'C':($p>=50?'D':'F'))));
        $gpaFor    = fn(float $p) => $p>=90?4.0:($p>=80?3.5:($p>=70?3.0:($p>=60?2.5:($p>=50?2.0:0.0))));

        $allPercentages = [];
        foreach ($years as $yearKey => $data) {
            $yearPercentages = [];
            foreach ($data['subjects'] as $courseName => $marks) {
                $avg = array_sum($marks) / count($marks);
                $years[$yearKey]['rows'][] = [
                    'course' => $courseName,
                    'assessments' => count($marks),
                    'average' => $avg,
                    'letter' => $letterFor($avg),
                ];
                $yearPercentages[] = $avg;
                $allPercentages[]  = $avg;
            }
            unset($years[$yearKey]['subjects']);
            $years[$yearKey]['average'] = $yearPercentages ? array_sum($yearPercentages) / count($yearPercentages) : null;
        }

        $overall = $allPercentages ? array_sum($allPercentages) / count($allPercentages) : null;

        $attendance = $this->db->fetchOne(
            "SELECT COUNT(*) total, SUM(CASE WHEN status='present' THEN 1 ELSE 0 END) present
             FROM attendance WHERE student_id=? AND tenant_id=?", [$studentId, $this->tid]
        );
        $attendanceRate = ($attendance['total'] ?? 0) > 0
            ? round($attendance['present'] / $attendance['total'] * 100) : null;

        $this->view('school/transcript_print', [
            'pageTitle' => 'Transcript',
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'student' => $student,
            'years' => $years, 'overall' => $overall,
            'overallLetter' => $overall !== null ? $letterFor($overall) : null,
            'overallGpa' => $overall !== null ? $gpaFor($overall) : null,
            'attendanceRate' => $attendanceRate,
        ]);
    }
}
