<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div class="page-header-title">Inbox</div>
  <a href="<?= $cfg['url'] ?>/school/messages/compose" class="btn btn-primary">+ Compose</a>
</div>
<div class="card">
  <?php foreach($messages as $m): ?>
  <a href="<?= $cfg['url'] ?>/school/messages/<?= $m['id'] ?>" style="display:block;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;<?= !$m['is_read']?'background:rgba(79,70,229,0.06);':'' ?>">
    <div style="display:flex;justify-content:space-between;align-items:center;">
      <div class="fw-600" style="color:var(--text)"><?= htmlspecialchars($m['sender_name']) ?></div>
      <div style="font-size:11px;color:var(--text-muted)"><?= date('M d', strtotime($m['created_at'])) ?></div>
    </div>
    <div style="font-size:13px;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($m['subject']??'(no subject)') ?></div>
    <?php if(!$m['is_read']): ?><span class="badge badge-primary" style="margin-top:4px">New</span><?php endif; ?>
  </a>
  <?php endforeach; ?>
  <?php if(empty($messages)): ?><div class="text-center text-muted" style="padding:40px">No messages in your inbox.</div><?php endif; ?>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
