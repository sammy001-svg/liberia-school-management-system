<?php
require_once ROOT_DIR . '/core/Controller.php';

class FinanceController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
        $this->redirect('/school/finance/invoices');
    }

    public function storeInvoice(): void {
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
        $fees = $this->db->fetchAll(
            "SELECT f.*, c.name AS class_name FROM fee_structures f LEFT JOIN classes c ON f.class_id=c.id WHERE f.tenant_id=? ORDER BY f.name",
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
        $this->requireAuth(['School Admin','Accountant']);
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

    public function payments(): void {
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
        $this->db->execute("DELETE FROM expenses WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        $this->flash('success','Expense removed.'); $this->redirect('/school/finance/expenses');
    }

    // --- COLLECTION ---
    public function collection(): void {
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
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
        $this->requireAuth(['School Admin','Accountant']);
        $errors = $this->validate($_POST, ['route_id' => 'required', 'month' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/finance/bus-billing'); }
        $route = $this->db->fetchOne("SELECT * FROM bus_routes WHERE id=? AND tenant_id=?", [$_POST['route_id'], $this->tid]);
        if (!$route) { $this->redirect('/school/finance/bus-billing'); }

        $month = $_POST['month']; // YYYY-MM
        $monthLabel = date('F Y', strtotime($month.'-01'));
        $tag = '[BUS-ROUTE:'.$route['id'].':'.$month.']';
        $dueDate = $_POST['due_date'] ?: date('Y-m-d', strtotime($month.'-01 +14 days'));

        $students = $this->db->fetchAll("SELECT student_id FROM bus_students WHERE route_id=? AND status='active'", [$route['id']]);
        $created = 0; $skipped = 0;
        foreach ($students as $s) {
            $exists = $this->db->fetchOne("SELECT id FROM invoices WHERE tenant_id=? AND student_id=? AND notes LIKE ?", [$this->tid, $s['student_id'], '%'.$tag]);
            if ($exists) { $skipped++; continue; }
            $invoiceNo = 'BUS-'.date('Ymd').'-'.rand(1000,9999);
            $this->db->insert(
                "INSERT INTO invoices (tenant_id,student_id,invoice_no,amount_due,due_date,notes,status) VALUES (?,?,?,?,?,?,?)",
                [$this->tid, $s['student_id'], $invoiceNo, $route['monthly_fee'], $dueDate, "Bus Fee - {$route['name']} - {$monthLabel} {$tag}", 'unpaid']
            );
            $created++;
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Generated {$created} invoice(s) for {$monthLabel}." . ($skipped > 0 ? " {$skipped} student(s) were already billed for this month." : ''));
        $this->redirect('/school/finance/bus-billing');
    }

    public function printInvoice(string $id): void {
        $this->requireAuth(['School Admin','Accountant']);
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
}
