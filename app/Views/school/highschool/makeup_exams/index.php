<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<?php $csrf = htmlspecialchars($csrf_token); $base = $cfg['url'] . '/school/makeup-exams'; ?>

<div class="page-header">
  <div>
    <div class="page-header-title">Make-up Exams</div>
    <div class="page-header-sub">An exam a student missed, set again online. Only the students you put on the sitting list can see it or sit it — the rest of the class never does.</div>
  </div>
  <button type="button" class="btn btn-primary" onclick="document.getElementById('addMakeupModal').classList.add('open')">+ Set a Make-up Exam</button>
</div>

<div class="stat-grid">
  <div class="stat-card">
    <div class="stat-label">Make-up Exams</div>
    <div class="stat-value"><?= (int)$stats['total'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--success);">
    <div class="stat-label">Open Now</div>
    <div class="stat-value"><?= (int)$stats['open'] ?></div>
  </div>
  <div class="stat-card" style="--card-color: var(--warning);">
    <div class="stat-label">Students Still to Sit</div>
    <div class="stat-value"><?= (int)$stats['waiting'] ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">All Make-up Exams (<?= count($exams) ?>)</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Exam</th><th>Class</th><th>Window</th><th>Questions</th><th>Sat</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($exams as $e): ?>
        <tr>
          <td>
            <div class="fw-600"><a href="<?= $base ?>/<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['title']) ?></a></div>
            <div style="font-size:11px;color:var(--text-muted)">
              <?= htmlspecialchars($e['course_name'] ?: 'General') ?> · <?= (int)$e['duration_minutes'] ?> min
              <?php if (!empty($e['missed_label'])): ?><br>Replaces: <?= htmlspecialchars($e['missed_label']) ?><?php endif; ?>
              <?php if (!empty($e['missed_on'])): ?> (missed <?= date('M d, Y', strtotime($e['missed_on'])) ?>)<?php endif; ?>
            </div>
          </td>
          <td><?= htmlspecialchars($e['class_name'] ?? '—') ?></td>
          <td style="font-size:12px;">
            <?= date('M d, H:i', strtotime($e['starts_at'])) ?><br>
            <span style="color:var(--text-muted)">to <?= date('M d, H:i', strtotime($e['ends_at'])) ?></span>
          </td>
          <td><?= (int)$e['question_count'] ?></td>
          <td><?= (int)$e['sat_count'] ?> / <?= (int)$e['listed_count'] ?></td>
          <td>
            <?php
              [$cls, $lbl] = [
                'draft'    => ['badge-muted',   'Draft'],
                'upcoming' => ['badge-info',    'Opens ' . date('M d, H:i', strtotime($e['starts_at']))],
                'open'     => ['badge-success', 'Open now'],
                'closed'   => ['badge-muted',   'Closed'],
              ][$e['window']];
            ?>
            <span class="badge <?= $cls ?>"><?= htmlspecialchars($lbl) ?></span>
          </td>
          <td>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              <a href="<?= $base ?>/<?= (int)$e['id'] ?>" class="btn btn-sm btn-secondary">Sitting List</a>
              <a href="<?= $base ?>/<?= (int)$e['id'] ?>/questions" class="btn btn-sm btn-outline">Questions</a>
              <form method="POST" action="<?= $base ?>/<?= (int)$e['id'] ?>/delete"
                    data-confirm="Remove the make-up exam '<?= htmlspecialchars(addslashes($e['title'])) ?>'? Any papers already sat for it are removed too."
                    data-confirm-title="Remove Make-up Exam" data-confirm-label="Remove">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-sm btn-danger">Del</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($exams)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-state-icon">🕒</div>
            <div class="empty-state-text">
              No make-up exams yet. When a student misses a test — illness, bereavement, away on school duty —
              <a href="javascript:void(0)" onclick="document.getElementById('addMakeupModal').classList.add('open')">set it again here</a>
              and name only the students who must sit it.
            </div>
          </div>
        </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="addMakeupModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <div class="modal-title">Set a Make-up Exam</div>
      <button class="modal-close" onclick="document.getElementById('addMakeupModal').classList.remove('open')">&times;</button>
    </div>
    <form method="POST" action="<?= $base ?>/store">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Title *</label>
          <input type="text" name="title" class="form-control" required maxlength="200" placeholder="e.g. Make-up — 2nd Period Mathematics Test">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Class *</label>
            <select name="class_id" class="form-control" required>
              <option value="">— Select Class —</option>
              <?php foreach ($classes as $c): ?><option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
            </select>
            <div class="form-hint">Only students of this class can be put on the sitting list.</div>
          </div>
          <div class="form-group">
            <label class="form-label">Subject</label>
            <select name="course_id" class="form-control">
              <option value="">— Any —</option>
              <?php foreach ($courses as $co): ?><option value="<?= (int)$co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Exam being made up</label>
            <input type="text" name="missed_label" class="form-control" maxlength="160" placeholder="e.g. 2nd Period Mathematics Test">
          </div>
          <div class="form-group">
            <label class="form-label">Date it was missed</label>
            <input type="date" name="missed_on" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Instructions to the student</label>
          <textarea name="description" class="form-control" rows="2" placeholder="Anything they should know before they begin."></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Duration (minutes) *</label>
          <input type="number" name="duration_minutes" class="form-control" required value="30" min="1">
          <div class="form-hint">Once a student starts, they have this many minutes — or until the window closes, whichever comes first.</div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Opens At *</label>
            <input type="datetime-local" name="starts_at" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Closes At *</label>
            <input type="datetime-local" name="ends_at" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addMakeupModal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Create &amp; Add Students</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
