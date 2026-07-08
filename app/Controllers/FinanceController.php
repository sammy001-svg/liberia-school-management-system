<?php
require_once ROOT_DIR . '/core/Controller.php';

class FinanceController extends Controller {
    private int $tid;
    public function __construct() { parent::__construct(); $this->tid = $this->tenantId() ?? 0; }

    public function index(): void {
        $this->requireAuth(['School Admin','Accountant','Super Admin']);
        $stats = [
            'total_due'  => $this->db->fetchOne("SELECT COALESCE(SUM(amount_due),0) AS c FROM invoices WHERE tenant_id=?",[$this->tid])['c']??0,
            'total_paid' => $this->db->fetchOne("SELECT COALESCE(SUM(amount_paid),0) AS c FROM invoices WHERE tenant_id=?",[$this->tid])['c']??0,
            'unpaid'     => $this->db->fetchOne("SELECT COUNT(*) AS c FROM invoices WHERE tenant_id=? AND status IN('unpaid','partial','overdue')",[$this->tid])['c']??0,
            'paid'       => $this->db->fetchOne("SELECT COUNT(*) AS c FROM invoices WHERE tenant_id=? AND status='paid'",[$this->tid])['c']??0,
        ];
        $recentPayments = $this->db->fetchAll("SELECT p.*, i.invoice_no, u.name AS student_name FROM payments p JOIN invoices i ON p.invoice_id=i.id JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id WHERE p.tenant_id=? ORDER BY p.paid_at DESC LIMIT 10",[$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/finance/index', ['pageTitle'=>'Finance','panelType'=>'school','tenant'=>$tenant,'stats'=>$stats,'recentPayments'=>$recentPayments,'flash'=>$this->getFlash()]);
    }

    public function invoices(): void {
        $this->requireAuth(['School Admin','Accountant','Super Admin']);
        $status = $_GET['status'] ?? '';
        $params = [$this->tid];
        $where  = "i.tenant_id=?";
        if ($status) { $where .= " AND i.status=?"; $params[] = $status; }
        $invoices = $this->db->fetchAll("SELECT i.*, u.name AS student_name, c.name AS class_name FROM invoices i JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id LEFT JOIN classes c ON s.class_id=c.id WHERE $where ORDER BY i.created_at DESC", $params);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/finance/invoices', ['pageTitle'=>'Invoices','panelType'=>'school','tenant'=>$tenant,'invoices'=>$invoices,'status'=>$status,'flash'=>$this->getFlash()]);
    }

    public function createInvoice(): void {
        $this->requireAuth(['School Admin','Accountant','Super Admin']);
        $students   = $this->db->fetchAll("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id=u.id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name",[$this->tid]);
        $feeStructs = $this->db->fetchAll("SELECT id,name,amount FROM fee_structures WHERE tenant_id=?",[$this->tid]);
        $this->view('school/highschool/finance/create_invoice', ['pageTitle'=>'Create Invoice','panelType'=>'school','students'=>$students,'feeStructs'=>$feeStructs,'flash'=>$this->getFlash()]);
    }

    public function storeInvoice(): void {
        $this->requireAuth(['School Admin','Accountant','Super Admin']);
        $invoiceNo = 'INV-'.date('Ymd').'-'.rand(1000,9999);
        $this->db->insert("INSERT INTO invoices (tenant_id,student_id,fee_structure_id,invoice_no,amount_due,discount,due_date,notes,status) VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid,$_POST['student_id'],$_POST['fee_structure_id']??null,$invoiceNo,$_POST['amount_due'],$_POST['discount']??0,$_POST['due_date']??null,$_POST['notes']??'','unpaid']);
        $this->flash('success','Invoice '.$invoiceNo.' created.'); $this->redirect('/school/finance/invoices');
    }

    public function payments(): void {
        $this->requireAuth(['School Admin','Accountant','Super Admin']);
        $payments = $this->db->fetchAll("SELECT p.*, i.invoice_no, u.name AS student_name FROM payments p JOIN invoices i ON p.invoice_id=i.id JOIN students s ON i.student_id=s.id JOIN users u ON s.user_id=u.id WHERE p.tenant_id=? ORDER BY p.paid_at DESC",[$this->tid]);
        $tenant = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]);
        $this->view('school/highschool/finance/payments', ['pageTitle'=>'Payments','panelType'=>'school','tenant'=>$tenant,'payments'=>$payments,'flash'=>$this->getFlash()]);
    }

    public function storePayment(): void {
        $this->requireAuth(['School Admin','Accountant','Super Admin']);
        $invoiceId = $_POST['invoice_id'];
        $amount    = (float)$_POST['amount'];
        $this->db->insert("INSERT INTO payments (tenant_id,invoice_id,amount,method,reference,received_by,notes) VALUES (?,?,?,?,?,?,?)",
            [$this->tid,$invoiceId,$amount,$_POST['method']??'cash',$_POST['reference']??'',$_SESSION['user_id'],$_POST['notes']??'']);
        $paid = $this->db->fetchOne("SELECT COALESCE(SUM(amount),0) AS t FROM payments WHERE invoice_id=?",[$invoiceId])['t']??0;
        $due  = $this->db->fetchOne("SELECT amount_due FROM invoices WHERE id=?",[$invoiceId])['amount_due']??0;
        $newStatus = $paid >= $due ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');
        $this->db->execute("UPDATE invoices SET amount_paid=?, status=? WHERE id=?",[$paid,$newStatus,$invoiceId]);
        $this->flash('success','Payment recorded.'); $this->redirect('/school/finance/invoices');
    }
}
