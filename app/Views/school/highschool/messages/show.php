<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
    <a href="<?= $cfg['url'] ?>/school/messages">Messages</a>
    <span>/</span>
    <span>View Message</span>
</div>

<div class="page-header">
    <div class="page-header-title">Message Details</div>
    <div style="display:flex;gap:10px;">
        <a href="<?= $cfg['url'] ?>/school/messages/compose?reply_to=<?= $message['sender_id'] ?>&subject=<?= urlencode($message['subject'] ?: '') ?>" class="btn btn-primary">Reply</a>
        <form method="POST" action="<?= $cfg['url'] ?>/school/messages/<?= $message['id'] ?>/delete" data-confirm="Delete this message?" data-confirm-label="Delete">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <button type="submit" class="btn btn-outline">Delete</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:15px;border-bottom:1px solid var(--border);">
            <div class="avatar avatar-lg"><?= strtoupper(substr($message['sender_name'],0,1)) ?></div>
            <div style="flex:1;">
                <div class="stat-label">From</div>
                <div class="fw-600"><?= htmlspecialchars($message['sender_name']) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="stat-label">Date</div>
                <div class="text-muted"><?= date('M d, Y H:i', strtotime($message['created_at'])) ?></div>
            </div>
        </div>

        <div style="margin-bottom:20px;">
            <div class="stat-label">Subject</div>
            <div class="fw-600" style="font-size:18px;"><?= htmlspecialchars($message['subject'] ?: '(No Subject)') ?></div>
        </div>

        <div style="line-height:1.6;color:var(--text);white-space:pre-wrap;"><?= htmlspecialchars($message['body']) ?></div>
    </div>
</div>

<div style="margin-top:20px;">
    <a href="<?= $cfg['url'] ?>/school/messages" class="btn btn-secondary">Back to Inbox</a>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
