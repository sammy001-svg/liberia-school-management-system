<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">My Fees</div>
    <div class="page-header-sub">Your school bills, what has been paid and your receipts.</div>
  </div>
</div>
<?php
$yearUrl = $cfg['url'] . '/student/fees';
$receiptUrl = $cfg['url'] . '/student/receipts';
$allReceiptsUrl = $cfg['url'] . '/student/receipts?year=';
require ROOT_DIR . '/app/Views/school/portals/partials/fee_account.php';
?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
