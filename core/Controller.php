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

    protected function hasPermission(string $key): bool {
        return in_array($key, $_SESSION['permissions'] ?? [], true);
    }

    /** Permission-based equivalent of requireAuth(): redirects to /unauthorized unless the
     *  logged-in user's role has been granted at least one of the given "module.action" keys. */
    protected function requirePermission(string|array $keys): void {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        foreach ((array)$keys as $key) {
            if ($this->hasPermission($key)) {
                return;
            }
        }
        $this->redirect('/unauthorized');
    }

    protected function view(string $viewPath, array $data = []): void {
        // Automatically inject tenant data if it exists in session but not in $data
        if (!isset($data['tenant']) && isset($_SESSION['tenant_id'])) {
            $data['tenant'] = $this->db->fetchOne("SELECT * FROM tenants WHERE id=?", [$_SESSION['tenant_id']]);
        }
        $data['csrf_token'] = $this->csrfToken();
        $data['canManageRoles'] = $this->hasPermission('roles.manage');

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

    /**
     * Validates and stores an uploaded document/attachment (PDF, Office docs, images,
     * archives, etc.) under public/uploads/{subdir}/, keyed to a random filename so the
     * original name never collides on disk. Unlike handleImageUpload(), this only checks
     * the file extension against an allow-list (no fileinfo dependency) since arbitrary
     * document formats can't be sniffed the way raster images can via getimagesize().
     * Returns [publicUrl, originalFilename] or [null, null] if no file was chosen.
     * On validation failure, appends to $errors[$fieldName] and returns [null, null].
     */
    protected function handleFileUpload(string $fieldName, string $subdir, array &$errors, int $maxBytes = 10485760): array {
        if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
            $errors[$fieldName] = 'Upload failed. Please try again.';
            return [null, null];
        }
        if ($_FILES[$fieldName]['size'] > $maxBytes) {
            $errors[$fieldName] = 'File must be smaller than ' . round($maxBytes / 1024 / 1024, 1) . 'MB.';
            return [null, null];
        }
        $origName = (string)$_FILES[$fieldName]['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExt = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip','jpg','jpeg','png','gif','webp','mp4'];
        if (!in_array($ext, $allowedExt, true)) {
            $errors[$fieldName] = 'File type not allowed. Accepted: ' . strtoupper(implode(', ', $allowedExt)) . '.';
            return [null, null];
        }
        $tmpPath = $_FILES[$fieldName]['tmp_name'];
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $destDir = dirname(__DIR__) . "/public/uploads/{$subdir}/";
        if (!is_dir($destDir) && !mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            $errors[$fieldName] = 'Could not prepare the upload folder.';
            return [null, null];
        }
        if (!move_uploaded_file($tmpPath, $destDir . $filename)) {
            $errors[$fieldName] = 'Could not save the uploaded file.';
            return [null, null];
        }
        $cfg = require dirname(__DIR__) . '/config/app.php';
        $publicUrl = rtrim($cfg['url'], '/') . "/uploads/{$subdir}/{$filename}";
        return [$publicUrl, $origName];
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
     * Keeps teachers.class_id and classes.class_teacher_id in sync — both columns
     * denormalize the same "who is this class's homeroom teacher" relationship, one
     * class can have at most one homeroom teacher, and one teacher can be homeroom
     * of at most one class. Assigning $classId to $teacherId (or clearing it with
     * null) updates both sides and bumps whichever other teacher/class previously
     * held the slot being taken over.
     */
    protected function assignHomeroom(int $tenantId, int $teacherId, ?int $classId): void {
        if ($classId !== null) {
            $sql = "UPDATE classes SET class_teacher_id=NULL WHERE tenant_id=? AND class_teacher_id=? AND id!=?";
            $this->db->execute($sql, [$tenantId, $teacherId, $classId]);
            $this->db->execute("UPDATE teachers SET class_id=NULL WHERE tenant_id=? AND class_id=? AND id!=?", [$tenantId, $classId, $teacherId]);
            $this->db->execute("UPDATE classes SET class_teacher_id=? WHERE id=? AND tenant_id=?", [$teacherId, $classId, $tenantId]);
        } else {
            $this->db->execute("UPDATE classes SET class_teacher_id=NULL WHERE tenant_id=? AND class_teacher_id=?", [$tenantId, $teacherId]);
        }
        $this->db->execute("UPDATE teachers SET class_id=? WHERE id=? AND tenant_id=?", [$classId, $teacherId, $tenantId]);
    }

    /**
     * Builds the view-data for the shared report card (school/report_card view), used by
     * both the admin Grades module and the Parent portal ($publishedOnly hides draft exams).
     * Three modes, chosen from the query string:
     *   ?exam_id=N  — single exam (default: latest exam with grades)
     *   ?term_id=N  — one period/term: per-subject averages across that term's exams
     *   ?year_id=N  — annual: subject × period matrix for the academic year + yearly average
     */
    protected function buildReportCardData(string $studentId, array $student, bool $publishedOnly): array {
        $tid = $this->tenantId() ?? 0;
        $pub = $publishedOnly ? " AND e.status='published'" : "";
        $letterFor = fn(float $p) => $p>=90?'A+':($p>=80?'A':($p>=70?'B':($p>=60?'C':($p>=50?'D':'F'))));
        // An exam belongs to an academic year either directly or through its term.
        $yearOfExam = "COALESCE(e.academic_year_id, (SELECT t2.academic_year_id FROM terms t2 WHERE t2.id=e.term_id))";

        $examOptions = $this->db->fetchAll(
            "SELECT DISTINCT e.id, e.name, e.exam_date FROM grades g JOIN exams e ON g.exam_id=e.id
             WHERE g.student_id=? AND g.tenant_id=?{$pub} ORDER BY e.exam_date DESC",
            [$studentId, $tid]
        );
        $termOptions = $this->db->fetchAll(
            "SELECT DISTINCT t.id, t.name, t.start_date, ay.name AS year_name
             FROM grades g JOIN exams e ON g.exam_id=e.id JOIN terms t ON e.term_id=t.id
             LEFT JOIN academic_years ay ON t.academic_year_id=ay.id
             WHERE g.student_id=? AND g.tenant_id=?{$pub} ORDER BY t.start_date DESC",
            [$studentId, $tid]
        );
        $yearOptions = $this->db->fetchAll(
            "SELECT DISTINCT ay.id, ay.name FROM grades g JOIN exams e ON g.exam_id=e.id
             JOIN academic_years ay ON ay.id={$yearOfExam}
             WHERE g.student_id=? AND g.tenant_id=?{$pub} ORDER BY ay.start_date DESC",
            [$studentId, $tid]
        );

        $mode = 'exam'; $exam = null; $examId = null; $term = null; $year = null;
        if (!empty($_GET['term_id'])) {
            $mode = 'term';
            $term = $this->db->fetchOne("SELECT t.*, ay.name AS year_name FROM terms t LEFT JOIN academic_years ay ON t.academic_year_id=ay.id WHERE t.id=? AND t.tenant_id=?", [$_GET['term_id'], $tid]);
            if (!$term) { $mode = 'exam'; }
        } elseif (!empty($_GET['year_id'])) {
            $mode = 'annual';
            $year = $this->db->fetchOne("SELECT * FROM academic_years WHERE id=? AND tenant_id=?", [$_GET['year_id'], $tid]);
            if (!$year) { $mode = 'exam'; }
        }
        if ($mode === 'exam') {
            $examId = $_GET['exam_id'] ?? ($examOptions[0]['id'] ?? null);
            $exam = $examId ? $this->db->fetchOne("SELECT * FROM exams e WHERE e.id=? AND e.tenant_id=?{$pub}", [$examId, $tid]) : null;
        }

        $grades = []; $termRows = []; $annual = null;
        $totalObtained = 0; $totalPossible = 0;
        $rankScopeSql = null; $rankParams = [];
        $attendanceRange = null; $docLabel = 'No exam selected';

        if ($mode === 'exam' && $exam) {
            $docLabel = $exam['name'];
            $grades = $this->db->fetchAll(
                "SELECT g.*, c.name AS course_name FROM grades g LEFT JOIN courses c ON g.course_id=c.id
                 WHERE g.student_id=? AND g.exam_id=? AND g.tenant_id=? ORDER BY c.name",
                [$studentId, $exam['id'], $tid]
            );
            foreach ($grades as $g) { $totalObtained += $g['marks_obtained']; $totalPossible += $g['total_marks']; }
            $rankScopeSql = "e.id=?"; $rankParams = [$exam['id']];
            if ($exam['term_id']) {
                $t = $this->db->fetchOne("SELECT start_date, end_date FROM terms WHERE id=?", [$exam['term_id']]);
                if ($t) { $attendanceRange = [$t['start_date'], $t['end_date']]; }
            }
        } elseif ($mode === 'term') {
            $docLabel = $term['name'] . ($term['year_name'] ? ' — ' . $term['year_name'] : '');
            $termRows = $this->db->fetchAll(
                "SELECT c.name AS course_name, AVG(g.marks_obtained/g.total_marks*100) AS avg_pct,
                        SUM(g.marks_obtained) obtained, SUM(g.total_marks) possible, COUNT(*) AS exam_count
                 FROM grades g JOIN exams e ON g.exam_id=e.id LEFT JOIN courses c ON g.course_id=c.id
                 WHERE g.student_id=? AND g.tenant_id=? AND e.term_id=? AND g.total_marks>0{$pub}
                 GROUP BY g.course_id, c.name ORDER BY c.name",
                [$studentId, $tid, $term['id']]
            );
            foreach ($termRows as $r) { $totalObtained += $r['obtained']; $totalPossible += $r['possible']; }
            $rankScopeSql = "e.term_id=?"; $rankParams = [$term['id']];
            $attendanceRange = [$term['start_date'], $term['end_date']];
        } elseif ($mode === 'annual') {
            $docLabel = 'Annual Report — ' . $year['name'];
            $periods = $this->db->fetchAll("SELECT id, name FROM terms WHERE academic_year_id=? AND tenant_id=? ORDER BY start_date", [$year['id'], $tid]);
            $cells = $this->db->fetchAll(
                "SELECT g.course_id, c.name AS course_name, e.term_id,
                        AVG(g.marks_obtained/g.total_marks*100) AS pct,
                        SUM(g.marks_obtained) obtained, SUM(g.total_marks) possible
                 FROM grades g JOIN exams e ON g.exam_id=e.id LEFT JOIN courses c ON g.course_id=c.id
                 WHERE g.student_id=? AND g.tenant_id=? AND g.total_marks>0{$pub} AND {$yearOfExam}=?
                 GROUP BY g.course_id, c.name, e.term_id",
                [$studentId, $tid, $year['id']]
            );
            $rows = []; $hasOther = false;
            foreach ($cells as $cell) {
                $key = $cell['course_id'] ?? ('x'.$cell['course_name']);
                $rows[$key]['course_name'] = $cell['course_name'] ?? '—';
                $tk = $cell['term_id'] ?? 'other';
                if ($tk === 'other') { $hasOther = true; }
                $rows[$key]['periods'][$tk] = round((float)$cell['pct'], 1);
                $rows[$key]['obtained'] = ($rows[$key]['obtained'] ?? 0) + $cell['obtained'];
                $rows[$key]['possible'] = ($rows[$key]['possible'] ?? 0) + $cell['possible'];
                $totalObtained += $cell['obtained']; $totalPossible += $cell['possible'];
            }
            foreach ($rows as &$r) {
                $r['yearly_pct'] = $r['possible'] > 0 ? round($r['obtained'] / $r['possible'] * 100, 1) : null;
                $r['grade_letter'] = $r['yearly_pct'] !== null ? $letterFor($r['yearly_pct']) : null;
            }
            unset($r);
            usort($rows, fn($a, $b) => strcmp($a['course_name'], $b['course_name']));
            $annual = ['periods' => $periods, 'hasOther' => $hasOther, 'rows' => array_values($rows)];
            $rankScopeSql = "{$yearOfExam}=?"; $rankParams = [$year['id']];
            $attendanceRange = [$year['start_date'], $year['end_date']];
        }

        $overallPct = $totalPossible > 0 ? round($totalObtained / $totalPossible * 100, 1) : 0;
        $overallGrade = $letterFor($overallPct);

        $rank = null; $rankOf = null;
        if ($rankScopeSql && $student['class_id']) {
            $classTotals = $this->db->fetchAll(
                "SELECT g.student_id, SUM(g.marks_obtained) obtained
                 FROM grades g JOIN exams e ON g.exam_id=e.id JOIN students s2 ON g.student_id=s2.id
                 WHERE {$rankScopeSql} AND g.tenant_id=? AND s2.class_id=?{$pub}
                 GROUP BY g.student_id ORDER BY obtained DESC",
                array_merge($rankParams, [$tid, $student['class_id']])
            );
            $rankOf = count($classTotals);
            foreach ($classTotals as $i => $row) {
                if ((int)$row['student_id'] === (int)$studentId) { $rank = $i + 1; break; }
            }
        }

        $attendance = null;
        if ($attendanceRange) {
            $total = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE student_id=? AND tenant_id=? AND date BETWEEN ? AND ?", [$studentId, $tid, $attendanceRange[0], $attendanceRange[1]])['c'];
            $present = $this->db->fetchOne("SELECT COUNT(*) c FROM attendance WHERE student_id=? AND tenant_id=? AND status='present' AND date BETWEEN ? AND ?", [$studentId, $tid, $attendanceRange[0], $attendanceRange[1]])['c'];
            $attendance = ['total'=>$total, 'present'=>$present, 'pct'=>$total>0 ? round($present/$total*100,1) : null];
        }

        return [
            'mode' => $mode, 'docLabel' => $docLabel,
            'exam' => $exam, 'examOptions' => $examOptions, 'selectedExamId' => $examId,
            'term' => $term, 'termOptions' => $termOptions,
            'year' => $year, 'yearOptions' => $yearOptions,
            'grades' => $grades, 'termRows' => $termRows, 'annual' => $annual,
            'totalObtained' => $totalObtained, 'totalPossible' => $totalPossible,
            'overallPct' => $overallPct, 'overallGrade' => $overallGrade,
            'rank' => $rank, 'rankOf' => $rankOf, 'attendance' => $attendance,
        ];
    }

    /** Random 4-digit login PIN (zero-padded), hashed/verified the same way a password is. */
    protected function generateUniquePin(): string {
        return str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /** Slugifies $name into a login username, appending a numeric suffix until it's unique within the tenant. */
    protected function generateUniqueUsername(string $name, int $tenantId): string {
        $base = trim(strtolower(preg_replace('/[^a-z0-9]+/i', '.', $name)), '.');
        $base = $base !== '' ? $base : 'user';
        $username = $base;
        $i = 1;
        while ($this->db->fetchOne("SELECT id FROM users WHERE username=? AND tenant_id=?", [$username, $tenantId])) {
            $username = $base . $i;
            $i++;
        }
        return $username;
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
