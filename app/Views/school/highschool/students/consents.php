<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$base = $cfg['url'] . '/school/consents';
$badge = ['granted' => ['badge-success', 'Full permission'], 'limited' => ['badge-warning', 'Partly given'], 'declined' => ['badge-danger', 'Refused']];
$short = ['internal' => 'School', 'website' => 'Website', 'social' => 'Social', 'print' => 'Print', 'media' => 'Press'];
?>
<div class="page-header">
  <div>
    <div class="page-header-title">Photo &amp; Video Consent</div>
    <div class="page-header-sub">Child protection: where each child’s photographs and video may be used. Parents answer in the Family Portal; the office can record signed paper forms here.</div>
  </div>
  <a href="<?= $base ?>/form" target="_blank" rel="noopener" class="btn btn-secondary">🖨 Print Blank Form</a>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));">
  <div class="stat-card"><div class="stat-label">Active students</div><div class="stat-value"><?= (int)($counts['total'] ?? 0) ?></div></div>
  <div class="stat-card" style="--card-color:var(--success);"><div class="stat-label">Full permission</div><div class="stat-value"><?= (int)($counts['granted'] ?? 0) ?></div></div>
  <div class="stat-card" style="--card-color:var(--warning);"><div class="stat-label">Partly given</div><div class="stat-value"><?= (int)($counts['limited'] ?? 0) ?></div></div>
  <div class="stat-card" style="--card-color:var(--danger);"><div class="stat-label">Refused</div><div class="stat-value"><?= (int)($counts['declined'] ?? 0) ?></div></div>
  <div class="stat-card" style="--card-color:var(--text-muted);"><div class="stat-label">No answer yet</div><div class="stat-value"><?= (int)($counts['missing'] ?? 0) ?></div></div>
</div>

<form method="GET" class="card" style="padding:14px 18px;margin-bottom:16px;">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" class="form-control" style="max-width:240px;" placeholder="Student name or admission no." value="<?= htmlspecialchars($q) ?>">
    <select name="status" class="form-control" style="max-width:190px;">
      <option value="">All answers</option>
      <?php foreach (['granted' => 'Full permission', 'limited' => 'Partly given', 'declined' => 'Refused', 'missing' => 'No answer yet'] as $k => $l): ?>
        <option value="<?= $k ?>" <?= $filter === $k ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= $base ?>" class="btn btn-outline">Reset</a>
  </div>
</form>

<div class="card">
  <div class="card-header"><div class="card-title">Students (<?= count($rows) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Student</th><th>Class</th><th>Answer</th><th>Allowed for</th><th>Signed by</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($r['name']) ?>
            <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no']) ?></div></td>
          <td><?= htmlspecialchars($r['class_name'] ?? '—') ?></td>
          <td>
            <?php if (!empty($r['status'])): [$cls, $lbl] = $badge[$r['status']] ?? ['badge-muted', $r['status']]; ?>
              <span class="badge <?= $cls ?>"><?= $lbl ?></span>
            <?php else: ?><span class="badge badge-muted">No answer</span><?php endif; ?>
          </td>
          <td>
            <?php if (!empty($r['status'])): $any = false; ?>
              <?php foreach ($short as $k => $lbl): if (!empty($r['allow_' . $k])): $any = true; ?>
                <span class="badge badge-info" style="margin:1px;"><?= $lbl ?></span>
              <?php endif; endforeach; ?>
              <?php if (!$any): ?><span style="color:var(--text-muted);font-size:12px;">Nothing — refused</span><?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if (!empty($r['parent_name'])): ?>
              <?= htmlspecialchars($r['parent_name']) ?>
              <div style="font-size:11px;color:var(--text-muted);">
                <?= !empty($r['signed_on']) ? date('j M Y', strtotime($r['signed_on'])) : '' ?>
                <?= ($r['source'] ?? '') === 'office' ? ' · paper form' : ' · portal' ?>
              </div>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><a href="<?= $base ?>/<?= (int)$r['id'] ?>" class="btn btn-sm btn-secondary">Record / View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-state-icon">📸</div>
            <div class="empty-state-text">No students match that filter.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
