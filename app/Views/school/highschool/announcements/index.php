<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div class="page-header-title">Announcements</div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addAnnouncementModal').classList.add('open')">+ Post Announcement</button>
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
  <?php if(empty($announcements)): ?><div class="text-center text-muted" style="padding:40px">No announcements yet. <a href="javascript:void(0)" onclick="document.getElementById('addAnnouncementModal').classList.add('open')">Post the first one</a></div><?php endif; ?>
</div>

<!-- Post Announcement Modal -->
<div class="modal-overlay" id="addAnnouncementModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Post Announcement</div>
      <button class="modal-close" onclick="document.getElementById('addAnnouncementModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $cfg['url'] ?>/school/announcements/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">

        <div class="modal-section-title">Content</div>
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="form-group">
          <label class="form-label">Message *</label>
          <textarea name="body" class="form-control" rows="6" required></textarea>
        </div>

        <div class="modal-section-title">Targeting</div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Audience</label>
            <select name="audience" class="form-control">
              <?php foreach(['all','students','teachers','parents','staff'] as $a): ?>
                <option value="<?= $a ?>"><?= ucfirst($a) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Class (optional)</label>
            <select name="class_id" class="form-control">
              <option value="">All Classes</option>
              <?php foreach($classes as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Expires On (optional)</label>
          <input type="date" name="expires_at" class="form-control">
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
            <input type="checkbox" name="is_pinned" value="1"> <span class="form-label" style="margin:0">Pin this announcement</span>
          </label>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addAnnouncementModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Post Announcement</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
