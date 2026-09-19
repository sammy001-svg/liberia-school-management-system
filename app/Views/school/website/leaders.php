<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/website">Website</a>
  <span>/</span><span>Leadership Team</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Leadership Team</div>
    <div class="page-header-sub">The people shown on the website’s Our Leadership page — principal, board members, heads of division and so on.</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/our-leadership" target="_blank" rel="noopener" class="btn btn-outline">View Page ↗</a>
    <button type="button" class="btn btn-primary" onclick="openLeaderModal()">＋ Add Person</button>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th style="width:80px;">Photo</th><th>Name &amp; Position</th><th>Showing</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($leaders as $i => $l): ?>
        <tr>
          <td>
            <?php if (!empty($l['photo_url'])): ?>
              <img src="<?= htmlspecialchars($l['photo_url']) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:50%;border:1px solid var(--border);">
            <?php else: ?>
              <div class="avatar" style="width:56px;height:56px;"><?= htmlspecialchars(mb_strtoupper(mb_substr($l['name'], 0, 1))) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= htmlspecialchars($l['name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($l['position']) ?></div>
          </td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/website/leaders/<?= $l['id'] ?>/toggle">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="badge <?= $l['is_active'] ? 'badge-success' : 'badge-muted' ?>" style="border:none;cursor:pointer;font:inherit;" title="Click to switch">
                <?= $l['is_active'] ? 'Showing' : 'Hidden' ?>
              </button>
            </form>
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <?php foreach ([['up', '↑', $i === 0], ['down', '↓', $i === count($leaders) - 1]] as [$dir, $arrow, $disabled]): ?>
                <form method="POST" action="<?= $cfg['url'] ?>/school/website/leaders/<?= $l['id'] ?>/reorder">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <input type="hidden" name="direction" value="<?= $dir ?>">
                  <button type="submit" class="btn btn-sm btn-outline" <?= $disabled ? 'disabled' : '' ?>><?= $arrow ?></button>
                </form>
              <?php endforeach; ?>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openLeaderModal(<?= json_encode($l, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/website/leaders/<?= $l['id'] ?>/delete"
                    data-confirm="Remove <?= htmlspecialchars($l['name']) ?> from the website?" data-confirm-title="Remove Person" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($leaders)): ?>
        <tr><td colspan="5">
          <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <div class="empty-state-text">No one added yet. Until you add people, the Our Leadership page shows only its descriptions of the Board, administration, educators and PTA.</div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="leaderModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="leaderModalTitle">Add Person</div>
      <button type="button" class="modal-close" onclick="document.getElementById('leaderModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="leaderForm" enctype="multipart/form-data" action="<?= $cfg['url'] ?>/school/website/leaders/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" id="l_name" class="form-control" required maxlength="150">
        </div>
        <div class="form-group">
          <label class="form-label">Position *</label>
          <input type="text" name="position" id="l_position" class="form-control" required maxlength="150" placeholder="e.g. Principal, Board Chair, Head of Elementary">
        </div>
        <div class="form-group">
          <label class="form-label">Short Bio</label>
          <textarea name="bio" id="l_bio" class="form-control" rows="4" maxlength="600" placeholder="Two or three sentences."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Photo</label>
          <input type="file" name="photo" class="form-control" accept="image/*">
          <div class="form-hint">A square or portrait head-and-shoulders photo works best. Up to 4MB.</div>
          <div id="leaderImageWrap" style="display:none;margin-top:10px;">
            <img id="leaderImage" src="" alt="" style="width:90px;height:90px;object-fit:cover;border-radius:50%;border:1px solid var(--border);">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:6px;">
              <input type="checkbox" name="remove_image" value="1"> Remove this photo
            </label>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
            <input type="checkbox" name="is_active" id="l_is_active" value="1" checked> Show on the website
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('leaderModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
const LEADER_BASE = '<?= $cfg['url'] ?>/school/website/leaders';
function openLeaderModal(l) {
  const form = document.getElementById('leaderForm');
  const wrap = document.getElementById('leaderImageWrap');
  form.reset();
  if (l) {
    document.getElementById('leaderModalTitle').textContent = 'Edit Person';
    form.action = LEADER_BASE + '/' + l.id + '/update';
    document.getElementById('l_name').value = l.name || '';
    document.getElementById('l_position').value = l.position || '';
    document.getElementById('l_bio').value = l.bio || '';
    document.getElementById('l_is_active').checked = String(l.is_active) === '1';
    if (l.photo_url) { document.getElementById('leaderImage').src = l.photo_url; wrap.style.display = ''; }
    else { wrap.style.display = 'none'; }
  } else {
    document.getElementById('leaderModalTitle').textContent = 'Add Person';
    form.action = LEADER_BASE + '/store';
    document.getElementById('l_is_active').checked = true;
    wrap.style.display = 'none';
  }
  document.getElementById('leaderModal').classList.add('open');
}
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
