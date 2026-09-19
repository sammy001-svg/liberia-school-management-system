<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $live = !empty($tenant['website_enabled']); ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Website</div>
    <div class="page-header-sub">Edit the school’s public website — every page’s text and photos, news and events, the gallery and the leadership team. Changes go live as soon as you save.</div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php if ($live): ?>
      <a href="<?= $cfg['url'] ?>/" target="_blank" rel="noopener" class="btn btn-outline">View Website ↗</a>
    <?php endif; ?>
  </div>
</div>

<div class="card" style="border-left:4px solid <?= $live ? 'var(--success)' : 'var(--warning)' ?>;">
  <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
    <div>
      <div style="display:flex;align-items:center;gap:10px;">
        <span class="badge <?= $live ? 'badge-success' : 'badge-warning' ?>"><?= $live ? '● Live' : '● Off' ?></span>
        <span class="fw-600" style="font-size:15px;"><?= $live ? 'Your website is live' : 'Your website is switched off' ?></span>
      </div>
      <div class="form-hint" style="font-size:12.5px;margin-top:6px;">
        <?= $live
            ? 'Visitors to ' . htmlspecialchars($cfg['url']) . '/ see the school website, with buttons to apply online and sign in to the portal.'
            : 'Every website page currently sends visitors straight to the login page. Your edits are kept and reappear when you switch it back on.' ?>
      </div>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/website/toggle"
          <?= $live ? 'data-confirm="Switch the website off? Visitors will go straight to the login page until you turn it back on." data-confirm-title="Switch Website Off" data-confirm-label="Switch Off"' : '' ?>>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <input type="hidden" name="website_enabled" value="<?= $live ? 0 : 1 ?>">
      <button type="submit" class="btn <?= $live ? 'btn-secondary' : 'btn-success' ?>"><?= $live ? 'Switch Website Off' : 'Switch Website On' ?></button>
    </form>
  </div>
</div>

<div class="stat-grid mt-16">
  <a class="stat-card" href="<?= $cfg['url'] ?>/school/website/news" style="--card-color:var(--blue);">
    <div class="stat-value"><?= (int)$stats['posts'] ?></div><div class="stat-label">Published News &amp; Events</div></a>
  <a class="stat-card" href="<?= $cfg['url'] ?>/school/website/gallery" style="--card-color:var(--success);">
    <div class="stat-value"><?= (int)$stats['gallery'] ?></div><div class="stat-label">Gallery Photos<?= $stats['gallery'] ? '' : ' (using built-in)' ?></div></a>
  <a class="stat-card" href="<?= $cfg['url'] ?>/school/website/leaders" style="--card-color:var(--purple);">
    <div class="stat-value"><?= (int)$stats['leaders'] ?></div><div class="stat-label">Leadership Profiles</div></a>
  <div class="stat-card" style="--card-color:var(--orange);">
    <div class="stat-value"><?= array_sum(array_column($edited, 'count')) ?></div><div class="stat-label">Customised Fields</div></div>
</div>

<div class="card mt-16">
  <div class="card-header"><div class="card-title">📄 Pages</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Page</th><th>Status</th><th>Last edited</th><th style="width:1%;">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($pages as $key => $page): ?>
          <tr>
            <td>
              <div class="fw-600"><?= $page['icon'] ?> <?= htmlspecialchars($page['label']) ?></div>
              <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                <?= htmlspecialchars($page['desc'] ?? (count($page['sections']) . ' sections · ' . array_sum(array_map('count', $page['sections'])) . ' editable fields')) ?>
              </div>
            </td>
            <td>
              <?php if (!empty($edited[$key])): ?>
                <span class="badge badge-purple"><?= (int)$edited[$key]['count'] ?> customised</span>
              <?php else: ?>
                <span class="badge badge-muted">Original</span>
              <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted);">
              <?= !empty($edited[$key]['at']) ? date('d M Y, H:i', strtotime($edited[$key]['at'])) : '—' ?>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <a href="<?= $cfg['url'] ?>/school/website/pages/<?= $key ?>" class="btn btn-sm btn-primary">Edit</a>
                <?php if ($live): ?>
                  <a href="<?= $cfg['url'] . $page['url'] ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline">View ↗</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card mt-16">
  <div class="card-body" style="font-size:13px;color:var(--text-muted);line-height:1.7;">
    <strong style="color:var(--text);">Formatting tips.</strong>
    In headings, wrap words in <code>*asterisks*</code> to show them in the gold/purple highlight style.
    In longer text, leave a blank line to start a new paragraph, and use <code>**double asterisks**</code> for bold.
    Lists take one item per line, with parts separated by <code>|</code> — for example <code>Excellence | We pursue the highest standards…</code>.
    Clearing a field restores its original text.
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
