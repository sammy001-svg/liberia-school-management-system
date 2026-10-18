<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$scopes = [
    'internal' => ['Inside the school', 'Class displays, report cards, the school noticeboard and school records.'],
    'website'  => ['School website', 'Photographs and video on the school’s own website.'],
    'social'   => ['Social media', 'The school’s own Facebook, WhatsApp and similar pages.'],
    'print'    => ['Printed material', 'Prospectus, newsletters, banners and other printed material.'],
    'media'    => ['Press & partners', 'Newspapers, television and partner organisations reporting on the school.'],
];
$badge = ['granted' => ['badge-success', 'Permission given'], 'limited' => ['badge-warning', 'Partly given'], 'declined' => ['badge-danger', 'Not given']];
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Photo &amp; Video Consent</div>
    <div class="page-header-sub">Child protection: you decide where your child’s photographs and video may be used. You can change this at any time.</div>
  </div>
</div>

<?php if (!$children): ?>
  <div class="card"><div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">No children are linked to your account yet. Please contact the school office.</div></div>
<?php else: ?>
  <?php foreach ($children as $child): $c = $consents[(int)$child['id']] ?? null; ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div class="card-title"><?= htmlspecialchars($child['name']) ?><?= !empty($child['class_name']) ? ' · ' . htmlspecialchars($child['class_name']) : '' ?></div>
        <?php if ($c): [$cls, $lbl] = $badge[$c['status']] ?? ['badge-muted', ucfirst($c['status'])]; ?>
          <span class="badge <?= $cls ?>"><?= $lbl ?> · signed <?= date('j M Y', strtotime($c['signed_on'] ?: $c['created_at'])) ?></span>
        <?php else: ?>
          <span class="badge badge-warning">Not answered yet</span>
        <?php endif; ?>
      </div>
      <form method="POST" action="<?= $cfg['url'] ?>/parent/consent/save">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="student_id" value="<?= (int)$child['id'] ?>">
        <div class="card-body">
          <p style="font-size:13.5px;line-height:1.7;color:var(--text);background:var(--surface-1);border:1px solid var(--border);border-radius:10px;padding:14px 16px;">
            <?= htmlspecialchars($statement) ?>
          </p>
          <div style="font-size:12.5px;color:var(--text-muted);margin:16px 0 8px;">Tick where photographs and video of <?= htmlspecialchars($child['name']) ?> may be used:</div>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:10px;">
            <?php foreach ($scopes as $key => [$label, $desc]): ?>
              <label style="display:flex;gap:10px;align-items:flex-start;border:1px solid var(--border);border-radius:10px;padding:12px 14px;cursor:pointer;">
                <input type="checkbox" name="allow_<?= $key ?>" value="1" style="margin-top:3px;"
                       <?= $c ? (!empty($c['allow_' . $key]) ? 'checked' : '') : ($key === 'internal' ? 'checked' : '') ?>>
                <span>
                  <span class="fw-600" style="font-size:13.5px;"><?= htmlspecialchars($label) ?></span>
                  <span style="display:block;font-size:11.5px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($desc) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <div style="font-size:11.5px;color:var(--text-muted);margin-top:10px;">Leave every box empty to refuse permission altogether.</div>

          <div class="form-row" style="margin-top:18px;">
            <div class="form-group"><label class="form-label">Your full name (this is your signature) *</label>
              <input type="text" name="parent_name" class="form-control" required maxlength="150"
                     value="<?= htmlspecialchars($c['parent_name'] ?? ($user['name'] ?? '')) ?>"></div>
            <div class="form-group"><label class="form-label">You are the child’s</label>
              <input type="text" name="relationship" class="form-control" maxlength="60" placeholder="Mother, father, guardian"
                     value="<?= htmlspecialchars($c['relationship'] ?? '') ?>"></div>
          </div>
          <div class="form-row">
            <div class="form-group"><label class="form-label">Phone</label>
              <input type="text" name="parent_phone" class="form-control" maxlength="40" value="<?= htmlspecialchars($c['parent_phone'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Anything the school should know</label>
              <input type="text" name="notes" class="form-control" maxlength="500" value="<?= htmlspecialchars($c['notes'] ?? '') ?>"></div>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:14px 16px;border-top:1px solid var(--border);">
          <span style="font-size:11.5px;color:var(--text-muted);align-self:center;">
            Submitting records today’s date against your name. You can come back and change it whenever you wish.
          </span>
          <button type="submit" class="btn btn-primary">✓ Save my answer</button>
        </div>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
