<?php
require_once ROOT_DIR . '/app/Controllers/FinanceBaseController.php';

/**
 * Bus fees: one bill per rider per month, at the route's monthly fee. The bills are
 * ordinary invoices in the school year, so they are paid on Fees Payment and show on
 * statements, arrears and the family portals like any other bill.
 */
class BusBillingController extends FinanceBaseController {

    public function index(): void {
        $this->guard(['finance.manage']);
        $routes = $this->db->fetchAll(
            "SELECT r.*, b.bus_number,
                    (SELECT COUNT(*) FROM bus_students bs WHERE bs.route_id=r.id AND bs.status='active') AS student_count
             FROM bus_routes r LEFT JOIN buses b ON r.bus_id=b.id
             WHERE r.tenant_id=? AND r.status='active' ORDER BY r.name", [$this->tid]
        );
        $this->financeView('bus_billing', [
            'pageTitle' => 'Bus Billing', 'routes' => $routes,
            'stats' => [
                'totalRoutes' => count($routes),
                'totalStudents' => array_sum(array_column($routes, 'student_count')),
                'monthlyPotential' => array_sum(array_map(fn($r) => $r['monthly_fee'] * $r['student_count'], $routes)),
            ],
        ]);
    }

    public function generate(): void {
        $this->guard(['finance.manage']);
        $errors = $this->validate($_POST, ['route_id' => 'required', 'month' => 'required']);
        if ($errors) { $this->failValidation($errors, '/school/finance/bus-billing'); }
        $route = $this->db->fetchOne("SELECT * FROM bus_routes WHERE id=? AND tenant_id=?", [$_POST['route_id'], $this->tid]);
        $month = (string)$_POST['month']; // YYYY-MM
        if (!$route || !preg_match('/^\d{4}-\d{2}$/', $month)) { $this->redirect('/school/finance/bus-billing'); }

        $monthLabel = date('F Y', strtotime($month . '-01'));
        $tag = '[BUS-ROUTE:' . $route['id'] . ':' . $month . ']';
        $dueDate = ($_POST['due_date'] ?? '') ?: date('Y-m-d', strtotime($month . '-01 +14 days'));
        $currency = $this->settings['default_currency'];
        // The bill belongs to the school year its month falls in.
        $year = $this->db->fetchOne("SELECT id FROM academic_years WHERE tenant_id=? AND ? BETWEEN start_date AND end_date LIMIT 1",
            [$this->tid, $month . '-01']) ?: Finance::currentYear($this->db, $this->tid);
        $description = "Bus Fee - {$route['name']} - {$monthLabel}";

        $students = $this->db->fetchAll("SELECT student_id FROM bus_students WHERE route_id=? AND status='active'", [$route['id']]);
        $alreadyBilled = array_flip(array_column(
            $this->db->fetchAll("SELECT student_id FROM invoices WHERE tenant_id=? AND notes LIKE ?", [$this->tid, '%' . $tag]), 'student_id'));

        $created = $skipped = 0;
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($students as $s) {
                if (isset($alreadyBilled[$s['student_id']])) { $skipped++; continue; }
                $invoiceNo = 'BUS-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $invoiceId = (int)$this->db->insert(
                    "INSERT INTO invoices (tenant_id,student_id,academic_year_id,invoice_no,description,amount_due,currency,due_date,notes,status)
                     VALUES (?,?,?,?,?,?,?,?,?,?)",
                    [$this->tid, $s['student_id'], $year['id'] ?? null, $invoiceNo, $description, $route['monthly_fee'], $currency,
                     $dueDate, "{$description} {$tag}", (float)$route['monthly_fee'] > 0 ? 'unpaid' : 'paid']
                );
                Finance::ledger($this->db, $this->tid, (int)$s['student_id'], 'charge', $description, (float)$route['monthly_fee'],
                    ['invoice_id' => $invoiceId, 'reference' => $invoiceNo, 'date' => $dueDate, 'academic_year_id' => $year['id'] ?? null, 'currency' => $currency]);
                $created++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Bus billing failed: ' . $e->getMessage());
            $this->flash('danger', 'Could not create the bus bills — no changes were made. Please try again.');
            $this->redirect('/school/finance/bus-billing');
        }
        $this->flash($created > 0 ? 'success' : 'warning', "Created {$created} bus bill(s) for {$monthLabel}." . ($skipped > 0 ? " {$skipped} student(s) were already billed for this month." : ''));
        $this->redirect('/school/finance/bus-billing');
    }
}
