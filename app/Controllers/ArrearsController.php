<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Arrears — balances left over from earlier academic years.
 *
 *  • Collection: take payments against a previous year's bills. The payment clears that
 *    year's balance but is reported as "Arrears Collections" income in the year received.
 *  • Overrides: a student with prior-year arrears can't be enrolled for a new year until an
 *    approver lets them through. The debt stays on record; every decision is logged.
 *  • Write-offs: clear a balance that shouldn't be collected (e.g. wrongly billed), with a reason.
 */
class ArrearsController extends FinanceBaseController {

    /** Students owing on any year before the current one, with totals per currency. */
    private function owingStudents(int $currentYearId): array {
        return $this->db->fetchAll(
            "SELECT s.id, s.admission_no, u.name, c.name AS class_name, COALESCE(i.currency, ?) cur,
                    SUM(i.amount_due - i.discount - i.amount_paid) owed, COUNT(DISTINCT i.academic_year_id) years
             FROM invoices i JOIN academic_years ay ON ay.id=i.academic_year_id
             JOIN students s ON s.id=i.student_id JOIN users u ON u.id=s.user_id LEFT JOIN classes c ON c.id=s.class_id
             WHERE i.tenant_id=? AND i.status IN ('unpaid','partial','overdue')
               AND ay.start_date < (SELECT start_date FROM academic_years WHERE id=?)
             GROUP BY s.id, cur HAVING owed > 0.005 ORDER BY u.name",
            [$this->settings['default_currency'], $this->tid, $currentYearId]);
    }

