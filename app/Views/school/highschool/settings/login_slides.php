<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/settings">Settings</a>
  <span>/</span><span>Login Carousel</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Login Carousel</div>
    <div class="page-header-sub">
      Announcements shown on the sign-in page — the one screen every parent, student and teacher
      sees, whether or not they have an account.
    </div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/login" target="_blank" class="btn btn-outline">View Login Page</a>
    <button type="button" class="btn btn-primary" onclick="openSlideModal()">＋ Add Announcement</button>
  </div>
</div>

<?php if (empty($slides)): ?>
<div class="alert alert-info">
  No announcements yet, so the login page is showing the three built-in slides that ship with the system.
  Add your first announcement below and it replaces them.
</div>
<?php elseif ($liveCount === 0): ?>
<div class="alert alert-warning">
  You have announcements, but none are currently showing — they are all hidden or outside their date range,
  so the login page has fallen back to the built-in slides.
</div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card" style="--card-color:var(--blue);">
    <div class="stat-value"><?= count($slides) ?></div><div class="stat-label">Announcements</div></div>
  <div class="stat-card" style="--card-color:var(--success);">
    <div class="stat-value"><?= (int)$liveCount ?></div><div class="stat-label">Showing Now</div></div>
  <div class="stat-card" style="--card-color:var(--purple);">
    <div class="stat-value"><?= count(array_filter($slides, fn($s) => !empty($s['image_url']))) ?></div>
    <div class="stat-label">With Images</div></div>
</div>

<div class="card mt-16">
  <div class="table-wrapper">
    <table>
      <thead><tr><th style="width:120px;">Image</th><th>Announcement</th><th>Showing</th><th>Dates</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach($slides as $i => $s): ?>
        <tr>
          <td>
            <?php if(!empty($s['image_url'])): ?>
              <img src="<?= htmlspecialchars($s['image_url']) ?>" alt=""
                   style="width:104px;height:60px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
            <?php else: ?>
              <div style="width:104px;height:60px;border-radius:6px;background:linear-gradient(135deg,var(--primary),var(--purple));
                          display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;">No image</div>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= htmlspecialchars($s['title']) ?></div>
            <?php if(!empty($s['caption'])): ?>
              <div style="font-size:12px;color:var(--text-muted);max-width:420px;margin-top:3px;"><?= htmlspecialchars($s['caption']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/settings/login-slides/<?= $s['id'] ?>/toggle">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="badge <?= $s['is_active'] ? 'badge-success' : 'badge-muted' ?>"
                      style="border:none;cursor:pointer;font:inherit;" title="Click to switch">
                <?= $s['is_active'] ? 'Showing' : 'Hidden' ?>
              </button>
            </form>
          </td>
          <td style="font-size:12px;">
            <?php if(empty($s['starts_on']) && empty($s['ends_on'])): ?>
              <span style="color:var(--text-muted);">Always</span>
            <?php else: ?>
              <?= !empty($s['starts_on']) ? date('d M Y', strtotime($s['starts_on'])) : 'Any time' ?>
              &rarr;
              <?= !empty($s['ends_on']) ? date('d M Y', strtotime($s['ends_on'])) : 'no end' ?>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <?php foreach ([['up','↑',$i === 0], ['down','↓',$i === count($slides)-1]] as [$dir,$arrow,$disabled]): ?>
              <form method="POST" action="<?= $cfg['url'] ?>/school/settings/login-slides/<?= $s['id'] ?>/reorder">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="direction" value="<?= $dir ?>">
                <button type="submit" class="btn btn-sm btn-outline" <?= $disabled ? 'disabled' : '' ?>><?= $arrow ?></button>
              </form>
              <?php endforeach; ?>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary"
                      onclick='openSlideModal(<?= json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/settings/login-slides/<?= $s['id'] ?>/delete"
                    data-confirm="Delete the announcement '<?= htmlspecialchars($s['title']) ?>' from the login carousel?"
                    data-confirm-title="Delete Announcement" data-confirm-label="Delete">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($slides)): ?>
        <tr><td colspan="6">
          <div class="empty-state">
            <div class="empty-state-icon">🖼️</div>
            <div class="empty-state-text">
              No announcements yet. Add one — a photo of the school, an enrolment notice, exam dates —
              and it appears on the sign-in page straight away.
            </div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add / edit announcement -->
<div class="modal-overlay" id="slideModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title" id="slideModalTitle">Add Announcement</div>
      <button type="button" class="modal-close" onclick="document.getElementById('slideModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="slideForm" enctype="multipart/form-data" action="<?= $cfg['url'] ?>/school/settings/login-slides/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Headline *</label>
          <input type="text" name="title" id="s_title" class="form-control" required maxlength="150"
                 placeholder="e.g. Enrolment for 2026/2027 is now open">
        </div>
        <div class="form-group">
          <label class="form-label">Message</label>
          <textarea name="caption" id="s_caption" class="form-control" rows="3" maxlength="400"
                    placeholder="e.g. Register at the school office from Monday to Friday, 8am to 3pm."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Background Image</label>
          <input type="file" name="image" class="form-control" accept="image/*">
          <div style="font-size:11px;color:var(--text-muted);margin-top:5px;">
            JPG, PNG, WEBP or GIF, up to 2MB. A wide landscape photo works best — the text sits on top of it.
            Leave empty to keep the current image.
          </div>
          <div id="currentImageWrap" style="display:none;margin-top:10px;">
            <img id="currentImage" src="" alt="" style="width:180px;height:100px;object-fit:cover;border-radius:6px;border:1px solid var(--border);">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:6px;">
              <input type="checkbox" name="remove_image" value="1"> Remove this image
            </label>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Show From</label>
            <input type="date" name="starts_on" id="s_starts_on" class="form-control">
          </div>
          <div class="form-group">
            <label class="form-label">Show Until</label>
            <input type="date" name="ends_on" id="s_ends_on" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
            <input type="checkbox" name="is_active" id="s_is_active" value="1" checked> Show on the login page
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('slideModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Announcement</button>
      </div>
    </form>
  </div>
</div>

<script>
const SLIDE_BASE = '<?= $cfg['url'] ?>/school/settings/login-slides';
function openSlideModal(slide) {
  const form = document.getElementById('slideForm');
  const wrap = document.getElementById('currentImageWrap');
  if (slide) {
    document.getElementById('slideModalTitle').textContent = 'Edit Announcement';
    form.action = SLIDE_BASE + '/' + slide.id + '/update';
    document.getElementById('s_title').value = slide.title || '';
    document.getElementById('s_caption').value = slide.caption || '';
    document.getElementById('s_starts_on').value = slide.starts_on || '';
    document.getElementById('s_ends_on').value = slide.ends_on || '';
    document.getElementById('s_is_active').checked = String(slide.is_active) === '1';
    if (slide.image_url) {
      document.getElementById('currentImage').src = slide.image_url;
      wrap.style.display = '';
    } else {
      wrap.style.display = 'none';
    }
  } else {
    document.getElementById('slideModalTitle').textContent = 'Add Announcement';
    form.action = SLIDE_BASE + '/store';
    form.reset();
    document.getElementById('s_is_active').checked = true;
    wrap.style.display = 'none';
  }
  document.getElementById('slideModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
