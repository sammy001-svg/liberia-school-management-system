<?php
require_once ROOT_DIR . '/core/Controller.php';

class FinanceController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requirePermission(['finance.manage']);
        $stats = [
            'total_due'  => $this->db->fetchOne("SELECT COALESCE(SUM(amount_due),0) AS c FROM invoices WHERE tenant_id=?",[$this->tid])['c']??0,
            'total_paid' => $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid),0) AS c FROM invoices WHERE tenant_id=?",[$this->tid])['c']??0,
            'unpaid'     => $this->db->fetchOne("SELECT COUNT(*) AS c FROM invoices WHERE tenant_id=? AND status IN('unpaid','partial','overdue')",[$this->tid])['c']??0,
            'paid'       => $this->db->fetchOne("SELECT COUNT(*) AS c FROM invoices WHERE tenant_id=? AND status='paid'",[$this->tid])['c']??0,
            'total_expenses' => $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) AS c FROM expenses WHERE tenant_id=?",[$this->tid])['c']??0,
        ];
        $recentPayments = $this->db->fetchAll("SELECT p.*, i.invoice_no, u.name AS student_name FROM payments p JOIN invoices i ON p.invoice_id=i.id JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id WHERE p.tenant_id=? ORDER BY p.paid_at DESC LIMIT 10",[$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/finance/index', ['pageTitle'=>'Finance','panelType'=>'school','tenant'=>$tenant,'stats'=>$stats,'recentPayments'=>$recentPayments,'flash'=>$this->getFlash()]);
    }

    public function invoices(): void {
        $this->requirePermission(['finance.manage']);
        $status = $_GET['status'] ?? '';
        $studentId = $_GET['student_id'] ?? '';
        $params = [$this->tid];
        $where  = "i.tenant_id=?";
        if ($status) { $where .= " AND i.status=?"; $params[] = $status; }
        if ($studentId) { $where .= " AND i.student_id=?"; $params[] = $studentId; }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM invoices i WHERE $where", $params)['c'];
        $p = $this->paginate($totalCount);

        $invoices = $this->db->fetchAll(
            "SELECT i.*, u.name AS student_name, c.name AS class_name FROM invoices i JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id LEFT JOIN classes c ON s.class_id=c.id WHERE $where ORDER BY i.created_at DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
            $params
        );
        $tenant     = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $students   = $this->db->fetchAll("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name",[$this->tid]);
        $feeStructs = $this->db->fetchAll("SELECT id,name,amount FROM fee_structures WHERE tenant_id=?",[$this->tid]);
        $filteredStudent = $studentId ? $this->db->fetchOne("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]) : null;
        $this->view('school/highschool/finance/invoices', [
            'pageTitle'=>'Invoices','panelType'=>'school','tenant'=>$tenant,'invoices'=>$invoices,'status'=>$status,'studentId'=>$studentId,'filteredStudent'=>$filteredStudent,'students'=>$students,'feeStructs'=>$feeStructs,
            'page'=>$p['page'],'totalPages'=>$p['totalPages'],'total'=>$p['total'],'perPage'=>$p['perPage'],
            'flash'=>$this->getFlash(),
        ]);
    }

    public function createInvoice(): void {
        $this->requirePermission(['finance.manage']);
        $this->redirect('/school/finance/invoices');
    }

    public function storeInvoice(): void {
        $this->requirePermission(['finance.manage']);
        $errors = $this->validate($_POST, [
            'student_id' => 'required',
            'amount_due' => 'required|numeric',
            'discount'   => 'numeric',
            'due_date'   => 'date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/invoices'); }
        $invoiceNo = 'INV-'.date('Ymd').'-'.rand(1000,9999);
        $this->db->insert("INSERT INTO invoices (tenant_id,student_id,fee_structure_id,invoice_no,amount_due,discount,due_date,notes,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid,$_POST['student_id'],$_POST['fee_structure_id']?:null,$invoiceNo,$_POST['amount_due'],$_POST['discount']??0,$_POST['due_date']??null,$_POST['notes']??'','unpaid']);
        $this->flash('success','Invoice '.$invoiceNo.' created.'); $this->redirect('/school/finance/invoices');
    }

    public function feeStructures(): void {
        $this->requirePermission(['finance.manage']);
        $fees = $this->db->fetchAll(
            "SELECT f.*, c.name AS class_name,
                    (SELECT COUNT(*) FROM students s WHERE s.tenant_id=f.tenant_id AND s.status='active' AND (f.class_id IS NULL OR s.class_id=f.class_id)) AS student_count
             FROM fee_structures f LEFT JOIN classes c ON f.class_id=c.id WHERE f.tenant_id=? ORDER BY f.name",
            [$this->tid]
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $academicYears = $this->db->fetchAll("SELECT id,name FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $stats = [
            'total' => count($fees),
            'classSpecific' => count(array_filter($fees, fn($f) => !empty($f['class_id']))),
            'schoolWide' => count(array_filter($fees, fn($f) => empty($f['class_id']))),
        ];
        $this->view('school/highschool/finance/fee_structures', ['pageTitle'=>'Fee Structures','panelType'=>'school','tenant'=>$tenant,'fees'=>$fees,'classes'=>$classes,'academicYears'=>$academicYears,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function storeFeeStructure(): void {
        $this->requirePermission(['finance.manage']);
        $errors = $this->validate($_POST, [
            'name'   => 'required|max:150',
            'amount' => 'required|numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/fees'); }
        $this->db->insert(
            "INSERT INTO fee_structures (tenant_id,name,amount,frequency,class_id,academic_year_id,description) VALUES (?,?,?,?,?,?,?)",
            [$this->tid,$_POST['name'],$_POST['amount'],$_POST['frequency']??'termly',$_POST['class_id']?:null,$_POST['academic_year_id']?:null,$_POST['description']??'']
        );
        $this->flash('success','Fee structure created.'); $this->redirect('/school/finance/fees');
    }

    // Generates one invoice per applicable student (the fee's class, or every active student
    // if the fee is school-wide) for a given billing period. Idempotent: a period tag is
    // embedded in the invoice notes and checked before inserting, so re-running for the same
    // period only bills students who weren't already invoiced — safe to click more than once.
    public function generateFeeInvoices(string $id): void {
        $this->requirePermission(['finance.manage']);
        $fee = $this->db->fetchOne("SELECT * FROM fee_structures WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$fee) { $this->redirect('/school/finance/fees'); }
        $errors = $this->validate($_POST, ['period' => 'required|max:100']);
        if ($errors) { $this->failValidation($errors, '/school/finance/fees'); }

        $period = trim($_POST['period']);
        $tag = '[FEE:'.$fee['id'].':'.$period.']';
        $dueDate = $_POST['due_date'] ?: date('Y-m-d', strtotime('+14 days'));

        $params = [$this->tid];
        $where = "tenant_id=? AND status='active'";
        if ($fee['class_id']) { $where .= " AND class_id=?"; $params[] = $fee['class_id']; }
        $students = $this->db->fetchAll("SELECT id FROM students WHERE $where", $params);

        $alreadyBilled = array_column(
            $this->db->fetchAll("SELECT student_id FROM invoices WHERE tenant_id=? AND notes LIKE ?", [$this->tid, '%'.$tag]),
            'student_id'
        );
        $alreadyBilled = array_flip($alreadyBilled);

        set_time_limit(120);
        $created = 0; $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($students as $s) {
                if (isset($alreadyBilled[$s['id']])) { $skipped++; continue; }
                $invoiceNo = 'INV-'.date('Ymd').'-'.bin2hex(random_bytes(4));
                $this->db->insert(
                    "INSERT INTO invoices (tenant_id,student_id,fee_structure_id,invoice_no,amount_due,due_date,notes,status) VALUES (?,?,?,?,?,?,?,?)",
                    [$this->tid, $s['id'], $fee['id'], $invoiceNo, $fee['amount'], $dueDate, "{$fee['name']} - {$period} {$tag}", 'unpaid']
                );
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("generateFeeInvoices failed: " . $e->getMessage());
            $this->flash('danger', 'Could not generate invoices — no changes were made. Please try again.');
            $this->redirect('/school/finance/fees');
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Generated {$created} invoice(s) for {$period}." . ($skipped > 0 ? " {$skipped} student(s) were already billed for this period." : ''));
        $this->redirect('/school/finance/fees');
    }

    public function payments(): void {
        $this->requirePermission(['finance.manage']);
        $payments = $this->db->fetchAll("SELECT p.*, i.invoice_no, u.name AS student_name FROM payments p JOIN invoices i ON p.invoice_id=i.id JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id WHERE p.tenant_id=? ORDER BY p.paid_at DESC",[$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) total, COALESCE(SUM(amount),0) totalAmount,
                    SUM(CASE WHEN DATE(paid_at)=CURDATE() THEN 1 ELSE 0 END) today
             FROM payments WHERE tenant_id=?", [$this->tid]
        );
        $this->view('school/highschool/finance/payments', ['pageTitle'=>'Payments','panelType'=>'school','tenant'=>$tenant,'payments'=>$payments,'stats'=>$stats,'flash'=>$this->getFlash()]);
    }

    public function storePayment(): void {
        $this->requirePermission(['finance.manage']);
        $errors = $this->validate($_POST, [
            'invoice_id' => 'required',
            'amount'     => 'required|numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/invoices'); }
        $invoiceId = $_POST['invoice_id'];
        $invoice = $this->db->fetchOne("SELECT amount_due FROM invoices WHERE id=? AND tenant_id=?", [$invoiceId, $this->tid]);
        if (!$invoice) { $this->redirect('/school/finance/invoices'); }
        $amount    = (float)$_POST['amount'];
        $this->db->insert("INSERT INTO payments (tenant_id,invoice_id,amount,method,reference,received_by,notes) VALUES (?,?,?,?,?,?,?)",
            [$this->tid,$invoiceId,$amount,$_POST['method']??'cash',$_POST['reference']??'',$_SESSION['user_id'],$_POST['notes']??'']);
        $paid = $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE invoice_id=?",[$invoiceId])['t']??0;
        $newStatus = $paid >= $invoice['amount_due'] ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $this->db->execute("UPDATE invoices SET amount_paid=?, status=? WHERE id=? AND tenant_id=?",[$paid,$newStatus,$invoiceId,$this->tid]);
        $this->flash('success','Payment recorded.'); $this->redirect('/school/finance/invoices');
    }

    // --- EXPENSES ---
    public function expenses(): void {
        $this->requirePermission(['finance.manage']);
        $category = $_GET['category'] ?? '';
        $params = [$this->tid];
        $where = "e.tenant_id=?";
        if ($category) { $where .= " AND e.category=?"; $params[] = $category; }

        $totalCount = $this->db->fetchOne("SELECT COUNT(*) c FROM expenses e WHERE $where", $params)['c'];
        $p = $this->paginate($totalCount);
        $expenses = $this->db->fetchAll(
            "SELECT e.*, u.name AS recorded_by_name FROM expenses e LEFT JOIN users u ON e.recorded_by=u.id WHERE $where ORDER BY e.expense_date DESC, e.id DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}",
            $params
        );
        $categories = $this->db->fetchAll("SELECT DISTINCT category FROM expenses WHERE tenant_id=? ORDER BY category", [$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $stats = [
            'total' => $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) c FROM expenses WHERE tenant_id=?", [$this->tid])['c'] ?? 0,
            'thisMonth' => $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) c FROM expenses WHERE tenant_id=? AND MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())", [$this->tid])['c'] ?? 0,
            'count' => $totalCount,
        ];
        $this->view('school/highschool/finance/expenses', [
            'pageTitle'=>'Expenses','panelType'=>'school','tenant'=>$tenant,'expenses'=>$expenses,'categories'=>$categories,'category'=>$category,'stats'=>$stats,
            'page'=>$p['page'],'totalPages'=>$p['totalPages'],'total'=>$p['total'],'perPage'=>$p['perPage'],
            'flash'=>$this->getFlash(),
        ]);
    }

    public function storeExpense(): void {
        $this->requirePermission(['finance.manage']);
        $errors = $this->validate($_POST, [
            'category'     => 'required|max:80',
            'amount'       => 'required|numeric',
            'expense_date' => 'required|date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/finance/expenses'); }
        $this->db->insert(
            "INSERT INTO expenses (tenant_id,category,description,amount,expense_date,payee,method,reference,recorded_by) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid,$_POST['category'],$_POST['description']??'',$_POST['amount'],$_POST['expense_date'],$_POST['payee']??'',$_POST['method']??'cash',$_POST['reference']??'',$_SESSION['user_id']]
        );
        $this->flash('success','Expense recorded.'); $this->redirect('/school/finance/expenses');
    }

    public function deleteExpense(string $id): void {
        $this->requirePermission(['finance.manage']);
        $this->db->execute("DELETE FROM expenses WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success','Expense removed.'); $this->redirect('/school/finance/expenses');
    }

    // --- COLLECTION ---
    public function collection(): void {
        $this->requirePermission(['finance.manage']);
        $classId = $_GET['class_id'] ?? '';
        $params = [$this->tid];
        $where = "i.tenant_id=? AND i.status NOT IN ('paid','waived')";
        if ($classId) { $where .= " AND s.class_id=?"; $params[] = $classId; }

        $balances = $this->db->fetchAll(
            "SELECT s.id AS student_id, u.name, u.phone, s.guardian_phone, s.guardian_name, c.name AS class_name,
                    SUM(i.amount_due - i.amount_paid) AS balance,
                    COUNT(i.id) AS invoice_count,
                    MIN(i.due_date) AS oldest_due_date,
                    MAX(CASE WHEN i.due_date IS NOT NULL THEN DATEDIFF(CURDATE(), i.due_date) ELSE NULL END) AS days_overdue
             FROM invoices i
             JOIN students s ON i.student_id=s.id
             JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE $where
             GROUP BY s.id
             HAVING balance > 0
             ORDER BY balance DESC",
            $params
        );
        $classes = $this->db->fetchAll("SELECT id,name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $stats = [
            'totalOutstanding' => array_sum(array_column($balances, 'balance')),
            'studentsOwing'    => count($balances),
            'overdue'          => count(array_filter($balances, fn($b) => $b['days_overdue'] !== null && $b['days_overdue'] > 0)),
        ];
        $this->view('school/highschool/finance/collection', [
            'pageTitle'=>'Manage Collection','panelType'=>'school','tenant'=>$tenant,'balances'=>$balances,'classes'=>$classes,'classId'=>$classId,'stats'=>$stats,
            'flash'=>$this->getFlash(),
        ]);
    }

    // --- BUS BILLING (bills students on active bus routes; creates normal invoices) ---
    public function busBilling(): void {
        $this->requirePermission(['finance.manage']);
        $routes = $this->db->fetchAll(
            "SELECT r.*, b.bus_number,
                    (SELECT COUNT(*) FROM bus_students bs WHERE bs.route_id=r.id AND bs.status='active') AS student_count
             FROM bus_routes r LEFT JOIN buses b ON r.bus_id=b.id
             WHERE r.tenant_id=? AND r.status='active' ORDER BY r.name", [$this->tid]
        );
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $stats = [
            'totalRoutes'   => count($routes),
            'totalStudents' => array_sum(array_column($routes, 'student_count')),
            'monthlyPotential' => array_sum(array_map(fn($r) => $r['monthly_fee'] * $r['student_count'], $routes)),
        ];
        $this->view('school/highschool/finance/bus_billing', [
            'pageTitle'=>'Bus Billing','panelType'=>'school','tenant'=>$tenant,'routes'=>$routes,'stats'=>$stats,'flash'=>$this->getFlash(),
        ]);
    }

    public function generateBusInvoices(): void {
        $this->requirePermission(['finance.manage']);
        $errors = $this->validate($_POST, ['route_id' => 'required', 'month' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/finance/bus-billing'); }
        $route = $this->db->fetchOne("SELECT * FROM bus_routes WHERE id=? AND tenant_id=?", [$_POST['route_id'], $this->tid]);
        if (!$route) { $this->redirect('/school/finance/bus-billing'); }

        $month = $_POST['month']; // YYYY-MM
        $monthLabel = date('F Y', strtotime($month.'-01'));
        $tag = '[BUS-ROUTE:'.$route['id'].':'.$month.']';
        $dueDate = $_POST['due_date'] ?: date('Y-m-d', strtotime($month.'-01 +14 days'));

        $students = $this->db->fetchAll("SELECT student_id FROM bus_students WHERE route_id=? AND status='active'", [$route['id']]);
        $alreadyBilled = array_column(
            $this->db->fetchAll("SELECT student_id FROM invoices WHERE tenant_id=? AND notes LIKE ?", [$this->tid, '%'.$tag]),
            'student_id'
        );
        $alreadyBilled = array_flip($alreadyBilled);

        set_time_limit(120);
        $created = 0; $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($students as $s) {
                if (isset($alreadyBilled[$s['student_id']])) { $skipped++; continue; }
                $invoiceNo = 'BUS-'.date('Ymd').'-'.bin2hex(random_bytes(4));
                $this->db->insert(
                    "INSERT INTO invoices (tenant_id,student_id,invoice_no,amount_due,due_date,notes,status) VALUES (?,?,?,?,?,?,?)",
                    [$this->tid, $s['student_id'], $invoiceNo, $route['monthly_fee'], $dueDate, "Bus Fee - {$route['name']} - {$monthLabel} {$tag}", 'unpaid']
                );
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log("generateBusInvoices failed: " . $e->getMessage());
            $this->flash('danger', 'Could not generate invoices — no changes were made. Please try again.');
            $this->redirect('/school/finance/bus-billing');
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Generated {$created} invoice(s) for {$monthLabel}." . ($skipped > 0 ? " {$skipped} student(s) were already billed for this month." : ''));
        $this->redirect('/school/finance/bus-billing');
    }

    public function printInvoice(string $id): void {
        $this->requirePermission(['finance.manage']);
        $invoice = $this->db->fetchOne(
            "SELECT i.*, u.name AS student_name, s.admission_no, c.name AS class_name
             FROM invoices i JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE i.id=? AND i.tenant_id=?", [$id, $this->tid]
        );
        if (!$invoice) { $this->redirect('/school/finance/invoices'); }
        $payments = $this->db->fetchAll("SELECT * FROM payments WHERE invoice_id=? AND tenant_id=? ORDER BY paid_at", [$id, $this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/finance/invoice_print', ['pageTitle'=>'Invoice','tenant'=>$tenant,'invoice'=>$invoice,'payments'=>$payments]);
    }

    // --- FINANCIAL REPORTS ---

    // Resolves the ?range= filter into [from, to, human label, resolved range name].
    // Falls back to "This Month" when a term/year filter is requested but none is
    // marked current, so the page never renders with an impossible empty range.
    private function resolveReportRange(): array {
        $range = $_GET['range'] ?? 'month';
        $now = date('Y-m-d');

        if ($range === 'term') {
            $term = $this->db->fetchOne("SELECT * FROM terms WHERE tenant_id=? AND is_current=1 LIMIT 1", [$this->tid]);
            if ($term) { return [$term['start_date'], $term['end_date'], 'Period: '.$term['name'], 'term']; }
            $range = 'month';
        }
        if ($range === 'year') {
            $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE tenant_id=? AND is_current=1 LIMIT 1", [$this->tid]);
            if ($year) { return [$year['start_date'], $year['end_date'], 'Academic Year: '.$year['name'], 'year']; }
            $range = 'month';
        }
        if ($range === 'all') {
            return ['2000-01-01', $now, 'All Time', 'all'];
        }
        if ($range === 'custom' && !empty($_GET['from']) && !empty($_GET['to'])) {
            $from = $_GET['from']; $to = $_GET['to'];
            return [$from, $to, date('M d, Y', strtotime($from)).' – '.date('M d, Y', strtotime($to)), 'custom'];
        }
        return [date('Y-m-01'), $now, 'This Month ('.date('F Y').')', 'month'];
    }

    private function computeReportData(string $from, string $to): array {
        $toEnd = $to.' 23:59:59';
        $totalBilled = $this->db->fetchOne("SELECT COALESCE(SUM(amount_due),0) c FROM invoices WHERE tenant_id=? AND created_at BETWEEN ? AND ?", [$this->tid, $from, $toEnd])['c'];
        $totalCollected = $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) c FROM payments WHERE tenant_id=? AND paid_at BETWEEN ? AND ?", [$this->tid, $from, $toEnd])['c'];
        $totalExpenses = $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) c FROM expenses WHERE tenant_id=? AND expense_date BETWEEN ? AND ?", [$this->tid, $from, $to])['c'];
        $netIncome = $totalCollected - $totalExpenses;
        $collectionRate = $totalBilled > 0 ? round($totalCollected / $totalBilled * 100, 1) : 0;

        $revenueByCategory = $this->db->fetchAll(
            "SELECT CASE WHEN f.name IS NOT NULL THEN f.name WHEN i.notes LIKE 'Bus Fee%' THEN 'Bus Fees' ELSE 'Other' END AS category, SUM(i.amount_due) total
             FROM invoices i LEFT JOIN fee_structures f ON i.fee_structure_id=f.id
             WHERE i.tenant_id=? AND i.created_at BETWEEN ? AND ?
             GROUP BY category ORDER BY total DESC", [$this->tid, $from, $toEnd]
        );
        $expensesByCategory = $this->db->fetchAll(
            "SELECT category, SUM(amount) total FROM expenses WHERE tenant_id=? AND expense_date BETWEEN ? AND ? GROUP BY category ORDER BY total DESC",
            [$this->tid, $from, $to]
        );
        $paymentsByMethod = $this->db->fetchAll(
            "SELECT method, SUM(amount) total, COUNT(*) cnt FROM payments WHERE tenant_id=? AND paid_at BETWEEN ? AND ? GROUP BY method ORDER BY total DESC",
            [$this->tid, $from, $toEnd]
        );

        // Trend is always the trailing 6 calendar months regardless of the filter above,
        // to give a stable at-a-glance chart no matter which report period is selected.
        $collectedByMonth = array_column($this->db->fetchAll(
            "SELECT DATE_FORMAT(paid_at,'%Y-%m') ym, SUM(amount) total FROM payments WHERE tenant_id=? AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY ym",
            [$this->tid]
        ), 'total', 'ym');
        $expensesByMonth = array_column($this->db->fetchAll(
            "SELECT DATE_FORMAT(expense_date,'%Y-%m') ym, SUM(amount) total FROM expenses WHERE tenant_id=? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY ym",
            [$this->tid]
        ), 'total', 'ym');
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-{$i} months"));
            $monthlyTrend[] = [
                'label'     => date('M Y', strtotime($ym.'-01')),
                'collected' => (float)($collectedByMonth[$ym] ?? 0),
                'expenses'  => (float)($expensesByMonth[$ym] ?? 0),
            ];
        }

        return compact('totalBilled','totalCollected','totalExpenses','netIncome','collectionRate','revenueByCategory','expensesByCategory','paymentsByMethod','monthlyTrend');
    }

    public function reports(): void {
        $this->requirePermission(['finance.manage']);
        [$from, $to, $periodLabel, $range] = $this->resolveReportRange();
        $data = $this->computeReportData($from, $to);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/finance/reports', array_merge($data, [
            'pageTitle'=>'Financial Reports','panelType'=>'school','tenant'=>$tenant,
            'periodLabel'=>$periodLabel,'range'=>$range,'from'=>$from,'to'=>$to,
            'flash'=>$this->getFlash(),
        ]));
    }

    public function printReport(): void {
        $this->requirePermission(['finance.manage']);
        [$from, $to, $periodLabel] = $this->resolveReportRange();
        $data = $this->computeReportData($from, $to);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/finance/report_print', array_merge($data, [
            'pageTitle'=>'Income Statement','tenant'=>$tenant,'periodLabel'=>$periodLabel,
        ]));
    }
}
