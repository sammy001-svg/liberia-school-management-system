<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<style>
.kid-tabs { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.kid-tab { display:flex; flex-direction:column; gap:2px; padding:10px 14px; min-width:180px; border:1px solid var(--border); border-radius:12px; background:var(--surface-1); color:var(--text); text-decoration:none; transition:.15s; }
.kid-tab:hover { border-color:var(--primary); }
.kid-tab.on { border-color:var(--primary); box-shadow:0 0 0 2px var(--primary-soft, rgba(76,174,114,.2)); }
.kid-tab b { font-size:13.5px; }
.kid-tab span { font-size:11.5px; color:var(--text-muted); }
.kid-tab .owe { color:var(--danger); font-weight:700; }
.kid-tab .ok { color:var(--success); font-weight:700; }
@media (max-width:720px) { .kid-tab { flex:1 1 100%; } }
</style>
<div class="page-header">
  <div>
    <div class="page-header-title">School Fees</div>
    <div class="page-header-sub">Bills, installments, payments and receipts for your children.</div>
  </div>
</div>

<?php if (!$children): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">No children are linked to your account yet. Please contact the school office.</div></div>
<?php else: ?>
  <?php if (count($children) > 1): ?>
  <nav class="kid-tabs" aria-label="Choose a child">
    <?php foreach ($children as $c): $owes = array_filter($c['totals'], fn($t) => $t['balance'] > 0.005); ?>
      <a class="kid-tab <?= (int)$c['id'] === $childId ? 'on' : '' ?>" href="<?= $cfg['url'] ?>/parent/finance?child=<?= (int)$c['id'] ?>" <?= (int)$c['id'] === $childId ? 'aria-current="page"' : '' ?>>
        <b><?= htmlspecialchars($c['name']) ?></b>
        <span><?= htmlspecialchars($c['class_name'] ?? '') ?></span>
        <?php if ($owes): ?>
          <span>Balance: <?php foreach ($owes as $cur => $t): ?><span class="owe"><?= Finance::money($t['balance'], $cur) ?></span> <?php endforeach; ?></span>
        <?php elseif ($c['totals']): ?>
          <span class="ok">Fully paid</span>
        <?php else: ?>
          <span>No bills this year</span>
        <?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <?php else: ?>
    <div style="margin:-6px 0 14px;font-weight:700;"><?= htmlspecialchars($children[0]['name']) ?></div>
  <?php endif; ?>

  <?php
  $yearUrl = $cfg['url'] . '/parent/finance?child=' . $childId;
  $receiptUrl = $cfg['url'] . '/parent/receipts';
  $allReceiptsUrl = $cfg['url'] . '/parent/receipts?child=' . $childId . '&year=';
  require ROOT_DIR . '/app/Views/school/portals/partials/fee_account.php';
  ?>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
