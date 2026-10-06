<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Sponsorships: the schemes that pay children's fees (Serve the Children, Aunty Shar
 * Child Sponsorship, and any the school adds). A sponsored child is billed as usual;
 * the sponsor's money is recorded on Fees Payment against the child's bills and tagged
 * to the sponsorship, so each scheme shows what it covers, has paid and still owes.
 */
class SponsorshipController extends FinanceBaseController {

    /** Schemes with this year's totals, and every sponsored child. */
    public function index(): void {
        $this->guard();
        $year = $this->selectedYear();
        $schemes = $this->db->fetchAll("SELECT * FROM sponsorship_schemes WHERE tenant_id=? ORDER BY is_active DESC, name", [$this->tid]);
        $rows = $year ? $this->sponsored((int)$year['id']) : [];

        $totals = [];
        foreach ($rows as $i => $r) {
            $t = Finance::sponsorshipTotals($this->db, $this->tid, $r);
            $rows[$i]['totals'] = $t;
            foreach ($t as $cur => $v) {
                $totals[(int)$r['scheme_id']][$cur] ??= ['cover' => 0, 'paid' => 0, 'owing' => 0];
                foreach (['cover', 'paid', 'owing'] as $k) { $totals[(int)$r['scheme_id']][$cur][$k] += $v[$k]; }
            }
        }
        $this->financeView('sponsorships', [
            'pageTitle' => 'Sponsorships', 'year' => $year, 'years' => $this->years(),
            'schemes' => $schemes, 'rows' => $rows, 'totals' => $totals,
            'schemeId' => (int)($_GET['scheme'] ?? 0),
            'students' => $this->db->fetchAll(
                "SELECT s.id, s.admission_no, u.name FROM students s JOIN users u ON u.id=s.user_id
                 WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid]),
        ]);
    }

    /** Sponsored children for a year, newest first; optionally one scheme only. */
    private function sponsored(int $yearId, int $schemeId = 0): array {
        $where = $schemeId ? ' AND sp.scheme_id=?' : '';
        $params = [$this->tid, $yearId];
        if ($schemeId) { $params[] = $schemeId; }
        return $this->db->fetchAll(
            "SELECT sp.*, sc.name AS scheme_name, s.admission_no, u.name AS student_name, c.name AS class_name
             FROM sponsorships sp
             JOIN sponsorship_schemes sc ON sc.id=sp.scheme_id
             JOIN students s ON s.id=sp.student_id JOIN users u ON u.id=s.user_id
             LEFT JOIN enrollments e ON e.student_id=sp.student_id AND e.academic_year_id=sp.academic_year_id
             LEFT JOIN classes c ON c.id=e.class_id
             WHERE sp.tenant_id=? AND sp.academic_year_id=?{$where}
             ORDER BY sp.status, sc.name, u.name", $params);
    }

    /** Adds a scheme, or edits / switches one off. */
    public function saveScheme(): void {
        $this->guard(['finance.manage']);
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') { $this->flash('danger', 'Give the scheme a name.'); $this->redirect('/school/finance/sponsorships'); }
        $data = [mb_substr($name, 0, 150), trim((string)($_POST['contact_person'] ?? '')) ?: null, trim((string)($_POST['phone'] ?? '')) ?: null,
                 trim((string)($_POST['email'] ?? '')) ?: null, mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500) ?: null,
                 empty($_POST['is_active']) ? 0 : 1];
        $clash = $this->db->fetchOne("SELECT id FROM sponsorship_schemes WHERE tenant_id=? AND name=? AND id<>?", [$this->tid, $name, $id]);
        if ($clash) { $this->flash('danger', 'There is already a scheme with that name.'); $this->redirect('/school/finance/sponsorships'); }
        if ($id && $this->db->fetchOne("SELECT id FROM sponsorship_schemes WHERE id=? AND tenant_id=?", [$id, $this->tid])) {
            $this->db->execute("UPDATE sponsorship_schemes SET name=?, contact_person=?, phone=?, email=?, notes=?, is_active=? WHERE id=?",
                array_merge($data, [$id]));
            $this->flash('success', 'Scheme saved.');
        } else {
            $this->db->insert("INSERT INTO sponsorship_schemes (name,contact_person,phone,email,notes,is_active,tenant_id) VALUES (?,?,?,?,?,?,?)",
                array_merge($data, [$this->tid]));
            $this->flash('success', "\"{$name}\" added.");
        }
        $this->redirect('/school/finance/sponsorships');
    }

    /** Puts a child on a scheme for a year (or changes what it covers). */
    public function sponsor(): void {
        $this->guard();
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $back = '/school/finance/sponsorships?year=' . $yearId;
        $schemeId = (int)($_POST['scheme_id'] ?? 0);
        $studentId = (int)($_POST['student_id'] ?? 0);
        $scheme = $this->db->fetchOne("SELECT * FROM sponsorship_schemes WHERE id=? AND tenant_id=?", [$schemeId, $this->tid]);
        $student = $this->db->fetchOne("SELECT s.id, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]);
        $year = $this->db->fetchOne("SELECT id FROM academic_years WHERE id=? AND tenant_id=?", [$yearId, $this->tid]);
        if (!$scheme || !$student || !$year) { $this->flash('danger', 'Choose a scheme, a student and a school year.'); $this->redirect($back); }

        $type = in_array($_POST['cover_type'] ?? '', ['full', 'percent', 'amount'], true) ? $_POST['cover_type'] : 'full';
        $value = round((float)($_POST['cover_value'] ?? 0), 2);
        if ($type === 'percent' && ($value <= 0 || $value > 100)) { $this->flash('danger', 'A share has to be between 1 and 100 percent.'); $this->redirect($back); }
        if ($type === 'amount' && $value <= 0) { $this->flash('danger', 'Enter the amount the scheme covers.'); $this->redirect($back); }
        if ($type === 'full') { $value = 0; }
        $currency = $type === 'amount' ? $this->currencyOrDefault($_POST['currency'] ?? null) : null;

        $existing = $this->db->fetchOne("SELECT id FROM sponsorships WHERE scheme_id=? AND student_id=? AND academic_year_id=?", [$schemeId, $studentId, $yearId]);
        if ($existing) {
            $this->db->execute("UPDATE sponsorships SET cover_type=?, cover_value=?, currency=?, reference=?, notes=?, status='active', ended_at=NULL, end_reason=NULL WHERE id=?",
                [$type, $value, $currency, trim((string)($_POST['reference'] ?? '')) ?: null, mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500) ?: null, $existing['id']]);
            $this->flash('success', "{$student['name']}'s sponsorship under {$scheme['name']} was updated.");
        } else {
            $this->db->insert(
                "INSERT INTO sponsorships (tenant_id,scheme_id,student_id,academic_year_id,cover_type,cover_value,currency,reference,notes,created_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$this->tid, $schemeId, $studentId, $yearId, $type, $value, $currency,
                 trim((string)($_POST['reference'] ?? '')) ?: null, mb_substr(trim((string)($_POST['notes'] ?? '')), 0, 500) ?: null, $_SESSION['user_id'] ?? null]);
            $this->flash('success', "{$student['name']} is now sponsored by {$scheme['name']}.");
        }
        $this->redirect($back);
    }

    /** Ends a sponsorship. Money already received against it stays on record. */
    public function end(string $id): void {
        $this->guard();
        $sp = $this->db->fetchOne("SELECT * FROM sponsorships WHERE id=? AND tenant_id=?", [(int)$id, $this->tid]);
        if (!$sp) { $this->redirect('/school/finance/sponsorships'); }
        $this->db->execute("UPDATE sponsorships SET status='ended', ended_at=NOW(), end_reason=? WHERE id=?",
            [mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255) ?: null, $sp['id']]);
        $this->flash('success', 'Sponsorship ended. Payments already received against it stay on record.');
        $this->redirect('/school/finance/sponsorships?year=' . $sp['academic_year_id']);
    }

    /** One scheme: the children on it, what it covers, and every payment it has made. */
    public function show(string $id): void {
        $this->guard();
        $scheme = $this->db->fetchOne("SELECT * FROM sponsorship_schemes WHERE id=? AND tenant_id=?", [(int)$id, $this->tid]);
        if (!$scheme) { $this->redirect('/school/finance/sponsorships'); }
        $year = $this->selectedYear();
        $rows = $year ? $this->sponsored((int)$year['id'], (int)$scheme['id']) : [];
        $totals = [];
        foreach ($rows as $i => $r) {
            $t = Finance::sponsorshipTotals($this->db, $this->tid, $r);
            $rows[$i]['totals'] = $t;
            foreach ($t as $cur => $v) {
                $totals[$cur] ??= ['cover' => 0, 'paid' => 0, 'owing' => 0];
                foreach (['cover', 'paid', 'owing'] as $k) { $totals[$cur][$k] += $v[$k]; }
            }
        }
        $payments = $year ? $this->db->fetchAll(
            "SELECT p.id, p.amount, p.method, p.reference, COALESCE(p.currency, i.currency) AS cur,
                    COALESCE(p.payment_date, DATE(p.paid_at)) AS pay_date, COALESCE(i.description, i.notes, i.invoice_no) AS label,
                    u.name AS student_name, s.admission_no
             FROM payments p JOIN sponsorships sp ON sp.id=p.sponsorship_id JOIN invoices i ON i.id=p.invoice_id
             JOIN students s ON s.id=sp.student_id JOIN users u ON u.id=s.user_id
             WHERE sp.scheme_id=? AND sp.academic_year_id=? AND p.status='active'
             ORDER BY pay_date DESC, p.id DESC", [$scheme['id'], $year['id']]) : [];

        $this->financeView('sponsorship_scheme', [
            'pageTitle' => $scheme['name'], 'scheme' => $scheme, 'year' => $year, 'years' => $this->years(),
            'rows' => $rows, 'totals' => $totals, 'payments' => $payments,
        ]);
    }
}
