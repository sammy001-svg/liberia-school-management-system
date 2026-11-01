<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php
$csrf = htmlspecialchars($csrf_token);
$base = $cfg['url'] . '/school/makeup-exams/' . (int)$exam['id'];
$now  = time();
$window = $exam['status'] !== 'published' ? 'draft'
  : ($now < strtotime($exam['starts_at']) ? 'upcoming'
  : ($now > strtotime($exam['ends_at']) ? 'closed' : 'open'));
[$wCls, $wLbl] = [
  'draft'    => ['badge-muted',   'Draft'],
  'upcoming' => ['badge-info',    'Opens ' . date('M d, H:i', strtotime($exam['starts_at']))],
  'open'     => ['badge-success', 'Open now'],
  'closed'   => ['badge-muted',   'Closed'],
][$window];
?>
<div class="breadcrumb">
  <a href="<?= $cfg['url'] ?>/school/makeup-exams">Make-up Exams</a><span>/</span><span><?= htmlspecialchars($exam['title']) ?></span>
</div>

<div class="page-header">
  <div>
    <div class="page-header-title"><?= htmlspecialchars($exam['title']) ?> <span class="badge <?= $wCls ?>"><?= htmlspecialchars($wLbl) ?></span></div>
    <div class="page-header-sub">
      <?= htmlspecialchars($exam['class_name'] ?? '—') ?> · <?= htmlspecialchars($exam['course_name'] ?: 'General') ?>
      · <?= (int)$exam['duration_minutes'] ?> min · <?= date('M d, H:i', strtotime($exam['starts_at'])) ?> to <?= date('M d, H:i', strtotime($exam['ends_at'])) ?>
      <?php if (!empty($exam['missed_label'])): ?><br>Replaces <strong><?= htmlspecialchars($exam['missed_label']) ?></strong><?php if (!empty($exam['missed_on'])): ?>, missed on <?= date('M d, Y', strtotime($exam['missed_on'])) ?><?php endif; ?><?php endif; ?>
    </div>
  </div>
  <div style="display:flex;gap:8px;flex-wrap:wrap;">
    <a href="<?= $base ?>/questions" class="btn btn-outline">Questions (<?= (int)$questionCount ?>)</a>
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editMakeupModal').classList.add('open')">Edit Details</button>
    <form method="POST" action="<?= $base ?>/publish"
          data-confirm="<?= $exam['status'] === 'published' ? 'Withdraw this make-up exam? The students will no longer see it.' : 'Publish this make-up exam to the students on the sitting list?' ?>"
          data-confirm-title="Make-up Exam" data-confirm-label="<?= $exam['status'] === 'published' ? 'Withdraw' : 'Publish' ?>">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <button type="submit" class="btn <?= $exam['status'] === 'published' ? 'btn-outline' : 'btn-primary' ?>">
        <?= $exam['status'] === 'published' ? 'Withdraw' : 'Publish' ?>
      </button>
    </form>
  </div>
</div>

<?php if ($exam['status'] !== 'published'): ?>
  <div class="alert alert-info" style="margin-bottom:20px;">
    Still a <strong>draft</strong>. Add the students who missed the exam and at least one question, then publish it — only then does it appear in their portal.
  </div>
<?php elseif ($window === 'upcoming'): ?>
  <div class="alert alert-info" style="margin-bottom:20px;">Published. The students on the list will see it, and can begin at <?= date('M d, Y H:i', strtotime($exam['starts_at'])) ?>.</div>
<?php elseif ($window === 'open'): ?>
  <div class="alert alert-success" style="margin-bottom:20px;">Open now — the students on the list can sit it until <?= date('M d, Y H:i', strtotime($exam['ends_at'])) ?>.</div>
<?php else: ?>
  <div class="alert alert-warning" style="margin-bottom:20px;">The window closed on <?= date('M d, Y H:i', strtotime($exam['ends_at'])) ?>. Edit the details to reopen it for anyone who still has to sit.</div>
