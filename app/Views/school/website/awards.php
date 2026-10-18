<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/website">Website</a>
  <span>/</span><span>Academic Recognition</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Academic Recognition</div>
    <div class="page-header-sub">The honour board on the website — best students and teachers of each period, semester and year, plus leadership and behaviour awards.</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/recognition" target="_blank" rel="noopener" class="btn btn-outline">View Page ↗</a>
    <button type="button" class="btn btn-primary" onclick="openAwardModal()">＋ Add Award</button>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th style="width:70px;">Photo</th><th>Award</th><th>Recipient</th><th>Board</th><th>Showing</th><th>Order</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($awards as $i => $a): ?>
        <tr>
          <td>
            <?php if (!empty($a['photo_url'])): ?>
              <img src="<?= htmlspecialchars($a['photo_url']) ?>" alt="" style="width:52px;height:52px;object-fit:cover;border-radius:50%;border:1px solid var(--border);">
            <?php else: ?>
              <div class="avatar" style="width:52px;height:52px;"><?= htmlspecialchars(mb_strtoupper(mb_substr($a['recipient_name'], 0, 1))) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= htmlspecialchars($a['title']) ?></div>
            <?php if (!empty($a['period_label'])): ?><div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($a['period_label']) ?></div><?php endif; ?>
          </td>
          <td>
            <div class="fw-600"><?= htmlspecialchars($a['recipient_name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);">
              <?= htmlspecialchars(ucfirst($a['recipient_role'] ?? 'student')) ?><?= !empty($a['class_name']) ? ' · ' . htmlspecialchars($a['class_name']) : '' ?>
            </div>
          </td>
          <td><span class="badge badge-info"><?= htmlspecialchars($groups[$a['award_group']] ?? 'Academic Recognition') ?></span></td>
          <td>
            <form method="POST" action="<?= $cfg['url'] ?>/school/website/awards/<?= $a['id'] ?>/toggle">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="badge <?= $a['is_active'] ? 'badge-success' : 'badge-muted' ?>" style="border:none;cursor:pointer;font:inherit;" title="Click to switch">
                <?= $a['is_active'] ? 'Showing' : 'Hidden' ?>
              </button>
            </form>
          </td>
          <td>
            <div style="display:flex;gap:4px;">
              <?php foreach ([['up', '↑', $i === 0], ['down', '↓', $i === count($awards) - 1]] as [$dir, $arrow, $disabled]): ?>
                <form method="POST" action="<?= $cfg['url'] ?>/school/website/awards/<?= $a['id'] ?>/reorder">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <input type="hidden" name="direction" value="<?= $dir ?>">
                  <button type="submit" class="btn btn-sm btn-outline" <?= $disabled ? 'disabled' : '' ?>><?= $arrow ?></button>
                </form>
              <?php endforeach; ?>
            </div>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <button type="button" class="btn btn-sm btn-secondary" onclick='openAwardModal(<?= json_encode($a, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Edit</button>
              <form method="POST" action="<?= $cfg['url'] ?>/school/website/awards/<?= $a['id'] ?>/delete"
                    data-confirm="Remove this award from the website?" data-confirm-title="Remove Award" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($awards)): ?>
        <tr><td colspan="7">
          <div class="empty-state"><div class="empty-state-icon">🏅</div>
            <div class="empty-state-text">No awards yet. Add the first winner — best student of the period, best teacher of the semester, and so on.</div></div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="awardModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="awardModalTitle">Add Award</div>
      <button type="button" class="modal-close" onclick="document.getElementById('awardModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" id="awardForm" enctype="multipart/form-data" action="<?= $cfg['url'] ?>/school/website/awards/store">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Board *</label>
            <select name="award_group" id="a_group" class="form-control">
              <?php foreach ($groups as $k => $label): ?><option value="<?= $k ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
            </select></div>
          <div class="form-group"><label class="form-label">Award *</label>
            <input type="text" name="title" id="a_title" class="form-control" required maxlength="150" list="awardTitles" placeholder="Best Student of the Period">
            <datalist id="awardTitles">
              <option value="Best Student of the Period"></option>
              <option value="Best Student of the Semester"></option>
              <option value="Best Student of the Year"></option>
              <option value="Best Teacher of the Semester"></option>
              <option value="Best Teacher of the Year"></option>
            </datalist></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Period</label>
            <input type="text" name="period_label" id="a_period" class="form-control" maxlength="80" placeholder="e.g. 1st Period, 2025/2026">
          </div>
          <div class="form-group"><label class="form-label">Date announced</label>
            <input type="date" name="awarded_on" id="a_date" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Recipient *</label>
            <input type="text" name="recipient_name" id="a_name" class="form-control" required maxlength="150"></div>
          <div class="form-group"><label class="form-label">They are a</label>
            <select name="recipient_role" id="a_role" class="form-control">
              <option value="student">Student</option><option value="teacher">Teacher</option><option value="staff">Staff member</option>
            </select></div>
        </div>
        <div class="form-group"><label class="form-label">Class / department</label>
          <input type="text" name="class_name" id="a_class" class="form-control" maxlength="80" placeholder="e.g. Grade 9, Mathematics"></div>
        <div class="form-group"><label class="form-label">Citation</label>
          <textarea name="citation" id="a_citation" class="form-control" rows="3" maxlength="600" placeholder="A sentence or two on why this award was given."></textarea></div>
        <div class="form-group">
          <label class="form-label">Photo</label>
          <input type="file" name="photo" class="form-control" accept="image/*">
          <div class="form-hint">A head-and-shoulders photo works best. Up to 4MB.</div>
          <div id="awardImageWrap" style="display:none;margin-top:10px;">
            <img id="awardImage" src="" alt="" style="width:90px;height:90px;object-fit:cover;border-radius:50%;border:1px solid var(--border);">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:6px;">
              <input type="checkbox" name="remove_image" value="1"> Remove this photo
            </label>
          </div>
        </div>
        <div class="form-group">
          <label style="display:flex;align-items:center;gap:8px;font-size:13px;">
            <input type="checkbox" name="is_active" id="a_active" value="1" checked> Show on the website
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('awardModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
const AWARD_BASE = '<?= $cfg['url'] ?>/school/website/awards';
function openAwardModal(a) {
  const form = document.getElementById('awardForm');
  const wrap = document.getElementById('awardImageWrap');
  form.reset();
  if (a) {
    document.getElementById('awardModalTitle').textContent = 'Edit Award';
    form.action = AWARD_BASE + '/' + a.id + '/update';
    document.getElementById('a_group').value = a.award_group || 'academic';
    document.getElementById('a_title').value = a.title || '';
    document.getElementById('a_period').value = a.period_label || '';
    document.getElementById('a_date').value = a.awarded_on || '';
    document.getElementById('a_name').value = a.recipient_name || '';
    document.getElementById('a_role').value = a.recipient_role || 'student';
    document.getElementById('a_class').value = a.class_name || '';
    document.getElementById('a_citation').value = a.citation || '';
    document.getElementById('a_active').checked = Number(a.is_active) === 1;
    if (a.photo_url) { document.getElementById('awardImage').src = a.photo_url; wrap.style.display = ''; }
    else { wrap.style.display = 'none'; }
  } else {
    document.getElementById('awardModalTitle').textContent = 'Add Award';
    form.action = AWARD_BASE + '/store';
    document.getElementById('a_active').checked = true;
    wrap.style.display = 'none';
  }
  document.getElementById('awardModal').classList.add('open');
}
</script>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
