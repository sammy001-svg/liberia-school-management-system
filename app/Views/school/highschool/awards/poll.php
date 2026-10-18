<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/awards/polls/' . (int)$poll['id'];
$total = array_sum(array_map(fn($r) => (int)$r['votes'], $results));
$statusBadge = ['draft' => ['badge-muted', 'Draft'], 'open' => ['badge-success', 'Voting open'], 'closed' => ['badge-info', 'Closed']];
[$sCls, $sLbl] = $statusBadge[$poll['status']] ?? ['badge-muted', $poll['status']];
?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/awards/polls">Award Voting</a><span>/</span><span><?= htmlspecialchars($poll['title']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($poll['title']) ?> <span class="badge <?= $sCls ?>"><?= $sLbl ?></span></div>
    <div class="page-header-sub">
      <?= htmlspecialchars($categories[$poll['category']] ?? '') ?><?= $poll['period_label'] ? ' · ' . htmlspecialchars($poll['period_label']) : '' ?>
      · Voters: <?= htmlspecialchars(implode(', ', array_map(fn($g) => $voterTypes[$g] ?? $g, array_filter(explode(',', (string)$poll['voter_groups']))))) ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <?php foreach ([['open', 'Open Voting', 'btn-primary', ['draft', 'closed']], ['closed', 'Close Voting', 'btn-secondary', ['open']], ['draft', 'Pause', 'btn-outline', ['open']]] as [$want, $label, $cls, $from]): ?>
      <?php if (in_array($poll['status'], $from, true)): ?>
        <form method="POST" action="<?= $base ?>/status"
              data-confirm="<?= $want === 'closed' ? 'Close this vote? The result becomes final.' : ($want === 'open' ? 'Open this vote so people can cast their ballots?' : 'Pause this vote?') ?>"
              data-confirm-title="Award Voting" data-confirm-label="<?= htmlspecialchars($label) ?>">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <input type="hidden" name="status" value="<?= $want ?>">
          <button type="submit" class="btn <?= $cls ?>"><?= htmlspecialchars($label) ?></button>
        </form>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($poll['status'] === 'closed' && empty($poll['published_at'])): ?>
      <form method="POST" action="<?= $base ?>/publish" data-confirm="Publish the winner on the website's recognition board?" data-confirm-title="Publish Winner" data-confirm-label="Publish">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <button type="submit" class="btn btn-primary">Publish Winner</button>
      </form>
    <?php elseif (!empty($poll['published_at'])): ?>
      <a href="<?= $cfg['url'] ?>/recognition" target="_blank" rel="noopener" class="btn btn-outline">View on Website ↗</a>
    <?php endif; ?>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <div class="stat-card"><div class="stat-label">Candidates</div><div class="stat-value"><?= count($results) ?></div></div>
  <div class="stat-card" style="--card-color:var(--success);"><div class="stat-label">Votes cast</div><div class="stat-value"><?= (int)$turnout['cast'] ?></div></div>
  <div class="stat-card" style="--card-color:var(--info);"><div class="stat-label">People who may vote</div><div class="stat-value"><?= (int)$turnout['eligible'] ?></div>
    <div class="stat-sub"><?= $turnout['eligible'] > 0 ? round($turnout['cast'] / $turnout['eligible'] * 100) : 0 ?>% turnout</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr minmax(280px,360px);gap:16px;align-items:start;" class="fp-grid">
  <div class="card">
    <div class="card-header"><div class="card-title"><?= $poll['status'] === 'closed' ? 'Result' : 'Standing' ?></div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Candidate</th><th><?= $kind === 'staff' ? 'Role' : 'Class' ?></th><th style="width:46%;">Votes</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($results as $i => $r): $pct = $total > 0 ? round((int)$r['votes'] / $total * 100) : 0; ?>
          <tr>
            <td class="fw-600">
              <?= $i === 0 && (int)$r['votes'] > 0 && $poll['status'] === 'closed' ? '🏅 ' : '' ?><?= htmlspecialchars($r['name']) ?>
            </td>
            <td><?= htmlspecialchars($r['class_name'] ?: '—') ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:10px;">
                <div style="flex:1;height:10px;border-radius:6px;background:var(--surface-1);border:1px solid var(--border);overflow:hidden;">
                  <span style="display:block;height:100%;width:<?= $pct ?>%;background:var(--primary-ink, var(--primary-light));"></span>
                </div>
                <span class="fw-700" style="white-space:nowrap;"><?= (int)$r['votes'] ?> · <?= $pct ?>%</span>
              </div>
            </td>
            <td>
              <form method="POST" action="<?= $base ?>/candidates/<?= (int)$r['id'] ?>/remove"
                    data-confirm="Remove <?= htmlspecialchars($r['name']) ?> from the ballot? Any votes for them are removed too."
                    data-confirm-title="Remove Candidate" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-sm btn-danger">×</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$results): ?>
            <tr><td colspan="4"><div class="empty-state"><div class="empty-state-icon">🗳️</div>
              <div class="empty-state-text">No candidates yet. Add at least two before opening the vote.</div></div></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Add a candidate</div></div>
    <form method="POST" action="<?= $base ?>/candidates">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="card-body">
        <div class="form-group">
          <label class="form-label"><?= $kind === 'staff' ? 'Teacher or staff member' : 'Student' ?></label>
          <select name="ref_id" class="form-control" required>
            <option value="">Choose…</option>
            <?php foreach ($people as $pers): ?>
              <option value="<?= (int)$pers['id'] ?>"><?= htmlspecialchars($pers['name']) ?><?= !empty($pers['class_name']) ? ' — ' . htmlspecialchars($pers['class_name']) : '' ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-hint">Voters see the names in alphabetical order.</div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Add to ballot</button>
      </div>
    </form>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
