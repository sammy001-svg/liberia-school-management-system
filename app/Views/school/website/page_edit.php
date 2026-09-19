<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$imgUrl = function (string $v) use ($cfg): string {
    $v = trim($v);
    if ($v === '') { return ''; }
    return preg_match('#^(https?:)?/#', $v) ? $v : rtrim($cfg['url'], '/') . '/assets/website/img/' . $v;
};
$hints = [
    'title'    => 'Wrap words in *asterisks* to highlight them.',
    'textarea' => 'Blank line = new paragraph. *highlight*, **bold**.',
    'lines'    => 'One item per line.',
    'list'     => 'One item per line; separate the parts with |',
];
?>
<style>
  .we-layout { display:grid; grid-template-columns:230px 1fr; gap:20px; align-items:start; }
  .we-nav { position:sticky; top:84px; display:flex; flex-direction:column; gap:2px; padding:8px; }
  .we-nav a { padding:8px 10px; border-radius:8px; font-size:13px; color:var(--text-muted); display:flex; gap:8px; align-items:center; }
  .we-nav a:hover { background:var(--surface-1); color:var(--text); }
  .we-nav a.active { background:var(--primary-soft); color:var(--primary); font-weight:600; }
  .we-field { padding:14px 0; border-top:1px dashed var(--border); }
  .we-field:first-child { border-top:0; padding-top:0; }
  .we-field-head { display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:6px; }
  .we-field-head .form-label { margin:0; }
  .we-restore { font-size:11.5px; color:var(--primary); background:none; border:0; cursor:pointer; padding:0; }
  .we-img { display:flex; gap:14px; align-items:flex-start; flex-wrap:wrap; }
  .we-img img { width:180px; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--border); background:var(--surface-1); }
  .we-img.logo img { object-fit:contain; background:#fff; }
  .we-savebar { position:sticky; bottom:0; z-index:5; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;
                padding:12px 16px; margin-top:16px; background:var(--bg-card); border:1px solid var(--border); border-radius:12px;
                box-shadow:0 -8px 24px -12px rgba(0,0,0,.35); }
  textarea.we-mono { font-family:ui-monospace, SFMono-Regular, Consolas, monospace; font-size:12.5px; line-height:1.6; }
  @media (max-width: 900px) { .we-layout { grid-template-columns:1fr; } .we-nav { position:static; flex-direction:row; flex-wrap:wrap; } }
</style>

<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/website">Website</a>
  <span>/</span><span><?= htmlspecialchars($page['label']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= $page['icon'] ?> <?= htmlspecialchars($page['label']) ?></div>
    <div class="page-header-sub"><?= htmlspecialchars($page['desc'] ?? 'Edit the text and photos on this page. Saving publishes the changes immediately.') ?></div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="<?= $cfg['url'] . $page['url'] ?>" target="_blank" rel="noopener" class="btn btn-outline">View Page ↗</a>
    <form method="POST" action="<?= $cfg['url'] ?>/school/website/pages/<?= $pageKey ?>/reset"
          data-confirm="Restore every field on '<?= htmlspecialchars($page['label']) ?>' to its original text and photos? Your edits on this page will be lost."
          data-confirm-title="Restore Defaults" data-confirm-label="Restore">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <button type="submit" class="btn btn-secondary">Restore Defaults</button>
    </form>
  </div>
</div>

<div class="we-layout">
  <nav class="card we-nav" aria-label="Website pages">
    <?php foreach ($pages as $key => $p): ?>
      <a href="<?= $cfg['url'] ?>/school/website/pages/<?= $key ?>" class="<?= $key === $pageKey ? 'active' : '' ?>"><span><?= $p['icon'] ?></span> <?= htmlspecialchars($p['label']) ?></a>
    <?php endforeach; ?>
  </nav>

  <form method="POST" action="<?= $cfg['url'] ?>/school/website/pages/<?= $pageKey ?>/save" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

    <?php foreach ($page['sections'] as $sectionName => $fields): ?>
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header"><div class="card-title"><?= htmlspecialchars($sectionName) ?></div></div>
        <div class="card-body">
          <?php foreach ($fields as $key => [$type, $label, $default]):
            $value = (string)($values[$key] ?? $default);
            $id = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $key);
            $isSaved = !empty($saved[$key]);
          ?>
            <div class="we-field">
              <div class="we-field-head">
                <label class="form-label" for="<?= $id ?>">
                  <?= htmlspecialchars($label) ?>
                  <?php if ($isSaved): ?><span class="badge badge-purple" style="margin-left:6px;font-size:10px;">Customised</span><?php endif; ?>
                </label>
                <?php if ($type !== 'image' && $default !== ''): ?>
                  <button type="button" class="we-restore" data-restore="<?= $id ?>" data-default="<?= htmlspecialchars($default) ?>">Restore original</button>
                <?php endif; ?>
              </div>

              <?php if ($type === 'image'): ?>
                <div class="we-img<?= str_contains($key, 'logo') ? ' logo' : '' ?>">
                  <?php if ($value !== ''): ?><img src="<?= htmlspecialchars($imgUrl($value)) ?>" alt="" id="<?= $id ?>_preview"><?php endif; ?>
                  <div style="flex:1;min-width:220px;">
                    <input type="file" name="images[<?= htmlspecialchars($key) ?>]" id="<?= $id ?>" class="form-control" accept="image/*" data-preview="<?= $id ?>_preview">
                    <div class="form-hint">JPG, PNG, WEBP or GIF, up to 4MB. Leave empty to keep the current photo.</div>
                    <?php if ($isSaved): ?>
                      <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:8px;cursor:pointer;">
                        <input type="checkbox" name="reset_image[<?= htmlspecialchars($key) ?>]" value="1"> Go back to the original photo
                      </label>
                    <?php endif; ?>
                  </div>
                </div>

              <?php elseif ($type === 'text' || $type === 'title'): ?>
                <input type="text" name="fields[<?= htmlspecialchars($key) ?>]" id="<?= $id ?>" class="form-control" value="<?= htmlspecialchars($value) ?>" maxlength="500">

              <?php else: ?>
                <?php $rows = max(2, min(14, substr_count($value, "\n") + (int)ceil(mb_strlen($value) / 95) + 1)); ?>
                <textarea name="fields[<?= htmlspecialchars($key) ?>]" id="<?= $id ?>" class="form-control<?= in_array($type, ['list', 'lines'], true) ? ' we-mono' : '' ?>" rows="<?= $rows ?>"><?= htmlspecialchars($value) ?></textarea>
              <?php endif; ?>

              <?php if (isset($hints[$type])): ?><div class="form-hint"><?= htmlspecialchars($hints[$type]) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>

    <div class="we-savebar">
      <span style="font-size:12.5px;color:var(--text-muted);">Changes are published as soon as you save.</span>
      <button type="submit" class="btn btn-primary">Save &amp; Publish</button>
    </div>
  </form>
</div>

<script>
document.querySelectorAll('[data-restore]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var field = document.getElementById(btn.getAttribute('data-restore'));
    if (field) { field.value = btn.getAttribute('data-default'); field.focus(); }
  });
});
document.querySelectorAll('input[type=file][data-preview]').forEach(function (input) {
  input.addEventListener('change', function () {
    if (!input.files || !input.files[0]) return;
    var img = document.getElementById(input.getAttribute('data-preview'));
    if (!img) {
      img = document.createElement('img');
      img.id = input.getAttribute('data-preview');
      input.closest('.we-img').prepend(img);
    }
    var reader = new FileReader();
    reader.onload = function (e) { img.src = e.target.result; };
    reader.readAsDataURL(input.files[0]);
  });
});
</script>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
