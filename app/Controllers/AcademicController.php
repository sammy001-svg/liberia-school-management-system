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
