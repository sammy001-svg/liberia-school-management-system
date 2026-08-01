<?php
require_once ROOT_DIR . '/core/Controller.php';

/**
 * Student accounts & receivables — the ledger-backed half of the finance module.
 * Balances come from student_ledger rather than being re-summed off invoices, so
 * discounts, scholarships and adjustments are all reflected in one place.
 */
class StudentAccountController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    private function guard(): void {
        $this->requirePermission(['finance.accounts','finance.manage']);
    }

    /** Post a single ledger entry. Charges are positive, credits negative. */
    public static function post(
        Database $db, int $tid, int $studentId, string $type, string $description,
        float $amount, array $links = []
    ): void {
        $db->insert(
            "INSERT INTO student_ledger
               (tenant_id,student_id,entry_date,entry_type,description,amount,invoice_id,payment_id,fee_item_id,academic_year_id,term_id,reference,recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [
                $tid, $studentId, $links['date'] ?? date('Y-m-d'), $type, $description, $amount,
                $links['invoice_id'] ?? null, $links['payment_id'] ?? null, $links['fee_item_id'] ?? null,
                $links['academic_year_id'] ?? null, $links['term_id'] ?? null,
                $links['reference'] ?? null, $_SESSION['user_id'] ?? null,
            ]
        );
    }

    // ── ACCOUNTS LIST ────────────────────────────────────────────────
    public function index(): void {
        $this->guard();
        $search  = trim($_GET['q'] ?? '');
        $classId = $_GET['class_id'] ?? '';
        $onlyOwing = !empty($_GET['owing']);

        $params = [$this->tid];
        $where  = "s.tenant_id=?";
        if ($search)  { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        if ($classId) { $where .= " AND s.class_id=?"; $params[] = $classId; }

        $rows = $this->db->fetchAll(
            "SELECT s.id, s.admission_no, u.name, c.name AS class_name,
                    COALESCE((SELECT SUM(l.amount) FROM student_ledger l WHERE l.student_id=s.id AND l.tenant_id=s.tenant_id),0) AS balance,
                    COALESCE((SELECT SUM(l.amount) FROM student_ledger l WHERE l.student_id=s.id AND l.tenant_id=s.tenant_id AND l.amount>0),0) AS charged,
                    COALESCE((SELECT -SUM(l.amount) FROM student_ledger l WHERE l.student_id=s.id AND l.tenant_id=s.tenant_id AND l.amount<0),0) AS credited
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE $where AND s.status='active'
             ORDER BY balance DESC, u.name", $params
        );
        if ($onlyOwing) { $rows = array_values(array_filter($rows, fn($r) => (float)$r['balance'] > 0.009)); }

        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $totals = [
            'charged'  => array_sum(array_column($rows, 'charged')),
            'credited' => array_sum(array_column($rows, 'credited')),
            'owing'    => array_sum(array_map(fn($r) => max(0, (float)$r['balance']), $rows)),
            'inCredit' => array_sum(array_map(fn($r) => max(0, -(float)$r['balance']), $rows)),
        ];

        $this->view('school/highschool/finance/accounts', [
            'pageTitle' => 'Student Accounts', 'panelType' => 'school',
            'rows' => $rows, 'classes' => $classes, 'totals' => $totals,
            'search' => $search, 'classId' => $classId, 'onlyOwing' => $onlyOwing,
            'flash' => $this->getFlash(),
        ]);
    }

    // ── STATEMENT OF ACCOUNT ─────────────────────────────────────────
    public function statement(string $studentId): void {
        $this->guard();
        $student = $this->db->fetchOne(
            "SELECT s.*, u.name, c.name AS class_name FROM students s
             JOIN users u ON s.user_id=u.id LEFT JOIN classes c ON s.class_id=c.id
             WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]
        );
        if (!$student) { $this->redirect('/school/finance/accounts'); }

        $from = $_GET['from'] ?? null;
        $to   = $_GET['to'] ?? null;
        $params = [$this->tid, $studentId];
        $where = "l.tenant_id=? AND l.student_id=?";
        if ($from) { $where .= " AND l.entry_date >= ?"; $params[] = $from; }
        if ($to)   { $where .= " AND l.entry_date <= ?"; $params[] = $to; }

        // Anything before the window is folded into a single opening balance line.
        $opening = 0.0;
        if ($from) {
            $opening = (float)($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount),0) b FROM student_ledger WHERE tenant_id=? AND student_id=? AND entry_date < ?",
                [$this->tid, $studentId, $from]
            )['b'] ?? 0);
        }

        $entries = $this->db->fetchAll(
            "SELECT l.* FROM student_ledger l WHERE $where ORDER BY l.entry_date, l.id", $params
        );

        $running = $opening;
        foreach ($entries as $i => $e) {
            $running += (float)$e['amount'];
            $entries[$i]['balance'] = $running;
        }

        $this->view('school/finance/statement_print', [
            'pageTitle' => 'Statement of Account',
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'student' => $student, 'entries' => $entries,
            'opening' => $opening, 'closing' => $running,
            'from' => $from, 'to' => $to,
        ]);
    }

    // ── ARREARS AGING ────────────────────────────────────────────────
    public function arrears(): void {
        $this->guard();
        $classId = $_GET['class_id'] ?? '';
        $params = [$this->tid];
        $classWhere = '';
        if ($classId) { $classWhere = " AND s.class_id=?"; $params[] = $classId; }

        // Age each outstanding charge by how long it has been unpaid, then apply
        // credits oldest-first so a part-payment clears the oldest debt.
        $charges = $this->db->fetchAll(
            "SELECT s.id AS student_id, s.admission_no, u.name, c.name AS class_name,
                    l.entry_date, l.amount,
                    DATEDIFF(CURDATE(), l.entry_date) AS age_days
             FROM student_ledger l
             JOIN students s ON l.student_id=s.id
             JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE l.tenant_id=? AND l.amount>0 AND s.status='active' {$classWhere}
             ORDER BY s.id, l.entry_date", $params
        );
        $credits = $this->db->fetchAll(
            "SELECT l.student_id, COALESCE(-SUM(l.amount),0) AS credit
             FROM student_ledger l WHERE l.tenant_id=? AND l.amount<0 GROUP BY l.student_id", [$this->tid]
        );
        $creditBy = [];
        foreach ($credits as $c) { $creditBy[$c['student_id']] = (float)$c['credit']; }

        $students = [];
        foreach ($charges as $ch) {
            $sid = $ch['student_id'];
            if (!isset($students[$sid])) {
                $students[$sid] = [
                    'student_id' => $sid, 'name' => $ch['name'], 'admission_no' => $ch['admission_no'],
                    'class_name' => $ch['class_name'], 'current' => 0.0, 'd30' => 0.0, 'd60' => 0.0, 'd90' => 0.0, 'total' => 0.0,
                ];
            }
            $remaining = (float)$ch['amount'];
            $credit = $creditBy[$sid] ?? 0.0;
            if ($credit > 0) {
                $applied = min($credit, $remaining);
                $remaining -= $applied;
                $creditBy[$sid] = $credit - $applied;
            }
            if ($remaining <= 0.009) { continue; }

            $age = (int)$ch['age_days'];
            $bucket = $age <= 30 ? 'current' : ($age <= 60 ? 'd30' : ($age <= 90 ? 'd60' : 'd90'));
            $students[$sid][$bucket] += $remaining;
            $students[$sid]['total']  += $remaining;
        }
        $rows = array_values(array_filter($students, fn($s) => $s['total'] > 0.009));
        usort($rows, fn($a, $b) => $b['total'] <=> $a['total']);

        $totals = [
            'current' => array_sum(array_column($rows, 'current')),
            'd30'     => array_sum(array_column($rows, 'd30')),
            'd60'     => array_sum(array_column($rows, 'd60')),
            'd90'     => array_sum(array_column($rows, 'd90')),
            'total'   => array_sum(array_column($rows, 'total')),
        ];

        $this->view('school/highschool/finance/arrears', [
            'pageTitle' => 'Arrears & Aging', 'panelType' => 'school',
            'rows' => $rows, 'totals' => $totals, 'classId' => $classId,
            'classes' => $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    // ── SCHOLARSHIPS & DISCOUNTS ─────────────────────────────────────
    public function scholarships(): void {
        $this->guard();
        $rows = $this->db->fetchAll(
            "SELECT sc.*, u.name AS student_name, s.admission_no, ay.name AS year_name
             FROM scholarships sc
             JOIN students s ON sc.student_id=s.id JOIN users u ON s.user_id=u.id
             LEFT JOIN academic_years ay ON sc.academic_year_id=ay.id
             WHERE sc.tenant_id=? ORDER BY sc.status, u.name", [$this->tid]
        );
        $this->view('school/highschool/finance/scholarships', [
            'pageTitle' => 'Scholarships & Discounts', 'panelType' => 'school',
            'rows' => $rows,
            'students' => $this->db->fetchAll("SELECT s.id, u.name, s.admission_no FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid]),
            'years' => $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]),
            'flash' => $this->getFlash(),
        ]);
    }

    public function storeScholarship(): void {
        $this->guard();
        $errors = $this->validate($_POST, [
            'student_id'  => 'required',
            'name'        => 'required|max:120',
            'award_type'  => 'required',
            'award_value' => 'required|numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/scholarships'); }

        $this->db->insert(
            "INSERT INTO scholarships (tenant_id,student_id,name,award_type,award_value,academic_year_id,notes,created_by)
             VALUES (?,?,?,?,?,?,?,?)",
            [
                $this->tid, $_POST['student_id'], $_POST['name'], $_POST['award_type'],
                (float)$_POST['award_value'], $_POST['academic_year_id'] ?: null,
                $_POST['notes'] ?: null, $_SESSION['user_id'],
            ]
        );
        $this->flash('success', 'Scholarship recorded. Apply it to a student account to credit their ledger.');
        $this->redirect('/school/finance/scholarships');
    }

    /** Credit the student's ledger by this award — percentage is taken of what they currently owe. */
    public function applyScholarship(string $id): void {
        $this->guard();
        $sc = $this->db->fetchOne("SELECT * FROM scholarships WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$sc) { $this->redirect('/school/finance/scholarships'); }

        $balance = (float)($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount),0) b FROM student_ledger WHERE tenant_id=? AND student_id=?",
            [$this->tid, $sc['student_id']]
        )['b'] ?? 0);

        $credit = $sc['award_type'] === 'percentage'
            ? round(max(0, $balance) * ((float)$sc['award_value'] / 100), 2)
            : (float)$sc['award_value'];

        if ($credit <= 0) {
            $this->flash('warning', 'Nothing to credit — this student has no outstanding balance.');
            $this->redirect('/school/finance/scholarships');
        }

        self::post($this->db, $this->tid, (int)$sc['student_id'], 'scholarship',
            $sc['name'] . ($sc['award_type'] === 'percentage' ? ' (' . rtrim(rtrim(number_format((float)$sc['award_value'], 2, '.', ''), '0'), '.') . '%)' : ''),
            -$credit, ['academic_year_id' => $sc['academic_year_id']]
        );

        $this->flash('success', 'Scholarship applied to the student account.');
        $this->redirect('/school/finance/scholarships');
    }

    public function endScholarship(string $id): void {
        $this->guard();
        $this->db->execute("UPDATE scholarships SET status='ended' WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success', 'Scholarship ended.');
        $this->redirect('/school/finance/scholarships');
    }

    // ── MANUAL LEDGER ADJUSTMENT ─────────────────────────────────────
    public function adjust(string $studentId): void {
        $this->guard();
        $student = $this->db->fetchOne("SELECT id FROM students WHERE id=? AND tenant_id=?", [$studentId, $this->tid]);
        if (!$student) { $this->redirect('/school/finance/accounts'); }

        $errors = $this->validate($_POST, [
            'entry_type'  => 'required',
            'description' => 'required|max:255',
            'amount'      => 'required|numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/accounts'); }

        $amount = abs((float)$_POST['amount']);
        // Credits reduce the balance; only a charge or a refund increases it.
        if (!in_array($_POST['entry_type'], ['charge','refund'], true)) { $amount = -$amount; }

        self::post($this->db, $this->tid, (int)$studentId, $_POST['entry_type'], $_POST['description'], $amount,
            ['date' => $_POST['entry_date'] ?: date('Y-m-d'), 'reference' => $_POST['reference'] ?: null]);

        $this->flash('success', 'Ledger entry recorded.');
        $this->redirect('/school/finance/accounts');
    }
}
