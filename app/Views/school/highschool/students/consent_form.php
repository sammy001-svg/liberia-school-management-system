<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/consents'; ?>
<div class="breadcrumb">
  <a href="<?= $base ?>">Photo Consent</a><span>/</span><span><?= htmlspecialchars($student['name']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($student['name']) ?></div>
    <div class="page-header-sub">
      <?= htmlspecialchars($student['admission_no']) ?><?= !empty($student['class_name']) ? ' · ' . htmlspecialchars($student['class_name']) : '' ?>
      <?php if ($parents): ?> · Parent: <?= htmlspecialchars(implode(', ', array_column($parents, 'name'))) ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $base ?>/form?student=<?= (int)$student['id'] ?>" target="_blank" rel="noopener" class="btn btn-outline">🖨 Print Form</a>
  </div>
</div>

<?php if ($consent): ?>
  <div class="alert alert-<?= $consent['status'] === 'declined' ? 'danger' : ($consent['status'] === 'limited' ? 'warning' : 'success') ?>">
    <?= $consent['status'] === 'declined'
        ? 'This family has refused permission. Do not publish photographs or video of this child.'
        : ($consent['status'] === 'limited' ? 'Partial permission — only the ticked uses are allowed.' : 'Full permission given.') ?>
    Signed by <strong><?= htmlspecialchars($consent['parent_name']) ?></strong>
    <?= !empty($consent['signed_on']) ? ' on ' . date('j M Y', strtotime($consent['signed_on'])) : '' ?>
    (<?= ($consent['source'] ?? '') === 'office' ? 'paper form recorded by the office' : 'signed in the Family Portal' ?>).
  </div>
<?php else: ?>
  <div class="alert alert-warning">No answer from this family yet. Record the signed paper form below, or ask the parent to answer in the Family Portal.</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">Record a signed form</div></div>
  <form method="POST" action="<?= $base ?>/<?= (int)$student['id'] ?>/save">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="card-body">
      <p style="font-size:13.5px;line-height:1.7;background:var(--surface-1);border:1px solid var(--border);border-radius:10px;padding:14px 16px;">
        <?= htmlspecialchars($statement) ?>
      </p>
      <div style="font-size:12.5px;color:var(--text-muted);margin:16px 0 8px;">Tick exactly what the parent agreed to:</div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:10px;">
        <?php foreach ($scopes as $key => [$label, $desc]): ?>
          <label style="display:flex;gap:10px;align-items:flex-start;border:1px solid var(--border);border-radius:10px;padding:12px 14px;cursor:pointer;">
            <input type="checkbox" name="allow_<?= $key ?>" value="1" style="margin-top:3px;" <?= !empty($consent['allow_' . $key]) ? 'checked' : '' ?>>
            <span>
              <span class="fw-600" style="font-size:13.5px;"><?= htmlspecialchars($label) ?></span>
              <span style="display:block;font-size:11.5px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($desc) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <div class="form-row" style="margin-top:18px;">
        <div class="form-group"><label class="form-label">Signed by (parent / guardian) *</label>
          <input type="text" name="parent_name" class="form-control" required maxlength="150"
                 value="<?= htmlspecialchars($consent['parent_name'] ?? ($parents[0]['name'] ?? '')) ?>"></div>
        <div class="form-group"><label class="form-label">Relationship</label>
          <input type="text" name="relationship" class="form-control" maxlength="60" value="<?= htmlspecialchars($consent['relationship'] ?? '') ?>"></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Phone</label>
          <input type="text" name="parent_phone" class="form-control" maxlength="40"
                 value="<?= htmlspecialchars($consent['parent_phone'] ?? ($parents[0]['phone'] ?? '')) ?>"></div>
        <div class="form-group"><label class="form-label">Date signed</label>
          <input type="date" name="signed_on" class="form-control" value="<?= htmlspecialchars($consent['signed_on'] ?? date('Y-m-d')) ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Note</label>
        <input type="text" name="notes" class="form-control" maxlength="500" value="<?= htmlspecialchars($consent['notes'] ?? '') ?>"></div>
    </div>
    <div style="display:flex;justify-content:flex-end;gap:10px;padding:14px 16px;border-top:1px solid var(--border);">
      <button type="submit" class="btn btn-primary">Save Consent</button>
    </div>
  </form>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
