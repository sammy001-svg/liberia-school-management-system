<?php
// Expects $page, $totalPages, $total, $perPage in scope. Preserves existing
// GET query params (search, filters) when building page links.
if ($totalPages > 1):
    $queryParams = $_GET;
    unset($queryParams['page']);
    $baseQuery = http_build_query($queryParams);
    $makeUrl = function($p) use ($baseQuery) {
        return '?' . ($baseQuery ? $baseQuery . '&page=' . $p : 'page=' . $p);
    };
    $windowStart = max(1, $page - 2);
    $windowEnd   = min($totalPages, $page + 2);
?>
<div class="pagination">
  <?php if ($page > 1): ?><a href="<?= $makeUrl($page - 1) ?>">&laquo;</a><?php else: ?><span class="disabled">&laquo;</span><?php endif; ?>

  <?php if ($windowStart > 1): ?>
    <a href="<?= $makeUrl(1) ?>">1</a>
    <?php if ($windowStart > 2): ?><span class="disabled">&hellip;</span><?php endif; ?>
  <?php endif; ?>

  <?php for ($i = $windowStart; $i <= $windowEnd; $i++): ?>
    <?php if ($i === $page): ?><span class="active"><?= $i ?></span><?php else: ?><a href="<?= $makeUrl($i) ?>"><?= $i ?></a><?php endif; ?>
  <?php endfor; ?>

  <?php if ($windowEnd < $totalPages): ?>
    <?php if ($windowEnd < $totalPages - 1): ?><span class="disabled">&hellip;</span><?php endif; ?>
    <a href="<?= $makeUrl($totalPages) ?>"><?= $totalPages ?></a>
  <?php endif; ?>

  <?php if ($page < $totalPages): ?><a href="<?= $makeUrl($page + 1) ?>">&raquo;</a><?php else: ?><span class="disabled">&raquo;</span><?php endif; ?>
</div>
<div class="pagination-info">Showing <?= (($page - 1) * $perPage) + 1 ?>&ndash;<?= min($page * $perPage, $total) ?> of <?= $total ?></div>
<?php endif; ?>
