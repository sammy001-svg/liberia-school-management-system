<?php
require_once ROOT_DIR . '/core/Controller.php';
require_once ROOT_DIR . '/app/Controllers/ParentPortalController.php';

/**
 * Parent consent for the use of a child's photographs and video — child protection.
 *
 * One standing record per child. Parents sign it themselves in the Family Portal;
 * the office can also record a signed paper form here, and print a blank form to
 * send home or a completed record for the file.
 */
class ConsentController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    private function guard(): void {
        $this->requirePermission(['students.view', 'students.manage', 'parents.manage']);
    }

    /** What each tick box covers, in the order they are shown. */
    public const SCOPES = [
        'internal' => ['Inside the school', 'Class displays, report cards, the school noticeboard and records.'],
        'website'  => ['School website', 'Photographs and video on the school’s own website.'],
        'social'   => ['Social media', 'The school’s own Facebook, WhatsApp and similar pages.'],
        'print'    => ['Printed material', 'Prospectus, newsletters, banners and other printed material.'],
        'media'    => ['Press & partners', 'Newspapers, television and partner organisations reporting on the school.'],
    ];

    public function index(): void {
        $this->guard();
        $q = trim((string)($_GET['q'] ?? ''));
        $filter = in_array($_GET['status'] ?? '', ['granted', 'limited', 'declined', 'missing'], true) ? $_GET['status'] : '';
        $params = [$this->tid];
        $where = "s.tenant_id=? AND s.status='active'";
        if ($q !== '') { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
        if ($filter === 'missing') { $where .= " AND mc.id IS NULL"; }
        elseif ($filter !== '') { $where .= " AND mc.status=?"; $params[] = $filter; }

        $rows = $this->db->fetchAll(
            "SELECT s.id, s.admission_no, u.name, c.name AS class_name,
                    mc.status, mc.signed_on, mc.parent_name, mc.source,
                    mc.allow_website, mc.allow_social, mc.allow_print, mc.allow_media, mc.allow_internal
             FROM students s
             JOIN users u ON u.id=s.user_id
             LEFT JOIN classes c ON c.id=s.class_id
             LEFT JOIN media_consents mc ON mc.student_id=s.id AND mc.tenant_id=s.tenant_id
             WHERE {$where} ORDER BY u.name", $params);

        $counts = $this->db->fetchOne(
            "SELECT COUNT(*) total,
                    SUM(mc.id IS NULL) missing,
                    SUM(mc.status='granted') granted,
                    SUM(mc.status='limited') limited,
                    SUM(mc.status='declined') declined
             FROM students s LEFT JOIN media_consents mc ON mc.student_id=s.id AND mc.tenant_id=s.tenant_id
             WHERE s.tenant_id=? AND s.status='active'", [$this->tid]) ?: [];

        $this->view('school/highschool/students/consents', [
            'pageTitle' => 'Photo & Video Consent', 'panelType' => 'school',
            'rows' => $rows, 'counts' => $counts, 'q' => $q, 'filter' => $filter,
            'scopes' => self::SCOPES, 'flash' => $this->getFlash(),
        ]);
    }

    /** One child's record, for the office to fill in from a signed paper form. */
    public function show(string $id): void {
        $this->guard();
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, c.name AS class_name FROM students s JOIN users u ON u.id=s.user_id
             LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.tenant_id=?", [(int)$id, $this->tid]);
        if (!$student) { $this->redirect('/school/consents'); }
        $this->view('school/highschool/students/consent_form', [
            'pageTitle' => 'Consent — ' . $student['name'], 'panelType' => 'school',
            'student' => $student,
            'consent' => $this->db->fetchOne("SELECT * FROM media_consents WHERE tenant_id=? AND student_id=?", [$this->tid, (int)$id]) ?: null,
            'tenant'  => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'scopes'  => self::SCOPES,
            'statement' => ParentPortalController::CONSENT_STATEMENT,
            'parents' => $this->db->fetchAll(
                "SELECT u.name, u.phone FROM parent_students ps JOIN parents p ON p.id=ps.parent_id
                 JOIN users u ON u.id=p.user_id WHERE ps.student_id=?", [(int)$id]),
            'flash' => $this->getFlash(),
        ]);
    }

    public function save(string $id): void {
        $this->guard();
        $student = $this->db->fetchOne("SELECT id FROM students WHERE id=? AND tenant_id=?", [(int)$id, $this->tid]);
        if (!$student) { $this->redirect('/school/consents'); }
        $on = [];
        foreach (array_keys(self::SCOPES) as $k) { $on[$k] = !empty($_POST['allow_' . $k]) ? 1 : 0; }
        $status = array_sum($on) === count($on) ? 'granted' : (array_sum($on) > 0 ? 'limited' : 'declined');
        $name = mb_substr(trim((string)($_POST['parent_name'] ?? '')), 0, 150);
        if ($name === '') {
            $this->flash('danger', 'Enter the name of the parent or guardian who signed the form.');
            $this->redirect('/school/consents/' . (int)$id);
        }
        $signed = ($_POST['signed_on'] ?? '') ?: date('Y-m-d');
        $this->db->execute(
            "INSERT INTO media_consents (tenant_id,student_id,status,allow_website,allow_social,allow_print,allow_media,allow_internal,
                                         parent_name,relationship,parent_phone,notes,statement_text,signed_on,recorded_by,source)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'office')
             ON DUPLICATE KEY UPDATE status=VALUES(status), allow_website=VALUES(allow_website), allow_social=VALUES(allow_social),
                                     allow_print=VALUES(allow_print), allow_media=VALUES(allow_media), allow_internal=VALUES(allow_internal),
                                     parent_name=VALUES(parent_name), relationship=VALUES(relationship), parent_phone=VALUES(parent_phone),
                                     notes=VALUES(notes), statement_text=VALUES(statement_text), signed_on=VALUES(signed_on),
                                     recorded_by=VALUES(recorded_by), source=VALUES(source)",
            [$this->tid, (int)$id, $status, $on['website'], $on['social'], $on['print'], $on['media'], $on['internal'],
             $name, mb_substr(trim((string)($_POST['relationship'] ?? '')), 0, 60) ?: null,
             mb_substr(trim((string)($_POST['parent_phone'] ?? '')), 0, 40) ?: null,
             mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500) ?: null,
             ParentPortalController::CONSENT_STATEMENT, $signed, $_SESSION['user_id'] ?? null]
        );
        $this->flash('success', 'Consent recorded.');
        $this->redirect('/school/consents/' . (int)$id);
    }

    /** A blank form on school letterhead, to print and send home for signature. */
    public function blankForm(): void {
        $this->guard();
        $studentId = (int)($_GET['student'] ?? 0);
        $this->view('school/highschool/students/consent_print', [
            'pageTitle' => 'Parent Consent Form',
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'student' => $studentId ? $this->db->fetchOne(
                "SELECT s.admission_no, u.name, c.name AS class_name FROM students s JOIN users u ON u.id=s.user_id
                 LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]) : null,
            'consent' => $studentId ? $this->db->fetchOne("SELECT * FROM media_consents WHERE tenant_id=? AND student_id=?", [$this->tid, $studentId]) : null,
            'scopes' => self::SCOPES,
            'statement' => ParentPortalController::CONSENT_STATEMENT,
        ]);
    }
}
