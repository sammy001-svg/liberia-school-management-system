<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/website">Website</a>
  <span>/</span><span>Gallery</span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title">Website Gallery</div>
    <div class="page-header-sub">Photos shown in the gallery on the Home, Student Life and News pages. Visitors can click any photo to enlarge it.</div>
  </div>
  <a href="<?= $cfg['url'] ?>/student-life" target="_blank" rel="noopener" class="btn btn-outline">View Gallery ↗</a>
</div>

<?php $shown = count(array_filter($photos, fn($p) => $p['is_active'])); ?>
<?php if ($shown === 0): ?>
<div class="alert alert-info">
  <?= empty($photos) ? 'No photos uploaded yet, so' : 'All your photos are hidden, so' ?> the website is showing its built-in gallery.
  Once at least one of your photos is showing, your gallery replaces it.
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">＋ Add Photos</div></div>
  <div class="card-body">
    <form method="POST" action="<?= $cfg['url'] ?>/school/website/gallery/store" enctype="multipart/form-data" style="display:grid;grid-template-columns:1.2fr 1fr auto;gap:12px;align-items:end;">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <div class="form-group" style="margin:0;">
        <label class="form-label">Photos</label>
        <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
        <div class="form-hint">Choose one or several at once. JPG, PNG, WEBP or GIF, up to 4MB each.</div>
      </div>
      <div class="form-group" style="margin:0;">
        <label class="form-label">Caption (optional)</label>
        <input type="text" name="caption" class="form-control" maxlength="200" placeholder="e.g. Sports day 2026">
        <div class="form-hint">Applied to every photo in this upload.</div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-bottom:18px;">Upload</button>
    </form>
  </div>
</div>

<?php if (!empty($photos)): ?>
<div class="mt-16" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;">
  <?php foreach ($photos as $i => $p): ?>
    <div class="card" style="overflow:hidden;<?= $p['is_active'] ? '' : 'opacity:.6;' ?>">
      <img src="<?= htmlspecialchars($p['image_url']) ?>" alt="" style="width:100%;height:150px;object-fit:cover;display:block;">
      <div class="card-body" style="padding:12px;">
        <form method="POST" action="<?= $cfg['url'] ?>/school/website/gallery/<?= $p['id'] ?>/update" style="display:flex;gap:6px;margin-bottom:10px;">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="text" name="caption" class="form-control" style="font-size:12.5px;" maxlength="200" placeholder="Caption" value="<?= htmlspecialchars($p['caption'] ?? '') ?>">
          <button type="submit" class="btn btn-sm btn-secondary" title="Save caption">✓</button>
        </form>
        <div style="display:flex;gap:4px;justify-content:space-between;align-items:center;">
          <div style="display:flex;gap:4px;">
            <?php foreach ([['up', '←', $i === 0], ['down', '→', $i === count($photos) - 1]] as [$dir, $arrow, $disabled]): ?>
              <form method="POST" action="<?= $cfg['url'] ?>/school/website/gallery/<?= $p['id'] ?>/reorder">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <input type="hidden" name="direction" value="<?= $dir ?>">
                <button type="submit" class="btn btn-sm btn-outline" title="Move <?= $dir === 'up' ? 'earlier' : 'later' ?>" <?= $disabled ? 'disabled' : '' ?>><?= $arrow ?></button>
              </form>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;gap:4px;">
            <form method="POST" action="<?= $cfg['url'] ?>/school/website/gallery/<?= $p['id'] ?>/toggle">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-outline"><?= $p['is_active'] ? 'Hide' : 'Show' ?></button>
            </form>
            <form method="POST" action="<?= $cfg['url'] ?>/school/website/gallery/<?= $p['id'] ?>/delete"
                  data-confirm="Delete this photo from the gallery?" data-confirm-title="Delete Photo" data-confirm-label="Delete">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
