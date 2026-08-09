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
      <label class="form-label">Send to *</label>
      <select name="audience" id="audience" class="form-control" onchange="audienceChanged()">
        <option value="individual">One person</option>
        <option value="all">Everyone</option>
        <option value="students">All Students</option>
        <option value="parents">All Parents</option>
        <option value="staff">All Staff</option>
      </select>
      <div id="broadcastNote" style="display:none;font-size:12px;color:var(--text-muted);margin-top:6px;">
        This goes to every active account in the group, each as their own message.
      </div>
    </div>
    <div class="form-group" id="recipientGroup">
      <label class="form-label">Recipient *</label>
      <select name="recipient_id" id="recipientSelect" class="form-control">
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
    <button type="submit" class="btn btn-primary" id="sendBtn">Send Message</button>
    <a href="<?= $cfg['url'] ?>/school/messages" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>

<script>
function audienceChanged() {
  const audience = document.getElementById('audience').value;
  const individual = audience === 'individual';
  document.getElementById('recipientGroup').style.display = individual ? '' : 'none';
  document.getElementById('broadcastNote').style.display = individual ? 'none' : '';
  // Only required when it's actually on screen, or the browser blocks submit on a hidden field.
  document.getElementById('recipientSelect').required = individual;
  document.getElementById('sendBtn').textContent = individual ? 'Send Message' : 'Send to Group';
}
audienceChanged();
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
