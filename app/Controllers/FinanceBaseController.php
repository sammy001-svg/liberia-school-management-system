<?php
require_once ROOT_DIR . '/core/Controller.php';
require_once ROOT_DIR . '/app/Services/Finance.php';

/**
 * Shared plumbing for the financial system's screens: the school, its finance settings,
 * the selected academic year and the lookups nearly every finance page needs.
 */
abstract class FinanceBaseController extends Controller {
    protected int $tid;
    protected array $settings = [];

    public function __construct() {
        parent::__construct();
        $this->tid = $this->tenantId() ?? 0;
        Finance::bootTenant($this->db, $this->tid);
        $this->settings = Finance::settings($this->db, $this->tid);
    }

    /** Finance staff: full finance, or the accounts clerk role that takes payments. */
    protected function guard(array $perms = ['finance.manage', 'finance.accounts']): void {
        $this->requirePermission($perms);
    }

    protected function years(): array {
        return $this->db->fetchAll("SELECT id, name, start_date, end_date, is_current FROM academic_years WHERE tenant_id=? ORDER BY start_date DESC", [$this->tid]);
    }

    /** The year picked in ?year=, else the current year, else the most recent one. */
    protected function selectedYear(): ?array {
        $years = $this->years();
        $want = (int)($_GET['year'] ?? $_POST['academic_year_id'] ?? 0);
        foreach ($years as $y) { if ((int)$y['id'] === $want) { return $y; } }
        foreach ($years as $y) { if ((int)$y['is_current']) { return $y; } }
        return $years[0] ?? null;
    }

    protected function classes(): array {
        return $this->db->fetchAll("SELECT id, name FROM classes WHERE tenant_id=? ORDER BY name", [$this->tid]);
    }

    protected function studentTypes(bool $activeOnly = true): array {
        return $this->db->fetchAll("SELECT id, name, is_active FROM student_types WHERE tenant_id=?" . ($activeOnly ? " AND is_active=1" : '') . " ORDER BY sort_order, name", [$this->tid]);
    }

    protected function categories(string $kind): array {
        return array_column($this->db->fetchAll("SELECT name FROM finance_categories WHERE tenant_id=? AND kind=? AND is_active=1 ORDER BY name", [$this->tid, $kind]), 'name');
    }

    protected function currencies(): array {
        return Finance::currencies($this->settings);
    }

    protected function financeView(string $view, array $data): void {
        $this->view('school/highschool/finance/' . $view, $data + [
            'panelType' => 'school',
            'tenant' => $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$this->tid]),
            'finSettings' => $this->settings,
            'currencies' => $this->currencies(),
            'canApprove' => Finance::canApprove(),
            'flash' => $this->getFlash(),
        ]);
    }

    /** Stores an optional receipt/proof upload (image or PDF) and returns its URL. */
    protected function storeProof(string $field, array &$errors): ?string {
        if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) { return null; }
        $ext = strtolower(pathinfo((string)$_FILES[$field]['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 5 * 1024 * 1024) {
                $errors[$field] = 'The receipt file must be a PDF or image under 5MB.';
                return null;
            }
            $head = file_get_contents($_FILES[$field]['tmp_name'], false, null, 0, 5);
            if ($head !== '%PDF-') { $errors[$field] = 'That file is not a valid PDF.'; return null; }
            $dir = ROOT_DIR . '/public/uploads/receipts/';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) { $errors[$field] = 'Could not prepare the upload folder.'; return null; }
            $name = bin2hex(random_bytes(8)) . '.pdf';
            if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dir . $name)) { $errors[$field] = 'Could not save the receipt file.'; return null; }
            $cfg = require ROOT_DIR . '/config/app.php';
            return rtrim($cfg['url'], '/') . '/uploads/receipts/' . $name;
        }
        return $this->handleImageUpload($field, 'receipts', $errors, 5 * 1024 * 1024);
    }

    /** Accepts only a currency this school works in. */
    protected function currencyOrDefault(?string $cur): string {
        return in_array($cur, $this->currencies(), true) ? $cur : $this->settings['default_currency'];
    }
}
