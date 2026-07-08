<?php
require_once ROOT_DIR . '/core/Controller.php';

class InventoryController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requireAuth(['School Admin', 'Super Admin', 'Staff']);
        $this->tid = $this->tenantId() ?? 0;
    }

    public function index(): void {
        $items = $this->db->fetchAll("SELECT * FROM inventory WHERE tenant_id = ?", [$this->tid]);
        $this->view('school/inventory/index', [
            'pageTitle' => 'School Inventory',
            'panelType' => 'school',
            'items' => $items,
            'flash' => $this->getFlash()
        ]);
    }

    public function store(): void {
        $this->db->insert(
            "INSERT INTO inventory (tenant_id, item_name, category, quantity, unit, location) VALUES (?, ?, ?, ?, ?, ?)",
            [$this->tid, $_POST['item_name'], $_POST['category'], $_POST['quantity'], $_POST['unit'], $_POST['location']]
        );
        $this->flash('success', 'Item added to inventory.');
        $this->redirect('/school/inventory');
    }

    // --- LIBRARY ---
    public function library(): void {
        $books = $this->db->fetchAll("SELECT * FROM library_books WHERE tenant_id = ?", [$this->tid]);
        $this->view('school/inventory/library/index', [
            'pageTitle' => 'Library Books',
            'panelType' => 'school',
            'books' => $books,
            'flash' => $this->getFlash()
        ]);
    }

    public function loans(): void {
        $loans = $this->db->fetchAll(
            "SELECT l.*, b.title as book_title, u.name as user_name 
             FROM library_loans l 
             JOIN library_books b ON l.book_id = b.id 
             JOIN users u ON l.user_id = u.id 
             WHERE l.tenant_id = ? ORDER BY l.issued_at DESC", 
            [$this->tid]
        );
        $this->view('school/inventory/library/loans', [
            'pageTitle' => 'Book Loans',
            'panelType' => 'school',
            'loans' => $loans,
            'flash' => $this->getFlash()
        ]);
    }

    public function issueBook(): void {
        $this->db->insert(
            "INSERT INTO library_loans (tenant_id, book_id, user_id, due_date) VALUES (?, ?, ?, ?)",
            [$this->tid, $_POST['book_id'], $_POST['user_id'], $_POST['due_date']]
        );
        $this->db->execute("UPDATE library_books SET status = 'issued' WHERE id = ?", [$_POST['book_id']]);
        $this->flash('success', 'Book issued successfully.');
        $this->redirect('/school/library/loans');
    }
}
