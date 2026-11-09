<?php
/**
 * Deployment health check — a temporary, read-only page for diagnosing a 500.
 *
 * Upload this file to the site's public folder (beside index.php), open
 * https://your-site/health.php, read the report, then DELETE THE FILE.
 *
 * It changes nothing and prints no passwords. It answers the questions a blank
 * 500 page will not: is the PHP version right, did every file arrive intact,
 * is the database reachable, and which file is broken.
 */

header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__);
$problems = [];
$notes    = [];

// ── 1. PHP version ─────────────────────────────────────────────────────────
$minPhp = '8.1.0';
$phpOk  = version_compare(PHP_VERSION, $minPhp, '>=');
if (!$phpOk) {
    $problems[] = 'This system needs PHP ' . $minPhp . ' or newer; this server runs ' . PHP_VERSION
        . '. In cPanel open <strong>Select PHP Version</strong> and choose 8.1 or 8.2.';
}

// ── 2. Required extensions ─────────────────────────────────────────────────
foreach (['pdo_mysql', 'mbstring', 'json', 'fileinfo'] as $ext) {
    if (!extension_loaded($ext)) {
        $problems[] = 'The PHP extension <code>' . $ext . '</code> is not enabled. Turn it on in cPanel under '
            . '<strong>Select PHP Version &rarr; Extensions</strong>.';
    }
}

// ── 3. Every PHP file parses ───────────────────────────────────────────────
// A file cut short by a failed upload is the commonest cause of a 500 right
// after a deploy, and it is invisible until something tries to load it.
$checked = $broken = 0;
$brokenFiles = [];
$dirs = ['app', 'core', 'config', 'public'];
foreach ($dirs as $d) {
    $base = $root . '/' . $d;
    if (!is_dir($base)) {
        $problems[] = 'The folder <code>' . $d . '/</code> is missing from the server.';
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') { continue; }
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        if ($path === 'public/health.php') { continue; }
        $checked++;
        $src = @file_get_contents($file->getPathname());
        if ($src === false) {
            $broken++; $brokenFiles[$path] = 'could not be read — check its permissions (644)';
            continue;
        }
        if (trim($src) === '') {
            $broken++; $brokenFiles[$path] = 'is empty — the upload did not complete';
            continue;
        }
        try {
            token_get_all($src, TOKEN_PARSE);
        } catch (ParseError $e) {
            $broken++;
            $brokenFiles[$path] = 'is not valid PHP: ' . $e->getMessage()
                . ' — upload this file again (in BINARY mode if you use FTP)';
        }
    }
}

// ── 4. The files every request needs ───────────────────────────────────────
$required = [
    'public/index.php', 'config/app.php', 'config/routes.php', 'config/database.php',
    'core/Database.php', 'core/Router.php', 'core/Controller.php', 'core/Model.php',
    'app/Controllers/AllControllers.php',
    'app/Views/layouts/header.php', 'app/Views/layouts/footer.php',
    'app/Views/school/partials/celdi_card_why_empty.php',
    'app/Services/Finance.php', 'app/Services/WebsiteContent.php',
];
foreach ($required as $rel) {
    if (!is_file($root . '/' . $rel)) {
        $problems[] = '<code>' . $rel . '</code> is missing. '
            . ($rel === 'config/database.php'
                ? 'This file holds the database settings and is deliberately not in the code archive — '
                  . 'restore it from your backup, or copy <code>config/database.php.example</code> and fill it in.'
                : 'Upload it from the release.');
    }
}

// ── 5. The database ────────────────────────────────────────────────────────
$dbOk = false;
if (is_file($root . '/config/database.php')) {
    try {
        $c = require $root . '/config/database.php';
        $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['database']};charset=" . ($c['charset'] ?? 'utf8mb4');
        $pdo = new PDO($dsn, $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $dbOk = true;
        $notes[] = 'Connected to the database <code>' . htmlspecialchars((string)$c['database']) . '</code>.';
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $notes[] = count($tables) . ' tables found.';
        foreach (['users', 'students', 'exams', 'grades', 'tenants'] as $t) {
            if (!in_array($t, $tables, true)) {
                $problems[] = 'The table <code>' . $t . '</code> is missing — this does not look like the right database.';
            }
        }
        $cols = $pdo->query("SHOW COLUMNS FROM exams")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('report_column', $cols, true)) {
            $problems[] = 'The <code>exams</code> table has no <code>report_column</code>. '
                . 'Import <code>sql/add_celdi_report_card.sql</code> in phpMyAdmin.';
        }
    } catch (Throwable $e) {
        // The message can name the host and user; the password is never in it.
        $problems[] = 'The database refused the connection: <code>' . htmlspecialchars($e->getMessage()) . '</code>. '
            . 'Check the settings in <code>config/database.php</code> against cPanel &rarr; MySQL Databases.';
    }
}

