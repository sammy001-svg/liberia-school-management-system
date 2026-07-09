<?php require ROOT_DIR . '/app/Views/layouts/header.php'; ?>
<div class="page-header"><div class="page-header-title">Online Classes</div><div class="text-muted">Scheduled virtual sessions for your class</div></div>

<div class="card">
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Title</th><th>Subject</th><th>Teacher</th><th>Date / Time</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach($onlineClasses as $c): ?>
        <?php
          $startTs = strtotime($c['scheduled_date'].' '.$c['start_time']);
          $endTs = $startTs + $c['duration_minutes']*60;
          $now = time();
          $isLive = $c['status']==='scheduled' && $now >= $startTs && $now <= $endTs;
        ?>
        <tr>
          <td class="fw-600"><?= htmlspecialchars($c['title']) ?></td>
          <td><?= htmlspecialchars($c['course_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($c['teacher_name'] ?? '—') ?></td>
          <td>
            <?= date('M d, Y', strtotime($c['scheduled_date'])) ?><br>
            <span style="font-size:11px;color:var(--text-muted)"><?= date('h:i A', strtotime($c['start_time'])) ?> · <?= $c['duration_minutes'] ?>min</span>
          </td>
          <td>
            <?php if($c['status']==='cancelled'): ?><span class="badge badge-danger">Cancelled</span>
            <?php elseif($c['status']==='completed'): ?><span class="badge badge-muted">Completed</span>
            <?php elseif($isLive): ?><span class="badge badge-success">Live Now</span>
            <?php else: ?><span class="badge badge-info">Scheduled</span><?php endif; ?>
          </td>
          <td>
            <?php if($c['status']!=='cancelled'): ?>
              <a href="<?= htmlspecialchars($c['meeting_link']) ?>" target="_blank" class="btn btn-sm btn-primary">Join</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($onlineClasses)): ?>
        <tr><td colspan="6"><div class="empty-state" style="padding:40px;"><div class="empty-state-icon">🎥</div><div class="empty-state-text">No online classes scheduled yet.</div></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require ROOT_DIR . '/app/Views/layouts/footer.php'; ?>
