<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Inbox</div>
    <div class="page-header-sub"><?= (int)($stats['unread'] ?? 0) ?> unread message<?= ($stats['unread'] ?? 0) === 1 ? '' : 's' ?></div>
  </div>
  <a href="<?= $cfg['url'] ?>/school/messages/compose" class="btn btn-primary">+ Compose</a>
</div>
<div class="card">
  <?php foreach($messages as $m): ?>
  <a href="<?= $cfg['url'] ?>/school/messages/<?= $m['id'] ?>" style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;<?= !$m['is_read']?'background:rgba(16,185,129,0.08);':'' ?>">
    <div class="avatar"><?= strtoupper(substr($m['sender_name'],0,1)) ?></div>
    <div style="flex:1;min-width:0;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
        <div class="fw-600" style="color:var(--text)"><?= htmlspecialchars($m['sender_name']) ?></div>
        <div style="font-size:11px;color:var(--text-muted);flex-shrink:0;"><?= date('M d', strtotime($m['created_at'])) ?></div>
      </div>
      <div style="font-size:13px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($m['subject'] ?: '(no subject)') ?></div>
      <?php if(!$m['is_read']): ?><span class="badge badge-primary" style="margin-top:4px">New</span><?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
  <?php if(empty($messages)): ?>
  <div class="empty-state">
    <div class="empty-state-icon">✉️</div>
    <div class="empty-state-text">No messages in your inbox.</div>
  </div>
  <?php endif; ?>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
