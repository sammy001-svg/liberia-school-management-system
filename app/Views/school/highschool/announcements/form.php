<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Post Announcement</div></div>
<div style="max-width:680px;">
<form method="POST" action="<?= $cfg['url'] ?>/school/announcements/store">
  <div class="card">
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Message *</label>
        <textarea name="body" class="form-control" rows="6" required></textarea>
      </div>
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
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="is_pinned" value="1"> <span class="form-label" style="margin:0">Pin this announcement</span>
        </label>
      </div>
    </div>
  </div>
  <div style="display:flex;gap:12px;margin-top:20px;">
    <button type="submit" class="btn btn-primary">Post Announcement</button>
    <a href="<?= $cfg['url'] ?>/school/announcements" class="btn btn-secondary">Cancel</a>
  </div>
</form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
