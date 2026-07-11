<?php
require ROOT_DIR . '/app/Views/layouts/header.php';
$homeUrl = match ($_SESSION['role'] ?? '') {
    'Student' => '/student/dashboard',
    'Parent'  => '/parent/dashboard',
    default   => '/school/dashboard',
};
?>
<div class="card" style="max-width:480px;margin:60px auto;text-align:center;padding:50px 40px;">
  <div style="font-size:52px;">🔒</div>
  <h2 style="margin-top:16px;font-size:19px;">Access Denied</h2>
  <p style="color:var(--text-muted);margin-top:8px;font-size:13.5px;">You don't have permission to view this page. If you believe this is a mistake, contact your school administrator.</p>
  <a href="<?= $cfg['url'] . $homeUrl ?>" class="btn btn-primary" style="margin-top:24px;">Back to Dashboard</a>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
