<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Fees Payment: one student's bills for a year, taking money against a specific bill,
 * cancelling a payment, the approval queue, and printable receipts.
 */
class FeePaymentController extends FinanceBaseController {

    private function student(int $id): ?array {
        return $this->db->fetchOne(
            "SELECT s.*, u.name, u.phone, u.avatar, c.name AS class_name FROM students s JOIN users u ON u.id=s.user_id
             LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.tenant_id=?", [$id, $this->tid]) ?: null;
    }

    /** A student's bills for one year (enrollment bills plus any other invoices dated in that year). */
    private function bills(int $studentId, array $year): array {
        return $this->db->fetchAll(
            "SELECT i.*, fb.start_date AS bill_start, fb.sort_order,
                    COALESCE(i.description, fs.name, i.notes, i.invoice_no) AS label,
                    (i.amount_due - i.discount - i.amount_paid) AS balance
             FROM invoices i LEFT JOIN fee_bills fb ON fb.id=i.fee_bill_id LEFT JOIN fee_structures fs ON fs.id=i.fee_structure_id
             WHERE i.tenant_id=? AND i.student_id=?
               AND (i.academic_year_id=? OR (i.academic_year_id IS NULL AND DATE(i.created_at) BETWEEN ? AND ?))
             ORDER BY i.fee_bill_id IS NULL, fb.sort_order, COALESCE(fb.start_date, i.due_date), i.id",
            [$this->tid, $studentId, $year['id'], $year['start_date'], $year['end_date']]);
    }

    public function show(): void {
        $this->guard();
        $year = $this->selectedYear();
        $studentId = (int)($_GET['student'] ?? 0);
        $student = $studentId ? $this->student($studentId) : null;
        $data = ['pageTitle' => 'Fees Payment', 'year' => $year, 'years' => $this->years(), 'student' => $student];
        $data['students'] = $this->db->fetchAll("SELECT s.id, s.admission_no, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.tenant_id=? AND s.status IN ('active','graduated') ORDER BY u.name", [$this->tid]);
        if ($student && $year) {
            $data['enrollment'] = $this->db->fetchOne(
                "SELECT e.*, c.name AS class_name, st.name AS type_name FROM enrollments e LEFT JOIN classes c ON c.id=e.class_id
                 LEFT JOIN student_types st ON st.id=e.student_type_id WHERE e.student_id=? AND e.academic_year_id=?", [$studentId, $year['id']]);
            $data['bills'] = $this->bills($studentId, $year);
            $invoiceIds = array_map('intval', array_column($data['bills'], 'id')) ?: [0];
            $data['payments'] = $this->db->fetchAll(
                "SELECT p.*, COALESCE(i.description, i.notes, i.invoice_no) AS label, u.name AS received_by_name
                 FROM payments p JOIN invoices i ON i.id=p.invoice_id LEFT JOIN users u ON u.id=p.received_by
                 WHERE p.invoice_id IN (" . implode(',', $invoiceIds) . ") ORDER BY COALESCE(p.payment_date, DATE(p.paid_at)) DESC, p.id DESC");
            $data['arrears'] = Finance::priorArrears($this->db, $this->tid, $studentId, (int)$year['id']);
            $totals = [];
            foreach ($data['bills'] as $b) {
                $c = $b['currency'] ?: $this->settings['default_currency'];
                $totals[$c] ??= ['billed' => 0, 'paid' => 0, 'balance' => 0];
                $totals[$c]['billed'] += (float)$b['amount_due'] - (float)$b['discount'];
                $totals[$c]['paid'] += (float)$b['amount_paid'];
                if ($b['status'] !== 'waived') { $totals[$c]['balance'] += max(0, (float)$b['balance']); }
            }
            $data['totals'] = $totals;
        }
        $this->financeView('fees_payment', $data);
    }

    public function store(): void {
        $this->guard();
        $studentId = (int)($_POST['student_id'] ?? 0);
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $back = '/school/finance/fees-payment?student=' . $studentId . '&year=' . $yearId;
        // Arrears collection posts here too and wants to come back to its own page.
        $return = (string)($_POST['back'] ?? '');
        if (str_starts_with($return, '/school/finance/')) { $back = $return; }
        $invoice = $this->db->fetchOne("SELECT * FROM invoices WHERE id=? AND tenant_id=? AND student_id=?", [(int)($_POST['invoice_id'] ?? 0), $this->tid, $studentId]);
        $errors = [];
        if (!$invoice) { $errors['invoice_id'] = 'Choose which bill this payment is for.'; }
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        if ($amount <= 0) { $errors['amount'] = 'Enter the amount received.'; }
        $date = $_POST['payment_date'] ?? date('Y-m-d');
        if (!strtotime($date) || $date > date('Y-m-d')) { $errors['payment_date'] = "The payment date can't be in the future."; }
        $method = array_key_exists($_POST['method'] ?? '', Finance::PAYMENT_METHODS) ? $_POST['method'] : 'cash';
        $reference = trim((string)($_POST['reference'] ?? ''));
        if (in_array($method, ['cheque', 'bank', 'pos', 'mobile'], true) && $reference === '') {
            $errors['reference'] = ['cheque' => 'Enter the cheque number.', 'bank' => 'Enter the bank slip number.', 'pos' => 'Enter the transaction number.', 'mobile' => 'Enter the transaction number.'][$method];
        }
        if ($invoice) {
            $pending = (float)($this->db->fetchOne("SELECT COALESCE(SUM(amount),0) t FROM payments WHERE invoice_id=? AND status='pending'", [$invoice['id']])['t'] ?? 0);
            $left = round((float)$invoice['amount_due'] - (float)$invoice['discount'] - (float)$invoice['amount_paid'] - $pending, 2);
            if ($invoice['status'] === 'waived') { $errors['invoice_id'] = 'That bill was written off.'; }
            elseif ($amount > $left + 0.005) { $errors['amount'] = 'That is more than the ' . Finance::money(max(0, $left), $invoice['currency']) . ' left on this bill.'; }
        }
        $file = $this->storeProof('receipt_file', $errors);
        if ($errors) { $this->failValidation($errors, $back); }

        $id = Finance::recordPayment($this->db, $this->tid, (int)$invoice['id'], $amount, [
            'method' => $method, 'reference' => $reference ?: null, 'notes' => trim($_POST['comment'] ?? '') ?: null,
            'payment_date' => $date, 'receipt_file' => $file,
        ]);
        $status = $this->db->fetchOne("SELECT status FROM payments WHERE id=?", [$id])['status'] ?? 'active';
        $this->flash('success', $status === 'pending'
            ? 'Payment recorded and sent for approval. It counts once an approver accepts it.'
            : 'Payment of ' . Finance::money($amount, $invoice['currency']) . ' recorded.');
        $this->redirect($back . ($status === 'active' ? (str_contains($back, '?') ? '&' : '?') . 'receipt=' . $id : ''));
    }

    public function cancel(string $id): void {
        $this->guard(['finance.manage']);
        $p = $this->db->fetchOne("SELECT p.id, i.student_id, i.academic_year_id FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE p.id=? AND p.tenant_id=?", [$id, $this->tid]);
        if (!$p) { $this->redirect('/school/finance/fees-payment'); }
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($reason === '') {
            $this->flash('danger', 'Give a reason for cancelling the payment.');
        } else {
            $ok = Finance::cancelPayment($this->db, $this->tid, (int)$id, $reason);
            $this->flash($ok ? 'success' : 'warning', $ok ? 'Payment cancelled — the amount is owed again.' : 'That payment was already cancelled.');
        }
        $back = (string)($_POST['back'] ?? '');
        $this->redirect(str_starts_with($back, '/school/finance/') ? $back
            : '/school/finance/fees-payment?student=' . $p['student_id'] . '&year=' . ($p['academic_year_id'] ?? ''));
    }

    // ── Approvals ──────────────────────────────────────────────────

    public function approvals(): void {
        $this->guard();
        $rows = $this->db->fetchAll(
            "SELECT p.*, COALESCE(i.description, i.notes, i.invoice_no) AS label, i.student_id, s.admission_no, su.name AS student_name,
                    c.name AS class_name, ru.name AS received_by_name
             FROM payments p JOIN invoices i ON i.id=p.invoice_id JOIN students s ON s.id=i.student_id JOIN users su ON su.id=s.user_id
             LEFT JOIN classes c ON c.id=s.class_id LEFT JOIN users ru ON ru.id=p.received_by
             WHERE p.tenant_id=? AND p.status='pending' ORDER BY p.id", [$this->tid]);
        $this->financeView('payment_approvals', ['pageTitle' => 'Payment Approvals', 'rows' => $rows]);
    }

    public function approve(string $id): void {
        $this->guard();
        if (!Finance::canApprove()) { $this->redirect('/unauthorized'); }
        $this->flash('success', Finance::approvePayment($this->db, $this->tid, (int)$id) ? 'Payment approved — it now counts.' : 'That payment was already handled.');
        $this->redirect('/school/finance/payment-approvals');
    }

    public function reject(string $id): void {
        $this->guard();
        if (!Finance::canApprove()) { $this->redirect('/unauthorized'); }
        $reason = trim((string)($_POST['reason'] ?? '')) ?: 'Rejected';
        $this->flash('success', Finance::rejectPayment($this->db, $this->tid, (int)$id, $reason) ? 'Payment rejected.' : 'That payment was already handled.');
        $this->redirect('/school/finance/payment-approvals');
    }

    // ── Receipts ───────────────────────────────────────────────────

    private function receiptRows(string $where, array $params): array {
        return $this->db->fetchAll(
            "SELECT p.*, COALESCE(i.description, i.notes, i.invoice_no) AS label, i.amount_due, i.discount, i.student_id, i.academic_year_id,
                    s.admission_no, su.name AS student_name, COALESCE(ec.name, c.name) AS class_name, ru.name AS received_by_name,
                    (SELECT COALESCE(SUM(p2.amount),0) FROM payments p2 WHERE p2.invoice_id=p.invoice_id AND p2.status='active'
                       AND (COALESCE(p2.payment_date, DATE(p2.paid_at)) < COALESCE(p.payment_date, DATE(p.paid_at))
                            OR (COALESCE(p2.payment_date, DATE(p2.paid_at)) = COALESCE(p.payment_date, DATE(p.paid_at)) AND p2.id <= p.id))) AS paid_to_date
             FROM payments p JOIN invoices i ON i.id=p.invoice_id JOIN students s ON s.id=i.student_id JOIN users su ON su.id=s.user_id
             LEFT JOIN enrollments e ON e.id=i.enrollment_id LEFT JOIN classes ec ON ec.id=e.class_id LEFT JOIN classes c ON c.id=s.class_id
             LEFT JOIN users ru ON ru.id=p.received_by
             WHERE p.tenant_id=? AND p.status='active' AND {$where}
             ORDER BY COALESCE(p.payment_date, DATE(p.paid_at)), p.id", array_merge([$this->tid], $params));
    }

    public function receipt(string $id): void {
        $this->guard();
        $rows = $this->receiptRows('p.id=?', [(int)$id]);
        if (!$rows) { $this->flash('danger', 'That receipt is not available (the payment may be pending or cancelled).'); $this->redirect('/school/finance/fees-payment'); }
        $this->printReceipts($rows, 'Receipt #' . (int)$id);
    }

    /** Every receipt for a student in a year, one after another (for "Download all receipts"). */
    public function receipts(): void {
        $this->guard();
        $year = $this->selectedYear();
        $studentId = (int)($_GET['student'] ?? 0);
        $rows = $year ? $this->receiptRows('i.student_id=? AND (i.academic_year_id=? OR (i.academic_year_id IS NULL AND DATE(i.created_at) BETWEEN ? AND ?))',
            [$studentId, $year['id'], $year['start_date'], $year['end_date']]) : [];
        if (!$rows) { $this->flash('warning', 'No receipts to print for this student and year.'); $this->redirect('/school/finance/fees-payment?student=' . $studentId); }
        $this->printReceipts($rows, 'Receipts — ' . $rows[0]['student_name']);
    }

    private function printReceipts(array $rows, string $title): void {
        $this->view('school/highschool/finance/receipt_print', [
            'pageTitle' => $title, 'rows' => $rows,
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'finSettings' => $this->settings,
        ]);
    }
}
