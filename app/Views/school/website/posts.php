<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/website">Website</a>
  <span>/</span><span>News &amp; Events</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Website News &amp; Events</div>
    <div class="page-header-sub">Stories and upcoming events shown on the website’s News &amp; Events page, with the latest three on the home page.</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/academy-news" target="_blank" rel="noopener" class="btn btn-outline">View Page ↗</a>
    <button type="button" class="btn btn-primary" onclick="openPostModal()">＋ New Post</button>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th style="width:110px;">Image</th><th>Post</th><th>Type</th><th>Publish date</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($posts as $p): $scheduled = $p['is_published'] && $p['published_on'] > date('Y-m-d'); ?>
        <tr>
          <td>
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" style="width:96px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
            <?php else: ?>
              <div style="width:96px;height:60px;border-radius:6px;background:var(--surface-1);display:flex;align-items:center;justify-content:center;font-size:10px;color:var(--text-muted);">School photo</div>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= htmlspecialchars($p['title']) ?></div>
            <?php if (!empty($p['excerpt'])): ?><div style="font-size:12px;color:var(--text-muted);max-width:420px;margin-top:3px;"><?= htmlspecialchars($p['excerpt']) ?></div><?php endif; ?>
          </td>
          <td>
            <span class="badge <?= $p['category'] === 'event' ? 'badge-warning' : 'badge-info' ?>"><?= $p['category'] === 'event' ? 'Event' : 'News' ?></span>
            <?php if ($p['category'] === 'event' && !empty($p['event_date'])): ?>
              <div style="font-size:11.5px;color:var(--text-muted);margin-top:4px;"><?= date('d M Y', strtotime($p['event_date'])) ?></div>
            <?php endif; ?>
          </td>
          <td style="font-size:12.5px;"><?= date('d M Y', strtotime($p['published_on'])) ?></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/website/news/<?= $p['id'] ?>/toggle">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="badge <?= !$p['is_published'] ? 'badge-muted' : ($scheduled ? 'badge-info' : 'badge-success') ?>"
                      style="border:none;cursor:pointer;font:inherit;" title="Click to switch">
                <?= !$p['is_published'] ? 'Draft' : ($scheduled ? 'Scheduled' : 'Published') ?>
              </button>
            </form>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <?php if ($p['is_published'] && !$scheduled): ?>
                <a href="<?= $cfg['url'] ?>/academy-news/<?= $p['id'] ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline">View</a>
              <?php endif; ?>
              <button type="button" class="btn btn-sm btn-secondary" onclick='openPostModal(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/website/news/<?= $p['id'] ?>/delete"
                    data-confirm="Delete '<?= htmlspecialchars($p['title']) ?>' from the website?" data-confirm-title="Delete Post" data-confirm-label="Delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($posts)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">📰</div>
            <div class="empty-state-text">No posts yet. Share school news, a sports day, a graduation or an upcoming PTA meeting — it appears on the website straight away.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="postModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="postModalTitle">New Post</div>
      <button type="button" class="modal-close" onclick="document.getElementById('postModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="postForm" enctype="multipart/form-data" action="<?= $cfg['url'] ?>/school/website/news/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Type</label>
            <select name="category" id="p_category" class="form-control" onchange="toggleEventFields()">
              <option value="news">News story</option>
              <option value="event">Upcoming event</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Publish Date</label>
            <input type="date" name="published_on" id="p_published_on" class="form-control">
            <div class="form-hint">A future date keeps the post hidden until then.</div>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" id="p_title" class="form-control" required maxlength="200" placeholder="e.g. CELDI students win the county spelling bee">
        </div>
        <div class="form-row" id="eventFields" style="display:none;">
          <div class="form-group">
            <label class="form-label">Event Date</label>
            <input type="date" name="event_date" id="p_event_date" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Location</label>
            <input type="text" name="event_location" id="p_event_location" class="form-control" maxlength="200" placeholder="e.g. School courtyard, Ben Town">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Summary</label>
          <textarea name="excerpt" id="p_excerpt" class="form-control" rows="2" maxlength="400" placeholder="One or two sentences shown on the news cards."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Full Story</label>
          <textarea name="body" id="p_body" class="form-control" rows="9" placeholder="Leave a blank line between paragraphs. Use **double asterisks** for bold."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Photo</label>
          <input type="file" name="image" class="form-control" accept="image/*">
          <div class="form-hint">JPG, PNG, WEBP or GIF, up to 4MB. A landscape photo works best. Without one, a school photo is used.</div>
          <div id="postImageWrap" style="display:none;margin-top:10px;">
            <img id="postImage" src="" alt="" style="width:180px;height:100px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:6px;">
              <input type="checkbox" name="remove_image" value="1"> Remove this photo
            </label>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
            <input type="checkbox" name="is_published" id="p_is_published" value="1" checked> Publish on the website (untick to save as a draft)
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('postModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Post</button>
      </div>
    </form>
  </div>
</div>

<script>
const POST_BASE = '<?= $cfg['url'] ?>/school/website/news';
function toggleEventFields() {
  document.getElementById('eventFields').style.display = document.getElementById('p_category').value === 'event' ? '' : 'none';
}
function openPostModal(post) {
  const form = document.getElementById('postForm');
  const wrap = document.getElementById('postImageWrap');
  form.reset();
  if (post) {
    document.getElementById('postModalTitle').textContent = 'Edit Post';
    form.action = POST_BASE + '/' + post.id + '/update';
    ['category', 'title', 'excerpt', 'body', 'published_on', 'event_date', 'event_location'].forEach(function (f) {
      document.getElementById('p_' + f).value = post[f] || '';
    });
    document.getElementById('p_is_published').checked = String(post.is_published) === '1';
    if (post.image_url) { document.getElementById('postImage').src = post.image_url; wrap.style.display = ''; }
    else { wrap.style.display = 'none'; }
  } else {
    document.getElementById('postModalTitle').textContent = 'New Post';
    form.action = POST_BASE + '/store';
    document.getElementById('p_published_on').value = new Date().toISOString().slice(0, 10);
    document.getElementById('p_is_published').checked = true;
    wrap.style.display = 'none';
  }
  toggleEventFields();
  document.getElementById('postModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