// ── 6. Writable places ─────────────────────────────────────────────────────
foreach (['public/uploads'] as $w) {
    if (is_dir($root . '/' . $w) && !is_writable($root . '/' . $w)) {
        $problems[] = '<code>' . $w . '/</code> is not writable, so uploads will fail. Set it to 755.';
    }
}

$ok = !$problems && !$broken;
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Deployment health check</title>
<style>
  body { font-family: system-ui, -apple-system, "Segoe UI", sans-serif; margin: 0; padding: 28px 20px;
         background: #f6f7f9; color: #1f2430; line-height: 1.6; }
  .wrap { max-width: 860px; margin: 0 auto; }
  h1 { font-size: 22px; margin: 0 0 4px; }
  .sub { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
  .verdict { padding: 14px 18px; border-radius: 10px; font-weight: 600; margin-bottom: 20px; }
  .good { background: #dcfce7; color: #14532d; border: 1px solid #86efac; }
  .bad  { background: #fee2e2; color: #7f1d1d; border: 1px solid #fca5a5; }
  .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px 20px; margin-bottom: 16px; }
  .card h2 { font-size: 15px; margin: 0 0 10px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
  ul { margin: 0; padding-left: 20px; }
  li { margin-bottom: 8px; }
  code { background: #f3f4f6; padding: 1px 5px; border-radius: 4px; font-size: 13px; }
  .foot { font-size: 13px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 14px; }
  .kv { display: grid; grid-template-columns: 220px 1fr; gap: 6px 14px; font-size: 14px; }
  .kv div:nth-child(odd) { color: #6b7280; }
</style></head><body><div class="wrap">

<h1>Deployment health check</h1>
<div class="sub">Read-only. Nothing on this page changes the system.</div>

<div class="verdict <?= $ok ? 'good' : 'bad' ?>">
  <?= $ok
      ? 'Everything checks out. If pages still fail, the cause is in the server log — see the last box below.'
      : count($problems) + $broken . ' problem(s) found. Each one is listed below with what to do about it.' ?>
</div>

<div class="card">
  <h2>This server</h2>
  <div class="kv">
    <div>PHP version</div><div><?= PHP_VERSION ?> <?= $phpOk ? '✓' : '✗ needs ' . $minPhp . ' or newer' ?></div>
    <div>Site folder</div><div><code><?= htmlspecialchars($root) ?></code></div>
    <div>PHP files checked</div><div><?= $checked ?><?= $broken ? ' — ' . $broken . ' will not parse' : ' — all parse correctly' ?></div>
    <div>Database</div><div><?= $dbOk ? 'reachable' : 'not reachable' ?></div>
  </div>
</div>

<?php if ($brokenFiles): ?>
<div class="card">
  <h2>Files that did not arrive intact</h2>
  <p>These are almost always a failed or partial upload. Upload each one again on its own.</p>
  <ul><?php foreach ($brokenFiles as $f => $why): ?>
    <li><code><?= htmlspecialchars($f) ?></code> <?= $why ?></li>
  <?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($problems): ?>
<div class="card">
  <h2>Problems</h2>
  <ul><?php foreach ($problems as $p): ?><li><?= $p ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<?php if ($notes): ?>
<div class="card">
  <h2>Also worth knowing</h2>
  <ul><?php foreach ($notes as $n): ?><li><?= $n ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="card">
  <h2>If this page says everything is fine</h2>
  <p>Then the failure is happening inside a page rather than at load time. Open
     <strong>cPanel &rarr; Metrics &rarr; Errors</strong>, or the file <code>error_log</code> in the site folder,
     and read the newest lines — the real message is there. Send me those lines and I will work from them.</p>
</div>

<div class="foot">
  <strong>Delete this file when you are done.</strong> It is a diagnostic, not part of the system,
  and it should not stay on a live site.
</div>

</div></body></html>
