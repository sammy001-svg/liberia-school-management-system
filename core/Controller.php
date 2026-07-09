<?php
require_once dirname(__DIR__) . '/core/Database.php';

abstract class Controller {
    protected Database $db;
    protected array $data = [];

    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    protected function startSession(): void {
        $cfg = require dirname(__DIR__) . '/config/app.php';
        if (session_status() === PHP_SESSION_NONE) {
            session_name($cfg['session_name']);
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? null) == 443
                || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    protected function requireAuth(array $allowedRoles = []): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
            $this->redirect('/unauthorized');
        }
    }

    protected function requireSchoolAdmin(): void {
        $this->requireAuth(['School Admin']);
    }

    protected function view(string $viewPath, array $data = []): void {
        // Automatically inject tenant data if it exists in session but not in $data
        if (!isset($data['tenant']) && isset($_SESSION['tenant_id'])) {
            $data['tenant'] = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$_SESSION['tenant_id']]);
        }
        $data['csrf_token'] = $this->csrfToken();

        extract($data);
        $viewFile = dirname(__DIR__) . "/app/Views/{$viewPath}.php";
        if (!file_exists($viewFile)) {
            die("View not found: {$viewPath}");
        }
        require $viewFile;
    }

    protected function redirect(string $url): never {
        $cfg  = require dirname(__DIR__) . '/config/app.php';
        $base = rtrim($cfg['url'], '/');
        if ($this->isAjax()) {
            $flash = $_SESSION['flash'] ?? null;
            unset($_SESSION['flash']);
            $this->json(['redirect' => $base . $url, 'flash' => $flash]);
        }
        header("Location: {$base}{$url}");
        exit;
    }

    protected function isAjax(): bool {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    protected function json(mixed $data, int $status = 200): never {
        if (ob_get_level()) { ob_end_clean(); }
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function flash(string $type, string $message): void {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    protected function getFlash(): ?array {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    protected function currentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    protected function tenantId(): ?int {
        return $_SESSION['tenant_id'] ?? null;
    }

    /**
     * Validate $_POST-style data against simple pipe-delimited rules, e.g.
     * ['email' => 'required|email', 'name' => 'required|max:150'].
     * Returns an assoc array of field => first error message (empty if valid).
     */
    protected function validate(array $data, array $rules): array {
        $errors = [];
        foreach ($rules as $field => $ruleStr) {
            $label = ucfirst(str_replace('_', ' ', $field));
            $value = trim((string)($data[$field] ?? ''));
            foreach (explode('|', $ruleStr) as $rule) {
                if ($rule === '') continue;
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'required' && $value === '') {
                    $errors[$field] = "{$label} is required.";
                    break;
                }
                if ($value === '') continue; // remaining rules only apply when a value is present
                switch ($name) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) { $errors[$field] = 'Enter a valid email address.'; break 2; }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) { $errors[$field] = "{$label} must be a number."; break 2; }
                        break;
                    case 'integer':
                        if (!ctype_digit(ltrim($value, '-'))) { $errors[$field] = "{$label} must be a whole number."; break 2; }
                        break;
                    case 'min':
                        if (strlen($value) < (int)$param) { $errors[$field] = "{$label} must be at least {$param} characters."; break 2; }
                        break;
                    case 'max':
                        if (strlen($value) > (int)$param) { $errors[$field] = "{$label} must be at most {$param} characters."; break 2; }
                        break;
                    case 'date':
                        if (strtotime($value) === false) { $errors[$field] = "Enter a valid date for {$label}."; break 2; }
                        break;
                    case 'in':
                        if (!in_array($value, explode(',', (string)$param), true)) { $errors[$field] = "Invalid value for {$label}."; break 2; }
                        break;
                }
            }
        }
        return $errors;
    }

    /**
     * Send validation errors back to the client: JSON for AJAX requests
     * (with per-field messages the modal JS renders inline), or a flash +
     * redirect for normal form posts.
     */
    protected function failValidation(array $errors, string $redirectUrl): never {
        if ($this->isAjax()) {
            $this->json(['error' => 'Please fix the highlighted fields and try again.', 'errors' => $errors], 422);
        }
        $_SESSION['flash'] = ['type' => 'danger', 'message' => implode(' ', $errors)];
        $this->redirect($redirectUrl);
    }

    /**
     * Parse an uploaded CSV file into an array of associative rows, keyed
     * by lowercased header column names. Returns [] if no file was uploaded.
     */
    protected function parseCsvUpload(string $fieldName): array {
        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            return [];
        }
        $rows = [];
        $handle = fopen($_FILES[$fieldName]['tmp_name'], 'r');
        if ($handle !== false) {
            // Strip a UTF-8 BOM if present (Excel's "CSV UTF-8" export adds one silently) —
            // otherwise it sticks to the first cell of whatever line is read first and can
            // make a genuinely blank line look non-empty to the check below.
            if (fread($handle, 3) !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            // Skip any fully-blank leading lines before the real header row
            // (common artifact of exports from Excel/Google Sheets/school-system CSVs).
            $header = false;
            while (($line = fgetcsv($handle)) !== false) {
                $nonEmpty = array_filter($line, fn($v) => trim((string)$v) !== '');
                if (!empty($nonEmpty)) { $header = $line; break; }
            }
            if ($header) {
                $header = array_map(fn($h) => strtolower(trim((string)$h)), $header);
                while (($data = fgetcsv($handle)) !== false) {
                    if (count($data) === 1 && trim((string)$data[0]) === '') continue;
                    $row = [];
                    foreach ($header as $i => $key) { $row[$key] = trim((string)($data[$i] ?? '')); }
                    $rows[] = $row;
                }
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * Validates and stores an uploaded image under public/uploads/{subdir}/ so it's
     * always web-servable regardless of document-root strategy, then returns its
     * public URL. Returns null (and does nothing) if no file was actually chosen —
     * that's normal, not an error. On validation failure, appends a message to
     * $errors[$fieldName] and returns null so the caller can failValidation() as usual.
     * Validates real image content via getimagesize() rather than trusting the
     * client-supplied MIME type, and doesn't depend on the fileinfo extension.
     */
    protected function handleImageUpload(string $fieldName, string $subdir, array &$errors, int $maxBytes = 2097152): ?string {
        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errors[$fieldName] = 'Upload failed. Please try again.';
            return null;
        }
        if ($_FILES[$fieldName]['size'] > $maxBytes) {
            $errors[$fieldName] = 'Image must be smaller than ' . round($maxBytes / 1024 / 1024, 1) . 'MB.';
            return null;
        }
        $tmpPath = $_FILES[$fieldName]['tmp_name'];
        $origExt = strtolower(pathinfo((string)$_FILES[$fieldName]['name'], PATHINFO_EXTENSION));

        if ($origExt === 'svg') {
            $content = file_get_contents($tmpPath, false, null, 0, 4096);
            if ($content === false || stripos($content, '<svg') === false) {
                $errors[$fieldName] = 'That does not look like a valid SVG file.';
                return null;
            }
            $ext = 'svg';
        } else {
            $imageInfo = @getimagesize($tmpPath);
            $allowedTypes = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
            if (!$imageInfo || !isset($allowedTypes[$imageInfo[2]])) {
                $errors[$fieldName] = 'Only JPG, PNG, WEBP, GIF or SVG images are allowed.';
                return null;
            }
            $ext = $allowedTypes[$imageInfo[2]];
        }

        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $destDir = dirname(__DIR__) . "/public/uploads/{$subdir}/";
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            $errors[$fieldName] = 'Could not prepare the upload folder.';
            return null;
        }
        if (!move_uploaded_file($tmpPath, $destDir . $filename)) {
            $errors[$fieldName] = 'Could not save the uploaded image.';
            return null;
        }
        $cfg = require dirname(__DIR__) . '/config/app.php';
        return rtrim($cfg['url'], '/') . "/uploads/{$subdir}/{$filename}";
    }

    /** Stream a downloadable CSV template with the given headers and an optional example row. */
    protected function downloadCsvTemplate(string $filename, array $headers, array $exampleRow = []): never {
        if (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        if ($exampleRow) { fputcsv($out, $exampleRow); }
        fclose($out);
        exit;
    }

    /** Stream a downloadable CSV export with the given headers and data rows (each row an assoc array or list matching $headers order). */
    protected function downloadCsv(string $filename, array $headers, array $rows): never {
        if (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ($rows as $row) { fputcsv($out, array_is_list($row) ? $row : array_values($row)); }
        fclose($out);
        exit;
    }

    /**
     * Flash a summary of a bulk import (X of Y rows imported), stash any
     * per-row error details for display on the next page, then redirect.
     */
    protected function finishBulkImport(int $successCount, int $totalRows, array $rowErrors, string $redirectUrl): never {
        if ($totalRows === 0) {
            $this->flash('danger', 'No rows found in the uploaded file.');
            $this->redirect($redirectUrl);
        }
        $type = empty($rowErrors) ? 'success' : ($successCount > 0 ? 'warning' : 'danger');
        $message = "Imported {$successCount} of {$totalRows} row(s).";
        if ($rowErrors) {
            $message .= ' ' . count($rowErrors) . ' row(s) had errors — see details below.';
            $_SESSION['import_errors'] = $rowErrors;
        }
        $this->flash($type, $message);
        $this->redirect($redirectUrl);
    }

    protected function getImportErrors(): array {
        $errors = $_SESSION['import_errors'] ?? [];
        unset($_SESSION['import_errors']);
        return $errors;
    }

    /**
     * Pagination helper: given a total row count, works out the current
     * page (from $_GET['page']), page size, and SQL OFFSET.
     */
    protected function paginate(int $totalCount, int $perPage = 20): array {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $totalPages = max(1, (int)ceil($totalCount / $perPage));
        $page = min($page, $totalPages);
        return [
            'page'       => $page,
            'perPage'    => $perPage,
            'totalPages' => $totalPages,
            'total'      => $totalCount,
            'offset'     => ($page - 1) * $perPage,
        ];
    }
}
