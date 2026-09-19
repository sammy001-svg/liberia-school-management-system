<?php // Print styling for finance reports + a letterhead that only appears on paper. Expects $tenant, $reportHeading. ?>
<style>
  .print-only { display: none; }
  @media print {
    .sidebar, .sidebar-overlay, .topbar, .toast-stack, .no-print, .page-header .btn, form.no-print { display: none !important; }
    .main-content { margin: 0 !important; padding: 0 !important; }
    .page-body { padding: 0 !important; }
    body, .app-layout, .main-content, .card { background: #fff !important; color: #111 !important; box-shadow: none !important; }
    .card { border: 1px solid #ccc !important; break-inside: avoid; }
    table th, table td { color: #111 !important; border-color: #ccc !important; }
    .print-only { display: block !important; }
    a { color: #111 !important; text-decoration: none !important; }
    @page { size: A4; margin: 12mm; }
  }
  .letterhead { text-align: center; border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 14px; }
  .letterhead img { height: 54px; }
  .letterhead .n { font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
  .letterhead .m { font-size: 11px; }
  .letterhead .t { font-size: 15px; font-weight: 700; margin-top: 6px; }
</style>
<div class="print-only letterhead">
  <?php if (!empty($tenant['logo'])): ?><img src="<?= htmlspecialchars($tenant['logo']) ?>" alt=""><?php endif; ?>
  <div class="n"><?= htmlspecialchars($tenant['name'] ?? '') ?></div>
  <div class="m"><?= htmlspecialchars((string)($tenant['address'] ?? '')) ?><?= !empty($tenant['phone']) ? ' · ' . htmlspecialchars($tenant['phone']) : '' ?></div>
  <div class="t"><?= htmlspecialchars($reportHeading ?? '') ?></div>
  <div class="m">Printed <?= date('M j, Y g:i A') ?></div>
</div>
