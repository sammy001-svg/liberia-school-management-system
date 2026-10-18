<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/awards/polls';
$statusBadge = ['draft' => ['badge-muted', 'Draft'], 'open' => ['badge-success', 'Voting open'], 'closed' => ['badge-info', 'Closed']]; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Award Voting</div>
    <div class="page-header-sub">Students, teachers and administrators vote for the best student of each period, semester and year, and the best teacher of the semester and year.</div>
  </div>
  <div style="display:flex;gap:8px;">
    <a href="<?= $cfg['url'] ?>/school/awards/vote" class="btn btn-outline">Cast My Vote</a>
    <button type="button" class="btn btn-primary" onclick="document.getElementById('pollModal').classList.add('open')">＋ New Vote</button>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Award</th><th>Period</th><th>Who votes</th><th>Candidates</th><th>Votes cast</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($polls as $p): [$cls, $lbl] = $statusBadge[$p['status']] ?? ['badge-muted', $p['status']]; ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($p['title']) ?>
            <div style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($categories[$p['category']] ?? $p['category']) ?></div></td>
          <td><?= htmlspecialchars($p['period_label'] ?: '—') ?>
            <?php if (!empty($p['year_name'])): ?><div style="font-size:11.5px;color:var(--text-muted);"><?= htmlspecialchars($p['year_name']) ?></div><?php endif; ?></td>
          <td>
            <?php foreach (array_filter(explode(',', (string)$p['voter_groups'])) as $g): ?>
              <span class="badge badge-info" style="margin:1px;"><?= htmlspecialchars($voterTypes[$g] ?? $g) ?></span>
            <?php endforeach; ?>
          </td>
          <td><?= (int)$p['candidates'] ?></td>
          <td class="fw-600"><?= (int)$p['votes'] ?></td>
          <td><span class="badge <?= $cls ?>"><?= $lbl ?></span>
            <?php if (!empty($p['published_at'])): ?><div><span class="badge badge-purple" style="margin-top:4px;">Published</span></div><?php endif; ?></td>
          <td><a href="<?= $base ?>/<?= (int)$p['id'] ?>" class="btn btn-sm btn-secondary">Open</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$polls): ?>
          <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon">🗳️</div>
            <div class="empty-state-text">No votes yet. Create one for the best student of this period, then add the candidates.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="pollModal">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">New Award Vote</div>
      <button type="button" class="modal-close" onclick="document.getElementById('pollModal').classList.remove('open')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/store">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">Award *</label>
          <select name="category" class="form-control" onchange="document.getElementById('poll_title').placeholder = this.options[this.selectedIndex].text;">
            <?php foreach ($categories as $k => $label): ?><option value="<?= $k ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
          </select></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Title shown to voters</label>
            <input type="text" name="title" id="poll_title" class="form-control" maxlength="150" placeholder="Best Student of the Period"></div>
          <div class="form-group"><label class="form-label">Period</label>
            <input type="text" name="period_label" class="form-control" maxlength="80" placeholder="e.g. 1st Period, First Semester, 2026/2027"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Academic year</label>
            <select name="academic_year_id" class="form-control"><option value="">—</option>
              <?php foreach ($years as $y): ?><option value="<?= $y['id'] ?>"><?= htmlspecialchars($y['name']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group"><label class="form-label">Voting opens / closes</label>
            <div style="display:flex;gap:6px;">
              <input type="date" name="opens_on" class="form-control"><input type="date" name="closes_on" class="form-control">
            </div></div>
        </div>
        <div class="form-group"><label class="form-label">Who may vote</label>
          <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <?php foreach ($voterTypes as $k => $label): ?>
              <label style="display:flex;gap:6px;align-items:center;font-size:13px;">
                <input type="checkbox" name="voters[]" value="<?= $k ?>" checked> <?= htmlspecialchars($label) ?>
              </label>
            <?php endforeach; ?>
          </div>
          <div class="form-hint">Each person gets one vote and can change it while the vote is open.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('pollModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create</button>
      </div>
    </form>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