    public function collection(): void {
        $this->guard();
        $year = Finance::currentYear($this->db, $this->tid);
        $studentId = (int)($_GET['student'] ?? 0);
        $data = ['pageTitle' => 'Arrears Collection', 'year' => $year, 'owing' => $year ? $this->owingStudents((int)$year['id']) : [], 'student' => null, 'bills' => []];
        if ($year && $studentId) {
            $data['student'] = $this->db->fetchOne("SELECT s.id, s.admission_no, u.name, c.name AS class_name FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN classes c ON c.id=s.class_id WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]);
            $data['bills'] = $this->db->fetchAll(
                "SELECT i.*, ay.name AS year_name, COALESCE(i.description, i.notes, i.invoice_no) AS label, (i.amount_due - i.discount - i.amount_paid) AS balance
                 FROM invoices i JOIN academic_years ay ON ay.id=i.academic_year_id
                 WHERE i.tenant_id=? AND i.student_id=? AND i.status IN ('unpaid','partial','overdue')
                   AND ay.start_date < ? ORDER BY ay.start_date, i.due_date, i.id",
                [$this->tid, $studentId, $year['start_date']]);
            $data['collected'] = $this->db->fetchAll(
                "SELECT p.*, COALESCE(i.description, i.invoice_no) AS label FROM payments p JOIN invoices i ON i.id=p.invoice_id
                 WHERE i.student_id=? AND p.tenant_id=? AND p.is_arrears=1 ORDER BY p.id DESC LIMIT 20", [$studentId, $this->tid]);
        }
        $this->financeView('arrears_collection', $data);
    }

    public function writeOff(string $invoiceId): void {
        $this->guard(['finance.manage']);
        if (!Finance::canApprove()) { $this->redirect('/unauthorized'); }
        $inv = $this->db->fetchOne("SELECT student_id FROM invoices WHERE id=? AND tenant_id=?", [$invoiceId, $this->tid]);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if (!$inv || $reason === '') { $this->flash('danger', 'Give a reason for clearing the balance.'); $this->redirect('/school/finance/arrears-collection'); }
        $amt = Finance::writeOff($this->db, $this->tid, (int)$invoiceId, $reason);
        $this->flash($amt > 0 ? 'success' : 'warning', $amt > 0 ? 'Balance cleared and logged under Balance Write-offs.' : 'Nothing was left to clear on that bill.');
        $back = (string)($_POST['back'] ?? '');
        $this->redirect(str_starts_with($back, '/school/finance/') ? $back : '/school/finance/arrears-collection?student=' . $inv['student_id']);
    }

    // ── Overrides ──────────────────────────────────────────────────

    public function overrides(): void {
        $this->guard();
        $tab = in_array($_GET['tab'] ?? '', ['pending', 'reviewed', 'audit', 'writeoffs'], true) ? $_GET['tab'] : 'pending';
        $base = "SELECT r.*, s.admission_no, su.name AS student_name, ay.name AS year_name, c.name AS class_name, st.name AS type_name,
                        rq.name AS requested_by_name, rv.name AS reviewed_by_name
                 FROM arrears_override_requests r JOIN students s ON s.id=r.student_id JOIN users su ON su.id=s.user_id
                 LEFT JOIN academic_years ay ON ay.id=r.academic_year_id LEFT JOIN classes c ON c.id=r.class_id
                 LEFT JOIN student_types st ON st.id=r.student_type_id LEFT JOIN users rq ON rq.id=r.requested_by LEFT JOIN users rv ON rv.id=r.reviewed_by
                 WHERE r.tenant_id=?";
        $rows = match ($tab) {
            'pending'   => $this->db->fetchAll("$base AND r.status='pending' ORDER BY r.id", [$this->tid]),
            'reviewed'  => $this->db->fetchAll("$base AND r.status<>'pending' ORDER BY r.reviewed_at DESC LIMIT 200", [$this->tid]),
            'audit'     => $this->db->fetchAll("$base AND r.status='approved' ORDER BY r.reviewed_at DESC LIMIT 200", [$this->tid]),
            'writeoffs' => $this->db->fetchAll(
                "SELECT w.*, s.admission_no, su.name AS student_name, ay.name AS year_name, COALESCE(i.description, i.invoice_no) AS label, u.name AS by_name
                 FROM balance_writeoffs w JOIN students s ON s.id=w.student_id JOIN users su ON su.id=s.user_id
                 LEFT JOIN academic_years ay ON ay.id=w.academic_year_id LEFT JOIN invoices i ON i.id=w.invoice_id LEFT JOIN users u ON u.id=w.created_by
                 WHERE w.tenant_id=? ORDER BY w.id DESC LIMIT 300", [$this->tid]),
        };
        $counts = array_column($this->db->fetchAll("SELECT status, COUNT(*) n FROM arrears_override_requests WHERE tenant_id=? GROUP BY status", [$this->tid]), 'n', 'status');
        $this->financeView('arrears_overrides', ['pageTitle' => 'Arrears Overrides', 'tab' => $tab, 'rows' => $rows, 'counts' => $counts]);
    }

    /** Approve (optionally clearing the prior balances first) or reject a pending request. */
    public function decide(string $id): void {
        $this->guard();
        if (!Finance::canApprove()) { $this->redirect('/unauthorized'); }
        $r = $this->db->fetchOne("SELECT * FROM arrears_override_requests WHERE id=? AND tenant_id=? AND status='pending'", [$id, $this->tid]);
        if (!$r) { $this->flash('warning', 'That request was already handled.'); $this->redirect('/school/finance/arrears-overrides'); }
        $decision = $_POST['decision'] ?? '';
        $note = trim((string)($_POST['note'] ?? ''));

        if ($decision === 'reject') {
            $this->db->execute("UPDATE arrears_override_requests SET status='rejected', reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?", [$_SESSION['user_id'] ?? null, $note ?: null, $id]);
            $this->flash('success', 'Request rejected. The student stays unenrolled until the arrears are settled.');
            $this->redirect('/school/finance/arrears-overrides');
        }
        if ($decision === 'clear') {
            if ($note === '') { $this->flash('danger', 'Give a reason for clearing the balance.'); $this->redirect('/school/finance/arrears-overrides'); }
            $year = $this->db->fetchOne("SELECT start_date FROM academic_years WHERE id=?", [$r['academic_year_id']]);
            foreach ($this->db->fetchAll(
                "SELECT i.id FROM invoices i JOIN academic_years ay ON ay.id=i.academic_year_id
                 WHERE i.tenant_id=? AND i.student_id=? AND i.status IN ('unpaid','partial','overdue') AND ay.start_date < ?",
                [$this->tid, $r['student_id'], $year['start_date'] ?? '9999-12-31']) as $inv) {
                Finance::writeOff($this->db, $this->tid, (int)$inv['id'], $note);
            }
        }
        $existing = $this->db->fetchOne("SELECT id FROM enrollments WHERE student_id=? AND academic_year_id=?", [$r['student_id'], $r['academic_year_id']]);
        if ($existing) {
            $eid = (int)$existing['id'];
        } else {
            $eid = (int)$this->db->insert(
                "INSERT INTO enrollments (tenant_id,student_id,academic_year_id,class_id,student_type_id,category,override_request_id,enrolled_by) VALUES (?,?,?,?,?,?,?,?)",
                [$this->tid, $r['student_id'], $r['academic_year_id'], $r['class_id'], $r['student_type_id'], $r['category'], $id, $_SESSION['user_id'] ?? null]);
        }
        Finance::syncEnrollment($this->db, $eid);
        $current = Finance::currentYear($this->db, $this->tid);
        if ($current && (int)$current['id'] === (int)$r['academic_year_id']) {
            $this->db->execute("UPDATE students SET class_id=?, academic_year_id=? WHERE id=?", [$r['class_id'], $r['academic_year_id'], $r['student_id']]);
        }
        $this->db->execute("UPDATE arrears_override_requests SET status='approved', reviewed_by=?, reviewed_at=NOW(), review_note=? WHERE id=?",
            [$_SESSION['user_id'] ?? null, ($decision === 'clear' ? 'Balance cleared: ' : '') . ($note ?: 'Approved'), $id]);
        $this->flash('success', $decision === 'clear' ? 'Prior balances cleared and the student registered.' : 'Override approved — the student is registered. Their prior-year debt stays on record.');
        $this->redirect('/school/finance/arrears-overrides');
    }
}
