<?php
require_once ROOT_DIR . '/core/Controller.php';

class InventoryController extends Controller {
    private int $tid;

    public function __construct() {
        parent::__construct();
        $this->requirePermission(['inventory.manage']);
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
        $errors = $this->validate($_POST, [
            'item_name'  => 'required|max:150',
            'quantity'   => 'numeric',
            'unit_price' => 'numeric',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/inventory'); }
        $this->db->insert(
            "INSERT INTO inventory (tenant_id, item_name, category, quantity, unit, location, supplier, unit_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$this->tid, $_POST['item_name'], $_POST['category'], $_POST['quantity'], $_POST['unit'], $_POST['location'], $_POST['supplier'] ?? '', $_POST['unit_price'] ?: null]
        );
        $this->flash('success', 'Item added to inventory.');
        $this->redirect('/school/inventory');
    }

    // --- LIBRARY ---
    public function library(): void {
        $books = $this->db->fetchAll("SELECT * FROM library_books WHERE tenant_id = ?", [$this->tid]);
        $stats = [
            'total' => count($books),
            'available' => count(array_filter($books, fn($b) => $b['status'] === 'available')),
            'issued' => count(array_filter($books, fn($b) => $b['status'] === 'issued')),
            'lostDamaged' => count(array_filter($books, fn($b) => in_array($b['status'], ['lost','damaged'], true))),
        ];
        $this->view('school/inventory/library/index', [
            'pageTitle' => 'Library Books',
            'panelType' => 'school',
            'books' => $books,
            'stats' => $stats,
            'flash' => $this->getFlash(),
            'importErrors' => $this->getImportErrors(),
        ]);
    }

    public function storeBook(): void {
        $errors = $this->validate($_POST, ['title' => 'required|max:255']);
        if ($errors) { $this->failValidation($errors, '/school/library'); }
        $this->db->insert(
            "INSERT INTO library_books (tenant_id, title, author, isbn, category, status) VALUES (?, ?, ?, ?, ?, 'available')",
            [$this->tid, $_POST['title'], $_POST['author'] ?? '', $_POST['isbn'] ?? '', $_POST['category'] ?? '']
        );
        $this->flash('success', 'Book added to library catalog.');
        $this->redirect('/school/library');
    }

    public function bulkTemplateBooks(): void {
        $this->downloadCsvTemplate('library_books_template.csv',
            ['title','author','isbn','category'],
            ['Things Fall Apart','Chinua Achebe','978-0435905255','Fiction']
        );
    }

    public function bulkUploadBooks(): void {
        $rows = $this->parseCsvUpload('csv_file');
        $success = 0;
        $rowErrors = [];
        foreach ($rows as $i => $row) {
            $line = $i + 2;
            try {
                $title = $row['title'] ?? '';
                if ($title === '') {
                    $rowErrors[] = "Row {$line}: title is required.";
                    continue;
                }
                $this->db->insert(
                    "INSERT INTO library_books (tenant_id, title, author, isbn, category, status) VALUES (?, ?, ?, ?, ?, 'available')",
                    [$this->tid, $title, $row['author'] ?? '', $row['isbn'] ?? '', $row['category'] ?? '']
                );
                $success++;
            } catch (\Throwable $e) {
                $rowErrors[] = "Row {$line}: could not be imported.";
            }
        }
        $this->finishBulkImport($success, count($rows), $rowErrors, '/school/library');
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
        $availableBooks = $this->db->fetchAll("SELECT id, title FROM library_books WHERE tenant_id = ? AND status = 'available' ORDER BY title", [$this->tid]);
        $borrowers = $this->db->fetchAll("SELECT id, name FROM users WHERE tenant_id = ? AND status = 'active' ORDER BY name", [$this->tid]);
        $stats = [
            'active' => count(array_filter($loans, fn($l) => !$l['returned_at'])),
            'overdue' => count(array_filter($loans, fn($l) => !$l['returned_at'] && strtotime($l['due_date']) < time())),
            'returned' => count(array_filter($loans, fn($l) => $l['returned_at'])),
        ];
        $this->view('school/inventory/library/loans', [
            'pageTitle' => 'Book Loans',
            'panelType' => 'school',
            'loans' => $loans,
            'availableBooks' => $availableBooks,
            'borrowers' => $borrowers,
            'stats' => $stats,
            'flash' => $this->getFlash()
        ]);
    }

    public function issueBook(): void {
        $errors = $this->validate($_POST, [
            'book_id'  => 'required',
            'user_id'  => 'required',
            'due_date' => 'required|date',
        ]);
        if ($errors) { $this->failValidation($errors, '/school/library/loans'); }
        $book = $this->db->fetchOne("SELECT status FROM library_books WHERE id=? AND tenant_id=?", [$_POST['book_id'], $this->tid]);
        if (!$book || $book['status'] !== 'available') {
            $this->flash('danger', 'That book is not available for loan.');
            $this->redirect('/school/library/loans');
        }
        $this->db->insert(
            "INSERT INTO library_loans (tenant_id, book_id, user_id, due_date) VALUES (?, ?, ?, ?)",
            [$this->tid, $_POST['book_id'], $_POST['user_id'], $_POST['due_date']]
        );
        $this->db->execute("UPDATE library_books SET status = 'issued' WHERE id = ?", [$_POST['book_id']]);
        $this->flash('success', 'Book issued successfully.');
        $this->redirect('/school/library/loans');
    }

    public function returnBook(string $id): void {
        $loan = $this->db->fetchOne("SELECT * FROM library_loans WHERE id=? AND tenant_id=?", [$id, $this->tid]);
        if (!$loan) { $this->redirect('/school/library/loans'); }
        if ($loan['returned_at']) {
            $this->flash('danger', 'That loan has already been returned.');
            $this->redirect('/school/library/loans');
        }
        $this->db->execute("UPDATE library_loans SET returned_at=NOW() WHERE id=?", [$id]);
        $this->db->execute("UPDATE library_books SET status='available' WHERE id=?", [$loan['book_id']]);
        $this->flash('success', 'Book marked as returned.');
        $this->redirect('/school/library/loans');
    }
}
