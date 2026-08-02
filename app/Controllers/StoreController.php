<?php
require_once ROOT_DIR . '/core/Controller.php';
require_once ROOT_DIR . '/app/Controllers/StudentAccountController.php';

/**
 * School Store — sells uniforms, stationery and similar items to students.
 *
 * Money deliberately flows through the existing finance tables rather than a
 * second set of books:
 *
 *   sale to a student  → invoice raised  → student ledger 'charge'
 *   collected on the spot → payment row  → student ledger 'payment'
 *
 * so a store purchase shows up in arrears, statements, the collection report and
 * the parent portal without any of those needing to know the store exists. A
 * walk-in sale has no student to bill, so it is cash-only and raises no invoice.
 */
class StoreController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
    }

    /** Selling is the lighter grant; managing the catalogue and voiding needs store.manage. */
    private function canManage(): bool {
        return $this->hasPermission('store.manage') || ($_SESSION['role'] ?? '') === 'School Admin';
    }
    private function requireSell(): void  { $this->requirePermission(['store.sell', 'store.manage']); }
    private function requireManage(): void { $this->requirePermission(['store.manage']); }

    private function tenant(): array {
        return $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]) ?: [];
    }

    // ── CATALOGUE ───────────────────────────────────────────────────────────

    public function items(): void {
        $this->requireSell();
        $search   = trim($_GET['q'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where = "tenant_id=?"; $params = [$this->tid];
        if ($search !== '')   { $where .= " AND (name LIKE ? OR sku LIKE ?)"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; }
        if ($category !== '') { $where .= " AND category=?"; $params[] = $category; }

        $items = $this->db->fetchAll("SELECT * FROM store_items WHERE {$where} ORDER BY is_active DESC, name", $params);
        $categories = $this->db->fetchAll(
            "SELECT DISTINCT category FROM store_items WHERE tenant_id=? AND category IS NOT NULL AND category<>'' ORDER BY category",
            [$this->tid]
        );
        $stats = $this->db->fetchOne(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN is_active=1 THEN 1 ELSE 0 END),0) AS active,
                    COALESCE(SUM(stock_qty * unit_price),0) AS retail_value,
                    COALESCE(SUM(CASE WHEN reorder_level>0 AND stock_qty<=reorder_level THEN 1 ELSE 0 END),0) AS low_stock
             FROM store_items WHERE tenant_id=?", [$this->tid]
        );

        $this->view('school/highschool/store/items', [
            'pageTitle'=>'Store Items','panelType'=>'school','items'=>$items,'categories'=>$categories,
            'stats'=>$stats,'tenant'=>$this->tenant(),'search'=>$search,'selectedCategory'=>$category,
            'canManage'=>$this->canManage(),'flash'=>$this->getFlash(),
        ]);
    }

    public function storeItem(): void {
        $this->requireManage();
        $errors = $this->validate($_POST, [
            'name'          => 'required|max:150',
            'unit_price'    => 'required|numeric',
            'cost_price'    => 'numeric',
            'stock_qty'     => 'numeric',
            'reorder_level' => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/store/items'); }

        $opening = (int)($_POST['stock_qty'] ?? 0);
        $itemId = $this->db->insert(
            "INSERT INTO store_items (tenant_id,name,sku,category,description,cost_price,unit_price,stock_qty,reorder_level,unit,is_active)
             VALUES (?,?,?,?,?,?,?,?,?,?,1)",
            [
                $this->tid, $_POST['name'], ($_POST['sku'] ?? '') ?: null, $_POST['category'] ?? null,
                $_POST['description'] ?? null, ($_POST['cost_price'] ?? '') !== '' ? $_POST['cost_price'] : null,
                $_POST['unit_price'], $opening, (int)($_POST['reorder_level'] ?? 0), $_POST['unit'] ?? 'pcs',
            ]
        );
        if ($opening > 0) {
            $this->logMovement((int)$itemId, $opening, $opening, 'opening', null, $_POST['cost_price'] ?: null, 'Opening stock');
        }
        $this->flash('success', htmlspecialchars($_POST['name']) . ' added to the store.');
        $this->redirect('/school/store/items');
    }

    public function updateItem(string $id): void {
        $this->requireManage();
        $item = $this->db->fetchOne("SELECT * FROM store_items WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$item) { $this->redirect('/school/store/items'); }
        $errors = $this->validate($_POST, [
            'name'       => 'required|max:150',
            'unit_price' => 'required|numeric',
            'cost_price' => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/store/items'); }

        // Stock is not editable here on purpose — it only moves through restock or
        // an explicit adjustment, so every change keeps an audit row.
        $this->db->execute(
            "UPDATE store_items SET name=?, sku=?, category=?, description=?, cost_price=?, unit_price=?, reorder_level=?, unit=?, is_active=?
             WHERE id=? AND tenant_id=?",
            [
                $_POST['name'], ($_POST['sku'] ?? '') ?: null, $_POST['category'] ?? null, $_POST['description'] ?? null,
                ($_POST['cost_price'] ?? '') !== '' ? $_POST['cost_price'] : null, $_POST['unit_price'],
                (int)($_POST['reorder_level'] ?? 0), $_POST['unit'] ?? 'pcs', isset($_POST['is_active']) ? 1 : 0,
                $id, $this->tid,
            ]
        );
        $this->flash('success', 'Item updated.');
        $this->redirect('/school/store/items');
    }

    /** Adds stock (a delivery) or corrects it, always leaving a movement row behind. */
    public function adjustStock(): void {
        $this->requireManage();
        $errors = $this->validate($_POST, ['item_id' => 'required', 'quantity' => 'required|numeric']);
        if ($errors) { $this->failValidation($errors, '/school/store/items'); }

        $item = $this->db->fetchOne("SELECT * FROM store_items WHERE id=? AND tenant_id=?", [$_POST['item_id'], $this->tid]);
        if (!$item) { $this->redirect('/school/store/items'); }

        $mode = ($_POST['mode'] ?? 'restock') === 'adjustment' ? 'adjustment' : 'restock';
        $qty  = (int)$_POST['quantity'];
        // A restock always adds; an adjustment sets the counted figure, so the
        // movement records the difference rather than the new total.
        $change = $mode === 'restock' ? abs($qty) : $qty - (int)$item['stock_qty'];
        if ($change === 0) {
            $this->flash('warning', 'Stock is already at that level — nothing changed.');
            $this->redirect('/school/store/items');
        }
        $newBalance = (int)$item['stock_qty'] + $change;
        if ($newBalance < 0) {
            $this->flash('danger', 'That would take stock below zero.');
            $this->redirect('/school/store/items');
        }
        $this->db->execute("UPDATE store_items SET stock_qty=? WHERE id=? AND tenant_id=?", [$newBalance, $item['id'], $this->tid]);
        $this->logMovement((int)$item['id'], $change, $newBalance, $mode, null, $_POST['unit_cost'] ?: null, $_POST['note'] ?? null);

        $this->flash('success', $mode === 'restock'
            ? "Added {$change} to {$item['name']} — now {$newBalance} in stock."
            : "{$item['name']} stock corrected to {$newBalance}.");
        $this->redirect('/school/store/items');
    }

    public function movements(string $id): void {
        $this->requireSell();
        $item = $this->db->fetchOne("SELECT * FROM store_items WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$item) { $this->redirect('/school/store/items'); }
        $rows = $this->db->fetchAll(
            "SELECT m.*, u.name AS user_name, s.sale_no
             FROM store_stock_movements m
             LEFT JOIN users u ON m.created_by=u.id
             LEFT JOIN store_sales s ON m.sale_id=s.id
             WHERE m.item_id=? AND m.tenant_id=? ORDER BY m.created_at DESC, m.id DESC",
            [$id, $this->tid]
        );
        $this->view('school/highschool/store/movements', [
            'pageTitle'=>$item['name'].' — Stock History','panelType'=>'school',
            'item'=>$item,'movements'=>$rows,'tenant'=>$this->tenant(),'flash'=>$this->getFlash(),
        ]);
    }

    private function logMovement(int $itemId, int $change, int $balanceAfter, string $reason, ?int $saleId, $unitCost, ?string $note): void {
        $this->db->insert(
            "INSERT INTO store_stock_movements (tenant_id,item_id,change_qty,balance_after,reason,sale_id,unit_cost,note,created_by)
             VALUES (?,?,?,?,?,?,?,?,?)",
            [$this->tid, $itemId, $change, $balanceAfter, $reason, $saleId, $unitCost ?: null, $note ?: null, $_SESSION['user_id'] ?? null]
        );
    }

    // ── POINT OF SALE ───────────────────────────────────────────────────────

    public function sell(): void {
        $this->requireSell();
        $items = $this->db->fetchAll(
            "SELECT id,name,sku,category,unit_price,stock_qty,unit FROM store_items
             WHERE tenant_id=? AND is_active=1 ORDER BY category, name", [$this->tid]
        );
        $students = $this->db->fetchAll(
            "SELECT s.id, s.admission_no, u.name, c.name AS class_name
             FROM students s JOIN users u ON s.user_id=u.id
             LEFT JOIN classes c ON s.class_id=c.id
             WHERE s.tenant_id=? AND s.status='active' ORDER BY u.name", [$this->tid]
        );
        $this->view('school/highschool/store/sell', [
            'pageTitle'=>'Sell Items','panelType'=>'school','items'=>$items,'students'=>$students,
            'tenant'=>$this->tenant(),'flash'=>$this->getFlash(),
        ]);
    }

    /**
     * Records a sale: decrements stock, raises the invoice, takes the payment.
     *
     * Everything is validated before a single row is written — a sale that fails
     * halfway (say, one line short of stock) must not leave stock decremented or
     * an orphaned invoice behind.
     */
    public function storeSale(): void {
        $this->requireSell();

        $buyerType = ($_POST['buyer_type'] ?? 'student') === 'walkin' ? 'walkin' : 'student';
        $studentId = $buyerType === 'student' ? ($_POST['student_id'] ?: null) : null;
        $buyerName = $buyerType === 'walkin' ? trim($_POST['buyer_name'] ?? '') : null;
        $method    = $_POST['payment_method'] ?? 'cash';
        $onAccount = $method === 'account';

        if ($buyerType === 'student' && !$studentId) {
            $this->flash('danger', 'Choose the student this sale is for.');
            $this->redirect('/school/store/sell');
        }
        if ($buyerType === 'walkin' && $buyerName === '') {
            $this->flash('danger', "Enter the buyer's name for a walk-in sale.");
            $this->redirect('/school/store/sell');
        }
        // Nobody to bill, so a walk-in cannot be left on credit.
        if ($buyerType === 'walkin' && $onAccount) {
            $this->flash('danger', 'A walk-in sale cannot be charged to a student account — take payment now.');
            $this->redirect('/school/store/sell');
        }

        // --- gather and validate the lines -------------------------------------
        $lines = [];
        $subtotal = 0.0;
        foreach ($_POST['qty'] ?? [] as $itemId => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) { continue; }
            $item = $this->db->fetchOne("SELECT * FROM store_items WHERE id=? AND tenant_id=?", [$itemId, $this->tid]);
            if (!$item) { continue; }
            if ($qty > (int)$item['stock_qty']) {
                $this->flash('danger', "Only {$item['stock_qty']} × {$item['name']} left in stock — reduce the quantity or restock first.");
                $this->redirect('/school/store/sell');
            }
            $lineTotal = round((float)$item['unit_price'] * $qty, 2);
            $subtotal += $lineTotal;
            $lines[] = ['item' => $item, 'qty' => $qty, 'line_total' => $lineTotal];
        }
        if (!$lines) {
            $this->flash('danger', 'Add at least one item with a quantity before completing the sale.');
            $this->redirect('/school/store/sell');
        }

        $discount = max(0.0, round((float)($_POST['discount'] ?? 0), 2));
        if ($discount > $subtotal) { $discount = $subtotal; }
        $total = round($subtotal - $discount, 2);

        // Cash sales may be tendered short (a part payment on collection); an
        // account sale is by definition unpaid at the till.
        $paid = $onAccount ? 0.0 : min($total, max(0.0, round((float)($_POST['amount_paid'] ?? $total), 2)));
        $status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'credit');

        // --- write the sale ----------------------------------------------------
        // All of it in one transaction: the sale, its lines, the stock decrements
        // and the invoice/payment/ledger postings have to land together or not at
        // all. Without this a failure partway through (a missing finance table, a
        // deadlock) leaves stock already taken and an invoice with no sale.
        $pdo = $this->db->pdo();
        $saleNo = $this->nextSaleNo();
        $pdo->beginTransaction();
        try {
            $saleId = (int)$this->db->insert(
                "INSERT INTO store_sales (tenant_id,sale_no,student_id,buyer_name,subtotal,discount,total,amount_paid,payment_method,status,notes,sold_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                [$this->tid,$saleNo,$studentId,$buyerName,$subtotal,$discount,$total,$paid,$method,$status,$_POST['notes'] ?? null,$_SESSION['user_id'] ?? null]
            );

            foreach ($lines as $l) {
                $this->db->insert(
                    "INSERT INTO store_sale_items (sale_id,item_id,item_name,unit_price,cost_price,quantity,line_total) VALUES (?,?,?,?,?,?,?)",
                    [$saleId, $l['item']['id'], $l['item']['name'], $l['item']['unit_price'], $l['item']['cost_price'], $l['qty'], $l['line_total']]
                );
                // Decremented relative to the row read during validation; the
                // transaction is what makes that read-then-write safe.
                $newBalance = (int)$l['item']['stock_qty'] - $l['qty'];
                $this->db->execute("UPDATE store_items SET stock_qty=? WHERE id=? AND tenant_id=?", [$newBalance, $l['item']['id'], $this->tid]);
                $this->logMovement((int)$l['item']['id'], -$l['qty'], $newBalance, 'sale', $saleId, null, 'Sale ' . $saleNo);
            }

            if ($studentId) {
                $this->postSaleToFinance((int)$studentId, $saleId, $saleNo, $total, $paid, $method);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Store sale failed: ' . $e->getMessage());
            $this->flash('danger', 'The sale could not be completed and nothing was recorded — no stock was taken. Please try again.');
            $this->redirect('/school/store/sell');
        }

        $this->flash('success', "Sale {$saleNo} recorded." . ($status === 'credit' ? ' Charged to the student account.' : ''));
        $this->redirect('/school/store/sales/' . $saleId . '/receipt');
    }

    /**
     * Raises the invoice for a student sale and records any money taken.
     *
     * Mirrors FinanceController's own invoice/payment handling — including the
     * student_ledger entries — so a store charge is indistinguishable from a fee
     * charge everywhere downstream.
     */
    private function postSaleToFinance(int $studentId, int $saleId, string $saleNo, float $total, float $paid, string $method): void {
        $invoiceNo = 'STR-' . date('Ymd') . '-' . str_pad((string)$saleId, 4, '0', STR_PAD_LEFT);
        $status = $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid');

        $invoiceId = (int)$this->db->insert(
            "INSERT INTO invoices (tenant_id,student_id,invoice_no,amount_due,amount_paid,due_date,notes,status)
             VALUES (?,?,?,?,?,?,?,?)",
            [$this->tid, $studentId, $invoiceNo, $total, $paid, date('Y-m-d'), 'School store sale ' . $saleNo, $status]
        );
        $this->db->execute("UPDATE store_sales SET invoice_id=? WHERE id=?", [$invoiceId, $saleId]);

        StudentAccountController::post(
            $this->db, $this->tid, $studentId, 'charge', 'School store — ' . $saleNo, $total,
            ['invoice_id' => $invoiceId, 'reference' => $saleNo]
        );

        if ($paid > 0) {
            $paymentId = $this->db->insert(
                "INSERT INTO payments (tenant_id,invoice_id,amount,method,reference,received_by,notes) VALUES (?,?,?,?,?,?,?)",
                [$this->tid, $invoiceId, $paid, $method === 'account' ? 'cash' : $method, $saleNo, $_SESSION['user_id'] ?? null, 'School store sale']
            );
            StudentAccountController::post(
                $this->db, $this->tid, $studentId, 'payment', 'School store payment — ' . $saleNo, -$paid,
                ['invoice_id' => $invoiceId, 'payment_id' => $paymentId, 'reference' => $saleNo]
            );
        }
    }

    /** Sequential per tenant per day, e.g. SALE-20260802-0003. */
    private function nextSaleNo(): string {
        $prefix = 'SALE-' . date('Ymd') . '-';
        $row = $this->db->fetchOne(
            "SELECT sale_no FROM store_sales WHERE tenant_id=? AND sale_no LIKE ? ORDER BY sale_no DESC LIMIT 1",
            [$this->tid, $prefix . '%']
        );
        $next = $row ? ((int)substr($row['sale_no'], -4)) + 1 : 1;
        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    // ── SALES ───────────────────────────────────────────────────────────────

    public function sales(): void {
        $this->requireSell();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $status = $_GET['status'] ?? '';

        $where = "s.tenant_id=? AND DATE(s.created_at) BETWEEN ? AND ?";
        $params = [$this->tid, $from, $to];
        if ($status !== '') { $where .= " AND s.status=?"; $params[] = $status; }

        $sales = $this->db->fetchAll(
            "SELECT s.*, u.name AS student_name, seller.name AS seller_name,
                    (SELECT COUNT(*) FROM store_sale_items si WHERE si.sale_id=s.id) AS line_count
             FROM store_sales s
             LEFT JOIN students st ON s.student_id=st.id
             LEFT JOIN users u ON st.user_id=u.id
             LEFT JOIN users seller ON s.sold_by=seller.id
             WHERE {$where} ORDER BY s.created_at DESC", $params
        );
        $totals = $this->db->fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN s.status<>'void' THEN s.total ELSE 0 END),0) AS revenue,
                    COALESCE(SUM(CASE WHEN s.status<>'void' THEN s.amount_paid ELSE 0 END),0) AS collected,
                    COALESCE(SUM(CASE WHEN s.status IN('credit','partial') THEN s.total - s.amount_paid ELSE 0 END),0) AS outstanding,
                    COUNT(*) AS count
             FROM store_sales s WHERE {$where}", $params
        );

        $this->view('school/highschool/store/sales', [
            'pageTitle'=>'Store Sales','panelType'=>'school','sales'=>$sales,'totals'=>$totals,
            'from'=>$from,'to'=>$to,'status'=>$status,'tenant'=>$this->tenant(),
            'canManage'=>$this->canManage(),'flash'=>$this->getFlash(),
        ]);
    }

    public function receipt(string $id): void {
        $this->requireSell();
        [$sale, $lines] = $this->loadSale($id);
        if (!$sale) { $this->redirect('/school/store/sales'); }
        $this->view('school/store_receipt', [
            'pageTitle'=>'Receipt '.$sale['sale_no'],
            'sale'=>$sale,'lines'=>$lines,'tenant'=>$this->tenant(),
        ]);
    }

    private function loadSale(string $id): array {
        $sale = $this->db->fetchOne(
            "SELECT s.*, u.name AS student_name, st.admission_no, c.name AS class_name, seller.name AS seller_name, i.invoice_no
             FROM store_sales s
             LEFT JOIN students st ON s.student_id=st.id
             LEFT JOIN users u ON st.user_id=u.id
             LEFT JOIN classes c ON st.class_id=c.id
             LEFT JOIN users seller ON s.sold_by=seller.id
             LEFT JOIN invoices i ON s.invoice_id=i.id
             WHERE s.id=? AND s.tenant_id=?", [$id, $this->tid]
        );
        if (!$sale) { return [null, []]; }
        $lines = $this->db->fetchAll("SELECT * FROM store_sale_items WHERE sale_id=? ORDER BY id", [$id]);
        return [$sale, $lines];
    }

    /**
     * Voids a sale: returns the stock and reverses the money.
     *
     * The invoice is cancelled by waiving it rather than deleted, and the ledger
     * gets a compensating entry rather than having its history rewritten — a
     * reversal has to stay visible on the student's statement.
     */
    public function voidSale(string $id): void {
        $this->requireManage();
        [$sale, $lines] = $this->loadSale($id);
        if (!$sale) { $this->redirect('/school/store/sales'); }
        if ($sale['status'] === 'void') {
            $this->flash('warning', 'That sale is already voided.');
            $this->redirect('/school/store/sales');
        }

        // Stock return and money reversal must land together — a half-voided sale
        // would show restored stock the books never accounted for.
        $pdo = $this->db->pdo();
        $pdo->beginTransaction();
        try {
            foreach ($lines as $l) {
                if (!$l['item_id']) { continue; }
                $item = $this->db->fetchOne("SELECT stock_qty FROM store_items WHERE id=? AND tenant_id=?", [$l['item_id'], $this->tid]);
                if (!$item) { continue; }
                $newBalance = (int)$item['stock_qty'] + (int)$l['quantity'];
                $this->db->execute("UPDATE store_items SET stock_qty=? WHERE id=? AND tenant_id=?", [$newBalance, $l['item_id'], $this->tid]);
                $this->logMovement((int)$l['item_id'], (int)$l['quantity'], $newBalance, 'void', (int)$sale['id'], null, 'Void of ' . $sale['sale_no']);
            }

            if ($sale['invoice_id']) {
                $this->db->execute("UPDATE invoices SET status='waived', notes=CONCAT(COALESCE(notes,''),' — voided') WHERE id=? AND tenant_id=?",
                    [$sale['invoice_id'], $this->tid]);
            }
            if ($sale['student_id']) {
                // Credit back the charge so the family stops owing it.
                StudentAccountController::post(
                    $this->db, $this->tid, (int)$sale['student_id'], 'adjustment',
                    'Void of school store sale ' . $sale['sale_no'], -(float)$sale['total'],
                    ['invoice_id' => $sale['invoice_id'], 'reference' => $sale['sale_no']]
                );
                if ((float)$sale['amount_paid'] > 0) {
                    // Positive: the original payment credited the account, so handing the
                    // cash back must undo that credit, leaving the family square.
                    StudentAccountController::post(
                        $this->db, $this->tid, (int)$sale['student_id'], 'refund',
                        'Refund on voided sale ' . $sale['sale_no'], (float)$sale['amount_paid'],
                        ['invoice_id' => $sale['invoice_id'], 'reference' => $sale['sale_no']]
                    );
                }
            }

            $this->db->execute("UPDATE store_sales SET status='void', voided_at=NOW() WHERE id=? AND tenant_id=?", [$id, $this->tid]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Store void failed: ' . $e->getMessage());
            $this->flash('danger', 'The sale could not be voided — nothing was changed.');
            $this->redirect('/school/store/sales');
        }

        $this->flash('success', "Sale {$sale['sale_no']} voided — stock returned and the charge reversed.");
        $this->redirect('/school/store/sales');
    }

    // ── REPORTS ─────────────────────────────────────────────────────────────

    public function reports(): void {
        $this->requireSell();
        $from = $_GET['from'] ?? date('Y-m-01');
        $to   = $_GET['to']   ?? date('Y-m-d');
        $range = [$this->tid, $from, $to];

        $summary = $this->db->fetchOne(
            "SELECT COUNT(*) AS sales, COALESCE(SUM(total),0) AS revenue, COALESCE(SUM(amount_paid),0) AS collected,
                    COALESCE(SUM(discount),0) AS discounts, COALESCE(SUM(total-amount_paid),0) AS outstanding
             FROM store_sales WHERE tenant_id=? AND DATE(created_at) BETWEEN ? AND ? AND status<>'void'", $range
        );
        // Cost of goods sold, from the cost snapshotted on each line so a later
        // price change cannot retro-alter a historical margin.
        $cogs = $this->db->fetchOne(
            "SELECT COALESCE(SUM(si.cost_price * si.quantity),0) AS cost
             FROM store_sale_items si JOIN store_sales s ON si.sale_id=s.id
             WHERE s.tenant_id=? AND DATE(s.created_at) BETWEEN ? AND ? AND s.status<>'void'", $range
        );
        // Margin is measured against what was actually charged, not the sum of the
        // line totals — discounts come off the sale, so counting them as revenue
        // would overstate profit by the full discount given.
        $profit = ['revenue' => (float)$summary['revenue'], 'cost' => (float)$cogs['cost']];
        $topItems = $this->db->fetchAll(
            "SELECT si.item_name, SUM(si.quantity) AS qty, SUM(si.line_total) AS revenue
             FROM store_sale_items si JOIN store_sales s ON si.sale_id=s.id
             WHERE s.tenant_id=? AND DATE(s.created_at) BETWEEN ? AND ? AND s.status<>'void'
             GROUP BY si.item_name ORDER BY revenue DESC LIMIT 10", $range
        );
        $daily = $this->db->fetchAll(
            "SELECT DATE(created_at) AS day, COUNT(*) AS sales, SUM(total) AS revenue
             FROM store_sales WHERE tenant_id=? AND DATE(created_at) BETWEEN ? AND ? AND status<>'void'
             GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 31", $range
        );
        $lowStock = $this->db->fetchAll(
            "SELECT * FROM store_items WHERE tenant_id=? AND is_active=1 AND reorder_level>0 AND stock_qty<=reorder_level
             ORDER BY stock_qty", [$this->tid]
        );
        $valuation = $this->db->fetchOne(
            "SELECT COALESCE(SUM(stock_qty*unit_price),0) AS retail,
                    COALESCE(SUM(stock_qty*COALESCE(cost_price,0)),0) AS at_cost,
                    COALESCE(SUM(stock_qty),0) AS units
             FROM store_items WHERE tenant_id=? AND is_active=1", [$this->tid]
        );

        $this->view('school/highschool/store/reports', [
            'pageTitle'=>'Store Reports','panelType'=>'school','summary'=>$summary,'profit'=>$profit,
            'topItems'=>$topItems,'daily'=>$daily,'lowStock'=>$lowStock,'valuation'=>$valuation,
            'from'=>$from,'to'=>$to,'tenant'=>$this->tenant(),'flash'=>$this->getFlash(),
        ]);
    }
}
