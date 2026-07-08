<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div class="page-header-title">Announcements</div>
  <a href="<?= $cfg['url'] ?>/school/announcements/create" class="btn btn-primary">+ Post Announcement</a>
</div>
<div class="card">
  <?php foreach($announcements as $a): ?>
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
      <div>
        <?php if($a['is_pinned']): ?><span class="badge badge-warning" style="margin-bottom:4px">📌 Pinned</span><?php endif; ?>
        <div class="fw-600" style="font-size:14px"><?= htmlspecialchars($a['title']) ?></div>
        <div style="margin-top:4px;color:var(--text-light);font-size:13px"><?= nl2br(htmlspecialchars(substr($a['body'],0,200))) ?>…</div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:6px"><?= htmlspecialchars($a['author']) ?> · <?= htmlspecialchars(ucfirst($a['audience'])) ?> · <?= date('M d, Y', strtotime($a['published_at'])) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if(empty($announcements)): ?><div class="text-center text-muted" style="padding:40px">No announcements yet.</div><?php endif; ?>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
