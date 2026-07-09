<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/messages">Messages</a>
  <span>/</span><span>Compose</span>
</div>
<div class="page-header"><div class="page-header-title"><?= $replyTo ? 'Reply to ' . htmlspecialchars($replyTo['name']) : 'Compose Message' ?></div></div>
<div style="max-width:680px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/messages/send">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
  <div class="card"><div class="card-body">
    <div class="form-group">
      <label class="form-label">To *</label>
      <select name="recipient_id" class="form-control" required>
        <option value="">— Select Recipient —</option>
        <?php foreach($users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= $replyTo && $replyTo['id']==$u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Subject</label>
      <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($prefillSubject ?? '') ?>">
    </div>
    <div class="form-group">
      <label class="form-label">Message *</label>
      <textarea name="body" class="form-control" rows="8" required></textarea>
    </div>
  </div></div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Send Message</button>
    <a href="<?= $cfg['url'] ?>/school/messages" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
