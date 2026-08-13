<?php
require_once ROOT_DIR . '/core/Controller.php';

class AcademicController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['academic.manage']);
        $years = $this->db->fetchAll("SELECT * FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $terms = $this->db->fetchAll(
            "SELECT t.*, y.name AS year_name FROM terms t JOIN academic_years y ON t.academic_year_id=y.id WHERE t.tenant_id=? ORDER BY t.start_date DESC",
            [$this->tid]
        );
        $this->view('school/highschool/academics/index', [
            'pageTitle' => 'Academic Years & Periods', 'panelType' => 'school',
            'years' => $years, 'terms' => $terms, 'flash' => $this->getFlash(),
        ]);
    }

    public function storeYear(): void {
        $this->requirePermission(['academic.manage']);
        $errors = $this->validate($_POST, [
            'name'       => 'required|max:50',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/academic-years'); }
        if (!empty($_POST['is_current'])) {
            $this->db->execute("UPDATE academic_years SET is_current=0 WHERE tenant_id=?", [$this->tid]);
        }
        $this->db->insert(
            "INSERT INTO academic_years (tenant_id,name,start_date,end_date,is_current) VALUES (?,?,?,?,?)",
            [$this->tid, $_POST['name'], $_POST['start_date'], $_POST['end_date'], !empty($_POST['is_current']) ? 1 : 0]
        );
        $this->flash('success', 'Academic year created.');
        $this->redirect('/school/academic-years');
    }

    public function updateYear(string $id): void {
        $this->requirePermission(['academic.manage']);
        $year = $this->db->fetchOne("SELECT id FROM academic_years WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$year) { $this->redirect('/school/academic-years'); }

        $errors = $this->validate($_POST, [
            'name'       => 'required|max:50',
            'start_date' => 'required|date',
            'end_date'   => 'required|date',
        ]);
        if (!$errors && strtotime($_POST['end_date']) < strtotime($_POST['start_date'])) {
            $errors['end_date'] = 'The end date cannot be before the start date.';
        }
        if ($errors) { $this->failValidation($errors, '/school/academic-years'); }

        // Only one year can be current, so promoting this one demotes the rest.
        if (!empty($_POST['is_current'])) {
            $this->db->execute("UPDATE academic_years SET is_current=0 WHERE tenant_id=?", [$this->tid]);
        }
        $this->db->execute(
            "UPDATE academic_years SET name=?, start_date=?, end_date=?, is_current=? WHERE id=? AND tenant_id=?",
            [$_POST['name'], $_POST['start_date'], $_POST['end_date'], !empty($_POST['is_current']) ? 1 : 0, $id, $this->tid]
        );
        $this->flash('success', 'Academic year updated.');
        $this->redirect('/school/academic-years');
    }

    /**
     * Count what an academic year is attached to. Deleting a year that is still in use
     * would cascade into student, exam and fee records, so this drives a hard block
     * rather than a warning. Each probe is guarded: this schema has drifted across
     * installs, and a table missing on one of them must not break the whole screen.
     */
    private function yearUsage(string $id): array {
        $probes = [
            'period'         => 'SELECT COUNT(*) c FROM terms WHERE academic_year_id=?',
            'class'          => 'SELECT COUNT(*) c FROM classes WHERE academic_year_id=?',
            'student'        => 'SELECT COUNT(*) c FROM students WHERE academic_year_id=?',
            'exam'           => 'SELECT COUNT(*) c FROM exams WHERE academic_year_id=?',
            'fee structure'  => 'SELECT COUNT(*) c FROM fee_structures WHERE academic_year_id=?',
            'certificate'    => 'SELECT COUNT(*) c FROM certificates WHERE academic_year_id=?',
            'timetable entry'=> 'SELECT COUNT(*) c FROM timetable WHERE academic_year_id=?',
        ];
        $used = [];
        foreach ($probes as $label => $sql) {
            try {
                $n = (int)($this->db->fetchOne($sql, [$id])['c'] ?? 0);
                if ($n > 0) { $used[$label] = $n; }
            } catch (\Throwable $e) {
                // Table or column absent on this install — nothing to protect against here.
                continue;
            }
        }
        return $used;
    }

    public function deleteYear(string $id): void {
        $this->requirePermission(['academic.manage']);
        $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$year) { $this->redirect('/school/academic-years'); }

        $used = $this->yearUsage($id);
        if ($used) {
            $parts = [];
            foreach ($used as $label => $n) { $parts[] = $n . ' ' . $label . ($n > 1 ? 's' : ''); }
            $this->flash('danger',
                "\"{$year['name']}\" can't be deleted — it is still linked to " . implode(', ', $parts) .
                '. Reassign or remove those first, or just leave the year in place and mark a different one as current.');
            $this->redirect('/school/academic-years');
        }

        $this->db->execute("DELETE FROM academic_years WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', "Academic year \"{$year['name']}\" deleted.");
        $this->redirect('/school/academic-years');
    }

    public function storeTerm(): void {
        $this->requirePermission(['academic.manage']);
        $errors = $this->validate($_POST, [
            'academic_year_id' => 'required',
            'name'             => 'required|max:80',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/academic-years'); }
        if (!empty($_POST['is_current'])) {
            $this->db->execute("UPDATE terms SET is_current=0 WHERE tenant_id=? AND academic_year_id=?", [$this->tid, $_POST['academic_year_id']]);
        }
        $this->db->insert(
            "INSERT INTO terms (tenant_id,academic_year_id,name,start_date,end_date,is_current) VALUES (?,?,?,?,?,?)",
            [$this->tid, $_POST['academic_year_id'], $_POST['name'], $_POST['start_date'], $_POST['end_date'], !empty($_POST['is_current']) ? 1 : 0]
        );
        $this->flash('success', 'Period created.');
        $this->redirect('/school/academic-years');
    }
}
