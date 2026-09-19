<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Student Enrollment: registers a student into a class for an academic year with a
 * student type and a new/old category. Enrolling creates the student's bills; the list
 * shows who has paid in full, who is part-paid and who hasn't paid at all.
 */
class EnrollmentController extends FinanceBaseController {

    public function index(): void {
        $this->guard();
        $year = $this->selectedYear();
        $search = trim($_GET['q'] ?? '');
        $classId = (int)($_GET['class'] ?? 0);
        $typeId = (int)($_GET['type'] ?? 0);
        $status = $_GET['status'] ?? '';

        $rows = [];
        $summary = ['enrolled' => 0, 'complete' => 0, 'pending' => 0, 'none' => 0];
        if ($year) {
            $params = [$this->tid, $year['id']];
            $where = "e.tenant_id=? AND e.academic_year_id=? AND e.status='active'";
            if ($search !== '') { $where .= " AND (u.name LIKE ? OR s.admission_no LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
            if ($classId) { $where .= " AND e.class_id=?"; $params[] = $classId; }
            if ($typeId) { $where .= " AND e.student_type_id=?"; $params[] = $typeId; }
            $rows = $this->db->fetchAll(
                "SELECT e.*, s.admission_no, u.name, c.name AS class_name, st.name AS type_name,
                        COALESCE(SUM(i.amount_due - i.discount), 0) billed, COALESCE(SUM(i.amount_paid), 0) paid,
                        COALESCE(SUM(CASE WHEN i.status='waived' THEN i.amount_due - i.discount - i.amount_paid ELSE 0 END), 0) waived,
                        GROUP_CONCAT(DISTINCT i.currency) currencies
                 FROM enrollments e JOIN students s ON s.id=e.student_id JOIN users u ON u.id=s.user_id
                 LEFT JOIN classes c ON c.id=e.class_id LEFT JOIN student_types st ON st.id=e.student_type_id
                 LEFT JOIN invoices i ON i.enrollment_id=e.id
                 WHERE $where GROUP BY e.id ORDER BY c.name, u.name", $params
            );
            foreach ($rows as &$r) {
                $r['balance'] = max(0, (float)$r['billed'] - (float)$r['paid'] - (float)$r['waived']);
                $r['pay_status'] = (float)$r['billed'] <= 0 ? 'nobill' : ($r['balance'] <= 0.005 ? 'complete' : ((float)$r['paid'] > 0 ? 'pending' : 'none'));
                $summary['enrolled']++;
                $summary[$r['pay_status'] === 'nobill' ? 'complete' : $r['pay_status']]++;
            }
            unset($r);
            if (in_array($status, ['complete', 'pending', 'none'], true)) {
                $rows = array_values(array_filter($rows, fn($r) => $r['pay_status'] === $status || ($status === 'complete' && $r['pay_status'] === 'nobill')));
            }
        }
        $students = $year ? $this->db->fetchAll(
            "SELECT s.id, s.admission_no, u.name, s.class_id, s.admission_type,
                    (SELECT e.id FROM enrollments e WHERE e.student_id=s.id AND e.academic_year_id=?) AS enrolled_id,
                    (SELECT COUNT(*) FROM enrollments e2 WHERE e2.student_id=s.id) AS past_enrollments
             FROM students s JOIN users u ON u.id=s.user_id WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name",
            [$year['id'], $this->tid]) : [];

        $this->financeView('enrollment', [
            'pageTitle' => 'Student Enrollment', 'year' => $year, 'years' => $this->years(), 'classes' => $this->classes(),
            'types' => $this->studentTypes(), 'rows' => $rows, 'students' => $students, 'summary' => $summary,
            'filters' => ['q' => $search, 'class' => $classId, 'type' => $typeId, 'status' => $status],
            'pendingOverrides' => (int)($this->db->fetchOne("SELECT COUNT(*) c FROM arrears_override_requests WHERE tenant_id=? AND status='pending'", [$this->tid])['c'] ?? 0),
        ]);
    }

    private function back(int $yearId): never {
        $this->redirect('/school/finance/enrollment?year=' . $yearId);
    }

    /** New or old: a student with any earlier enrollment, or admitted as returning, is "old". */
    private function autoCategory(int $studentId, int $yearId): string {
        $prior = $this->db->fetchOne(
            "SELECT COUNT(*) c FROM enrollments e JOIN academic_years ay ON ay.id=e.academic_year_id
             WHERE e.student_id=? AND ay.start_date < (SELECT start_date FROM academic_years WHERE id=?)", [$studentId, $yearId]);
        if ((int)($prior['c'] ?? 0) > 0) { return 'old'; }
        $s = $this->db->fetchOne("SELECT admission_type FROM students WHERE id=?", [$studentId]);
        return ($s['admission_type'] ?? 'new') === 'old' ? 'old' : 'new';
    }

    /**
     * Enrolls (or re-enrolls) one student. Returns [status, message] where status is
     * 'enrolled' | 'updated' | 'blocked' (prior-year arrears; an override request was filed).
     */
    private function enrollOne(int $studentId, int $yearId, int $classId, int $typeId, string $category, bool $overrideNow, string $reason = ''): array {
        $student = $this->db->fetchOne("SELECT s.id, u.name FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=? AND s.tenant_id=?", [$studentId, $this->tid]);
        if (!$student) { return ['error', 'Student not found.']; }
        if ($category !== 'new' && $category !== 'old') { $category = $this->autoCategory($studentId, $yearId); }

        $existing = $this->db->fetchOne("SELECT * FROM enrollments WHERE student_id=? AND academic_year_id=?", [$studentId, $yearId]);
        if ($existing) {
            $this->db->execute("UPDATE enrollments SET class_id=?, student_type_id=?, category=?, status='active' WHERE id=?",
                [$classId, $typeId ?: null, $category, $existing['id']]);
            Finance::syncEnrollment($this->db, (int)$existing['id']);
            $this->followClass($studentId, $yearId, $classId);
            return ['updated', "{$student['name']}'s enrollment was updated and their bills recalculated."];
        }

        $overrideId = null;
        if ($this->settings['block_arrears_enrollment'] === '1') {
            $arrears = Finance::priorArrears($this->db, $this->tid, $studentId, $yearId);
            if ($arrears) {
                $owed = implode(' + ', array_map(fn($c, $a) => Finance::money($a, $c), array_keys($arrears), $arrears));
                if ($overrideNow && Finance::canApprove()) {
                    $overrideId = (int)$this->db->insert(
                        "INSERT INTO arrears_override_requests (tenant_id,student_id,academic_year_id,class_id,student_type_id,category,outstanding,reason,status,requested_by,reviewed_by,reviewed_at,review_note)
                         VALUES (?,?,?,?,?,?,?,?,'approved',?,?,NOW(),'Approved at enrollment')",
                        [$this->tid, $studentId, $yearId, $classId, $typeId ?: null, $category, $owed, $reason ?: 'Approved at enrollment', $_SESSION['user_id'] ?? null, $_SESSION['user_id'] ?? null]);
                } else {
                    $dup = $this->db->fetchOne("SELECT id FROM arrears_override_requests WHERE tenant_id=? AND student_id=? AND academic_year_id=? AND status='pending'", [$this->tid, $studentId, $yearId]);
                    if (!$dup) {
                        $this->db->insert(
                            "INSERT INTO arrears_override_requests (tenant_id,student_id,academic_year_id,class_id,student_type_id,category,outstanding,reason,requested_by)
                             VALUES (?,?,?,?,?,?,?,?,?)",
                            [$this->tid, $studentId, $yearId, $classId, $typeId ?: null, $category, $owed, $reason ?: null, $_SESSION['user_id'] ?? null]);
                    }
                    return ['blocked', "{$student['name']} still owes {$owed} from previous years. An override request was sent for approval (Arrears Overrides) — or collect the arrears first."];
                }
            }
        }
        $eid = (int)$this->db->insert(
            "INSERT INTO enrollments (tenant_id,student_id,academic_year_id,class_id,student_type_id,category,override_request_id,enrolled_by) VALUES (?,?,?,?,?,?,?,?)",
            [$this->tid, $studentId, $yearId, $classId, $typeId ?: null, $category, $overrideId, $_SESSION['user_id'] ?? null]);
        Finance::syncEnrollment($this->db, $eid);
        $this->followClass($studentId, $yearId, $classId);
        return ['enrolled', "{$student['name']} enrolled" . ($overrideId ? ' (arrears override recorded)' : '') . '.'];
    }

    /** Enrolling for the current year also moves the student into that class everywhere else. */
    private function followClass(int $studentId, int $yearId, int $classId): void {
        $current = Finance::currentYear($this->db, $this->tid);
        if ($current && (int)$current['id'] === $yearId) {
            $this->db->execute("UPDATE students SET class_id=?, academic_year_id=? WHERE id=? AND tenant_id=?", [$classId, $yearId, $studentId, $this->tid]);
        }
    }

    public function store(): void {
        $this->guard();
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        if (!$this->db->fetchOne("SELECT id FROM academic_years WHERE id=? AND tenant_id=?", [$yearId, $this->tid])
            || !$this->db->fetchOne("SELECT id FROM classes WHERE id=? AND tenant_id=?", [$classId, $this->tid])) {
            $this->flash('danger', 'Choose the academic year and class.');
            $this->redirect('/school/finance/enrollment');
        }
        $typeId = (int)($_POST['student_type_id'] ?? 0);
        [$status, $msg] = $this->enrollOne((int)($_POST['student_id'] ?? 0), $yearId, $classId, $typeId, (string)($_POST['category'] ?? 'auto'),
            !empty($_POST['override_now']), trim($_POST['override_reason'] ?? ''));
        $this->flash(['enrolled' => 'success', 'updated' => 'success', 'blocked' => 'warning'][$status] ?? 'danger', $msg);
        $this->back($yearId);
    }

    /** Enrolls every active student currently in a class (not yet enrolled this year) in one go. */
    public function bulk(): void {
        $this->guard(['finance.manage']);
        $yearId = (int)($_POST['academic_year_id'] ?? 0);
        $fromClass = (int)($_POST['from_class_id'] ?? 0);
        $toClass = (int)($_POST['class_id'] ?? 0);
        $typeId = (int)($_POST['student_type_id'] ?? 0);
        if (!$yearId || !$toClass || !$this->db->fetchOne("SELECT id FROM classes WHERE id=? AND tenant_id=?", [$toClass, $this->tid])) {
            $this->flash('danger', 'Choose the class to enroll into.');
            $this->back($yearId);
        }
        $students = $this->db->fetchAll(
            "SELECT s.id FROM students s WHERE s.tenant_id=? AND s.status='active' AND s.class_id=?
               AND NOT EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id=s.id AND e.academic_year_id=?)",
            [$this->tid, $fromClass ?: $toClass, $yearId]);
        $done = $blocked = 0;
        foreach ($students as $s) {
            [$status] = $this->enrollOne((int)$s['id'], $yearId, $toClass, $typeId, 'auto', false);
            $status === 'blocked' ? $blocked++ : $done++;
        }
        $this->flash($done ? 'success' : 'warning', "{$done} student(s) enrolled." . ($blocked ? " {$blocked} held back for prior-year arrears (see Arrears Overrides)." : ''));
        $this->back($yearId);
    }

    public function update(string $id): void {
        $this->guard();
        $e = $this->db->fetchOne("SELECT * FROM enrollments WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$e) { $this->redirect('/school/finance/enrollment'); }
        $classId = (int)($_POST['class_id'] ?? $e['class_id']);
        if (!$this->db->fetchOne("SELECT id FROM classes WHERE id=? AND tenant_id=?", [$classId, $this->tid])) { $classId = (int)$e['class_id']; }
        $category = in_array($_POST['category'] ?? '', ['new', 'old'], true) ? $_POST['category'] : $e['category'];
        $this->db->execute("UPDATE enrollments SET class_id=?, student_type_id=?, category=? WHERE id=?",
            [$classId, (int)($_POST['student_type_id'] ?? 0) ?: null, $category, $id]);
        [$c, $u, $r, $k] = Finance::syncEnrollment($this->db, (int)$id);
        $this->followClass((int)$e['student_id'], (int)$e['academic_year_id'], $classId);
        $this->flash('success', 'Enrollment updated — bills recalculated' . ($k ? " ({$k} already-paid bill(s) from the old setup were kept)." : '.'));
        $this->back((int)$e['academic_year_id']);
    }

    /** Removes an enrollment and its unpaid bills. Refused while money is recorded against it. */
    public function withdraw(string $id): void {
        $this->guard(['finance.manage']);
        $e = $this->db->fetchOne("SELECT e.*, u.name FROM enrollments e JOIN students s ON s.id=e.student_id JOIN users u ON u.id=s.user_id WHERE e.id=? AND e.tenant_id=?", [$id, $this->tid]);
        if (!$e) { $this->redirect('/school/finance/enrollment'); }
        $paid = (int)($this->db->fetchOne("SELECT COUNT(*) c FROM payments p JOIN invoices i ON i.id=p.invoice_id WHERE i.enrollment_id=? AND p.status IN ('active','pending')", [$id])['c'] ?? 0);
        if ($paid) {
            $this->flash('danger', "{$e['name']} has {$paid} payment(s) recorded for this year. Cancel those payments first, or change the class instead of unenrolling.");
            $this->back((int)$e['academic_year_id']);
        }
        $this->db->execute("UPDATE enrollments SET status='withdrawn' WHERE id=?", [$id]);
        Finance::syncEnrollment($this->db, (int)$id);
        $this->db->execute("DELETE FROM enrollments WHERE id=?", [$id]);
        $this->flash('success', "{$e['name']} was unenrolled and their bills for the year removed.");
        $this->back((int)$e['academic_year_id']);
    }
}