<?php endif; ?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">On the Sitting List</div>
    <div class="stat-value"><?= (int)$stats['listed'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--info);">
    <div class="stat-label">Sat and Submitted</div>
    <div class="stat-value"><?= (int)$stats['sat'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Average Score</div>
    <div class="stat-value"><?= $stats['avgScore'] !== null ? $stats['avgScore'] . '%' : '—' ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Questions / Marks</div>
    <div class="stat-value"><?= (int)$questionCount ?> <span style="font-size:14px;font-weight:600;color:var(--text-muted);">/ <?= number_format((float)$totalMarks, 0) ?></span></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr minmax(290px,370px);gap:16px;align-items:start;" class="fp-grid">
  <div class="card">
    <div class="card-header"><div class="card-title">Sitting List (<?= count($sitting) ?>)</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Student</th><th>Why they missed it</th><th>Status</th><th>Score</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($sitting as $r): ?>
          <tr>
            <td>
              <div class="fw-600"><?= htmlspecialchars($r['student_name']) ?></div>
              <div style="font-family:monospace;font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($r['admission_no'] ?? '') ?></div>
            </td>
            <td style="font-size:12.5px;">
              <?= htmlspecialchars($reasons[$r['reason']] ?? ucfirst((string)$r['reason'])) ?>
              <?php if (!empty($r['note'])): ?><div style="color:var(--text-muted);font-size:11.5px;"><?= htmlspecialchars($r['note']) ?></div><?php endif; ?>
              <?php if (!empty($r['approved_by_name'])): ?><div style="color:var(--text-muted);font-size:11px;">Approved by <?= htmlspecialchars($r['approved_by_name']) ?></div><?php endif; ?>
            </td>
            <td>
              <?php if ($r['attempt_status'] === 'submitted'): ?><span class="badge badge-success">Submitted</span>
                <div style="font-size:11px;color:var(--text-muted);margin-top:3px;"><?= date('M d, H:i', strtotime($r['submitted_at'])) ?></div>
              <?php elseif ($r['attempt_status'] === 'in_progress'): ?><span class="badge badge-warning">Sitting now</span>
              <?php else: ?><span class="badge badge-muted">Not sat yet</span><?php endif; ?>
            </td>
            <td>
              <?php if ($r['score'] !== null): $pct = $r['total_marks'] > 0 ? round($r['score'] / $r['total_marks'] * 100, 1) : 0; ?>
                <span class="badge badge-<?= $pct >= 70 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') ?>">
                  <?= number_format($r['score'], 1) ?> / <?= number_format($r['total_marks'], 1) ?> (<?= $pct ?>%)
                </span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <?php if ($r['attempt_status'] !== null): ?>
                <form method="POST" action="<?= $base ?>/students/<?= (int)$r['student_id'] ?>/reset"
                      data-confirm="Clear <?= htmlspecialchars(addslashes($r['student_name'])) ?>'s paper so they can sit this make-up exam again?"
                      data-confirm-title="Let Them Sit Again" data-confirm-label="Clear paper">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <button type="submit" class="btn btn-sm btn-outline" title="Let them sit again">↻</button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= $base ?>/students/<?= (int)$r['student_id'] ?>/remove"
                      data-confirm="Take <?= htmlspecialchars(addslashes($r['student_name'])) ?> off this sitting list?"
                      data-confirm-title="Remove from Sitting List" data-confirm-label="Remove">
                  <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                  <button type="submit" class="btn btn-sm btn-danger">×</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$sitting): ?>
          <tr><td colspan="5">
            <div class="empty-state"><div class="empty-state-icon">👤</div>
              <div class="empty-state-text">Nobody is listed yet. Add the student or students who missed the exam — they are the only ones who will ever see it.</div></div>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Add students who missed it</div></div>
    <form method="POST" action="<?= $base ?>/students/add">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="card-body">
        <?php if (!$available): ?>
          <p style="font-size:13px;color:var(--text-muted);margin:0;">
            Every active student of <?= htmlspecialchars($exam['class_name'] ?? 'this class') ?> is already on the list.
          </p>
        <?php else: ?>
          <div class="form-group">
            <label class="form-label"><?= htmlspecialchars($exam['class_name'] ?? 'Class') ?> — choose one or more</label>
            <select name="student_ids[]" class="form-control" multiple size="<?= min(10, max(4, count($available))) ?>" required>
              <?php foreach ($available as $a): ?>
                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?><?= !empty($a['admission_no']) ? ' — ' . htmlspecialchars($a['admission_no']) : '' ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-hint">Hold Ctrl (or Cmd) to pick several at once.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Why they missed the exam</label>
            <select name="reason" class="form-control">
              <?php foreach ($reasons as $k => $label): ?><option value="<?= $k ?>"><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Note (optional)</label>
            <textarea name="note" class="form-control" rows="2" maxlength="300" placeholder="e.g. Medical certificate received 14 Oct."></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Add to the sitting list</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="modal-overlay" id="editMakeupModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Edit Make-up Exam</div>
      <button class="modal-close" onclick="document.getElementById('editMakeupModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $base ?>/update">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required maxlength="200" value="<?= htmlspecialchars($exam['title']) ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Exam being made up</label>
            <input type="text" name="missed_label" class="form-control" maxlength="160" value="<?= htmlspecialchars($exam['missed_label'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Date it was missed</label>
            <input type="date" name="missed_on" class="form-control" value="<?= htmlspecialchars($exam['missed_on'] ?? '') ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Subject</label>
          <select name="course_id" class="form-control">
            <option value="">— Any —</option>
            <?php foreach ($courses ?? [] as $co): ?>
              <option value="<?= (int)$co['id'] ?>" <?= (int)$exam['course_id'] === (int)$co['id'] ? 'selected' : '' ?>><?= htmlspecialchars($co['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (empty($courses)): ?>
            <input type="hidden" name="course_id" value="<?= (int)($exam['course_id'] ?? 0) ?: '' ?>">
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Instructions to the student</label>
          <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($exam['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Duration (minutes) *</label>
          <input type="number" name="duration_minutes" class="form-control" required min="1" value="<?= (int)$exam['duration_minutes'] ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Opens At *</label>
            <input type="datetime-local" name="starts_at" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($exam['starts_at'])) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Closes At *</label>
            <input type="datetime-local" name="ends_at" class="form-control" required value="<?= date('Y-m-d\TH:i', strtotime($exam['ends_at'])) ?>">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editMakeupModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
