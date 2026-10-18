<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/awards/vote'; ?>
<style>
.ballot-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; }
.ballot-option { display:flex; gap:10px; align-items:center; border:1px solid var(--border); border-radius:10px; padding:12px 14px; cursor:pointer; transition:.15s; }
.ballot-option:hover { border-color:var(--primary); }
.ballot-option.chosen { border-color:var(--success); background:var(--primary-soft); }
.ballot-option b { font-size:13.5px; }
.ballot-option span { display:block; font-size:11.5px; color:var(--text-muted); }
</style>
<div class="page-header">
  <div>
    <div class="page-header-title">Award Voting</div>
    <div class="page-header-sub">Choose who you think deserves each award. One vote each — you can change it while the vote is open.</div>
  </div>
</div>

<?php if (!$polls): ?>
  <div class="card"><div class="empty-state"><div class="empty-state-icon">🗳️</div>
    <div class="empty-state-text">No vote is open to you at the moment. You will see the ballot here as soon as the school opens one.</div></div></div>
<?php else: ?>
  <?php foreach ($polls as $poll): ?>
    <div class="card" style="margin-bottom:18px;">
      <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div>
          <div class="card-title"><?= htmlspecialchars($poll['title']) ?></div>
          <?php if (!empty($poll['period_label'])): ?>
            <div style="font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($poll['period_label']) ?></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($poll['my_vote'])): ?>
          <span class="badge badge-success">You have voted</span>
        <?php else: ?>
          <span class="badge badge-warning">Not voted yet</span>
        <?php endif; ?>
      </div>
      <form method="POST" action="<?= $base ?>/<?= (int)$poll['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="card-body">
          <div class="ballot-grid">
            <?php foreach ($poll['candidates'] as $c): $chosen = (int)($poll['my_vote'] ?? 0) === (int)$c['id']; ?>
              <label class="ballot-option <?= $chosen ? 'chosen' : '' ?>">
                <input type="radio" name="candidate_id" value="<?= (int)$c['id'] ?>" <?= $chosen ? 'checked' : '' ?> required>
                <span>
                  <b><?= htmlspecialchars($c['name']) ?></b>
                  <span><?= htmlspecialchars($c['class_name'] ?: ($c['kind'] === 'staff' ? 'Staff' : 'Student')) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
          <?php if (!$poll['candidates']): ?>
            <p style="color:var(--text-muted);font-size:13px;margin:0;">No candidates have been listed for this award yet.</p>
          <?php endif; ?>
        </div>
        <?php if ($poll['candidates']): ?>
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;padding:14px 16px;border-top:1px solid var(--border);">
          <span style="font-size:11.5px;color:var(--text-muted);align-self:center;">
            Your vote is counted once. The result is announced when the school closes the vote.
          </span>
          <button type="submit" class="btn btn-primary"><?= !empty($poll['my_vote']) ? 'Change my vote' : 'Cast my vote' ?></button>
        </div>
        <?php endif; ?>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
