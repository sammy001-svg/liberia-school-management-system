<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header">
  <div>
    <div class="page-header-title">Make-up Exams</div>
    <div class="text-muted">Exams you missed, set again for you to sit online. Only you can see the ones listed here.</div>
  </div>
</div>

<div class="card">
  <div class="card-body" style="padding:0;">
    <?php foreach ($exams as $i => $e): ?>
    <div style="padding:18px 22px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;<?= $i > 0 ? 'border-top:1px solid var(--border);' : '' ?>">
      <div>
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
          <span class="fw-600"><?= htmlspecialchars($e['title']) ?></span>
          <?php if ($e['course_name']): ?><span class="badge badge-muted"><?= htmlspecialchars($e['course_name']) ?></span><?php endif; ?>
          <?php if ($e['state'] === 'completed'): ?>
            <?php $pct = $e['total_marks'] > 0 ? round($e['score'] / $e['total_marks'] * 100, 1) : 0; ?>
            <span class="badge badge-<?= $pct >= 70 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">Completed — <?= $pct ?>%</span>
          <?php elseif ($e['state'] === 'open'): ?><span class="badge badge-success">Open Now</span>
          <?php elseif ($e['state'] === 'upcoming'): ?><span class="badge badge-info">Opens <?= date('M d, H:i', strtotime($e['starts_at'])) ?></span>
          <?php else: ?><span class="badge badge-danger">Window closed</span><?php endif; ?>
        </div>
        <?php if (!empty($e['missed_label'])): ?>
          <div style="font-size:12.5px;color:var(--text-light);margin-top:6px;">
            Replaces <strong><?= htmlspecialchars($e['missed_label']) ?></strong><?php if (!empty($e['missed_on'])): ?>, which you missed on <?= date('M d, Y', strtotime($e['missed_on'])) ?><?php endif; ?>
            <?php if (!empty($e['reason'])): ?> · recorded as <?= htmlspecialchars(strtolower($reasons[$e['reason']] ?? $e['reason'])) ?><?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if ($e['description']): ?><div style="font-size:13px;color:var(--text-light);margin-top:6px;"><?= htmlspecialchars($e['description']) ?></div><?php endif; ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:6px;">
          <?= (int)$e['duration_minutes'] ?> minutes · Closes <?= date('M d, Y H:i', strtotime($e['ends_at'])) ?>
        </div>
      </div>
      <div>
        <?php if ($e['state'] === 'completed'): ?>
          <a href="<?= $cfg['url'] ?>/student/exams/<?= (int)$e['id'] ?>/result" class="btn btn-sm btn-secondary">View Result</a>
        <?php elseif ($e['state'] === 'open'): ?>
          <a href="<?= $cfg['url'] ?>/student/exams/<?= (int)$e['id'] ?>/take" class="btn btn-sm btn-primary"><?= $e['attempt_status'] === 'in_progress' ? 'Resume Exam' : 'Start Exam' ?></a>
        <?php elseif ($e['state'] === 'closed'): ?>
          <span class="text-muted" style="font-size:12px;">Speak to the school office</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($exams)): ?>
    <div class="empty-state" style="padding:40px;">
      <div class="empty-state-icon">🕒</div>
      <div class="empty-state-text">You have no make-up exams. If you missed a test, the school will set it here for you and it will appear on this page.</div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
